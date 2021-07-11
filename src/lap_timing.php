<?php


namespace datagutten\amb\laps;


use InvalidArgumentException;
use PDO;
use PDOException;
use UnexpectedValueException;

class lap_timing extends passing_db
{
	public $time_offset_hours=0;
	public $best_rounds;
	public $previous_round;
	public $transponders;

    function __construct(array $config, string $decoder_id = null)
	{
        parent::__construct($config, $decoder_id);
		date_default_timezone_set('GMT');
	}
	public static function query_today($timestamp_day=false): string
    {
        date_default_timezone_set('GMT');
		if($timestamp_day===false)
			$timestamp_day=time();
		$time_today_start=strtotime('0:00',$timestamp_day).'000000';
		$time_today_end=strtotime('23:59',$timestamp_day).'999999';
        return sprintf('rtc_time>=%s AND rtc_time<=%s',$time_today_start,$time_today_end);
	}

    /**
     * @param array $passing1 First passing
     * @param array $passing2 Last passing
     * @param int $limit
     * @return int Round time in seconds
     */
    public static function lap_time(array $passing1, array $passing2, $limit = 60)
    {
        $round_time=$passing2['rtc_time']-$passing1['rtc_time']; //Calculate lap time
        $round_time_seconds=$round_time/pow(1000,2); //Convert lap time to seconds
        if($round_time_seconds>$limit) {
            //printf("time %d is above limit %d\n", $round_time_seconds, $limit);
            return null;
        }
        return $round_time_seconds;
    }

    /**
     * Convert decoder time to valid unix timestamp
     * @param int $time
     * @return int
     */
    public static function convert_time(int $time): int
    {
        return intval($time/pow(1000,2));
    }

    /**
     * Get last rounds
     * @param int $lap_count_limit Limit the number of laps returned
     * @param int $lap_time_limit Lap times above this limit will be ignored
     * @param int $time_before Only fetch passings before time
     * @return array
     * @throws PDOException Database error
     */
    function laps($lap_count_limit = 90, $lap_time_limit = 60, $time_before = 0): array
    {
        if (!is_numeric($lap_count_limit))
            throw new UnexpectedValueException('Lap count limit is not numeric');
        if (!is_numeric($lap_time_limit))
            throw new UnexpectedValueException('Lap time limit is not numeric');
        if (!is_numeric($time_before))
            throw new UnexpectedValueException('Time before is not numeric');

        //The returned number of passings might be lower than the limit because the limit includes invalid passings
        //TODO: Count and limit rounds instead of passings
        if($time_before == 0)
            $passings = $this->db->query(sprintf('SELECT * FROM %s ORDER BY rtc_time DESC LIMIT %d', $this->table, $lap_count_limit*2));
        else
            $passings = $this->db->query(sprintf('SELECT * FROM %s WHERE rtc_time<%d ORDER BY rtc_time DESC LIMIT %d', $this->table, $time_before, $lap_count_limit*2));
        $car_prev_passing = [];
        $count = 0;
        $laps = [];
        foreach ($passings as $passing)
        {
            if(isset($car_prev_passing[$passing['transponder']]))
            {
                $next_passing = $car_prev_passing[$passing['transponder']];
                $lap_time = self::lap_time($passing, $next_passing, $lap_time_limit);
                if(empty($lap_time)) //Invalid lap time
                {
                    continue;
                }

                $laps[] = [
                    'lap_time'=>$lap_time,
                    'transponder'=>(int)$passing['transponder'],
                    'start_time'=>self::convert_time($passing['rtc_time']),
                    'end_time'=>self::convert_time($next_passing['rtc_time']),
                    'start_number'=>(int)$passing['passing_number'],
                    'end_number'=>(int)$next_passing['passing_number'],
                ];
                $count++;
            }

            $car_prev_passing[$passing['transponder']] = $passing;
            if($count>=$lap_count_limit)
                break;
        }
        if($count<$lap_count_limit && !empty($passing))
        {
            //printf("Have %d/%d, want %d, missing %d, got %d passings\n", $count, count($laps), $lap_count_limit, $lap_count_limit-$count, $passings->rowCount());
            $laps2 = $this->laps($lap_count_limit-$count, $lap_time_limit, $passing['rtc_time']);
            //printf("Got %d more\n", count($laps2));

            $laps = array_merge($laps, $laps2);
        }
        return $laps;
    }

    /**
     * Calculate stats for rounds
     * @param array $rounds Return value from laps()
     * @param bool $save_debug_info Save files with debug information
     * @return array Argument array with added values for CSS class and best time
     */
	function stats(array $rounds, $save_debug_info = false): array
    {
		$reverse_rounds=array_reverse($rounds,true);
		//First round has highest key
		foreach($reverse_rounds as $key=>$round) //First round first
		{
		    //$round['lap_time'] = $round['lapTime'];
			$transponder=$round['transponder'];
			if(!isset($rounds[$key+1]))
			{
				$this->previous_round[$round['transponder']]=$round['lap_time'];
				continue;
			}
			$debug='';
			$debug2='';
			if(!empty($this->previous_round[$round['transponder']]))
			{
				if($round['lap_time']<$this->previous_round[$round['transponder']])
				//if($rounds[$key]['lapTime']<$rounds[$key+1]['lapTime'])
				{
					$rounds[$key]['class']='faster';
					$debug.=sprintf("Transponder %s: %s is faster than %s\n",$round['transponder'],$round['lap_time'],$this->previous_round[$round['transponder']]);
				}
				else
				{
					$rounds[$key]['class']='slower';
					$debug.=sprintf("Transponder %s: %s is slower than %s\n",$round['transponder'],$round['lap_time'],$this->previous_round[$round['transponder']]);
				}
			}
			if(date('Y-m-d',$round['start_time'])!=date('Y-m-d',$rounds[$key+1]['start_time']))
				unset($this->best_rounds[$transponder]);

			//$best_round=$this->best_round($round['transponder'],$round['lapTime']);
			//Find best round
			if(empty($this->best_rounds[$transponder]))
				$this->best_rounds[$transponder]=$round['lap_time'];
			elseif($round['lap_time']<$this->best_rounds[$transponder])
			{
				$rounds[$key]['class']='best-time';
				$rounds[$key]['best-time']=$round['lap_time'];
				$debug2.=sprintf("Transponder %s: New best time: %s (better than %s)\n",$transponder,$round['lap_time'],$this->best_rounds[$transponder]);
				$this->best_rounds[$transponder]=$round['lap_time'];
			}
			else
			{
				$rounds[$key]['best-time']=$this->best_rounds[$transponder];
				$debug2.=sprintf("Tranponder %s: Current time: %s (Best is %s)\n",$transponder,$round['lap_time'],$this->best_rounds[$transponder]);
			}

			$this->previous_round[$round['transponder']]=$round['lap_time'];
		}
		if($save_debug_info && isset($debug) && isset($debug2)) {
            file_put_contents('debug.txt', $debug);
            file_put_contents('debug2.txt', $debug2);
        }
		return $rounds;
	}

    /**
     * Get unique transponders
     * @param bool $timestamp_day
     * @return array Unique transponder numbers
     */
	function unique_transponders($timestamp_day=false): array
    {
		$q_todays_transponders=sprintf('SELECT transponder FROM %s WHERE %s GROUP BY transponder ORDER BY rtc_time DESC',$this->table,$this->query_today($timestamp_day)); //Get todays drivers
        $st = $this->db->query($q_todays_transponders);
        return $st->fetchAll(PDO::FETCH_COLUMN);
	}

    /**
     * Get information about a transponder
     * @param string $transponder Transponder number
     * @return array
     */
	function transponder_info(string $transponder): array
    {
		if(empty($transponder))
			throw new InvalidArgumentException('Transponder empty');

		$st_transponder=$this->db->prepare('SELECT * FROM transponders WHERE transponder_id=?');

		if(!isset($this->transponders[$transponder]))
		{
            $st_transponder->execute(array($transponder));
            $transponder_db = $st_transponder->fetch(PDO::FETCH_ASSOC);
            if(empty($transponder_db))
                return array();
			$this->transponders[$transponder]=array_filter($transponder_db);
			return $this->transponders[$transponder];
		}
		else
			return $this->transponders[$transponder];
	}
	
	
}
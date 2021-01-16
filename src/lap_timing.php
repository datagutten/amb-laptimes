<?php


namespace datagutten\amb\laps;


class lap_timing extends passing_db
{
	public $time_offset_hours=0;
	public $best_rounds;
	public $previous_round;
	public $transponders;

    function __construct($decoder_id, $config = [])
	{
        parent::__construct($decoder_id, $config);
		date_default_timezone_set('GMT');
	}
	public static function query_today($timestamp_day=false)
	{
        date_default_timezone_set('GMT');
		if($timestamp_day===false)
			$timestamp_day=time();
		$time_today_start=strtotime('0:00',$timestamp_day).'000000';
		$time_today_end=strtotime('23:59',$timestamp_day).'999999';
        return sprintf('rtc_time>=%s AND rtc_time<=%s',$time_today_start,$time_today_end);
	}

    /**
     * Calculate stats for rounds
     * @param array $rounds Return value from rounds()
     * @param bool $save_debug_info Save files with debug information
     * @return array Argument array with added values for CSS class and best time
     */
	function stats($rounds, $save_debug_info = false)
	{
		$reverse_rounds=array_reverse($rounds,true);
		//First round has highest key
		foreach($reverse_rounds as $key=>$round) //First round first
		{
			$transponder=$round['transponder'];
			if(!isset($rounds[$key+1]))
			{
				$this->previous_round[$round['transponder']]=$round['lapTime'];
				continue;
			}
			$debug='';
			$debug2='';
			if(!empty($this->previous_round[$round['transponder']]))
			{
				if($round['lapTime']<$this->previous_round[$round['transponder']])
				//if($rounds[$key]['lapTime']<$rounds[$key+1]['lapTime'])
				{
					$rounds[$key]['class']='faster';
					$debug.=sprintf("Transponder %s: %s is faster than %s\n",$round['transponder'],$round['lapTime'],$this->previous_round[$round['transponder']]);
				}
				else
				{
					$rounds[$key]['class']='slower';
					$debug.=sprintf("Transponder %s: %s is slower than %s\n",$round['transponder'],$round['lapTime'],$this->previous_round[$round['transponder']]);
				}
			}
			if(date('Y-m-d',$round['start_time'])!=date('Y-m-d',$rounds[$key+1]['start_time']))
				unset($this->best_rounds[$transponder]);

			//$best_round=$this->best_round($round['transponder'],$round['lapTime']);
			//Find best round
			if(empty($this->best_rounds[$transponder]))
				$this->best_rounds[$transponder]=$round['lapTime'];
			elseif($round['lapTime']<$this->best_rounds[$transponder])
			{
				$rounds[$key]['class']='best-time';
				$rounds[$key]['best-time']=$round['lapTime'];
				$debug2.=sprintf("Transponder %s: New best time: %s (better than %s)\n",$transponder,$round['lapTime'],$this->best_rounds[$transponder]);
				$this->best_rounds[$transponder]=$round['lapTime'];
			}
			else
			{
				$rounds[$key]['best-time']=$this->best_rounds[$transponder];
				$debug2.=sprintf("Tranponder %s: Current time: %s (Best is %s)\n",$transponder,$round['lapTime'],$this->best_rounds[$transponder]);
			}

			$this->previous_round[$round['transponder']]=$round['lapTime'];
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
	function unique_transponders($timestamp_day=false)
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
	function transponder_info($transponder)
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
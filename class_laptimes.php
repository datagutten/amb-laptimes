<?php
require 'db.php';
class laptimes extends passings_db
{
	public $time_offset_hours=0;
	public $decoder='20f93';
	public $db;
	public $best_rounds;
	public $previous_round;
	public $transponders;
	function __construct()
	{
		require 'tools/pdo_helper.class.php';
		$this->db=new pdo_helper;
		$this->db->connect_db_config();
		date_default_timezone_set('GMT');
	}
	function query_today($timestamp_day=false)
	{
		if($timestamp_day===false)
			$timestamp_day=time();
		$time_today_start=strtotime('0:00',$timestamp_day).'000000';
		$time_today_end=strtotime('23:59',$timestamp_day).'999999';
		$q=sprintf('rtc_time>=%s AND rtc_time<=%s',$time_today_start,$time_today_end);
		return $q;
	}

    /**
     * Get last rounds
     * @param int $limit
     * @return array
     */
	function rounds($limit=90)
	{
	    if(!is_numeric($limit))
	        throw new UnexpectedValueException('Limit is not numeric');
        //The returned number of passings might be lower than the limit because the limit includes invalid passings
        //TODO: Count and limit rounds instead of passings
		$passings=$this->db->query(sprintf('SELECT * FROM passings_%s ORDER BY rtc_time DESC LIMIT %d',$this->decoder, $limit), 'all');
		$transponders=array_column($passings,'transponder');

		foreach($passings as $key=>$passing) //Last passing first
		{
			unset($transponders[$key]); //Avoid hitting current transponder with search
			$prev_passing_key=array_search($passing['transponder'],$transponders); //Find previous passing for this transponder

			/*if(!isset($car_prev_passing[$passing['transponder']])) //First passing
			{
				$car_prev_passing[$passing['transponder']]=$passing;
				continue;
			}*/
			if($prev_passing_key===false)
				continue;

			unset($transponders[$prev_passing_key]); //Remove previous passing
			$prev_passing=$passings[$prev_passing_key];
			$round_time=$passing['rtc_time']-$prev_passing['rtc_time']; //Calculate lap time
			$round_time_seconds=$round_time/pow(1000,2); //Convert lap time to seconds
			//$best_time=$this->best_round($passing['transponder'],$round_time_seconds);
			if($round_time_seconds>60) //Hide rounds which is too slow
				continue;

			$start_time=substr($prev_passing['rtc_time'],0,-6); //Convert start time to valid unix timestamp
			$round=array(
				'round_time'=>$round_time,
				'lapTime'=>$round_time_seconds,
				'transponder'=>$passing['transponder'],
				'start_time'=>$start_time,
				'first_passing'=>$prev_passing['passing_number'],
				'last_passing'=>$passing['passing_number'],
				'avatar'=>'',
				'nickname'=>'',
				'datetime'=>date('H:i:s d.m.Y',$start_time),
				'best-time'=>'',
				'class'=>'');
			$transponder=$this->transponder_info($passing['transponder']);
			if(!empty($transponder))
				$round=array_merge($round,$transponder);

			/*if(isset($this->transponders[$passing['transponder']]))
			{
				$transponder=$this->transponders[$passing['transponder']];
				print_r($transponder);
				foreach(array('transponder','nickname','avatar') as $field)
				{
					if(!empty($transponder[$field]))
						$round[$field]=$transponder[$field];
				}
			}*/
			//print_r($round);
			/*if($round_time_seconds<60 && isset($last_round_key[$passing['transponder']]))
			{
				//Remove previous round
				//unset($rounds[$last_round_key[$passing['transponder']]]);
			}*/
			$rounds[$key]=$round;
			$last_round_key[$passing['transponder']]=$key;
			$last_round[$passing['transponder']]=$round;
			$car_prev_passing[$passing['transponder']]=$passing;

		}
		return $rounds;
	}
	/*function best_round($transponder,$last_round=false)
	{
		if(empty($this->best_rounds[$transponder]))
		{
			if(!empty($last_round))
				return $this->best_rounds[$transponder]=$last_round;
			else
				return false;
		}
		//var_dump($last_round<$this->best_rounds[$transponder]);
		if($last_round<$this->best_rounds[$transponder])
		{
			$this->best_rounds[$transponder]=$last_round;
			return true;
		}
			
		else
			return $this->best_rounds[$transponder];
	}*/

    /**
     * Calculate stats for rounds
     * @param array $rounds Return value from rounds()
     * @return array Argument array with added values for CSS class and best time
     */
	function stats($rounds)
	{
		$reverse_rounds=array_reverse($rounds,true);
		//First round has highest key
		$counter=0;
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
			/*if($counter>=30)
				break;*/
			$counter++;
		}
		/*file_put_contents('debug.txt',$debug);
		file_put_contents('debug2.txt',$debug2);*/
		return $rounds;
	}

    /**
     * Get unique transponders
     * @param bool $timestamp_day
     * @return array Unique transponder numbers
     */
	function unique_transponders($timestamp_day=false)
	{
		$q_todays_transponders=sprintf('SELECT distinct transponder FROM passings_%s WHERE %s ORDER BY rtc_time DESC',$this->decoder,$this->query_today($timestamp_day)); //Get todays drivers
		return $this->db->query($q_todays_transponders,'all_column');
	}

    /**
     * Get information about a transponder
     * @param string $transponder Transponder number
     * @return array
     */
	function transponder_info($transponder)
	{
		if(empty($transponder))
			return false;
		/*$st_transponder=$this->db->prepare('SELECT transponder_id,transponder,nickname FROM transponders WHERE transponder_id=?');
		$st_avatar=$this->db->prepare('SELECT avatar FROM transponders WHERE transponder_id=?');*/
		$st_transponder=$this->db->prepare('SELECT * FROM transponders WHERE transponder_id=?');

		if(!isset($this->transponders[$transponder]))
		{
            $transponder_db = $this->db->execute($st_transponder,array($transponder),'assoc');
            if(empty($transponder_db))
                return array();
			$this->transponders[$transponder]=array_filter($transponder_db);
			return $this->transponders[$transponder];
		}
			
		else
			return $this->transponders[$transponder];
	}
	
	
}
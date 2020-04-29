<?php
class passings_db
{
    /**
     * @var pdo_helper
     */
	public $db;
	public $st_insert=false;
	public $times_indb;
	public $table;
	function __construct($decoder_id)
	{
		//require 'config.php';
		$this->table='passings_'.$decoder_id;

		$this->db = new pdo_helper();
		try {
            $this->db->connect_db_config();
        }
        catch (Exception $e)
        {
            die($e->getMessage());
        }
	}

	function init()
	{
		$this->st_insert=$this->db->prepare($this->q="INSERT INTO {$this->table} (rtc_time,passing_number,transponder,strength,hits,flags,decoder_id) VALUES (?,?,?,?,?,?,?)");
		$st_times=$this->db->query("SELECT rtc_time FROM {$this->table}") or $this->error();
		$this->times_indb=$st_times->fetchAll(PDO::FETCH_COLUMN); //Get all passings already in db	
	}
	function insert($record_parsed)
	{
		if(array_search($record_parsed['RTC_TIME'],$this->times_indb)!==false)
			return true;
		if(isset($record_parsed['error'])) //Don't put bad records in the DB
			return false;
		$status=$this->st_insert->execute($fields=array($record_parsed['RTC_TIME'],$record_parsed['PASSING_NUMBER'],$record_parsed['TRANSPONDER'],$record_parsed['STRENGTH'],$record_parsed['HITS'],$record_parsed['FLAGS'],dechex($record_parsed['DECODER_ID'])));
		if($status===false)
		{
			$errorinfo=$this->st_insert->errorInfo();
			trigger_error("SQL error: ".$errorinfo[2]);
			return false;
		}
	}
	function error()
	{
		$errorinfo=$this->db->errorInfo();
		throw new Exception("SQL error: ".$errorinfo[2]);
	}
}
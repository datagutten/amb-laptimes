<?php


namespace datagutten\amb\laps;


use FileNotFoundException;
use PDO;
use pdo_helper;
use PDOException;
use PDOStatement;

class passing_db
{
    /**
     * @var pdo_helper
     */
	public $db;
    /**
     * @var PDOStatement
     */
    public $st_insert;
	public $times_indb;
	public $table;

    /**
     * passing_db constructor.
     * @param $decoder_id
     * @throws FileNotFoundException Config file not found
     * @throws PDOException Database connection failed
     */
	function __construct($decoder_id)
	{
		//require 'config.php';
		$this->table='passings_'.$decoder_id;

		$this->db = new pdo_helper();
        $this->db->connect_db_config();
	}

	function init()
	{
		$this->st_insert=$this->db->prepare($this->q="INSERT INTO {$this->table} (rtc_time,passing_number,transponder,strength,hits,flags,decoder_id) VALUES (?,?,?,?,?,?,?)");
		$st_times=$this->db->query("SELECT rtc_time FROM {$this->table}");
		$this->times_indb=$st_times->fetchAll(PDO::FETCH_COLUMN); //Get all passings already in db	
	}

    /**
     * Create passing table for a decoder
     * @param $decoder_id
     * @throws PDOException
     */
    public function create_table($decoder_id)
    {
        $this->db->query(sprintf('CREATE TABLE `passings_%s` (
								  `rtc_time` bigint(16) NOT NULL,
								  `passing_number` int(4) DEFAULT NULL,
								  `transponder` int(9) DEFAULT NULL,
								  `strength` int(3) DEFAULT NULL,
								  `hits` int(3) DEFAULT NULL,
								  `flags` int(3) DEFAULT NULL,
								  `decoder_id` varchar(6) DEFAULT NULL,
								  PRIMARY KEY (`rtc_time`)
								) ENGINE=InnoDB DEFAULT CHARSET=utf8;
								 ',$decoder_id));
    }

    /**
     * Insert parsed passing record into database
     * @param array $record_parsed Parsed passing record
     */
    function insert($record_parsed)
    {
        if(array_search($record_parsed['RTC_TIME'],$this->times_indb)!==false)
            return;
        $this->st_insert->execute($fields=array($record_parsed['RTC_TIME'],$record_parsed['PASSING_NUMBER'],$record_parsed['TRANSPONDER'],$record_parsed['STRENGTH'],$record_parsed['HITS'],$record_parsed['FLAGS'],dechex($record_parsed['DECODER_ID'])));
    }
}
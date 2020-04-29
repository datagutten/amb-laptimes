<?php


namespace datagutten\amb\laps;


use Exception;
use PDO;
use pdo_helper;
use PDOException;

class passing_db
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
}
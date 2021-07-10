<?php


namespace datagutten\amb\laps;


use datagutten\tools\PDOConnectHelper;
use PDO;
use PDOException;
use PDOStatement;

class passing_db
{
    /**
     * @var PDO
     */
	public $db;
    /**
     * @var PDOStatement
     */
    public $st_insert;
    /**
     * @var PDOStatement Query to insert a transponder in the database
     */
    public $st_insert_transponder;
    /**
     * @var PDOStatement Query to update a existing transponder in the database
     */
    public $st_update_transponder;

	public $times_indb;
	public $table;

    /**
     * @var array Transponders in database
     */
	public $transpondersInDB;

    /**
     * passing_db constructor.
     * @param string $decoder_id Decoder ID
     * @param array $config Database configuration
     * @throws PDOException Database connection failed
     */
	function __construct(string $decoder_id, $config = [])
	{
		$this->table='passings_'.$decoder_id;
		if(empty($config))
            $config = require 'config_db.php';

        $dsn = PDOConnectHelper::build_dsn($config);
        $this->db = new PDO($dsn, $config['db_user'], $config['db_password']);
        $this->db->exec("SET sql_mode=(SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
    }

	function init()
	{
	    $query = "INSERT INTO {$this->table} (rtc_time,passing_number,transponder,strength,hits,flags,decoder_id) VALUES (?,?,?,?,?,?,?)";
		$this->st_insert=$this->db->prepare($query);
		$st_times=$this->db->query("SELECT rtc_time FROM {$this->table}");
		$this->times_indb=$st_times->fetchAll(PDO::FETCH_COLUMN); //Get all passings already in db
	}

    /**
     * Prepare transponder related queries
     */
	function init_transponders()
    {
        $this->st_insert_transponder = $this->db->prepare('INSERT INTO transponders (transponder_id, transponder_name, nickname) VALUES (?,?,?)');
        $this->st_update_transponder = $this->db->prepare('UPDATE transponders SET transponder_name=?, nickname=? WHERE transponder_id=?');
        $this->transpondersInDB = $this->transponders(true);
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

    /**
     * Get all transponder information in database
     * @param bool $id_only Get only the id of the transponder
     * @return array|PDOStatement
     */
    function transponders($id_only = false)
    {
        if($id_only) {
            $st_transponders = $this->db->query('SELECT transponder_id FROM transponders');
            $transponders = $st_transponders->fetchAll(PDO::FETCH_COLUMN);
            if (empty($transponders))
                $transponders = [];
            return $transponders;
        }
        else
        {
            return $this->db->query('SELECT * FROM transponders');
        }
    }

    /**
     * Save transponder info in the database
     * @param $activity_info
     */
    function save_transponder($activity_info)
    {
        if(empty($this->transpondersInDB))
            $this->init_transponders();
        if(array_search($activity_info['transponder_id'], $this->transpondersInDB)===false) {
            $this->st_insert_transponder->execute(array($activity_info['transponder_id'], $activity_info['transponder_name'], $activity_info['driver_name']));
            $this->transpondersInDB[] = $activity_info['transponder_id'];
        }
        else
            $this->st_update_transponder->execute([$activity_info['transponder_name'], $activity_info['driver_name'], $activity_info['transponder_id']]);
    }
}
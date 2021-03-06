<?php


use datagutten\amb\laps\passing_db;
use PHPUnit\Framework\TestCase;

class passing_dbTest extends TestCase
{
    /**
     * @var passing_db
     */
    private $passings;

    public function setUp(): void
    {
        $config = require __DIR__.'/test_config.php';
        $this->passings = new passing_db($config['db'], 'test_decoder');


        $this->passings->db->query('DROP TABLE IF EXISTS transponders');
        $this->passings->db->query('DROP TABLE IF EXISTS passings_test_decoder');

        $this->passings->create_table('test_decoder');
    }

    public function testCreate_table()
    {
        $this->passings->db->query('DROP TABLE IF EXISTS passings_test_table');
        $this->assertFalse($this->passings->tableExists('passings_test_table'));
        $this->passings->create_table('test_table');
        $this->assertTrue($this->passings->tableExists('passings_test_table'));
    }

    public function testInit()
    {
        $this->passings->db->query('DROP TABLE IF EXISTS passings_test_decoder');
        $this->assertFalse($this->passings->tableExists('passings_test_decoder'));
        $this->passings->init();
        $this->assertTrue($this->passings->tableExists('passings_test_decoder'));
    }

    public function testInsert()
    {
        $passings = $this->passings;
        $passings->init();

        $passing = [
            'version' => 2,
            'length' => 51,
            'crc' => 'c79e',
            'flags_header' => 0000,
            'type' => 1,
            'record_hex' => '8e0233009ec700000100010448bc010003041d582c000408e805bfc4a41b050005026a0006023200080200008104930f02008f',
            'PASSING_NUMBER' => 113736,
            'TRANSPONDER' => 2906141,
            'RTC_TIME' => 1437769372993000,
            'STRENGTH' => 106,
            'HITS' => 50,
            'FLAGS' => 0,
            'DECODER_ID' => 135059
        ];
        $passings->insert($passing);
        $st = $passings->db->query('SELECT * FROM passings_test_decoder WHERE rtc_time=1437769372993000');
        $this->assertEquals(1, $st->rowCount());
    }

    public function testTransponders()
    {
        $this->passings->init_transponders();
        $this->passings->db->query(file_get_contents(__DIR__.'/test_data/transponder_records.sql'));
        $transponders = $this->passings->transponders(true);
        $this->assertTrue(is_array($transponders));
        $this->assertEquals("2284132", $transponders[1]);
    }

    public function testTransponders2()
    {
        $this->passings->init_transponders();
        $this->passings->db->query(file_get_contents(__DIR__.'/test_data/transponder_records.sql'));
        $transponders = $this->passings->transponders();
        $this->assertInstanceOf('PDOStatement', $transponders);
    }

    public function testInitTransponders()
    {
        $this->assertFalse($this->passings->tableExists('transponders'));
        $this->passings->init_transponders();
        $this->assertTrue($this->passings->tableExists('transponders'));
    }

    public function testSaveTransponder()
    {
        $this->passings->init_transponders();
        $st = $this->passings->db->query('SELECT * FROM transponders WHERE transponder_id=2583246');
        $this->assertEquals(0, $st->rowCount());
        $this->passings->save_transponder(['transponder_id' => 2583246, 'transponder_name' => 'Xray Xb2 2019', 'driver_name' => 'Steffler']);
        $st = $this->passings->db->query('SELECT * FROM transponders WHERE transponder_id=2583246');
        $this->assertEquals(1, $st->rowCount());
    }

    public function testUpdateTransponder()
    {
        $this->passings->save_transponder(['transponder_id' => 2583246, 'transponder_name' => 'Xray Xb2 2019', 'driver_name' => 'Steffler']);
        $row = $this->passings->db->query('SELECT * FROM transponders WHERE transponder_id=2583246')->fetch();
        $this->assertEquals('Steffler', $row['nickname']);
        $this->passings->save_transponder(['transponder_id' => 2583246, 'transponder_name' => 'Xray Xb2 2019', 'driver_name' => 'Steffler_test']);
        $row = $this->passings->db->query('SELECT * FROM transponders WHERE transponder_id=2583246')->fetch();
        $this->assertEquals('Steffler_test', $row['nickname']);
    }
}

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
        $this->passings = new passing_db('test_decoder', $config['db']);

        $this->passings->db->query('DROP TABLE IF EXISTS transponders');
        $this->passings->db->query('DROP TABLE IF EXISTS passings_test_decoder');

        $this->passings->create_table('test_decoder');
        $this->passings->db->query(file_get_contents(__DIR__.'/../src/transponders.sql'));
    }

    public function testCreate_table()
    {
        try {
            $st = $this->passings->db->query('SELECT * FROM passings_test_decoder');
            $this->assertEquals(0, $st->rowCount());
        }
        catch (PDOException $e)
        {
            $this->fail($e->getMessage());
        }
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
        $transponders = $this->passings->transponders(true);
        $this->assertTrue(is_array($transponders));
    }

    public function testTransponders2()
    {
        $transponders = $this->passings->transponders();
        $this->assertInstanceOf('PDOStatement', $transponders);
    }

    public function testSaveTransponder()
    {
        $st = $this->passings->db->query('SELECT * FROM transponders WHERE transponder_id=2583246');
        $this->assertEquals(0, $st->rowCount());
        $this->passings->save_transponder(['transponder_id' => 2583246, 'transponder_name' => 'Xray Xb2 2019', 'driver_name' => 'Steffler']);
        $st = $this->passings->db->query('SELECT * FROM transponders WHERE transponder_id=2583246');
        $this->assertEquals(1, $st->rowCount());
    }
}

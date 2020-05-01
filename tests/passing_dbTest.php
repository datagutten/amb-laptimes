<?php


use datagutten\amb\laps\passing_db;
use PHPUnit\Framework\TestCase;

class passing_dbTest extends TestCase
{
    public function testCreate_table()
    {
        set_include_path(__DIR__);
        $passings = new passing_db('test_decoder');
        $passings->create_table('test_decoder');

        try {
            $st = $passings->db->query('SELECT * FROM passings_test_decoder');
            $this->assertEquals(0, $st->rowCount());
        }
        catch (PDOException $e)
        {
            $this->fail($e->getMessage());
        }

        //$passings->insert()
        $passings->db->query('DROP TABLE passings_test_decoder');
    }

    public function testInsert()
    {
        set_include_path(__DIR__);
        $passings = new passing_db('test_decoder');
        try {
            $passings->create_table('test_decoder');
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
        finally {
            $passings->db->query('DROP TABLE passings_test_decoder');
        }
    }
}

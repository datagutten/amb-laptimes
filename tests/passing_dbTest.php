<?php


use datagutten\amb\laps\passing_db;
use PHPUnit\Framework\TestCase;

class passing_dbTest extends TestCase
{
    public function testCreate_table()
    {
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
}

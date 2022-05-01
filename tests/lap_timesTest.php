<?php

use datagutten\amb\laps\lap_timing;
use PHPUnit\Framework\TestCase;

class lap_timesTest extends TestCase
{
    /**
     * @var lap_timing
     */
    protected $laps;

    public function setUp(): void
    {
        $config = require __DIR__.'/test_config.php';
        $this->laps = new lap_timing($config, '20f93');

        if (!$this->laps->tableExists('transponders'))
        {
            $this->laps->db->query(file_get_contents(__DIR__ . '/../src/transponders.sql'));
            $this->laps->db->query(file_get_contents(__DIR__ . '/test_data/transponder_records.sql'));
        }

        if (!$this->laps->tableExists('passings_20f93'))
            $this->laps->db->query(file_get_contents(__DIR__ . '/test_data/passings.sql'));
    }

    function testQueryToday()
    {
        $q = lap_timing::query_today(1588188349);
        $this->assertEquals('rtc_time>=1588118400000000 AND rtc_time<=1588204740999999', $q);
        $q = lap_timing::query_today();
        $time_today_start=strtotime('0:00').'000000';
        $time_today_end=strtotime('23:59').'999999';
        $this->assertEquals(sprintf('rtc_time>=%d AND rtc_time<=%d', $time_today_start, $time_today_end), $q);
    }

    public function testInvalidLimit()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Lap count limit is not numeric');
        $this->laps->laps('a');
    }

    public function testInvalidLapTimeLimit()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->laps->laps(2, 'a');
    }
    public function testInvalidStartTime()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->laps->laps(2, 60, 'a');
    }

    public function testRounds()
    {
        $lap_times = $this->laps->laps(5);
        $round =[
            'lap_time' => 37.643,
            'transponder' => 7824878,
            'start_time' => 1588188349,
            'end_time' => 1588188387,
            'start_number' => 470764,
            'end_number' => 470765];

        $this->assertEquals($round, $lap_times[0]);
    }

    public function testConvertTime()
    {
        $time = lap_timing::convert_time(1588188349800000);
        $this->assertEquals(1588188349, $time);
    }

    function testStats()
    {
        $rounds = $this->laps->laps(5);
        $stats = $this->laps->stats($rounds);
        $this->assertEquals('slower', $stats[0]['class']);
        $this->assertEquals(strtotime('19:25:49 29.04.2020'), $stats[0]['start_time']);
        $this->assertEquals('best-time', $stats[1]['class']);
    }

    public function testLimitRounds()
    {
        $this->assertCount(5, $this->laps->laps(5));
        $this->assertCount(10, $this->laps->laps(10));
        $this->assertCount(16, $this->laps->laps(16));
        $this->assertCount(50, $this->laps->laps(50));
    }

    function testLimitRounds2()
    {
        $rounds = $this->laps->laps(10);
        $this->assertLessThanOrEqual(10, count($rounds));
        $round =[
            'lap_time' => 37.643,
            'transponder' => 7824878,
            'start_time' => 1588188349,
            'end_time' => 1588188387,
            'start_number' => 470764,
            'end_number' => 470765];
        $this->assertEquals($round, $rounds[0]);
    }

    function testUniqueTransponders()
    {
        $transponders = $this->laps->unique_transponders(1588188349);
        $this->assertEquals([8432586, 2546398, 2484233, 5589166, 7824878], $transponders);
    }
}

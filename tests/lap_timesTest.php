<?php

use PHPUnit\Framework\TestCase;
use datagutten\amb\laps\lap_timing as laptimes;

class lap_timesTest extends TestCase
{
    function testQueryToday()
    {
        $q = laptimes::query_today(1588188349);
        $this->assertEquals('rtc_time>=1588118400000000 AND rtc_time<=1588204740999999', $q);
        $q = laptimes::query_today();
        $time_today_start=strtotime('0:00').'000000';
        $time_today_end=strtotime('23:59').'999999';
        $this->assertEquals(sprintf('rtc_time>=%d AND rtc_time<=%d', $time_today_start, $time_today_end), $q);
    }

    function testInvalidLimit()
    {
        set_include_path(__DIR__);
        chdir('..');
        $times = new laptimes('20f93');
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Limit is not numeric');
        $times->rounds('asdf');
    }

    function testStats()
    {
        set_include_path(__DIR__);
        chdir('..');
        $times = new laptimes('20f93');
        //$q = 'SELECT * FROM passings_20f93 WHERE transponder=7824878';
        $rounds = $times->rounds(10);
        $stats = $times->stats($rounds);
        $this->assertEquals('slower', $stats[0]['class']);
        $this->assertEquals('19:25:49 29.04.2020', $stats[0]['datetime']);
        $this->assertEquals('best-time', $stats[1]['class']);
    }

    function testLimitRounds()
    {
        set_include_path(__DIR__);
        chdir('..');
        $times = new laptimes('20f93');
        $rounds = $times->rounds(10);
        $this->assertLessThanOrEqual(10, count($rounds));
        $round =[
            'round_time' => 37643000,
            'lapTime' => 37.643,
            'transponder' => 7824878,
            'start_time' => 1588188349,
            'first_passing' => 470764,
            'last_passing' => 470765,
            'avatar' => null,
            'nickname' => 'PetterS',
            'datetime' => '19:25:49 29.04.2020',
            'best-time' => null,
            'class' => null,
            'transponder_id' => '7824878',
            'transponder_name'=> 'PetterS'];
        $this->assertEquals($round, $rounds[0]);
    }

    function testUniqueTransponders()
    {
        set_include_path(__DIR__);
        chdir('..');
        $times = new laptimes('20f93');
        $transponders = $times->unique_transponders(1588188349);
        $this->assertEquals([8432586, 2546398, 2484233, 5589166, 7824878], $transponders);
    }
}

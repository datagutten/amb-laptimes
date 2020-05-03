<?php


use datagutten\amb\laps\mylaps;
use PHPUnit\Framework\TestCase;

class mylapsTest extends TestCase
{

    public function testActivities()
    {
        $mylaps = new mylaps(238);
        $activities = $mylaps->activities();
        $this->assertTrue(is_array($activities));
    }

    public function testActivityId()
    {
        $mylaps = new mylaps(238);
        $activities = $mylaps->activities();
        $id = mylaps::activity_id($activities[0]);
        $this->assertIsInt($id);
    }

    function testActivityInfo()
    {
        $mylaps = new mylaps(238);

        $activity = $mylaps->activity_info(692252279);
        $this->assertEquals('MR.T', $activity['driver_name']);
        $this->assertEquals('Trygve Berntsen 1-8 Track', $activity['transponder_name']);
        $this->assertEquals(8114139, $activity['transponder_id']);
        $this->assertEquals('https://speedhive.mylaps.com/Images/MYLAPS-GA-eeedd3a85f24490ebdcdea232cef1b66/1', $activity['avatar_url']);
    }

    function testActivityInfoCarId()
    {
        $mylaps = new mylaps(238);

        $activity = $mylaps->activity_info(692252350);
        $this->assertEquals('7558082 [0]', $activity['driver_name']);
        $this->assertEquals('7558082 [0]', $activity['transponder_name']);
        $this->assertEquals(7558082, $activity['transponder_id']);
    }

    public function testTransponderId()
    {
        $id = mylaps::transponder_id(692252279);
        $this->assertEquals(8114139, $id);
    }

    public function testDownloadAvatar()
    {
        $avatar_file = sys_get_temp_dir().'/avatar_test.jpg';
        mylaps::download_avatar('https://speedhive.mylaps.com/Images/MYLAPS-GA-66c5a32dc0344fce946b0e8f6262dbff/1', $avatar_file);
        $this->assertFileExists($avatar_file);
        $this->assertGreaterThan(0, filesize($avatar_file));
    }
}

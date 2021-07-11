<?php /** @noinspection PhpUnhandledExceptionInspection */


use datagutten\amb\laps\mylaps;
use datagutten\amb\laps\passing_db;
use PHPUnit\Framework\TestCase;

class mylapsTest extends TestCase
{
    /**
     * @var string Avatar folder path
     */
    private $avatar_folder;

    public function setUp(): void
    {
        $filesystem = new Symfony\Component\Filesystem\Filesystem();
        $this->avatar_folder = sys_get_temp_dir().'/avatar_test';
        if(file_exists($this->avatar_folder))
            $filesystem->remove($this->avatar_folder);
        mkdir($this->avatar_folder);
    }

    public function testActivities()
    {
        $activities = mylaps::activities(238);
        $this->assertTrue(is_array($activities));
    }

    public function testActivityId()
    {
        $activities = mylaps::activities(238);
        $id = mylaps::activity_id($activities[0]);
        $this->assertTrue(is_int($id));
    }

    function testActivityInfo()
    {
        $activity = mylaps::activity_info(692252279);
        $this->assertEquals('MR.T', $activity['driver_name']);
        $this->assertEquals('Trygve Berntsen 1-8 Track', $activity['transponder_name']);
        $this->assertEquals(8114139, $activity['transponder_id']);
        $this->assertEquals('https://speedhive.mylaps.com/Images/MYLAPS-GA-eeedd3a85f24490ebdcdea232cef1b66/1', $activity['avatar_url']);
    }

    function testActivityInfoCharset()
    {
        $activity = mylaps::activity_info(692252467);
        $this->assertEquals('kai rønning', $activity['driver_name'], 'Driver name does not match');
        $this->assertEquals('kai rønning', $activity['transponder_name'], 'Transponder name does not match');
        $this->assertEquals(9784547, $activity['transponder_id']);
        $this->assertEquals('https://speedhive.mylaps.com/Images/MYLAPS-GA-0c031e0ee83b4072b38efefd1b528d56/1', $activity['avatar_url']);
    }

    function testActivityInfoCarId()
    {
        $activity = mylaps::activity_info(692252350);
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
        $avatar_file = mylaps::download_avatar('https://speedhive.mylaps.com/Images/MYLAPS-GA-66c5a32dc0344fce946b0e8f6262dbff/1', $this->avatar_folder, 2583246);
        $this->assertFileExists($avatar_file);
        $this->assertGreaterThan(0, filesize($avatar_file));
    }

    public function testSave_transponder_info()
    {
        $mylaps = new mylaps();
        $config = require __DIR__.'/test_config.php';
        $passings = new passing_db($config['db']);
        $passings->db->query('DROP TABLE IF EXISTS transponders');

        $mylaps->save_transponder_info(238, $this->avatar_folder, $passings);
        $this->assertTrue($passings->tableExists('transponders'));
        $transponders = $passings->transponders(true);
        $this->assertIsArray($transponders);
        $this->assertGreaterThan(2, count($transponders));
    }
}

<?Php

use datagutten\amb\laps\exceptions\MyLapsException;
use datagutten\amb\laps\mylaps;
use datagutten\amb\laps\passing_db;

require 'vendor/autoload.php';



$config = require 'config.php';
if (empty($config['decoders']))
    die("Missing decoder key in config file\n");
$passing_db = new passing_db('', $config['db']);

$decoders = $config['decoders'];

if(!isset($argv[1]))
    die(sprintf("Usage: %s [decoder name]", __FILE__));
if(!isset($decoders[$argv[1]]))
    die("Invalid decoder\n");
else
    $decoder = $decoders[$argv[1]];

$mylaps = new mylaps();

try {
    $activities = mylaps::activities($decoder['mylaps_id']);
}
catch (MyLapsException $e)
{
    die($e->getMessage()."\n");
}

$existing_transponders = $passing_db->transponders(true);

$st_insert_transponder = $passing_db->db->prepare('INSERT INTO transponders (transponder_id, transponder_name, nickname) VALUES (?,?,?)');
$st_update_transponder = $passing_db->db->prepare('UPDATE transponders SET transponder_name=?, nickname=? WHERE transponder_id=?');

if(!file_exists('avatars'))
    mkdir('avatars');

foreach($activities as $activity)
{
	//$activityId=686314222; //Roger Berntsen, navn på bruker, men ikke transponder og avatar
	//$activityId=686300815; //Holla
	//$activityId=686291167;// Sandgrind
    try {
        $activity_info = $mylaps->activity_info(mylaps::activity_id($activity));
    }
    catch (MyLapsException $e)
    {
        echo $e->getMessage()."\n";
        continue;
    }
    if(empty($activity_info['transponder_name']) && empty($activity_info['driver_name']))
        continue;

    if(!empty($activity_info['avatar_url'])) {
        $avatar_folder = __DIR__.'/avatars';
        try {
            mylaps::download_avatar($activity_info['avatar_url'], $avatar_folder, $activity_info['transponder_id']);
        }
        catch (MyLapsException $e)
        {
            printf("Error downloading avatar: %s\n", $e->getMessage());
        }
    }

    if(array_search($activity_info['transponder_id'], $existing_transponders)===false) {
        $st_insert_transponder->execute(array($activity_info['transponder_id'], $activity_info['transponder_name'], $activity_info['driver_name']));
        $existing_transponders[] = $activity_info['transponder_id'];
    }
    else
        $st_update_transponder->execute([$activity_info['transponder_name'], $activity_info['driver_name'], $activity_info['transponder_id']]);
}
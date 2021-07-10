<?Php

use datagutten\amb\laps\passing_db;
use datagutten\amb\parser;
use datagutten\amb\parser\socket;

require 'vendor/autoload.php';


//Capture data from a AMB decoder using a socket

$config = require 'config.php';
if (empty($config['decoders']))
    die("Missing decoder key in config file\n");

if(!isset($argv[1]))
    die("Usage: passing_saver.php [decoder name]");
if(!isset($config['decoders'][$argv[1]]))
    die("Invalid decoder\n");
else
    $decoder = $config['decoders'][$argv[1]];

$debug = isset($argv[2]);

if(!isset($decoder['address']))
    die("Decoder address not set in config file\n");
if(!isset($decoder['port']))
    $decoder['port'] = 5403;

try {
    $socket = new socket($decoder['address'], $decoder['port']);
    $db = new passing_db($config['db'], $decoder['id']);
    $db->init();
}
catch (parser\exceptions\ConnectionError $e)
{
    die($e->getMessage()."\n");
}

while (true) 
{
    try {
        $records = $socket->read_records();
    }
    catch (parser\exceptions\ConnectionError $e)
    {
        die($e->getMessage()."\n");
    }

    if(empty($records))
        continue;
    if(!isset($decoder_validated))
    {
        try {
            $record_parsed = parser\parser::parse($records[0]);
            if (dechex($record_parsed['DECODER_ID']) != $decoder['id'])
                die(sprintf("Received message from decoder %s, expected %s\n", $record_parsed['DECODER_ID'], $decoder['id']));
            else
                $decoder_validated = true;
        }
        catch (parser\exceptions\AmbParseError $e)
        {
            continue;
        }
    }

	foreach($records as $record)
	{
	    try {
            $record_parsed = parser\parser::parse($record, 0x01);
        }
        catch (parser\exceptions\AmbParseError $e)
        {
            echo $e->getMessage()."\n";
            continue;
        }
		if($record_parsed===true)
        {
            if($debug)
                echo "Skip invalid type\n";
            continue;
        }

		print_r($record_parsed);
		if(!is_array($record_parsed))
			continue;
		$db->insert($record_parsed);
	}
}

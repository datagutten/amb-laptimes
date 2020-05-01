<?Php

use datagutten\amb\laps\passing_db;
use datagutten\amb\parser\socket;

require 'vendor/autoload.php';
$parser=new amb_p3_parser;


//Capture data from a AMB decoder using a socket
if(isset($argv[1]) && file_exists($conf=dirname(__FILE__)."/config_socket_{$argv[1]}.php"))
	require $conf;
else
	die("Missing config name\n");

$socket = new socket($address, $port);

while (true) 
{
    $records = $socket->read_records();
    if(empty($records))
        continue;

	foreach($records as $record)
	{
		$record_parsed=$parser->parse($record,0x01);
		if($record_parsed===true)
        {
            //echo "Skip invalid type\n";
            continue;
        }

		print_r($record_parsed);
		if(!isset($db))
		{
			$db=new passing_db(dechex($record_parsed['DECODER_ID']));
			$db->init();
		}
		if(!is_array($record_parsed))
			continue;
		$db->insert($record_parsed);
	}
}

<?Php

use datagutten\amb\laps\passing_db;

require 'vendor/autoload.php';
$parser=new amb_p3_parser;


//Capture data from a AMB decoder using a socket
if(isset($argv[1]) && file_exists($conf=dirname(__FILE__)."/config_socket_{$argv[1]}.php"))
	require $conf;
else
	die("Missing config name\n");

$socket=socket_create(AF_INET,SOCK_STREAM,0);
$result=socket_connect($socket,$address,$port) or die("Could not connect to server\n");
$buffer=socket_read ($socket, 1024);

//Remove incomplete data at the beginning
if(substr($buffer,0,1)!=chr(0x8E))
	$buffer=substr($buffer,strpos($buffer,chr(0x8E)));

while (true) 
{
	if(($endpos=strpos($buffer,chr(0x8F)))===false)
		$buffer.=socket_read ($socket, 1024); //No complete data in buffer, read more
	if(empty($buffer))
		continue;
	$data_end=strrpos($buffer,chr(0x8F)); //Get position of last end byte
	$records=$parser->get_records($buffer);
	//var_dump(count($records));
	$buffer=substr($buffer,$data_end+1); //Remove data from buffer

	foreach($records as $record)
	{
		//var_dump(strlen($record));
		$record_parsed=$parser->parse($record,0x01);
		if(is_bool($record_parsed))
			continue;
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

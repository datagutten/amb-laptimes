<?php

use datagutten\tools\files\files;
use datagutten\tools\PDOConnectHelper;

require __DIR__ . '/../vendor/autoload.php';

copy(files::path_join(__DIR__, 'test_config_db.php'), files::path_join(__DIR__, 'config_db.php'));

$db = PDOConnectHelper::connect_db_config(['db_host' => '127.0.0.1', 'db_user' => 'root', 'db_password' => 'root', 'db_name' => 'passings_test', 'db_port' => 3800]);

$db->query(file_get_contents(__DIR__ . '/test_data/passings.sql'));
$db->query(file_get_contents(__DIR__ . '/test_data/transponders.sql'));
$db->query(file_get_contents(__DIR__ . '/test_data/transponder_records.sql'));
//$db->query(sprintf('LOAD DATA LOCAL INFILE "%s"', files::path_join(__DIR__, 'test_data', 'passings.sql')));

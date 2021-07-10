<?php

use datagutten\tools\files\files;
use datagutten\tools\PDOConnectHelper;

require __DIR__ . '/../vendor/autoload.php';

copy(files::path_join(__DIR__, 'test_config_db.php'), files::path_join(__DIR__, 'config_db.php'));
$config = require 'test_config_db.php';
$db = PDOConnectHelper::connect_db_config($config);

$db->query(file_get_contents(__DIR__ . '/test_data/passings.sql'));
$db->query(file_get_contents(__DIR__ . '/test_data/transponders.sql'));
$db->query(file_get_contents(__DIR__ . '/test_data/transponder_records.sql'));

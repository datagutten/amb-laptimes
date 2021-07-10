<?php

use datagutten\tools\PDOConnectHelper;

require __DIR__ . '/../vendor/autoload.php';

$config = require 'test_config.php';
$db = PDOConnectHelper::connect_db_config($config);

$db->query(file_get_contents(__DIR__ . '/test_data/passings.sql'));
$db->query(file_get_contents(__DIR__ . '/test_data/transponders.sql'));
$db->query(file_get_contents(__DIR__ . '/test_data/transponder_records.sql'));

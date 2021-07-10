<?Php
$config['db_host']="127.0.0.1";
$config['db_user']=getenv('DB_USER');
$config['db_password']=getenv('DB_PASSWORD');
$config['db_name']=getenv('DB_DATABASE');
$config['db_type']='mysql';
return $config;
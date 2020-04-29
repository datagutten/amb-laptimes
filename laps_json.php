<?Php

require 'vendor/autoload.php';
$laptimes=new laptimes($_GET['decoder']);

echo json_encode($laptimes->stats($laptimes->rounds()));
//$laptimes->rounds();
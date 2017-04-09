<?Php
require 'class_laptimes.php';
$laptimes=new laptimes;
echo json_encode($laptimes->stats($laptimes->rounds()));
//$laptimes->rounds();
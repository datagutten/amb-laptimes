<?Php
require 'vendor/autoload.php';
$db = new pdo_helper();
$db->connect_db_config();


$data=file_get_contents('https://speedhive.mylaps.com/Practice/238/PracticeTrackData?id=238');
$dom=new DOMDocument;
@$dom->loadHtml($data);
//print_r($dom->getElementsByTagName('h1')->);
$xml=simplexml_import_dom($dom);

$activities=$xml->xpath("/html/body//a[contains(@href,'Activity')]"); ///@href
foreach($activities as $activity)
{
	//$activityId=686314222; //Roger Berntsen, navn på bruker, men ikke transponder og avatar
	//$activityId=686300815; //Holla
	//$activityId=686291167;// Sandgrind
	$href=(string)$activity->attributes()['href'];
	$activityId=preg_replace('#/Practice/([0-9]+)/Activity#','$1',$href);

	$user_name=$activity->xpath('.//span[@class="nickname"]');

	if(empty($user_name)) //No user name and no transponder name
		continue;
	elseif(empty($user_name[0])) //No user name, but has transponder name
		$user_name=NULL;
	else
		$user_name=(string)$user_name[0];
	if(empty($user_name))
		$user_name=NULL;

//preg_match('/[0-9]{7} \[[0-9]\]/',$user_name[0])
	$transponder=trim($activity->xpath('.//div[@class="user-data"]')[0]);

	if(!preg_match('/([0-9]{7}) \[[0-9]\]/',$transponder,$transpondernum)) //Transponder has name
	{
		$transponder_name=str_replace('Transponder #','',$transponder);
		//var_dump($transponder_name);
		$csv=file_get_contents('https://speedhive.mylaps.com/Export/GetCsv?activityId='.$activityId);
        $csv=str_replace(chr(0),'',$csv);

		preg_match('/([0-9]{7}),/',$csv,$transponder);
		if(empty($transponder))
		    continue;
		$transponder=$transponder[1];
	}
	elseif(empty($user_name))
		continue;
	else
	{
		$transponder_name=NULL;
		$transponder=$transpondernum[1];
	}

	$avatar=$activity->xpath('.//img[@class="user-avatar"]/@src');

	if($avatar[0]!='/Images/MYLAPS-GA-b3d87aa2c32141af84b14508d8b35cb6/1')
	{
		$avatar='https://speedhive.mylaps.com'.(string)$avatar[0];
		$avatar_data=file_get_contents($avatar);
		$avatar_base64=base64_encode($avatar_data);
	}
	else
		$avatar_base64=NULL;

	$st_insert_transponder=$db->prepare('INSERT IGNORE INTO transponders VALUES (?,?,?,?)');
	$st_insert_transponder->execute(array($transponder,$transponder_name,$user_name,$avatar_base64));
}
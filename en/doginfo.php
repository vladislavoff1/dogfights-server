<?php
//header('Content-Type: text/html; charset=utf-8');
/*________________
    INCLUDE          
________________*/
require_once("../db_settings.php");
require_once("../settings.php");

/*________________
    INIT          
________________*/
//DB settings
$host = DB_HOST;
$user = DB_LOGIN;
$pass = DB_PWD;
$db = DB_NAME;
set_exception_handler('ErrorHandler');
set_error_handler('ErrorHandler');

function MyErrorHandler($errno, $errmsg, $filename, $linenum) {     
	if (!in_array($errno, Array(E_NOTICE, E_STRICT, E_WARNING))) {             
		$date = date('Y-m-d H:i:s (T)');             
		$f = fopen('errors.log', 'a');                 
		if (!empty($f)) {                     
			//$err  = "\r\n";             
			$err .= $date.time."  ";   
			$err .= "dogInfo: ";   
			//$err .= "  $errno\r\n";             
			$err .= "$errmsg\r\n";             
			//$err .= "  $filename\r\n";             
			//$err .= "  $linenum\r\n";             
			//$err .= "\r\n";             
			fwrite($f, $err);            
			fclose($f);    
			//echo $err;                                
		}             
	}
}                          

////trigger_error('start');
// Vkontakte
//auth_key = md5(api_id + '_' + viewer_id + '_' + api_secret)  формула валидации

$api_secret='';

//$fp = fopen(LOG_FILE,'a+');
//loger($_SERVER['REQUEST_URI']);

/*________________
    Logic
________________*/
$conn = @mysql_connect($host, $user, $pass);
if(!$conn) trigger_error(MSG_DB_NOT_CONNECT);
mysql_selectdb($db);
mysql_query('SET NAMES UTF8');


$command = isset($_GET['do'])? urldecode($_GET['do']) : '';
if(!function_exists($command)){
   trigger_error(TXT_METHOD . $command . TXT_NOT_SUPPORTED);
}

$uid= isset($_GET['user']) ? (int) urldecode($_GET['user']): '';
$sig= isset($_GET['sig']) ? urldecode($_GET['sig']): '';
$api_secret = TXT_API_SECRET;
//$auth_key = md5(api_id + '_' + viewer_id + '_' + api_secret);//  формула валидаци
//checkParams($sig, $auth_key); 

//GetDog();
call_user_func($command); 
//fclose($fp);

/*__________________________
   Functions
   
__________________________*/
function GetDog ()
{
	Global $conn;
	$uid= isset($_GET['user']) ? (int) urldecode($_GET['user']): '';
	if($uid == '' || $uid == '2147483647' || $uid == '0') printMsg(MSG_NOT_CORRECT_USER_ID,2);   
	
	$result = isReg($uid); 
	if ($result == 'true'){
		GetMyDog($uid); 
	} elseif ($result == 'false'){
	    $msg = MSG_NO_USER_INFORMATION;
	    printMsg($msg,2);   
	} else {
		isBanned($result);  
	}    
}
/**
* Проверяет, зарегистрирован ли юзер с заданным $uid
* 
* @param int $uid
* @return int
*/
function isReg($uid)
{
	Global $conn;
	
	$query = 'SELECT `users`.*, `banned_users`.`days_to_unlock` AS `days`
				FROM `users` 
				LEFT JOIN `banned_users` ON `banned_users`.`user_id` = `users`.`id` 
				WHERE `users`.`VKuser`=' . (int)$uid;
	$res = mysql_query($query);
	$data = mysql_fetch_array($res);
	
	if(($data['days'] == 0 || $data['days'] == NULL) && $data['VKuser'] != NULL){
		$isregistered = 'true'; 
	} elseif($data['days'] > 0) {
		$isregistered = $data['days']; 
	} else {
		$isregistered = 'false'; 
	}
	
//	loger($query);
//	loger('registered=' . $isregistered);

	return $isregistered; 
}

/**
 * XML types:
 * 
 * 0 -  
 */

/**
* Проверяет, заблокирован ли юзер с заданным $uid
* 
* @param int $uid
* @return int
*/
function isBanned($days)
{
	$msg = MSG_USER_IS_BANNED.$days.MSG_USER_IS_BANNED_DAYS;
	printMsg($msg,3); 
/*	echo <<<MSG
<result type="3">
  <msg>{$msg}</msg>
</result>
MSG;
*/

}

/**
* Выдает информацию о собаке.
* При дальнейшей разработке сюда добавится все вещи, опыт....
* 
* @param int $uid
*/
function GetMyDog($uid)
{ 
  	Global $conn;
  
	$uid= isset($_GET['user']) ? (int) urldecode($_GET['user']): '';
	if($uid == '' || $uid == '2147483647' || $uid == '0') trigger_error(MSG_NOT_CORRECT_USER_ID);

	$addbonus = getBonus($uid); 

	$query = 'SELECT `users_dogs`.*, `dogs_breed`.`breed_name`, `dogs_breed`.`breed_id`, `users_fights`.`wins`, `users_balance`.`balance`
				  FROM `users_dogs`
				   INNER JOIN `dogs_breed` ON `dogs_breed`.`breed_id` = `users_dogs`.`dogbreed`   
				   INNER JOIN `users_balance` ON `users_balance`.`user_id` = `users_dogs`.`user_id`
				   LEFT JOIN `users_fights` ON `users_fights`.`user_id` = `users_dogs`.`user_id`
				  WHERE `users_dogs`.`user_id` = (SELECT `users`.`id` FROM `users` WHERE `users`.`VKuser` = '.(int)$uid.' ORDER by `users`.`id` LIMIT 1) 
			 ';
	
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_NO_USER_INFORMATION);
		
	$data = mysql_fetch_array($result);  
//	print_r($data);exit;	  
	
	$userID = $data['user_id'];
	if ($data['wins'] == '') $datawins = '';
	else $datawins = "<win>{$data['wins']}</win>";
//	if ($data['losses'] == '') $datalosses = '';
//	else $datalosses = "<lose>{$data['losses']}</lose>";

	$responce = '<result type="1">';
/*				
	$responce .= "<dog>
				   <name>{$data['DogName']}</name>
				   <breedID>{$data['breed_id']}</breedID>
				   <breed>{$data['breed_name']}</breed>
				   <level>{$data['DogLevel']}</level>
				   <nextlevel>{$data['next_level']}</nextlevel>
				   <exp>{$data['DogExp']}</exp>
				   <str>{$data['strength']}</str>
				   <dex>{$data['dexterity']}</dex>
				   <endu>{$data['endurance']}</endu>
				   <money>{$data['balance']}</money>
				   {$datawins}
				   {$datalosses}
			";
*/	
	$responce .= "<dog><name>{$data['DogName']}</name><breedID>{$data['breed_id']}</breedID><breed>{$data['breed_name']}</breed><level>{$data['DogLevel']}</level><nextlevel>{$data['next_level']}</nextlevel><exp>{$data['DogExp']}</exp><str>{$data['strength']}</str><dex>{$data['dexterity']}</dex><endu>{$data['endurance']}</endu><money>{$data['balance']}</money>{$datawins}";			

	
	$query = "SELECT `used_items`.*
				  FROM `used_items`
				  WHERE `used_items`.`user_id` = '{$userID}'
				  AND `used_items`.`slot` < 4
			 ";
	
	$result = mysql_query($query);
//	loger($query);
	if(mysql_error($conn)) trigger_error(MSG_NO_USER_INFORMATION);
	
	while ($data = mysql_fetch_array($result))
	{
		$dataClow = '';	
		$dataFang = '';
		$dataArmor = '';	
		switch ($data['slot']) {
			case 1:
				$dataClow = "<claw>{$data['item_id']}</claw>";
				break;
			case 2:
				$dataFang = "<fang>{$data['item_id']}</fang>";
				break;
			case 3:
				$dataArmor = "<armor>{$data['item_id']}</armor>";
				break;
			default:
				
		}
		
		$responce .= "{$dataClow}{$dataFang}{$dataArmor}";
	}

	$responce .= "</dog>";
	$responce .= "<bonus>{$addbonus}</bonus>";
	$responce .= "</result>";

//	echo $responce; 
	echo base64_encode($responce);
}

function getBonus($uid)
{
	Global $conn;
	$bonus = USERS_EVERYDAY_BONUS;
	
	$query = "SELECT `users_bonus`.`user_id`, IF(`users_bonus`.`date` = CURRENT_DATE(),1,0) as `issued`
				FROM `users_bonus`
				INNER JOIN `users` ON `users`.`id` = `users_bonus`.`user_id`
				WHERE `users`.`VKuser` = {$uid} ";
	$result = mysql_query($query,$conn);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_array($result);
	
	if ($data['user_id'] == '') 
	{
		$query = "INSERT INTO `users_bonus` 
					SET `user_id` = (SELECT `users`.`id` FROM `users` WHERE `users`.`VKuser` = {$uid} LIMIT 1), 
						`date` = NOW() ";
		mysql_query($query,$conn);
		if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	}
	
	if ($data['issued'] != 1)
	{
		$query1 = "UPDATE `users_bonus` 
					SET `date` = NOW() 
					WHERE `user_id` = (SELECT `users`.`id` FROM `users` WHERE `VKuser` = {$uid} LIMIT 1) ";
		
		$query2 = "UPDATE `users_balance` 
					SET `balance` = `balance`+{$bonus} 
					WHERE `user_id` = (SELECT `users`.`id` FROM `users` WHERE `VKuser` = {$uid} LIMIT 1) ";
		
		mysql_query($query1,$conn);
		mysql_query($query2,$conn);
		if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
		
		return 1;
	} else {
		return 0;
	}
}

function friendsRating()
{ 
  	Global $conn;
  	$friends = array();

	$friends = isset($_GET['friends']) ? urldecode($_GET['friends']): '';
	if(!$friends) trigger_error(MSG_NOT_CORRECT_USER_ID); 
	
	$mas = explode(",", $friends);

	foreach ($mas as $key => $val)
	{
   		$mas[$key] = intval($val);
	}
	
	$sqlWHERE = " WHERE `users`.`VKuser` = 1 ";
	for ($i=0; $i<count($mas); $i++)
	{
		$friendID = (int)$mas[$i];
		if ($friendID == '2147483647') continue;
		
		$sqlWHERE .= " OR `users`.`VKuser` = {$friendID} ";
	}	

	$query = "SELECT `users`.`VKuser`,`users_dogs`.`DogLevel` 
					FROM `users`
					 INNER JOIN `users_dogs` ON `users_dogs`.`user_id` = `users`.`id`   
				  	{$sqlWHERE}
				  	ORDER BY `users_dogs`.`DogLevel` DESC
			     ";
//	print_r($query); echo "<br>";
	$result = mysql_query($query);
//	loger($query);
	if(mysql_error($conn)) trigger_error(MSG_NO_USER_INFORMATION);
	
	$responce = '<result type="4">';
	
	while ($data = mysql_fetch_array($result))
	{ 
	/*				
		$responce .= "<friend>
					   <user>{$data['DogName']}</user>
					   <level>{$data['DogLevel']}</level>
					  </friend> 
				";
	*/	
		if ($data != '') $responce .= "<friend><user>{$data['VKuser']}</user><level>{$data['DogLevel']}</level></friend>";
	}
	
	$responce .= '</result>';

//	echo $responce; 
	echo base64_encode($responce);
}

//_________________COMMON______________________
function ErrorHandler($type,$err,$file,$line)
{

$response = <<<Error
<result error="1"><error><msg>{$err}</msg></error></result>
Error;
//loger("Error=$type $err at line $line");
//echo $response; 
echo base64_encode($response);
die();
}

function checkParams($sig, $auth_key)
{ 
	if ($sig == $auth_key) return true;
	else trigger_error(MSG_WRONG_CODE);
}

function printMsg($msg, $type)
{
	$responce = "<result type='{$type}'><msg>$msg</msg></result>";
//	echo $responce;
	echo base64_encode($responce);
	die();
}

function loger($msg)
{
//    Global $fp;
//    fputs($fp, $msg . "\n");
}

?>
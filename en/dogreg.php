<?php
//header('Content-Type: text/html; charset=cp1251');
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
mysql_select_db($db);
mysql_query('SET NAMES UTF8');
//mysql_set_charset('utf8', $conn);

$command = isset($_GET['do'])? urldecode($_GET['do']) : '';
if(!function_exists($command)){
   trigger_error(TXT_METHOD . $command . TXT_NOT_SUPPORTED);
}

$uid= isset($_GET['user']) ? (int) urldecode($_GET['user']): '';
$sig= isset($_GET['sig']) ? urldecode($_GET['sig']): '';
$api_secret = TXT_API_SECRET;
$auth_key = md5($uid.'_'.$api_secret);
//checkParams($sig, $auth_key); 

call_user_func($command); 
//fclose($fp);

/*__________________________
   Functions
   
__________________________*/
function GetDog ()
{
	Global $conn;
	$uid= isset($_GET['user']) ? (int) urldecode($_GET['user']): '';
	if($uid == '' || $uid == '2147483647') trigger_error(MSG_NOT_CORRECT_USER_ID);    
	
	$result = isReg($uid); 
	if ($result == 'true'){
		GetMyDog($uid); 
	} elseif ($result == 'false'){
	    DogPresentation();  
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
//	die("reg=$isregistered<br>SELECT COUNT(*) FROM `users_dogs` WHERE `VKuser`=$uid");
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
//	echo '<result type="3">{$msg}</result>'; 
	echo <<<MSG
<result type="3">
  <msg>{$msg}</msg>
</result>
MSG;

}
/**
* Список всех пород собак с начальными параметрами
* 
*/
function DogPresentation()
{
  Global $conn;
  $responce = '<result type="0">';
  $res = mysql_query('SELECT * FROM `dogs_breed`');
  while($row = mysql_fetch_assoc($res)){
  $responce .= <<<DOGS
<dog><breedID>{$row['breed_id']}</breedID><breed>{$row['breed_name']}</breed><str>{$row['strength']}</str><dex>{$row['dexterity']}</dex><endu>{$row['endurance']}</endu></dog>
DOGS;
  }
$responce .= '</result>';
//echo $responce; 
echo base64_encode($responce); 
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

  if($uid == '' || $uid == '2147483647') trigger_error(MSG_NOT_CORRECT_USER_ID); 

  $query = 'SELECT `users_dogs`.*, `dogs_breed`.*, `users_dogs`.`id` AS dogid 
			  FROM `users_dogs`  
			   INNER JOIN `dogs_breed` ON `dogs_breed`.`breed_id` = `users_dogs`.`dogbreed`  
			   WHERE `users_dogs`.`user_id` = (SELECT `users`.`id` FROM `users` WHERE `users`.`VKuser` = '.(int)$uid.' LIMIT 1 ) ';

  $res = mysql_query($query);
//  loger($query);
  if(!$res) trigger_error(MSG_NO_USER_INFORMATION);
  
  $dog = mysql_fetch_assoc($res);
 // loger($dog);
  if(!$dog ) trigger_error(MSG_NO_OWNER_INFORMATION); 
/*  
  $response = <<<Dog
  <result type="1">
   <dog>
     <name>{$dog['DogName']}</name>
       <dogID>{$dog['dogid']}</dogID>
       <breed>{$dog['breed_name']}</breed>
       <level>{$dog['DogLevel']}</level>     
     <str>{$dog['strength']}</str>  
     <dex>{$dog['dexterity']}</dex>
     <endu>{$dog['endurance']}</endu> 
   </dog>
  </result>
Dog;


$response = <<<Dog
<result type="1"><dog><name>{$dog['DogName']}</name><dogID>{$dog['dogid']}</dogID><breed>{$dog['breed_name']}</breed><level>{$dog['DogLevel']}</level><exp>{$dog['DogExp']}</exp><str>{$dog['strength']}</str><dex>{$dog['dexterity']}</dex><endu>{$dog['endurance']}</endu></dog></result>
Dog;
*/

  $response = "<result type=\"1\"><dog><id>{$uid}</id><name>{$dog['DogName']}</name><breedID>{$dog['breed_id']}</breedID><breed>{$dog['breed_name']}</breed><level>{$dog['DogLevel']}</level><str>{$dog['strength']}</str><dex>{$dog['dexterity']}</dex><endu>{$dog['endurance']}</endu></dog></result>";
  echo base64_encode($response);
}
/**
* Регистрация пользователя и его собаки
* 
*/  
function userReg()
{
	Global $conn;
	
	$uid = isset($_GET['user']) ? (int) urldecode($_GET['user']): ''; 
	if($uid == '' || $uid == '2147483647') trigger_error(MSG_NOT_CORRECT_USER_ID); 

	$inviter = isset($_GET['invite']) ? (int) urldecode($_GET['invite']): ''; 
	if($inviter == '' || $inviter == '2147483647' || $inviter == 0) $inviter == '';

	chkUser($uid);	
	
	$dogBreedID = isset($_GET['breed']) ? (int) urldecode($_GET['breed']): '';
	if(!$dogBreedID) trigger_error(MSG_DOG_ID_WRONG_TYPE);
	
	$dogName = isset($_GET['name']) ?  rawurldecode($_GET['name']): '';
	if(!$dogName) trigger_error(MSG_NO_DOG_NAME);

	$dogName = iconv("CP1251", "UTF-8", $_GET['name']); 
	
	$dogLevel = DOG_LEVEL;
	$userFirstBalance = USR_FIRST_BALANCE;
	
	/*ADD New USER*/
	$query = "INSERT INTO `users` SET `VKuser` = {$uid} ";
	
	mysql_query($query, $conn);
	if(mysql_error($conn)) trigger_error(ERR_DOG_NOT_CREATED);
	
	/*ADD New User's balance information*/
	$query = "INSERT INTO `users_balance` SET `user_id` = (SELECT `users`.`id` FROM `users` WHERE VKuser = '{$uid}' LIMIT 1), `balance` = '".$userFirstBalance."' ";

	mysql_query($query, $conn);
	if(mysql_error($conn)) trigger_error(ERR_DOG_NOT_CREATED);
	
	
	$query = 'SELECT * FROM `dogs_breed` WHERE `breed_id` = '.$dogBreedID;
	$result = mysql_query($query, $conn);
	if(mysql_error($conn)) trigger_error(ERR_DOG_NOT_CREATED);
	else $data = mysql_fetch_array($result);
		
	/*ADD New User's DOG*/
	$query = "INSERT INTO `users_dogs` SET `user_id` = (SELECT `users`.`id` FROM `users` WHERE VKuser = '{$uid}' LIMIT 1), DogBreed = {$dogBreedID}, DogName=\"". mysql_escape_string($dogName)."\", strength = {$data['strength']}, dexterity = {$data['dexterity']}, endurance = {$data['endurance']} ";
	
	mysql_query($query, $conn);
	if(mysql_error($conn)) trigger_error(ERR_DOG_NOT_CREATED);

	if (isset($inviter) && $inviter != '')
	{
		$bonus = INVITER_BONUS;
		$query = "UPDATE `users_balance` SET `balance` = `balance`+{$bonus} WHERE `user_id` = (SELECT `users`.`id` FROM `users` WHERE `VKuser` = '{$inviter}' LIMIT 1) ";
		mysql_query($query, $conn);
		if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	}

	GetMyDog($uid); 
                     
}

function chkUser($uid)
{
	Global $conn;
	
	$query = "SELECT `users`.`id` FROM `users` WHERE `users`.`VKuser`= '{$uid}' ORDER BY `users`.`id` LIMIT 1 ";
	$res = mysql_query($query);
	$data = mysql_fetch_array($res);
	
	if($data['id'] != ''){
		GetMyDog($uid); 
		exit;
	}
	return true; 	
}

/**
* put your comment here...
* 
*/
function fight()
{
	$msg = TXT_BEFORE_FIGHT;
 	echo "<arena>{$msg}</arena>"; 
}  


//_________________COMMON______________________
function ErrorHandler($type,$err,$file,$line)
{
$response = <<<Error
<result error="1"><error><type>{$type}</type><msg>{$err}</msg><line>{$line}</line></error></result>
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

function loger($msg)
{
 //   Global $fp;
 //   fputs($fp, $msg . "\n");
}

?>
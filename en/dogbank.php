<?php
header('Content-Type: text/html; charset=utf-8');
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
set_exception_handler('MyErrorHandler');
set_error_handler('MyErrorHandler');

function MyErrorHandler($errno, $errmsg, $filename, $linenum) {     
	if (!in_array($errno, Array(E_NOTICE, E_STRICT, E_WARNING))) {             
		$date = date('Y-m-d H:i:s (T)');             
		$f = fopen('errors.log', 'a');                 
		if (!empty($f)) {                     
			//$err  = "\r\n";             
			$err .= $date.time."  ";   
			$err .= "dogBank: ";   
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

//trigger_error('start');
// FB
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
$auth_key = md5($uid.'_'.$api_secret);
//checkParams($sig, $auth_key); 

//GetDog();
call_user_func($command); 
//fclose($fp);

/*__________________________
   Functions
   
__________________________*/

function getP()
{
	//phpinfo();
	$tz = date_default_timezone_get();
    print_r($tz);
	
	$time = time()+10800;
	print_r($time);
}

/**
* Получить информацию о состоянии личного счёта в приложении
* 
* @param int $uid
* 
*/
function getUserBalance()
{
	Global $conn;
	$request = '';
	
	$uid = isset($_GET['user']) ? (int) urldecode($_GET['user']): ''; 
	if(!$uid) trigger_error(MSG_USER_ID_WRONG_TYPE);
//	loger('uid=' . $uid);
	
	/*Get user's Application Balance*/
	$userAppBalnceXML = getUserAppBalance($uid);
	preg_match('/<error_code>([0-9]{1,5})<\/error_code>/is', $userAppBalnceXML, $err);
	if (count($err) != 0) printMsg("Error: {$err['1']} when we get user app balance!!",3);
	
	preg_match('/<balance>([0-9]{1,10})<\/balance>/is', $userAppBalnceXML, $mas);
//	$userMaxCoins = $mas['1'];
	$userGameVotes = $mas['1']/100;
	
//	$responce = "<result type='1'><votes>{$userGameVotes}</votes><coins>{$userMaxCoins}</coins></result>";
	$responce = "<result type='1'><votes>{$userGameVotes}</votes></result>";
	
//	echo $responce;
	echo base64_encode($responce);
}


/**
* Проверяет, зарегистрирован ли юзер с заданным $uid
* 
* @param int $uid
* @return int
*/
function addUserBalance()
{
	Global $conn;
	$request = '';
	
	$uid = isset($_GET['user']) ? (int) urldecode($_GET['user']): ''; 
	if(!$uid) trigger_error(MSG_USER_ID_WRONG_TYPE);
//	loger('uid=' . $uid);
	
	$votes = isset($_GET['votes']) ? (int) urldecode($_GET['votes']): ''; 
	if(!$votes || $votes == 0) printMsg("Wrong votes number!",3);
//	loger('addBalance=' . $votes);
	
	/*Get Application Balance*/
	$userAddedBalnceXML = withdrawVotes($uid, $votes);

	preg_match('/<error_code>([0-9]{1,5})<\/error_code>/is', $userAddedBalnceXML, $err);
	if (count($err) != 0) printMsg("Error:{$err['1']} when we add user app balance!!",3);
	
	preg_match('/<transferred>([0-9]{1,10})<\/transferred>/is', $userAddedBalnceXML, $mas);
	$userAddedBalnce = $mas['1'] * 2;
		
	/*GET user's balance*/
	$query = "SELECT `balance`, `user_id`
				FROM `users_balance` 
				WHERE `user_id` = (SELECT `id` FROM `users` WHERE `user` = '{$uid}' ORDER BY `id` LIMIT 1)";
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_assoc($result);

	if ($data['balance'] == '') printMsg("Wrong User Information!", 2); 
	
	$userID = $data['user_id'];
	$curBalance = $data['balance'];
	$newBalance = $curBalance + $userAddedBalnce;
	
	/*UPDATE user's balance*/
	$query = "UPDATE `users_balance` 
				SET `users_balance`.`balance` = '{$newBalance}',
					`users_balance`.`last_update` = NOW(), 
					`users_balance`.`last_sum` = '{$userAddedBalnce}'
				WHERE `user_id` = '{$userID}' ";

	mysql_query($query, $conn);
	if(mysql_error($conn)) trigger_error("При покупке произошла ошибка! Попробуйте ещё раз.");
	
	$msg = 'Голоса успешно обменены!';
	printMsg($msg,2);
	
}

function getAppBalance()
{
	/*
	В данном случае sig равен md5("api_id=4method=secure.getAppBalancerandom=83962759timestamp=1238714241v=2.0api_secret")
	
	$host = 'http://api.facebook.ru/api.php';
	$api_id = '1748039';
	$method = 'secure.getAppBalance';
	$random = mt_rand(); 
	$timestamp = time();	//Unixtime сервера разработчика
	$v = '2.0';             //Версия API (текущее значение 2.0)
	$api_secret;

	Пример запроса:
    http://api.vkontakte.ru/api.php?api_id=4&v=2.0&method=secure.getAppBalance&timestamp=1238714241&random=83962759&sig=7598d64720bb39544679f2ca256fa538
	*/
	
	$host = 'http://api.facebook.ru/api.php';
	$api_id = '1748039';
	$method = 'secure.getAppBalance';
	$random = rand(); 
	$timestamp = time()+10800;
//	$timestamp = $_SERVER['REQUEST_TIME'];
//	$timestamp = strtotime('now');
	$v = '2.0';
	$api_secret = TXT_SECURE_CODE;
//	$sigstr = "api_id=".$api_id."method=".$method."random=".$random."timestamp=".$timestamp."v=".$v.$api_secret;
	$sig = md5("api_id=".$api_id."method=".$method."random=".$random."timestamp=".$timestamp."v=".$v.$api_secret);
	
	$request = $host."?";
	$request .= "api_id=".$api_id;
	$request .= "&v=".$v;
	$request .= "&method=".$method;
	$request .= "&timestamp=".$timestamp;
	$request .= "&random=".$random;
	$request .= "&sig=".$sig;
	
	$answer = file_get_contents($request);
	echo $answer;
}

function getUserAppBalance($uid)
{
	if ($uid == '' || $uid == '2147483647') printMsg("Wrong user ID",3);
	/*
	secure.getBalance - Возвращает баланс пользователя на счету приложения в сотых долях голоса. 
	
	api_id - идентификатор приложения, присваивается при создании.
	sig - подпись запроса по безопасной схеме.
	v - версия API, текущая версия равна 2.0.
	timestamp - UNIX-time сервера.
	random - любое случайное число для обеспечения уникальности запроса
	uid - ID пользователя.
	format - XML (необязательный параметр)
	
	sig = md5(name1=value1name2=value2api_secret)
	В данном случае sig равен md5("api_id=4method=secure.getBalancerandom=83962759timestamp=1238714241v=2.0uid=1571177api_secret")

	Пример запроса:
    http://api.facebook.com/api.php?api_id=4&v=2.0&method=secure.getAppBalance&timestamp=1238714241&random=83962759&uid=1571177&sig=7598d64720bb39544679f2ca256fa538
	*/
	
	$host = 'http://api.vkontakte.ru/api.php';
	$api_id = '1748039';
	$method = 'secure.getBalance';
	$random = rand(); 
	$timestamp = time()+10800;
	$v = '2.0';
	$api_secret = TXT_SECURE_CODE;
//	$uid = '1571177'; // ID Сергея 
	
//	$sigstr = "api_id=".$api_id."method=".$method."random=".$random."timestamp=".$timestamp."uid=".$uid."v=".$v.$api_secret;
	$sig = md5("api_id=".$api_id."method=".$method."random=".$random."timestamp=".$timestamp."uid=".$uid."v=".$v.$api_secret);
	
	$request = $host."?";
	$request .= "api_id=".$api_id;
	$request .= "&v=".$v;
	$request .= "&method=".$method;
	$request .= "&timestamp=".$timestamp;
	$request .= "&random=".$random;
	$request .= "&uid=".$uid;
	$request .= "&sig=".$sig;
//print_r($request);	
	$answer = file_get_contents($request);
	return $answer;
}

function withdrawVotes($uid, $votes)
{
	if ($uid == '' || $uid == '2147483647') printMsg("Wrong user ID",3);
	/*
	secure.withdrawVotes - Списывает голоса со счета пользователя на счет приложения (в сотых долях).  
	
	api_id - идентификатор приложения, присваивается при создании.
	sig - подпись запроса по безопасной схеме.
	v - версия API, текущая версия равна 2.0.
	timestamp - UNIX-time сервера.
	random - любое случайное число для обеспечения уникальности запроса
	uid - ID пользователя.
	votes - количество списываемых с пользователя голосов (в сотых долях). 
	format - XML (необязательный параметр)
	
	sig = md5(name1=value1name2=value2api_secret)
	В данном случае sig равен md5("api_id=4method=secure.getBalancerandom=83962759timestamp=1238714241v=2.0uid=1571177api_secret")

	Пример запроса:
    http://api.facebook.com/api.php?api_id=4&v=2.0&method=secure.getAppBalance&timestamp=1238714241&random=83962759&uid=1571177&sig=7598d64720bb39544679f2ca256fa538
	*/
	
	$host = 'http://api.facebook.com/api.php';
	$api_id = '1748039';
	$method = 'secure.withdrawVotes';
	$random = rand(); 
	$timestamp = time()+10800;
	$v = '2.0';
	$api_secret = TXT_SECURE_CODE;
	$VKvotes = $votes * 100;
	
//	$sigstr = "api_id=".$api_id."method=".$method."random=".$random."timestamp=".$timestamp."uid=".$uid."v=".$v.$api_secret;
	$sig = md5("api_id=".$api_id."method=".$method."random=".$random."timestamp=".$timestamp."uid=".$uid."v=".$v."votes=".$VKvotes.$api_secret);
	
	$request = $host."?";
	$request .= "api_id=".$api_id;
	$request .= "&v=".$v;
	$request .= "&method=".$method;
	$request .= "&timestamp=".$timestamp;
	$request .= "&random=".$random;
	$request .= "&uid=".$uid;
	$request .= "&votes=".$VKvotes;
	$request .= "&sig=".$sig;
	
	$answer = file_get_contents($request);
	return $answer;
}

function getServerTime ()
{
/*
	api_id - ������������� ����������, ������������� ��� ��������.
	sig - ������� ������� �� ����������� �����.
	v - ������ API, ������� ������ ����� 2.0.
	format - ������ ������������ ������ � XML ��� JSON. �� ��������� XML. 
	
	� ������ ������ sig ����� md5("6492api_id=4method=getFriendsv=2.0secret")
*/
	$host = 'http://api.facebook.com/api.php';
	$api_id = '1748039';
	$method = 'getServerTime';
	$format = 'XML';
	$v = '2.0';
	$api_secret = TXT_API_SECRET;
	$uid = '1571177';
	$test_mode = '0';
	
//	$sigstr =  "api_id=".$api_id."method=".$method."v=".$v.$api_secret;
//print_r($sigstr); echo "<br>";

	$sig = md5($uid."api_id=".$api_id."method=".$method."v=".$v.$api_secret);
		
	$request = $host."?";
	$request .= "api_id=".$api_id;
	$request .= "&sig=".$sig;
	$request .= "&v=".$v;
	$request .= "&format=".$format;
	$request .= "&test_mode=".$test_mode;

	$answer = file_get_contents($request);
	print_r($answer);
	
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

function printMsg($msg, $type)
{
	$responce = "<result type='{$type}'><msg>$msg</msg></result>";
//	echo $responce;
    echo base64_encode($responce);
	die();
}

function checkParams($sig, $auth_key)
{ 
	if ($sig == $auth_key) return true;
	else trigger_error(MSG_WRONG_CODE);
}

function loger($msg)
{
//  Global $fp;
//  fputs($fp, $msg . "\n");
}

?>
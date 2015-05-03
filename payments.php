<?php
/*________________
    INCLUDE          
________________*/
require_once("db_settings.php");
require_once("settings.php");

require 'server/fb-php-sdk/facebook.php';

$config = array(
	'appId' => API_ID,
	'secret' => API_SECRET,
	'fileUpload' => false,
	'allowSignedRequest' => false,
);

$facebook = new Facebook($config);
$access_token = $facebook->getAccessToken();


$host = DB_HOST;
$user = DB_LOGIN;
$pass = DB_PWD;
$db = DB_NAME;
set_exception_handler('MyErrorHandler');
set_error_handler('MyErrorHandler');


$conn = @mysql_connect($host, $user, $pass);
if(!$conn) trigger_error(MSG_DB_NOT_CONNECT);
mysql_select_db($db);
mysql_query('SET NAMES UTF8');


function MyErrorHandler($errno, $errmsg, $filename, $linenum) {     
	if (!in_array($errno, Array(E_NOTICE, E_STRICT, E_WARNING))) {             
		$date = date('Y-m-d H:i:s (T)');             
		$f = fopen('errors.log', 'a');                 
		if (!empty($f)) {                     
			$err  = "\r\n";             
			$err .= $date.time."  ";   
			$err .= "payments: ";   
			$err .= "  $errno\r\n";             
			$err .= "$errmsg\r\n";             
			$err .= "  $filename\r\n";             
			$err .= "  $linenum\r\n";             
			$err .= "\r\n";             
			fwrite($f, $err);            
			fclose($f);    
			//echo $err;                                
		}             
	}
}



function payment($response) {
	//echo $response;
	$user = $response['user']['id'];
	$query = "SELECT `users`.`id` FROM `users` WHERE `users`.`VKuser`= {$user} LIMIT 1";

	$res = mysql_query($query);
	$uid = mysql_fetch_assoc($res);
	$uid = $uid['id'];

	$count = $response['items'][0]['quantity'];
	$test = isset($response['test']) ? $response['test'] : 0;
	$status = ($response['actions'][0]['status'] == 'completed') ? 1 : 0;

	$query = "INSERT INTO `users` SET `VKuser` = {$uid} ";
	
	$query = "INSERT INTO `payments` SET `id_fb` = {$uid}, `count` = {$count}, `test` = {$test}, `completed` = {$status}";
	mysql_query($query);

	if($status == 1){
		$money = $count * 200;
		$query = "UPDATE `users_balance` SET `balance` = `balance`+{$money} WHERE `user_id` = {$uid}";
		mysql_query($query);
	}


	$response = print_r($response, true);
	trigger_error($response);
}

$verify_token = VERIFY_TOKEN;

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['hub_mode'])
  && $_GET['hub_mode'] == 'subscribe' && isset($_GET['hub_verify_token'])
  && $_GET['hub_verify_token'] == $verify_token) {
	echo $_GET['hub_challenge'];
} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$post_body = file_get_contents('php://input');
	$obj = json_decode($post_body, true);
	$response = $facebook->api("/".$obj['entry'][0]['id']."?access_token=".$access_token);
	payment($response);
}

?>
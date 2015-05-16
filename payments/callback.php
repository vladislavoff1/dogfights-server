<?php

// Skip these two lines if you're using Composer
define('FACEBOOK_SDK_V4_SRC_DIR', 'facebook-php-sdk/src/Facebook/');
require 'facebook-php-sdk/autoload.php';
require_once("../db_settings.php");

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\GraphObject;
use Facebook\FacebookRequestException;

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

FacebookSession::setDefaultApplication(
    APP_ID,
    APP_SECRET);


$verify_token = VERIFY_TOKEN;
$app_token = APP_TOKEN;


// Use one of the helper classes to get a FacebookSession object.
//   FacebookRedirectLoginHelper
//   FacebookCanvasLoginHelper
//   FacebookJavaScriptLoginHelper
// or create a FacebookSession with a valid access token:
$session = new FacebookSession($app_token);

// Get the GraphUser object for the current user:

$method = $_SERVER['REQUEST_METHOD'];
//stdout('RTU');
if ($method == 'GET' && $_GET['hub_verify_token'] === $verify_token) {
  echo $_GET['hub_challenge'];
} else {
    $data = file_get_contents("php://input");
    $json = json_decode($data, true);

    if( $json["object"] && $json["object"] == "payments" ) {
        $payment_id = $json["entry"][0]["id"];
        try {
            $result = (new FacebookRequest(
                $session, 'GET', '/' . $payment_id . '?fields=user,actions,items'
            ))->execute()->getGraphObject(GraphObject::className());
            $actions = $result->getPropertyAsArray('actions');
            if( $actions[0]->getProperty('status') == 'completed' ){

                $user = $result->getProperty('user');
                $items = $result->getPropertyAsArray('items');
                $product = $items[0]->getProperty('product');
                $quantity = $items[0]->getProperty('quantity');
                $recipient = $user->getProperty('id');

                $query = "SELECT `users`.`id` FROM `users` WHERE `users`.`VKuser`= {$recipient} LIMIT 1";

                $res = mysql_query($query);
                $uid = mysql_fetch_assoc($res);
                $uid = $uid['id'];

                $test = $user->getProperty('test');

                $query = "INSERT INTO `payments` SET `id_fb` = {$uid}, `count` = {$quantity}, `test` = {$test}, `completed` = 1";
                mysql_query($query);
                
                $money = $quantity * 200;
                $query = "UPDATE `users_balance` SET `balance` = `balance`+{$money} WHERE `user_id` = {$uid}";
                mysql_query($query);

                $response = print_r($response, true);
            trigger_error('success payment id (test ' + $test + '): '.$result->getProperty('id'));
                        }
        } catch (FacebookRequestException $e) {
          // The Graph API returned an error
            trigger_error($e->getRawResponse());
        } catch (\Exception $e) {
            trigger_error($e);
        }
    }
}
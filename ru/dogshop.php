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
call_user_func($command); 
//fclose($fp);

/*
$uid= isset($_GET['user']) ? (int) urldecode($_GET['user']): '';
$sig= isset($_GET['sig']) ? urldecode($_GET['sig']): '';
$api_secret = TXT_API_SECRET;
$auth_key = md5($uid.'_'.$api_secret);
//checkParams($sig, $auth_key); 
*/

/*__________________________
   Functions
   
__________________________*/
/**
 * Выводит информацию о каждом из предметов в магазине
 * 
 * @param int $uid
 * @param int $type
 * type=1 -> clows
 * type=2 -> fangs
 * type=3 -> armor 
 * type=4 -> steroids
 */

function getItems ()
{
	Global $conn;
	$xmlFile = 'dogShop.xml';
	
	if (file_exists($xmlFile)) { 
	    echo base64_encode(file_get_contents($xmlFile));
	} else {
		
		$responce = '<result type="1">';
	
		$query = "SELECT *
					FROM `items`
					ORDER BY `type`, `id` 
				 ";
		
	//	loger($query);
		$result = mysql_query($query, $conn);
		if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
		
		while ($row = mysql_fetch_array($result))
		{
			if ($row['damage'] == 0) $rowdamage = '';
			else $rowdamage = "<damage>{$row['damage']}</damage>";
			if ($row['protect'] == 0) $rowprotect = '';
			else $rowprotect = "<protect>{$row['protect']}</protect>";
			if ($row['duration'] == 0) $rowduration = '';
			else $rowduration = "<duration>{$row['duration']}</duration>";
			
			$responce .= "<item><itemID>{$row['id']}</itemID><name>{$row['name']}</name><desc>{$row['description']}</desc>{$rowdamage}{$rowprotect}{$rowduration}<rate>{$row['rate']}</rate><level>{$row['from_level']}</level><price>{$row['price']}</price><type>{$row['type']}</type></item>";
		}
	
		$responce .= '</result>';
		
	
		
		$fxml = fopen($xmlFile,'w+');
		fputs($fxml, $responce);
		fclose($fxml);
		
		if (file_exists($xmlFile)) {
		    echo base64_encode(file_get_contents($xmlFile));
		} 	
	}
//	echo $responce;
//	echo base64_encode($responce);
}

/**
* Покупка предмета в магазине
* 
* @param int $uid
* @param int $item_id
* @param int $amount
*/
function buyItem()
{
	Global $conn;
	
	$uid = isset($_GET['user']) ? (int) urldecode($_GET['user']): ''; 
	if($uid == '' || $uid == '2147483647' || $uid == '0') printMsg(MSG_NOT_CORRECT_USER_ID,3);
	
	$item_id = isset($_GET['item']) ? (int) urldecode($_GET['item']): ''; 
	if(!$item_id) trigger_error(MSG_USER_ID_WRONG_TYPE);
	
	$amount = isset($_GET['amount']) ? (int) urldecode($_GET['amount']): ''; 
	if($amount == '' || $amount == '2147483647' || $amount <= '0' || $amount > '30') trigger_error(MSG_WRONG_AMOUNT);
	
	$query = 'SELECT `items`.`type`, `items`.`duration`, `items`.`price`
				FROM `items` 
				WHERE `items`.`id` = ' . (int)$item_id .'
			 ';
	
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_assoc($result);
	
	if ($data == '') trigger_error(MSG_WRONG_BUY_ITEM);
//	if ($data['type'] == 4) $setExpire = ", `expire` = '".$data['duration']."' ";
//	else $setExpire = '';
	$itemPrice = $data['price'];
	$itemCost = $amount * $itemPrice;
	
	/*CHECK user's balance for available coins*/
	$query = "SELECT `balance`, `user_id` FROM `users_balance` WHERE `user_id` = (SELECT `users`.`id` FROM `users` WHERE `VKuser` = '{$uid}' ORDER by `users`.`id` LIMIT 1)";
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_assoc($result);

	if (isset($data['balance']) && $data['balance'] != '' && $data['balance'] < $itemCost)
	{ 
		$msg = MSG_NOT_ENOUGH_MONEY;
		printMsg($msg,4);
	}
	elseif ($data['balance'] != '') $newBalance = $data['balance'] - $itemCost;

	$userID = $data['user_id'];
	
	/*SEARCH same items that we bought earlier*/
	$query = "SELECT * 
				FROM `purchased_items` 
				WHERE `purchased_items`.`item_id` = '". (int)$item_id ."'   
				AND `purchased_items`.`user_id` = '{$userID}' ";
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_assoc($result);

	/*ADD NEW or UPDATE purchased_item*/
	if (isset($data['id'])) {
		$item_amount = $data['amount'] + $amount;
		$query = "UPDATE `purchased_items` SET `amount` = '{$item_amount}', `slot`='0' WHERE `user_id` = '{$userID}' AND `purchased_items`.`id` = " . (int)$data['id'];
	} else {
		checkFreeSlots($userID);  
		$query = "INSERT INTO `purchased_items` SET `user_id` = '{$userID}', `item_id` = '{$item_id}', `amount` = '{$amount}', `slot`='0' ";
	}
	
	mysql_query($query, $conn);
	if(mysql_error($conn)) trigger_error(MSG_BUY_ITEM_ERR);

	/*UPDATE user's balance after purchase*/
	$query = "UPDATE `users_balance` SET `balance` = '{$newBalance}' WHERE `user_id` = '{$userID}' ";
	
	mysql_query($query, $conn);
	if(mysql_error($conn)) trigger_error(MSG_BUY_ITEM_ERR);

  	$msg = MSG_BUY_ITEM_OK;
	$responce = <<<MSG
<result type="2">
  <msg>{$msg}</msg>
</result>
MSG;
//	echo $responce;
	echo base64_encode($responce);
 
}

/**
 * GET free slot for items
*/
function checkFreeSlots($uid)
{
	Global $conn;
	
	$query = "SELECT COUNT(*)
				FROM `purchased_items`
				WHERE `purchased_items`.`user_id` = '{$uid}' 
				AND `purchased_items`.`amount` <> 0
				AND `purchased_items`.`slot` = 0
				ORDER by `purchased_items`.`id`
			 ";
	
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_array($result);

	if ($data[0] >= '6') {
		printMsg(MSG_NO_ITEM_SLOT,4);
	} else return true;
}

//_________________COMMON______________________
/*function ErrorHandler($type,$err,$file,$line)
{
$response = <<<Error
<result error="1"><error><type>{$type}</type><msg>{$err}</msg><line>{$line}</line></error></result>
Error;
//loger("Error=$type $err at line $line");
//echo $response;
echo base64_encode($response);
die();
}*/

function ErrorHandler($errno, $errmsg, $filename, $linenum) {     
	if (!in_array($errno, Array(E_NOTICE, E_STRICT, E_WARNING))) {             
		$date = date('Y-m-d H:i:s (T)');             
		$f = fopen('errors.log', 'a');                 
		if (!empty($f)) {                     
			$err  = "\r\n";             
			$err .= "  $date\r\n";             
			$err .= "  $errno\r\n";             
			$err .= "  $errmsg\r\n";             
			$err .= "  $filename\r\n";             
			$err .= "  $linenum\r\n";             
			$err .= "\r\n";             
			fwrite($f, $err);            
			fclose($f);    
			echo $err;                    
		}             
	} 
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
 //   Global $fp;
  //  fputs($fp, $msg . "\n");
}

?>
<?php

function microtime_float() 
{ 
    list($usec, $sec) = explode(" ", microtime()); 
    return ((float)$usec + (float)$sec); 
} 
$time_start = microtime_float();
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
set_exception_handler('MyErrorHandler');
set_error_handler('MyErrorHandler');

function MyErrorHandler($errno, $errmsg, $filename, $linenum) {     
	if (!in_array($errno, Array(E_NOTICE, E_STRICT, E_WARNING))) {             
		$date = date('Y-m-d H:i:s (T)');             
		$f = fopen('errors.log', 'a');                 
		if (!empty($f)) {                     
			//$err  = "\r\n";             
			$err .= $date.time."  ";   
			$err .= "dogHome: ";   
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
 */


function getInfo ()
{
	Global $conn, $time_start;
	
	
	
	$uid= isset($_GET['user']) ? (int) urldecode($_GET['user']): '';
	if($uid == '' || $uid == '2147483647' || $uid == '0') trigger_error(MSG_NOT_CORRECT_USER_ID);
	
	/*GET item information*/
	$query = "SELECT `users`.`id`
				FROM `users` 
				WHERE `users`.`VKuser` = '{$uid}'
				ORDER by `users`.`id` 
				LIMIT 1
			 ";

	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$udata = mysql_fetch_assoc($result);
	
	$userID = $udata['id'];
	
	$query = "SELECT `purchased_items`.*, `items`.*, `items`.`id` AS itemID
				  FROM `purchased_items`
				   INNER JOIN `items` ON `items`.`id` = `purchased_items`.`item_id`
				  WHERE `purchased_items`.`user_id` = '{$userID}'
				  ORDER by `purchased_items`.`id`
			 ";
	
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_NO_USER_INFORMATION);
	
	$responce = '<result type="1">';
	$responce .= "<nonactive>";
	while ($data = mysql_fetch_array($result))
	{
		$data['price'] = $data['price']/2;
		
		if ($data['item_id'] != '')	{
		/*	
			$responce .= "<item> 
						   <itemID>{$data['itemID']}</itemID>
						   <name>{$data['name']}</name>
						   <desc>{$data['description']}</desc>
						   <rate>{$data['rate']}</rate>
						   <level>{$data['from_level']}</level>
						   <price>{$data['price']}</price>
						   <amount>{$data['amount']}</amount>
						   <slot>{$data['slot']}</slot>
						   <type>{$data['type']}</type>
						  </item> 
					  ";
		*/
			$responce .= "<item><itemID>{$data['itemID']}</itemID><name>{$data['name']}</name><desc>{$data['description']}</desc><rate>{$data['rate']}</rate><level>{$data['from_level']}</level><price>{$data['price']}</price><amount>{$data['amount']}</amount><slot>{$data['slot']}</slot><type>{$data['type']}</type></item>";			  
		}
	}

	$responce .= '</nonactive>';
	
	$query = "SELECT `used_items`.*, `items`.*, `items`.`id` AS itemID
				  FROM `used_items`
				   INNER JOIN `items` ON `items`.`id` = `used_items`.`item_id`
				  WHERE `used_items`.`user_id` = '{$userID}' 
			 ";
	
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_NO_USER_INFORMATION);
	
	$responce .= "<active>";
	while ($data = mysql_fetch_array($result))
	{
		$data['price'] = $data['price']/2;
		
		if ($data['item_id'] != '')	{
		/*	
			$responce .= "<item> 
						   <itemID>{$data['itemID']}</itemID>
						   <name>{$data['name']}</name>
						   <desc>{$data['description']}</desc>
						   <rate>{$data['rate']}</rate>
						   <level>{$data['from_level']}</level>
						   <price>{$data['price']}</price>
						   <amount>{$data['amount']}</amount>
						   <slot>{$data['slot']}</slot>
						   <type>{$data['type']}</type>
						   <expire>{$data['expire']}</expire>
						  </item> 
					  ";
		*/
			$responce .= "<item><itemID>{$data['itemID']}</itemID><name>{$data['name']}</name><desc>{$data['description']}</desc><rate>{$data['rate']}</rate><level>{$data['from_level']}</level><price>{$data['price']}</price><amount>{$data['amount']}</amount><slot>{$data['slot']}</slot><type>{$data['type']}</type><expire>{$data['expire']}</expire></item>";			  
		}
	}

//	$responce .= '</active></result>';
	
	$time = microtime_float()-$time_start; 
	$responce .= '</active>';
	$responce .= "<time>$time</time></result>";

//	echo $responce; 
	echo base64_encode($responce);
}

/**
* Передвижение предметов в ячейки
* 
* @param int $uid
* @param int $item_id
* @param int $amount
* @param int $slot
* @param int $from
*/
function moveItem()
{
	Global $conn;
	
	$uid = isset($_GET['user']) ? (int) urldecode($_GET['user']): ''; 
	if($uid == '' || $uid == '2147483647' || $uid == '0') trigger_error(MSG_NOT_CORRECT_USER_ID);
	
	$item_id = isset($_GET['item']) ? (int) urldecode($_GET['item']): ''; 
	if(!$item_id) trigger_error(MSG_USER_ID_WRONG_TYPE);
	
	$amount = isset($_GET['amount']) ? (int) urldecode($_GET['amount']): ''; 
	if(!$amount) $amount = 1;

	$slot = isset($_GET['slot']) ? (int) urldecode($_GET['slot']): ''; 
	if($slot < 0 || $slot == '2147483647') trigger_error(MSG_WRONG_REQUEST_PARAM);
		
	$from = isset($_GET['from']) ? (int) urldecode($_GET['from']): ''; 
	if($from == '') $from = 0;
	
	if ($slot != '0' && $from != '0') printMsg(MSG_WRONG_REQUEST_PARAM,4);
	
	/*GET item information*/
	$query = "SELECT `items`.`rate`, `items`.`type`, `items`.`duration`
				FROM `items` 
				WHERE `items`.`id` = '{$item_id}'
			 ";
	
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$idata = mysql_fetch_assoc($result);
	
	if ($idata == '') printMsg(MSG_WRONG_BUY_ITEM,4);

	$itemRate = $idata['rate'];
	$itemType = $idata['type'];
	$itemDuration = $idata['duration'];
	
	/*GET User's dog information*/
	$query = "SELECT `users_dogs`.`user_id`, `users_dogs`.`strength`, `users_dogs`.`dexterity`, `users_dogs`.`endurance`
				FROM `users_dogs` 
				WHERE `users_dogs`.`user_id` = (SELECT `users`.`id` FROM `users` WHERE `VKuser` = '{$uid}' ORDER by `users`.`id` LIMIT 1) 
			";
//print_r($query); echo "<br>";	
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$udata = mysql_fetch_assoc($result);
	
	if ($udata['user_id'] == '') printMsg(MSG_NOT_CORRECT_USER_ID,4);
	
	$userID = $udata['user_id'];
	$curDexterity = $udata['dexterity'];
	$curStrength = $udata['strength'];
	$curEndurance = $udata['endurance'];
	
	/*Chose a move way*/
	if ($slot == '0') $table = "used_items";
	else $table = "purchased_items";

	if ($table != '') {
		$query = "SELECT *
					FROM `{$table}`
					WHERE `item_id` = '{$item_id}'   
					AND `user_id` = '{$userID}'
				";
//	print_r($query); echo "<br>";
		$result = mysql_query($query); 
		if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
		$data = mysql_fetch_assoc($result);
	}
	
	if (!isset($data['id']) || $data['id'] == '') trigger_error(MSG_WRONG_ITEM);
	if ($amount > $data['amount']) trigger_error(MSG_WRONG_AMOUNT);
	
	if ($slot != '0') {
		$newPassiveAmount = $data['amount'] - 1;
		$newActiveAmount = 1;
	} else {
		$newPassiveAmount = $data['amount'] + 1;
		$newActiveAmount = 0;
	}
	
	switch ($itemType) {
		case 1:
			if ($slot == 0){
				$newDexterity = $curDexterity - $itemRate;
				$sqlSETParams = " , `slot` = '0' ";
			} else {
				$newDexterity = $curDexterity + $itemRate;
				$sqlSETParams = " , `slot` = '1' ";
				if ($slot != '1') printMsg(MSG_WRONG_ITEM_SLOT,4);
			}
			$sqlSET = " SET `dexterity` = ".$newDexterity;
			break;
		case 2:
			if ($slot == 0){
				$newStrength = $curStrength - $itemRate;
				$sqlSETParams = " , `slot` = '0' ";
			} else {
				$newStrength = $curStrength + $itemRate;
				$sqlSETParams = " , `slot` = '2' ";
				if ($slot != '2') printMsg(MSG_WRONG_ITEM_SLOT,4);
			}
			$sqlSET = " SET `strength` = ".$newStrength;
			break;
		case 3:
			if ($slot == 0){
				$newEndurance = $curEndurance - $itemRate;
				$sqlSETParams = " , `slot` = '0' ";
			} else {
				$newEndurance = $curEndurance + $itemRate;
				$sqlSETParams = " , `slot` = '3' ";
				if ($slot != '3') printMsg(MSG_WRONG_ITEM_SLOT,4);
			}
			$sqlSET = " SET `endurance` = ".$newEndurance;
			break;
		case 4:
			if ($slot == 0) $condition = $from;
			else $condition = $slot;
			
			if(!moveSteroidItem($userID, $item_id, $amount, $itemDuration, $slot, $condition)) trigger_error(MSG_MOVE_ITEM_ERROR);
			else printMsg(MSG_MOVE_ITEM_OK,2);
			$sqlSETParams = '';
			$sqlSET = '';
			break;
		default:
			$sqlSETParams = '';
			$sqlSET = '';
		break;				
	}
	
	$query1 = '';
	$query2 = '';
	$query3 = '';
	$refresh = 0;
	
	/*UPDATE purchased_items and user's dog*/
	if ($sqlSET != '' ) 
	{
		if ($amount > 1) trigger_error(MSG_WRONG_AMOUNT);
		
		/*IF we move item from nonactive to active slot*/
		if ($slot != 0) 
		{	
			$chkquery = "SELECT * 
							FROM `used_items` 
							WHERE `slot` = '{$slot}' 
							AND `user_id` = '{$userID}' ";
			
			$result = mysql_query($chkquery,$conn); 
			if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
			$resdata = mysql_fetch_assoc($result);

			/*IF we swap the same items*/
			if ($resdata['id'] != '') 
			{ 	
				if ($item_id != $resdata['item_id']) {
					$query1 = "UPDATE `purchased_items` SET `item_id` = '{$resdata['item_id']}', `amount` = '{$resdata['amount']}', `slot` = '0' WHERE `purchased_items`.`id` = " . $data['id'];
					$query2 = "UPDATE `used_items` SET `item_id` = '{$data['item_id']}', `amount` = '1' WHERE `used_items`.`id` = " . $resdata['id'];
					$query3 = "UPDATE `users_dogs` {$sqlSET} WHERE `users_dogs`.`user_id` = '{$userID}' " ;
					$refresh = 1;
				} else {
					printMsg(MSG_ITEM_SLOT_ALREADY_USED,4);
				}
			} 
			else 
			{ 	
				if ($newPassiveAmount == 0) {
					$query1 = "DELETE FROM `purchased_items` WHERE `purchased_items`.`id` = " . $data['id'];
				} else {
					$query1 = "UPDATE `purchased_items` SET `amount` = '{$newPassiveAmount}' WHERE `purchased_items`.`id` = " . $data['id'];
				}
				
				$query2 = "INSERT INTO `used_items` SET `user_id` = '{$userID}', `amount` = '1', `item_id` = '{$item_id}' {$sqlSETParams} ";
				$query3 = "UPDATE `users_dogs` {$sqlSET} WHERE `users_dogs`.`user_id` = '{$userID}' ";
			}
		}
		/*IF we move item from active to nonactive slot*/ 
		else 
		{
			$chkquery = "SELECT * 
							FROM `purchased_items` 
							WHERE `item_id` = '{$item_id}' 
							AND `user_id` = '{$userID}' ";
			
			$result = mysql_query($chkquery,$conn); 
			if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
			$resdata = mysql_fetch_assoc($result);
			
			if ($resdata['item_id'] != 0) {
				$query1 = "UPDATE `purchased_items` SET `amount` = '{$newPassiveAmount}', `slot` = '0' WHERE `purchased_items`.`id` = " . (int)$resdata['id'];
			} else {
				$query1 = "INSERT INTO `purchased_items` SET `user_id` = '{$userID}', `item_id` = '{$item_id}', `amount` = '{$amount}', `slot` = '0' ";
			}
			
			$query2 = "DELETE FROM `used_items` WHERE `used_items`.`id` = " . (int)$data['id'];
			$query3 = "UPDATE `users_dogs` {$sqlSET} WHERE `users_dogs`.`user_id` = '{$userID}' ";
		}
		
	}
	
//	print_r($query); echo "<br>";
//	print_r($chkquery); echo "<br>";
//	print_r($query1); echo "<br>";
//	print_r($query2); echo "<br>";
//	print_r($query3); echo "<br>";
//exit;
	if (($query1 != '' && $query2 != '' && $query3 != '')) 
	{
		mysql_query($query1, $conn);
		if(mysql_error($conn)) trigger_error(MSG_MOVE_ITEM_ERROR);
	
		mysql_query($query2, $conn);
		if(mysql_error($conn)) trigger_error(MSG_MOVE_ITEM_ERROR);	
		
		mysql_query($query3, $conn);
		if(mysql_error($conn)) trigger_error(MSG_MOVE_ITEM_ERROR);	
		
		if ($refresh == '1')  refreshParameter($userID, $resdata['item_id']);

		$msg = MSG_MOVE_ITEM_OK;
	} 
	else {
		$msg = MSG_MOVE_ITEM_ERROR;
	}
	
	$responce = "<result type='2'><msg>{$msg}</msg></result>";
//	echo $responce; 
	echo base64_encode($responce); 
}

/**
 * Move steroid item to temp table `used_items`
 *
 * @param unknown_type $uid
 * @return unknown
 */
function moveSteroidItem($userID, $item, $amount, $expire, $slot, $condition)
{
	Global $conn;
		
	if ($slot > 6 || ($slot > 0 && $slot < 4)) trigger_error(MSG_WRONG_ITEM_SLOT);
	if ($condition > 6 || $condition < 4) trigger_error(MSG_WRONG_REQUEST_PARAM);
		
	if ($slot == 0) {
		$andWhere = " AND `used_items`.`slot` = '{$condition}' ";
	} else {
		$andWhere = '';
	}
	//print_r($andWHERE); echo "<br>"; 
	
	if ($slot == 0) $table = "used_items";
	else $table = "purchased_items"; 

	/*CHANGES in used_items*/
	$query = "SELECT `id`, `amount`, `slot`
				FROM `{$table}`
				WHERE `item_id` = '{$item}'
				AND `user_id` = '{$userID}'  
				{$andWhere}
			";
//	print_r($query); echo "<br>"; 
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_array($result);
//	print_r($data);	echo "<br>"; 
	
	if ($data == '') trigger_error(MSG_WRONG_STEROID_ITEM);
	if ($amount > $data['amount']) trigger_error(MSG_WRONG_ITEM_AMOUNT);
	
	$newFromAmount = $data['amount'] - $amount;
	
	/*MOVE FROM nonactive TO active*/
	if ($slot != '0') { 
		$tablefrom = "purchased_items";
		$tableto = "used_items";
		$tt = 1;
		$andWhere2 = " AND `{$tableto}`.`slot` = '{$slot}' ";
		$andSET = ", `expire` = '5' ";
	} 
	/*MOVE FROM active TO nonactive*/
	else {
		$tablefrom = "used_items";
		$tableto = "purchased_items";
		$tt = 0;
		$andWhere2 = '';
		$andSET = "";
	}
		
	/*CHECK item amount in "purchased_items" OR "used_items"*/
	$query2 = "SELECT `id`, `amount`
				FROM `{$tableto}`
				WHERE `item_id` = '{$item}'
				AND `user_id` = '{$userID}' 
				{$andWhere2} 
			";
//	print_r($query2); echo "<br>"; 
	$rez = mysql_query($query2);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$toData = mysql_fetch_array($rez);
//	print_r($toData);	echo "<br>";
	
	if ($toData == '')
	{
		if ($tt == 1) 
		{
			$query3 = "SELECT `id`, `item_id`, `amount`
						FROM `{$tableto}`
						WHERE `user_id` = '{$userID}'
						{$andWhere2} 
				";
		//	print_r($query3); echo "<br>"; 
			$rez3 = mysql_query($query3);
			if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
			$checkSlot = mysql_fetch_array($rez3);
		//	print_r($checkSlot);	echo "<br>";
			
			if ($checkSlot != '') printMsg(MSG_ITEM_SLOT_ALREADY_USED,4);
		} 
		
		$query1 = "INSERT INTO `{$tableto}` 
					SET `user_id` = '{$userID}', 
					`item_id` = '{$item}',
				    `amount` = '{$amount}',
				    `slot` = '{$slot}'
					{$andSET}
			";
	}
	else 
	{
		$newAmount = $toData['amount'] + $amount;
		$query1 = "UPDATE `{$tableto}` 
					SET `amount` = '{$newAmount}'
					WHERE `id` = '{$toData['id']}'   
		";
	}

	if ($newFromAmount == '0')
	{
		$query2 = "DELETE
					FROM `$tablefrom`
					WHERE `id` = '{$data['id']}'
			";
	} else {
		$query2 = "UPDATE `$tablefrom` 
					SET `amount` = '{$newFromAmount}'
					WHERE `id` = '{$data['id']}'     
		";
	}
	
//	print_r($query1); echo "<br>"; 
//	print_r($query2); echo "<br>"; 
	
//	exit;
	
	if ($query1 != '' && $query2 != '')
	{
		mysql_query($query1, $conn);
		if(mysql_error($conn)) trigger_error(MSG_MOVE_ITEM_ERROR);
		
		mysql_query($query2, $conn);
		if(mysql_error($conn)) trigger_error(MSG_MOVE_ITEM_ERROR);	
		
		return true;
	}
		
	return false;	
}

/**
 * 
 */
function refreshParameter($uid, $old_item)
{
	Global $conn;
	
	$query = "SELECT * FROM `items` WHERE `id` = '{$old_item}' ";
	
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_array($result);
	
	$itemRate = $data['rate'];
	$itemType = $data['type'];
	
	$query = "SELECT `strength`, `dexterity`, `endurance`
				FROM `users_dogs` 
				WHERE `user_id` = '{$uid}' ";
	
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_array($result);
	
	switch ($itemType) {
		case 1:
			$newDexterity = $data['dexterity'] - $itemRate;
			$sqlSET = " SET `dexterity` = ".$newDexterity;
			break;
		case 2:
			$newStrength = $data['strength'] - $itemRate;
			$sqlSET = " SET `strength` = ".$newStrength;
			break;
		case 3:
			$newEndurance = $data['endurance'] - $itemRate;
			$sqlSET = " SET `endurance` = ".$newEndurance;
			break;
		default:
			$sqlSET = '';
		break;				
	}
	
	if ($sqlSET != '') {
		$query2 = "UPDATE `users_dogs` {$sqlSET} WHERE `users_dogs`.`user_id` = '{$uid}' ";

		mysql_query($query2, $conn);
		if(mysql_error($conn)) trigger_error(MSG_MOVE_ITEM_ERROR);	
		return true;
	} else {
		trigger_error(MSG_MOVE_ITEM_ERROR);	
	}
}

/**
* Продажа предмета в будке
* 
* @param int $uid
* @param int $item_id
* @param int $amount
*/
function sellItem()
{
	Global $conn;
	
	$uid = isset($_GET['user']) ? (int) urldecode($_GET['user']): ''; 
	if(!$uid) trigger_error(MSG_USER_ID_WRONG_TYPE);
	
	$item_id = isset($_GET['item']) ? (int) urldecode($_GET['item']): ''; 
	if(!$item_id) trigger_error(MSG_USER_ID_WRONG_TYPE);
	
	$amount = isset($_GET['amount']) ? (int) urldecode($_GET['amount']): ''; 
	if(!$amount) $amount = 1;
	$amount = 1;

	$slot = isset($_GET['slot']) ? (int) urldecode($_GET['slot']): ''; 
	if(!isset($slot) || $slot > 6) trigger_error("Wrong slot number!");
	
	$query = 'SELECT `items`.`price`, `items`.`rate`, `items`.`type`
				FROM `items` 
				WHERE `items`.`id` = ' . $item_id .'
			 ';
	
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_assoc($result);
	
	if ($data == '') trigger_error(MSG_WRONG_BUY_ITEM);
	
	$itemPrice = $data['price'];
	$itemCost = $amount * ($itemPrice/2);
	$itemRate = $data['rate'];
	$itemType = $data['type'];
	
	/*GET item amount and status from database*/
	if ($slot == '0') {
		$query = "SELECT `purchased_items`.*, `users_dogs`.`strength`, `users_dogs`.`dexterity`, `users_dogs`.`endurance`
					FROM `purchased_items`
					INNER JOIN `users_dogs` ON `users_dogs`.`user_id` = `purchased_items`.`user_id`
					WHERE `purchased_items`.`item_id` = '{$item_id}'   
					AND `purchased_items`.`user_id` = (SELECT `users`.`id` FROM `users` WHERE VKuser = '{$uid}' ORDER by `users`.`id` LIMIT 1)
		";
		$useTable = '`purchased_items`';
	} else {
		$query = "SELECT `used_items`.*, `users_dogs`.`strength`, `users_dogs`.`dexterity`, `users_dogs`.`endurance`
					FROM `used_items`
					INNER JOIN `users_dogs` ON `users_dogs`.`user_id` = `used_items`.`user_id`
					WHERE `used_items`.`item_id` = '{$item_id}'   
					AND `used_items`.`user_id` = (SELECT `users`.`id` FROM `users` WHERE VKuser = '{$uid}' ORDER by `users`.`id` LIMIT 1)
					AND `used_items`.`slot` = '{$slot}'
		";
		$useTable = '`used_items`';
	}
	
	$result = mysql_query($query); 
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_assoc($result);
	
	if ($data == '') trigger_error(MSG_WRONG_ITEM_SLOT);
	if ($amount > $data['amount']) trigger_error(MSG_WRONG_AMOUNT);
	
	$userID = (int)$data['user_id'];
	
	switch ($itemType) {
		case 1:
			$newDexterity = $data['dexterity'] - ($itemRate * $amount);
			$sqlSET = " SET `dexterity` = ".$newDexterity;
			break;
		case 2:
			$newStrength = $data['strength'] - ($itemRate * $amount);	
			$sqlSET = " SET `strength` = ".$newStrength;
			break;
		case 3:
			$newEndurance = $data['endurance'] - ($itemRate * $amount);
			$sqlSET = " SET `endurance` = ".$newEndurance;
			break;
		case 4:
			$sqlSET = '';
			break;
		default:
			$sqlSET = '';
		break;				
	}

	/*DELETE or UPDATE purchased_items*/
	if (isset($data['id']) && isset($data['amount']) ) 
	{
		$query2 = '';
		if ($data['amount'] == $amount){
			$query = "DELETE FROM {$useTable} WHERE `id` = " . (int)$data['id'];
			if ($sqlSET != '' && $slot != '0'){
				$query2 = "UPDATE `users_dogs` {$sqlSET} WHERE `users_dogs`.`user_id` = '{$userID}' ";
			}
		} else {
			$item_amount = $data['amount'] - $amount;
			$query = "UPDATE {$useTable} SET `amount` = '{$item_amount}' WHERE `id` = " . (int)$data['id'];
			if ($sqlSET != '' && $slot != '0'){
				$query2 = "UPDATE `users_dogs` {$sqlSET} WHERE `users_dogs`.`user_id` = '{$userID}' ";
			}
		}
		
	//	print_r($query); echo "<br>";
	//	print_r($query2); echo "<br>";
	//	exit;
		mysql_query($query, $conn);
		if ($query2 != '') {
			mysql_query($query2, $conn);
		}
		if(mysql_error($conn)) trigger_error(MSG_SELL_ITEM_ERR);
	}
	
	/*GET user's balance*/
	$query = "SELECT `balance` FROM `users_balance` WHERE `user_id` = '{$userID}' ";
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_assoc($result);

	if ($data['balance'] != '') $newBalance = $data['balance'] + $itemCost;
	
	/*UPDATE user's balance after purchase*/
	$query = "UPDATE `users_balance` SET `balance` = '{$newBalance}' WHERE `user_id` = '{$userID}' ";
	mysql_query($query, $conn);
	if(mysql_error($conn)) trigger_error(MSG_SELL_ITEM_ERR);

	printMsg(MSG_SELL_ITEM_OK,3);
//	$responce = "<result type='3'><msg>{$msg}</msg></result>";
//	echo $responce;
//	echo base64_encode($responce);
 
}

/**
 * Change dog's breed and name
 *
 */
function changeDog()
{
	Global $conn;
	
	$uid = isset($_GET['user']) ? (int) urldecode($_GET['user']): ''; 
	if($uid == '' || $uid == '2147483647') trigger_error(MSG_NOT_CORRECT_USER_ID); 
	
	$dogBreedID = isset($_GET['breed']) ? (int) urldecode($_GET['breed']): '';
	if(!$dogBreedID) trigger_error(MSG_DOG_ID_WRONG_TYPE);
	
	$dogName = isset($_GET['name']) ?  rawurldecode($_GET['name']): '';
	if(!$dogName) $dogName = '';
	$dogName = iconv("CP1251", "UTF-8", $dogName);
	
	$query = "SELECT `dogs_breed`.`breed_id`
				FROM  `dogs_breed`
				WHERE `dogs_breed`.`breed_id` = '{$dogBreedID}'
			";
	
	$result = mysql_query($query, $conn);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_array($result);

	if ($data == '') printMsg(MSG_WRONG_BREED,3);
	
	$query = "SELECT `users_balance`.`balance`, `users_balance`.`user_id`
				FROM `users_balance`
				WHERE `users_balance`.`user_id` = (SELECT `users`.`id` FROM `users` WHERE VKuser = '{$uid}' ORDER BY `users`.`id` LIMIT 1)	
			";
//	print_r($query); echo "<br>";
	$result = mysql_query($query, $conn);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_array($result);
	
	if ($data == '') printMsg(MSG_NOT_CORRECT_USER_ID,3);
	
	$userID = $data['user_id'];
	$newUserBalance = $data['balance'] - USR_CHNG_DOG;
	if ($newUserBalance < 0) printMsg(MSG_NOT_ENOUGH_MONEY_CHNG,3);
	
	/*UPDATE User's balance after change*/
	$query1 = "UPDATE `users_balance` SET `balance` = '{$newUserBalance}' WHERE `user_id` = {$userID} ";
	
	if ($dogName != '')	$setDog = ", `DogName` = \"". mysql_escape_string($dogName)."\" ";
	else $setDog = '';
	
	/*UPDATE User's DOG*/
	$query2 = "UPDATE `users_dogs` 
				SET
					`DogBreed` = '{$dogBreedID}' 
					{$setDog}
				WHERE 
					`user_id` = '{$userID}' 
			";
	
//	print_r($query1); echo "<br>";	
//	print_r($query2); echo "<br>";	
//	exit;
	if ($query1 != '' && $query2 != '')
	{
		mysql_query($query1, $conn);
		mysql_query($query2, $conn);
		if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
		
		printMsg(MSG_DOG_BREED_NAME_CHNG,3);
	} else {
		printMsg(MSG_DOG_BREED_CHNG_ERR,3);
	}
}

function chooseDog()
{
  Global $conn;
  $responce = '<result type="5">';
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

function enc(){
	
	echo base64_decode('PHJlc3VsdCB0eXBlPScyJz48bXNnPtCd0LUg0YPQtNCw0LvQvtGB0Ywg0L/QvtC70YPRh9C40YLRjCDQuNC90YTQvtGA0LzQsNGG0LjRjiDQviDRgdC+0LHQsNC60LUg0Lgg0LXQtSDQstC70LDQtNC10LvRjNGG0LUuIDwvbXNnPjwvcmVzdWx0Pg==');
}

//_________________COMMON______________________
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
			//echo $err;                                
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
//   fputs($fp, $msg . "\n");
}

?>
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

/*________________
    Logic
________________*/
$conn = @mysql_connect($host, $user, $pass);
if(!$conn) trigger_error(MSG_DB_NOT_CONNECT);
mysql_select_db($db);
mysql_query('SET NAMES UTF8');

$command = isset($_GET['do'])? urldecode($_GET['do']) : '';
if(!function_exists($command)){
   trigger_error(TXT_METHOD . $command . TXT_NOT_SUPPORTED);
}
call_user_func($command); 

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
 * Выводит информацию о каждом из предметов в тренажерном зале
 * 
 */

function getItems ()
{
	Global $conn;
	$xmlFile = 'dogGym.xml';
	
	if (file_exists($xmlFile)) { 
	    echo base64_encode(file_get_contents($xmlFile));
	} else {
		
		$responce = '<result type="1">';
		$query = "SELECT *
					FROM `training_items`
					ORDER BY `id` 
				 ";
		
		//echo $query."<br>";		
		$result = mysql_query($query, $conn);
		if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL.' : '.(mysql_error($conn)));
		
		while ($row = mysql_fetch_array($result))
		{
			if ($row['strength'] == 0) $rowStrength = '';
			else $rowStrength = "<str>{$row['strength']}</str>";
			if ($row['dexterity'] == 0) $rowDexterity = '';
			else $rowDexterity = "<dex>{$row['dexterity']}</dex>";
			if ($row['endurance'] == 0) $rowEndurance = '';
			else $rowEndurance = "<endr>{$row['endurance']}</endr>";
			
			$responce .= "<item><itemID>{$row['id']}</itemID><name>{$row['name']}</name><desc>{$row['description']}</desc>{$rowStrength}{$rowDexterity}{$rowEndurance}<time>{$row['time']}</time><price>{$row['price']}</price></item>";
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
* @param int $training_id
* @param int $amount
*/
function buyTraining()
{
	Global $conn;
	
	$uid = isset($_GET['user']) ? (int) urldecode($_GET['user']): ''; 
	if($uid == '' || $uid == '2147483647') trigger_error(MSG_NOT_CORRECT_USER_ID); 
	
	$training_id = isset($_GET['training']) ? (int) urldecode($_GET['training']): ''; 
	if(!$training_id || $training_id == '2147483647') trigger_error(MSG_WRONG_REQUEST_PARAM);
	
	/*CHECK user's balance for available coins*/
	$query = "SELECT `users_balance`.`balance`, `users_dogs`.`user_id`, `users_dogs`.`strength`, `users_dogs`.`dexterity`, `users_dogs`.`endurance` 
				FROM `users_dogs` 
				INNER JOIN `users_balance` ON `users_balance`.`user_id` = `users_dogs`.`user_id`
				WHERE `users_dogs`.`user_id` = (SELECT `id` FROM `users` WHERE `VKuser` = '{$uid}' ORDER BY `id` LIMIT 1) ";
	//echo $query."<br>";
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL.' : '.(mysql_error($conn)));
	$udata = mysql_fetch_assoc($result);
	//print_r($udata); echo "<br>";
	
	$userID = $udata['user_id'];
	
	/*CHECKING for the available to training*/
	checkLockTrainings($userID);
	
	/*CHECKING for the ITEM existence*/
	$query = "SELECT  `price`, `time`, `strength`, `dexterity`, `endurance`
				FROM `training_items` 
				WHERE `id` = '{$training_id}' ";
	
	//echo $query."<br>";
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL.' : '.(mysql_error($conn)));
	$tdata = mysql_fetch_assoc($result);
	//print_r($tdata); echo "<br>";
	
	if ($tdata == '') trigger_error(MSG_WRONG_BUY_TRAIN);

	$trainingPrice = $tdata['price'];
	$trainingTime = $tdata['time'];
		
	/*CHECKING user's balance for available coins and TAKE a BALANCE*/
	if (isset($udata['balance']) && $udata['balance'] != '' && $udata['balance'] < $trainingPrice)
	{ 
		printMsg(MSG_NOT_ENOUGH_MONEY_TRAIN, 3);
	}
	elseif ($udata['balance'] != '') $newBalance = $udata['balance'] - $trainingPrice;
		
	/*CHECK for alredy trained users*/
	$query = "SELECT  `id` FROM `users_trainings` WHERE `user_id` = '{$userID}' ";
	//echo $query."<br>";
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL.' : '.(mysql_error($conn)));
	$utdata = mysql_fetch_assoc($result);
	
	if ($utdata['id'] == '')
	{
		/*ADD NEW training*/
		$queryUT = "INSERT INTO `users_trainings` 
					SET `user_id` = '{$userID}', 
						`training_id` = '{$training_id}', 
						`unlock_time` = ADDTIME(NOW(), '{$trainingTime}')
				 ";
	} else {
		/*UPDATE existing training row*/
		$queryUT = "UPDATE `users_trainings` 
					SET `unlock_time` = ADDTIME(NOW(), '{$trainingTime}')
					WHERE `id` = '{$utdata['id']}'
				 ";
	}
//	print_r($queryUT); echo "<br>";

	$sqlSET = 'SET ';
	$newStrength = '';
	$newDexterity = '';
	$newEndurance = '';
	if (isset($tdata['strength']) && $tdata['strength'] != '0') {
		$newStrength = $udata['strength'] + $tdata['strength'];
		$sqlSET .= " `strength` = '{$newStrength}',";
	}
	if (isset($tdata['dexterity']) && $tdata['dexterity'] != '0') {
		$newDexterity = $udata['dexterity'] + $tdata['dexterity'];
		$sqlSET .= " `dexterity` = '{$newDexterity}',";
	}
	if (isset($tdata['endurance']) && $tdata['endurance'] != '0') {
		$newEndurance = $udata['endurance'] + $tdata['endurance'];
		$sqlSET .= " `endurance` = '{$newEndurance}',";
	}
	
	$sqlSET = substr($sqlSET, 0, strlen($sqlSET)-1);
	
	/*UPDATE user's dogs figures*/
	if ($newStrength != '' || $newDexterity != '' || $newEndurance != '')
	{
		$queryUD = "UPDATE `users_dogs` 
					{$sqlSET} 
					WHERE `user_id` = '{$userID}' ";
	//	print_r($queryUD); echo "<br>";
	} else {
		printMsg(MSG_WRONG_REQUEST_PARAM, 3);
	}
	
	/*UPDATE user's balance after purchase*/
	$queryUB = "UPDATE `users_balance` 
				SET `balance` = '{$newBalance}' 
				WHERE `user_id` = '{$userID}' ";
	//print_r($queryUB); echo "<br>";

	/*PROCESS SQL queries*/
	if (isset($queryUT) || isset($queryUD) || isset($queryUB)) {
		mysql_query($queryUT, $conn);
		mysql_query($queryUD, $conn);
		mysql_query($queryUB, $conn);
		//echo $queryUT."<br>";
		//echo $queryUD."<br>";
		//echo $queryUB."<br>";
	
		if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL.' : '.(mysql_error($conn)));
	}

	printMsg(MSG_DOG_TRAIN_START_OK, 2);
}

/**
 * GET free slot for items
*/
function checkLockTrainings($uid)
{
	Global $conn;
	
	$query = "SELECT IF(NOW() <= DATE_SUB(`users_trainings`.`unlock_time`, INTERVAL 1 HOUR), DATE_FORMAT( DATE_SUB( FROM_UNIXTIME( UNIX_TIMESTAMP(`users_trainings`.`unlock_time`) - UNIX_TIMESTAMP(NOW()) ), INTERVAL 3 HOUR), '%H:%i:%s'), '') as unlocktime
				FROM `users_trainings` 
				WHERE `user_id` = '{$uid}' 
			 ";
	//echo $query."<br>";
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL.' : '.(mysql_error($conn)));
	$data = mysql_fetch_array($result);

	if (isset($data['unlocktime']) && $data['unlocktime'] != '')
	{
		printMsg(MSG_DOG_TRAIN_NOT_YET.$data['unlocktime'],4);
	}
	else return true; 
}

//_________________COMMON______________________
/*function ErrorHandler($type,$err,$file,$line)
{
$response = <<<Error
<result error="1"><error><type>{$type}</type><msg>{$err}</msg><line>{$line}</line></error></result>
Error;
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

?>
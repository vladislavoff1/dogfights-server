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
set_exception_handler('MyErrorHandler');
set_error_handler('MyErrorHandler');

function MyErrorHandler($errno, $errmsg, $filename, $linenum) {     
	if (!in_array($errno, Array(E_NOTICE, E_STRICT, E_WARNING))) {             
		$date = date('Y-m-d H:i:s (T)');             
		$f = fopen('errors.log', 'a');                 
		if (!empty($f)) {                     
			//$err  = "\r\n";             
			$err .= $date.time."  ";   
			$err .= "dogFight: ";   
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
// fb
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
if (!mysql_select_db($db)) {
   die("Couldn't select database!");
} 
/*
if (!mysql_select_db($db)) {
   die("Couldn't select database: " . mysql_errno($conn) . ' ' . mysql_error($conn));
} 
*/
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
 * Get Ememy fo fight
 *
 */

function getEnemy ()
{
	Global $conn;
	$where = array();
		
	$uid = isset($_GET['user']) ? (int) urldecode($_GET['user']): ''; 
	if($uid == '' || $uid == '2147483647' || $uid == '0') printMsg(MSG_NOT_CORRECT_USER_ID,3);

	checkLockTrainings($uid);

	$checkfightsXML = checkLockFights($uid);
	
	preg_match('/<time>([0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2})<\/time>/is', $checkfightsXML, $lock);
	if (count($lock) != 0)
	{
		echo base64_encode($checkfightsXML);
		exit;
	}
	
	preg_match('/<leftfights>([0-9]{1,5})<\/leftfights>/is', $checkfightsXML, $mas);
	if (count($mas) != 0) $userLeftFights = $checkfightsXML;
	else $userLeftFights = '';
		
	$responce = '<result type="1">';
	$responce .= $userLeftFights;

	$query = 'SELECT `users_dogs`.`DogLevel`, `users_dogs`.`user_id`  
				FROM `users_dogs` 
				WHERE `users_dogs`.`user_id` = (SELECT `users`.`id` FROM `users` WHERE `users`.`VKuser`=' . (int)$uid .' ORDER BY `users`.`id` LIMIT 1) ';
	
	$result = mysql_query($query);
//	loger($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_assoc($result);
	
	$userID = $data['user_id'];
	$dogLevel = $data['DogLevel'];
	$prev = $dogLevel-1;
	$next = $dogLevel+1;
	
	$query = "SELECT `users_dogs`.`DogLevel`
                   FROM `users_dogs` 
                   WHERE `users_dogs`.`DogLevel` = '{$next}' 
                   LIMIT 1
				";
	
	$result = mysql_query($query);
//	loger($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_array($result);

	$query2 = "SELECT `users_dogs`.`DogLevel`
                   FROM `users_dogs` 
                   WHERE `users_dogs`.`DogLevel` = '{$prev}' 
                   LIMIT 1
				";
	
	$result2 = mysql_query($query2);
//	loger($query2);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data2 = mysql_fetch_array($result2);

	if ($dogLevel == '1'){
		$where[0] = ' WHERE `users_dogs`.`doglevel` = '.$dogLevel;
		
		if ($data['DogLevel'] != '' && $data['DogLevel'] == '2'){
			$where[1] = ' WHERE `users_dogs`.`doglevel` = '.$dogLevel;
			$where[2] = ' WHERE `users_dogs`.`doglevel` = '.$next;
		}
		else {
			$where[1] = ' WHERE `users_dogs`.`doglevel` = '.$dogLevel;
			$where[2] = ' WHERE `users_dogs`.`doglevel` = '.$dogLevel;
		}
	} 
	else 
	{
		$where[0] = ' WHERE `users_dogs`.`doglevel` = '.$prev;
		$where[1] = ' WHERE `users_dogs`.`doglevel` = '.$dogLevel;
		
		if ($data['DogLevel'] != '' && $data['DogLevel'] == $next){
			$where[2] = ' WHERE `users_dogs`.`doglevel` = '.$next;
		} else {
			$where[2] = ' WHERE `users_dogs`.`doglevel` = '.$dogLevel;
		}
		
		if ($data2['DogLevel'] == '') {
			$level = $prev - 1;
			$where[0] = ' WHERE `users_dogs`.`doglevel` = '.$level;
			$where[1] = ' WHERE `users_dogs`.`doglevel` = '.$level;
		}
	}.............
	
	$finalUids = array();
	
	for ($i=0;$i<count($where);$i++)
	{
		$ids = array();

		$queryCount = "SELECT COUNT(*) FROM `users_dogs` {$where[$i]}";
		$count = mysql_fetch_row(mysql_query($queryCount, $conn));
		if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
		
		$limitDel = 4;

		if ($count[0] > '5000' && $count[0] < '50000') {
			$limitDel = 10;
		}
		if ($count[0] > '50000' && $count[0] < '100000') {
			$limitDel = 20;
		} 
		if ($count[0] > '100000' && $count[0] < '150000') {
			$limitDel = 30;
		}
		if ($count[0] > '150000' && $count[0] < '200000') {
			$limitDel = 40;
		}
		if ($count[0] > '200000' && $count[0] < '300000') {
			$limitDel = 60;
		}
		if ($count[0] > '300000' && $count[0] < '400000') {
			$limitDel = 80;
		}
		
		$limitMax = $count[0]/$limitDel;
		
		if ((int)$limitMax > 0)	$sqlLIMIT = " LIMIT 0,".(int)$limitMax." ";
		else $sqlLIMIT = "";
	
		$ids = array();
	//	print_r($where); echo "<br>";
		$query = "SELECT `users_dogs`.`user_id`
					FROM `users_dogs` 
					{$where[$i]} AND `users_dogs`.`user_id` != {$uid}
					{$sqlLIMIT}
				 ";
		echo $query;
	//	print_r($query); echo "<br>";
		$result3 = mysql_query($query, $conn);
		if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
		while ($data3 = mysql_fetch_array($result3))
		{
			$ids[] = $data3;
		}
	//	print_r($ids); exit;
		$maxkey = count($ids)-1;
	//	print_r($maxkey); echo "<br>";
		$old = [];
		for ($j=0; $j<2; $j++)
		{
			$randkey = rand(0,$maxkey-$j); 

			while ($h) {
				$h = false;
				for($i = 0; $i < $j; $i++)
					if($h == $old[$i]){
						$h = true;
						break;
					}
				$randkey++;
				if($randkey >= $maxkey){
					$randkey = 0;
				}
			}
			$old[j] = $randkey;
			$finalUids[] = $ids[$randkey]['user_id'];
		}

/*THE SECOND METHOD TO CHOSEE RANDOM ENEMY*/
/*
		$query = "SELECT `users_dogs`.`user_id`
					FROM `users_dogs` 
					{$where[$i]}
					AND `id` >= (rand()*(SELECT count(*) FROM users_dogs {$where[$i]}) ) 
					AND `users_dogs`.`user_id` <> '1'  
					LIMIT 2
				 ";

	//	print_r($query); echo "<br>";
		$result3 = mysql_query($query, $conn);
		if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
		while ($data3 = mysql_fetch_array($result3))
		{
			$ids[] = $data3;
		}
	//	print_r($ids); exit;

		for ($j=0; $j<2; $j++)
		{
			$finalUids[] = $ids[$j]['user_id'];
		}
*/
	}
	
	$query = 'SELECT `users`.`VKuser`, `users_dogs`.`DogName`, `users_dogs`.`DogLevel`, `users_dogs`.`strength`, `users_dogs`.`dexterity`, `users_dogs`.`endurance` 
				FROM `users_dogs`
				INNER JOIN `users` ON `users`.`id` = `users_dogs`.`user_id` 
				WHERE `users_dogs`.`user_id` in ('. join(', ', $finalUids). ') 
				ORDER BY `users_dogs`.`DogLevel`
				LIMIT 6
			';	
		
//	print_r($query); echo "<br>";
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	
	while ($row = mysql_fetch_array($result))
	{
	/*
		$responce .= "
					<dog>
					   <user>{$row['VKuser']}<user> 
					   <dogID>{$row['dog_id']}</dogID> 
					   <dogname>{$row['DogName']}</dogname>
					   <breedID>{$row['breed_id']}</breedID>
					   <breed>{$row['breed_name']}</breed>
					   <level>{$row['DogLevel']}</level>
					   <health>{$row['DogHealth']}</health>
					   <exp>{$row['DogExp']}</exp>
					   <str>{$row['strength']}</str>  
					   <dex>{$row['dexterity']}</dex>
					   <endu>{$row['endurance']}</endu> 
					</dog>
				  
		";
		*/
	//	$responce .= "<dog><user>{$row['VKuser']}</user><name>{$row['DogName']}</name><breedID>{$row['breed_id']}</breedID><breed>{$row['breed_name']}</breed><level>{$row['DogLevel']}</level><health>{$row['DogHealth']}</health><exp>{$row['DogExp']}</exp><str>{$row['strength']}</str><dex>{$row['dexterity']}</dex><endu>{$row['endurance']}</endu></dog>";
		$responce .= "<dog><user>{$row['VKuser']}</user><name>{$row['DogName']}</name><level>{$row['DogLevel']}</level><str>{$row['strength']}</str><dex>{$row['dexterity']}</dex><endu>{$row['endurance']}</endu></dog>";
		
	}

	$responce .= '</result>';
	
//	echo $responce;
	echo base64_encode($responce);
}


/**
 * Dogs fight
 *
 */
function dogFight()
{
	Global $conn;
	$user = array();
	$enemy = array();
	
	$uid = isset($_GET['user']) ? (int) urldecode($_GET['user']): ''; 
	if(!$uid) printMsg(MSG_NOT_CORRECT_USER_ID,3);
	
	$enid = isset($_GET['enemy']) ? (int) urldecode($_GET['enemy']): ''; 
	if(!$enid) printMsg(MSG_NOT_CORRECT_USER_ID,3);

	checkLockTrainings($uid);

	$checkfightsXML = checkLockFights($uid);
	
	preg_match('/<time>([0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2})<\/time>/is', $checkfightsXML, $lock);
	if (count($lock) != 0)
	{
		echo base64_encode($checkfightsXML);
		die();
	}
	
	$user = getInfo($uid);
	$enemy = getInfo($enid);
	
//	print_r($user); echo "<br>";
//	print_r($enemy); echo "<br>";
	
	$fightResult = fightLogic($user, $enemy);
	
//	print_r($fightResult); echo "<br>";
	
	preg_match('/<win>([0-9]{1,10})<\/win>/is', $fightResult, $win);
	
//print_r($user['id']); echo "<br>";
//print_r($enemy); echo "<br>";
	$dogSkill = '5';
	$coins = '0';
	if ($uid == $win[1]) {
		if ($user['DogLevel'] < $enemy['DogLevel']) {
			$dogSkill = '20';
			$coins = '50';
		//	echo "win MAX"; echo "<br>";
		}
		if ($user['DogLevel'] >= $enemy['DogLevel']) {
			$dogSkill = '10';
			$coins = '20';
		//	echo "win MIN"; echo "<br>";
		}
		$result = '1';
		$resmsg = "Собака выиграла поединок";
	} else {
	//	echo "LOSE"; echo "<br>";
		$result = '0';
		$resmsg = "Собака пала смертью храбрых";
	}
	
//print_r($user['DogLevel']); echo "<br>";
//print_r($enemy['DogLevel']); echo "<br>";
//echo $dogSkill."<br>";
//echo $coins."<br>";

	if (!processResult($result, $user['id'], $dogSkill, $coins)) trigger_error(MSG_PROBLEM_WITH_SQL);
	
	$responce = "<result type='2'>";
    $responce .= "<start><dog id='{$user['VKuser']}'><name>{$user['DogName']}</name><health>{$user['DogHealth']}</health></dog><dog id='{$enemy['VKuser']}'><name>{$enemy['DogName']}</name><health>{$enemy['DogHealth']}</health></dog></start>";
	$responce .= $fightResult;
	$responce .= "<dog>";
	$responce .= "<userID>{$uid}</userID>";
	$responce .= "<exp>{$dogSkill}</exp>";
	$responce .= "<money>{$coins}</money>";
	$responce .= "<resmsg>{$resmsg}</resmsg>";
	$responce .= "</dog>";
	$responce .= "</result>";
		
//	echo $responce;
	echo base64_encode($responce);
	
//	exit;
}

function getInfo($userid)
{
	Global $conn;
	
	$query = "SELECT `users_dogs`.`user_id` as id, `users_dogs`.`DogHealth`, `users_dogs`.`strength`, `users_dogs`.`dexterity`, `users_dogs`.`endurance`, IF(`items`.`type` = '4',SUM(`items`.`rate`),0), `users_dogs`.`DogLevel`, `users_dogs`.`DogName`
                FROM `users_dogs` 
                LEFT JOIN `used_items` ON `used_items`.`user_id` = `users_dogs`.`user_id`
                LEFT JOIN `items` ON `items`.`id` = `used_items`.`item_id`
                WHERE `users_dogs`.`user_id` = (SELECT `users`.`id` FROM `users` WHERE `VKuser` = '{$userid}' ORDER BY `users`.`id` LIMIT 1)
 				GROUP BY `items`.`type` DESC
				LIMIT 1		    
			";
//print_r($query);
	$result = mysql_query($query);
//	loger($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_array($result);
	
	$data['VKuser'] = $userid;
	if ($data != '') return $data;
	else trigger_error("Указанного пользователя не существует!!");
}

/**
 * Update dog params after the fight
 *
 * @param int $result
 * @param int $uid
 * @param int $dogSkill
 * @param int $coins
 * @return true or false
 */
function processResult($result, $uid, $dogSkill, $coins)
{
	Global $conn;
	
	$query = "SELECT `users_dogs`.`DogLevel`, `users_dogs`.`DogExp`, `users_dogs`.`DogHealth`, `users_dogs`.`next_level`, `users_balance`.`balance`, `users_fights`.`wins`, `users_fights`.`losses`, `users_fights`.`fights_amount`, `users_fights`.`lock_fights`
              	FROM `users_dogs` 
               	INNER JOIN `users_balance` ON `users_balance`.`user_id` = `users_dogs`.`user_id`
                LEFT JOIN `users_fights` ON `users_fights`.`user_id` = `users_dogs`.`user_id`
                WHERE `users_dogs`.`user_id` = '{$uid}' 
			";
	
	$res = mysql_query($query);
//	loger($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_array($res);
//print_r($data); echo "<br>";
	if($data == '') printMsg("Нет необходимых данных!!"); 
	
	$newDogExp = $data['DogExp'] + $dogSkill;
	if ($newDogExp >= $data['next_level'])
	{
		if ($newDogExp = $data['next_level']) {
			$newExp = $data['DogExp'] + $dogSkill - $data['next_level'] + 1;
		} else {
			$newExp = $data['DogExp'] + $dogSkill - $data['next_level'];
		}
		$newNextLevel = getNewNextLevel($data['next_level']);
		$newDogHealth = getNewDogHealth($data['DogHealth']);
		$newDogLevel = $data['DogLevel'] + 1;
	}
	else {
		$newExp = $newDogExp;
		$newNextLevel = $data['next_level'];
		$newDogLevel = $data['DogLevel'];
		$newDogHealth = $data['DogHealth'];
	}
//print_r($newExp); echo "<br>";	
//print_r($newNextLevel); echo "<br>";
//print_r($newDogLevel); echo "<br>";

	$newFightsAmount = $data['fights_amount'] + 1;
	$newLockFights = $data['lock_fights'] + 1;
//print_r($newLockFights); echo "<br>";
	//Check params and update their
	if ($result == '1')
	{
		//UPDATE table `users_fights`
		if (isset($data['wins']) && $data['wins'] != '') {
			$wins = $data['wins'] + 1;
			$query1 = "UPDATE `users_fights`
						SET `wins` = '{$wins}',
							`got_experience` = '{$dogSkill}',
							`got_coins` = '{$coins}',
							`fights_amount` = '{$newFightsAmount}',
							`lock_fights` = '{$newLockFights}'
						WHERE
							`user_id` = '{$uid}'	
					";
		} else {
			$wins = '1';
			$newFightsAmount = '1';
			$newLockFights = '1';
			$query1 = "INSERT INTO `users_fights`
						SET `user_id` = '{$uid}',
							`wins` = '{$wins}',
							`got_experience` = '{$dogSkill}',
							`got_coins` = '{$coins}',
							`fights_amount` = '{$newFightsAmount}',
							`lock_fights` = '{$newLockFights}'
					";
		}
		
		//UPDATE table `users_balance`
		if ($data != '') 
		{
			$newBalance = $data['balance'] + $coins;
			$query3 = "UPDATE `users_balance`
							SET 
								`users_balance`.`balance` = '{$newBalance}'
							WHERE
								`user_id` = '{$uid}'
						";
		}
	}
	else 
	{
		//UPDATE table `users_fights`
		if (isset($data['losses']) && $data['losses'] != '') {
			$losses = $data['losses'] + 1;
			$query1 = "UPDATE `users_fights`
						SET `losses` = '{$losses}',
							`got_experience` = '{$dogSkill}',
							`got_coins` = '{$coins}',
							`fights_amount` = '{$newFightsAmount}',
							`lock_fights` = '{$newLockFights}'
						WHERE
							`user_id` = '{$uid}'	
					";
		} else {
			$losses = '1';
			$newFightsAmount = '1';
			$newLockFights = '1';
			$query1 = "INSERT INTO `users_fights`
						SET `user_id` = '{$uid}',
							`losses` = '{$losses}',
							`got_experience` = '{$dogSkill}',
							`got_coins` = '{$coins}',
							`fights_amount` = '{$newFightsAmount}',
							`lock_fights` = '{$newLockFights}'
					";
		}
		$query3 = '';
		
	}
	/*UPDATE teble `users_dogs` and `user_fights`*/
	if ($data != '') 
	{
		$query2 = "UPDATE `users_dogs`
						SET `users_dogs`.`DogLevel` = '{$newDogLevel}',
							`users_dogs`.`DogExp` = '{$newExp}',
							`users_dogs`.`next_level` = '{$newNextLevel}',
							`users_dogs`.`DogHealth` = '{$newDogHealth}'
						WHERE
							`user_id` = '{$uid}'
					";
	}
	
//	print_r($query1); echo "<br>";
//	print_r($query2); echo "<br>";
//	print_r($query3); echo "<br>";
//exit;	
	if (($query1 != '' && $query2 != '')) {
	//	loger($query1);
		mysql_query($query1, $conn);
		if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	
	//	loger($query2);
		mysql_query($query2, $conn);
		if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);	
		
		if (isset($query3) && $query3 != ''){
		//	loger($query3);
			mysql_query($query3, $conn);
			if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);	
		}
		
		updateLockFights($uid);
		updateDopingItems($uid);
		
		return true;
	} 
	else {
		return false;
	}
	
}

/**
 * Set new DogLevel amount for next level
 *
 * @param int $curNextLevel
 * @return $newNextLevel
 */
function getNewNextLevel($curNextLevel)
{
	$newNextLevel = $curNextLevel + 50 + ($curNextLevel/10);
	return $newNextLevel;
}

/**
 * Set new health amount for next level
 *
 * @param int $curDogHealth
 * @return $newNextLevel
 */
function getNewDogHealth($curDogHealth)
{
	$newDogHealth = $curDogHealth + 50;
	return $newDogHealth;
}

/**
 * Update number of fights until the lock
 *
 * @param int $uid
 * @return true
 */
function updateLockFights($uid)
{
	Global $conn;
	
	$query = "SELECT `users_fights`.`lock_fights` 
              	FROM `users_fights` 
                WHERE `users_fights`.`user_id` = '{$uid}' 
			";
	
	$res = mysql_query($query);
//	loger($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_array($res);
	
	$lockTime = '150';
	if ($data['lock_fights'] == 5) 
	{
		$query = "UPDATE `users_fights`
              		SET `lock_fights` = '0',
              			`unlock_time` = DATE_ADD(NOW(), INTERVAL {$lockTime} MINUTE)
                	WHERE `users_fights`.`user_id` = '{$uid}' 
			";
	//	loger($query);
		mysql_query($query, $conn);
		if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	}
	
	return true;
}

/**
 * Checking the number of fights until the lock
 *
 */
function checkLockFights($uid)
{
	Global $conn;
/*	
	$uid= isset($_GET['user']) ? (int) urldecode($_GET['user']): '';
	if($uid == '' || $uid == '2147483647' || $uid == '0') printMsg(MSG_NOT_CORRECT_USER_ID,3);
*/	
	$query = "SELECT IF(NOW() <= DATE_SUB(`users_fights`.`unlock_time`, INTERVAL 1 HOUR), DATE_FORMAT( DATE_SUB( FROM_UNIXTIME( UNIX_TIMESTAMP(`users_fights`.`unlock_time`) - UNIX_TIMESTAMP(NOW()) ), INTERVAL 4 HOUR), '%H:%i:%s'), '') as unlocktime, `users_fights`.`lock_fights`
              	FROM `users_fights` 
                WHERE `users_fights`.`user_id` = (SELECT `users`.`id` FROM `users` WHERE `VKuser` = '{$uid}' ORDER by `users`.`id` LIMIT 1)
			";
	
	$res = mysql_query($query);
//	loger($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_array($res);
	
	if (isset($data['unlocktime']) && $data['unlocktime'] != '') 
	{
		$responce = "<result type='4'><time>{$data['unlocktime']}</time></result>";
		return $responce;
	} 
	else {
		if (isset($data['lock_fights']) && $data['lock_fights'] != '') {
			$leftFights = 5 - $data['lock_fights'];
		} else {
			$leftFights = '5';
		}
		$responce = "<leftfights>{$leftFights}</leftfights>";
		return $responce;
	}
}

function checkLockTrainings($uid)
{
	Global $conn;
	
	$query = "SELECT IF(NOW() <= DATE_SUB(`users_trainings`.`unlock_time`, INTERVAL 1 HOUR), DATE_FORMAT( DATE_SUB( FROM_UNIXTIME( UNIX_TIMESTAMP(`users_trainings`.`unlock_time`) - UNIX_TIMESTAMP(NOW()) ), INTERVAL 3 HOUR), '%H:%i:%s'), '') as unlocktime
				FROM `users_trainings` 
				WHERE `user_id` = (SELECT `users`.`id` FROM `users` WHERE `VKuser` = '{$uid}' ORDER by `users`.`id` LIMIT 1) 
			 ";
	$result = mysql_query($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_array($result);

	if (isset($data['unlocktime']) && $data['unlocktime'] != '')
	{
		printMsg(MSG_DOG_NOW_TRAINS.$data['unlocktime'],6);
	}
	else return true; 
}

/**
 * Buy one more fight
 *
 */
function buyFight()
{
	Global $conn;
	
	$uid = isset($_GET['user']) ? (int) urldecode($_GET['user']): ''; 
	if($uid == '' || $uid == '2147483647' || $uid == '0') printMsg(MSG_NOT_CORRECT_USER_ID,3);
//	loger('uid=' . $uid);
	
	$query = "SELECT `users_fights`.`user_id`, `users_fights`.`lock_fights`, `users_balance`.`balance`
              	FROM `users_fights`
              	INNER JOIN `users_balance` ON `users_balance`.`user_id` = `users_fights`.`user_id`
                WHERE `users_fights`.`user_id` = (SELECT `users`.`id` FROM `users` WHERE `users`.`VKuser` = '{$uid}' ORDER by `users`.`id` LIMIT 1) 
			";
	
	$res = mysql_query($query);
//	loger($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	$data = mysql_fetch_array($res);
	
	if ($data['lock_fights'] == 0) 
	{
		if ($data['balance'] < 100) printMsg(MSG_NOT_ENOUGH_MONEY_BUY_F,3);
		$newUserBalance = $data['balance'] - 100;
		$query = " UPDATE `users_balance`
              		SET `balance` = {$newUserBalance}
                	WHERE `users_balance`.`user_id` = '{$data['user_id']}'
                ";
		
		$newLockFights = '4';
		$query2 = "UPDATE `users_fights`
              		SET `lock_fights` = {$newLockFights},
              			`unlock_time` = '0'
                	WHERE `users_fights`.`user_id` = '{$data['user_id']}' 
			     ";
		
		
		
	//	loger($query);
		mysql_query($query, $conn);
		
	//	loger($query2);
		mysql_query($query2, $conn);
		
		if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
		
		$responce = printMsg(MSG_BUY_FIGHT_OK,5);
	}
}

function updateDopingItems($uid)
{
	Global $conn;
	$defaultExpireTime = '5';
	
	$query = "SELECT `used_items`.`id`, `used_items`.`amount`, `used_items`.`expire`
              	FROM `used_items`
                WHERE `used_items`.`expire` IS NOT NULL
                AND `used_items`.`amount` <> 0
                AND `used_items`.`user_id` = '{$uid}' 
			";
	
	$res = mysql_query($query);
//	loger($query);
	if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	
	while($data = mysql_fetch_array($res))
	{
		if ($data['id'] == '') return true;
		
		$newExpireTime = $data['expire'] - 1;
		$curItemAmount = $data['amount'];
		$newItemAmount = $curItemAmount - 1;
		
		if ($newExpireTime == '0') 
		{
			if ($curItemAmount > '1') {
				
				$queryUPD = "UPDATE `used_items`
								SET `amount` = '{$newItemAmount}',
									`expire` = '{$defaultExpireTime}'
								WHERE
									`id` = {$data['id']}
				";
			}
			if ($curItemAmount == '1') {
				$queryUPD = "DELETE FROM `used_items` WHERE	`id` = {$data['id']} ";
			}
		} 
		else 
		{
			$queryUPD = "UPDATE `used_items`
							SET `expire` = '{$newExpireTime}'
							WHERE `id` = {$data['id']}
				";
		}

	//	loger($queryUPD);
		mysql_query($queryUPD, $conn);
		if(mysql_error($conn)) trigger_error(MSG_PROBLEM_WITH_SQL);
	}
	return true;
}


/**
 * Stroke logic
 *
 * @param array $dog1
 * @param array $dog2
 * @param array $aBlow
 * @return array
 */
function stroke(Array &$dog1, Array &$dog2, Array &$aBlow) 
{
	$oldHealth = $dog2['health'];
	$stroke = rand(1, count($aBlow));
	$power = $dog1['power'] * rand($aBlow[$stroke]['min']*100, $aBlow[$stroke]['max']*100)/100; //рассчет силы удара

	/* рассчет коэфициента увеличивающего повреждения уставшей собаки */
	if ($dog2['fatigue'] >= 100) {
		$cf = 0.1;
	} else {
		$cf = 100/(100-$dog2['fatigue']);
	}

	$cv = rand(0.6*(100-$dog2['vitality']), 100)/100;

	$damage = 2*$power*$cf*$cv; //3 - просто коэфициент
//	$dog2['fatigue'] += $damage;
	
//	$damage = ($dog1['health']+$dog2['health'])/100*$power*$cf*$cv; //3 - ������ ����������
	$dog2['fatigue'] += (int)($damage/$oldHealth);
	
	$dog2['health'] -= $damage;
	
	if ($dog2['health'] < 0) $dog2['health'] = 0;

	return array(
		'stroke' => $stroke,
		'power' => $power,
		'damage' => round($damage),
		'health' => round($dog2['health']),
		'oldHealth' => round($oldHealth),
		'pDamage' => 100*$damage/($oldHealth+0.1). '%',
	);
}
/**
 * Enter description here...
 *
 * @param array $user
 * @param array $enemy
 * @return fight result
 */
function fightLogic(array $user, array $enemy)
{
	Global $conn;
	$responce = '';
//	$maxVitality = 6;
	$maxPDamage = '15'; //максимальный урон в процентах для контрудара
	
//	print_r($user); echo "<br>";
//	print_r($enemy); echo "<br>";
	
//	$maxVitality = max($user[5]+1,$enemy[5]+1);
    $maxVitality = min($user['endurance'],$enemy['endurance']);

/*
power <-> strength
skill <-> dexterity
fatigue <-> endurance
vitality <-> коэффициент контрудара
*/	
	
	$dog1 = array(
		'id' => $user['id'],
		'userID' => $user['VKuser'],
		'health' => $user['DogHealth'],
		'power' => $user['strength'],
		'skill' => $user['dexterity'],
		'vitality' => $user['endurance'],
		'fatigue' => 0,
		'coef'	=> $user[5]+1
	);

	$dog2 = array(
		'id' => $enemy['id'],
		'enemyID' => $enemy['VKuser'],
		'health' => $enemy['DogHealth'],
		'power' => $enemy['strength'],
		'skill' => $enemy['dexterity'],
		'vitality' => $enemy['endurance'],
		'fatigue' => 0,
		'coef'	=> $enemy[5]+1
	);

/*
	$dog1 = array(
		'health' => 70,
		'power' => 7,
		'skill' => 5,
		'vitality' => 6,
		'fatigue' => 0,
	);
	
	$dog2 = array(
		'health' => 70,
		'power' => 6,
		'skill' => 5,
		'vitality' => 7,
		'fatigue' => 0,
	);
*/
//print_r($maxVitality); echo "<br>";
//print_r($dog1); echo "<br>";
//print_r($dog2); echo "<br>";	
	
	$aBlow = array(
	1 => array(
		'name' => 'Бросок',
		'min' => 0.3,
		'max' => 0.5,
	),
	2 => array(
		'name' => 'Удар в корпус',
		'min' => 0.5,
		'max' => 0.65,
	),
	3 => array(
		'name' => 'Удар в голову',
		'min' => 0.8,
		'max' => 1,
	),
	4 => array(
		'name' => 'Укус лапы',
		'min' => 0.2,
		'max' => 0.5,
	),
	5 => array(
		'name' => 'Укус спины',
		'min' => 0.4,
		'max' => 0.7,
	),
	6 => array(
		'name' => 'Укус шеи',
		'min' => 0.8,
		'max' => 0.98,
	),
	7 => array(
		'name' => 'Супер удар',
		'min' => 1.1,
		'max' => 1.5,
	),
);

$aBattle = array();
$winId = null;
$i = 0;
while ($dog1['health'] > 0 || $dog2['health'] > 0) {

	$stroke = stroke($dog1, $dog2, $aBlow);
	$aBattle[$i][1] = $stroke; //результаты удара

	if ($dog2['health'] <= 0) { //если убили вторую собаку бой закончен
		$winId = $dog1['userID'];
		break;
	}

	if ($dog2['vitality'] >= $maxVitality && $stroke['pDamage'] < $maxPDamage && rand(1, 10) <= $dog2['coef']) {
		$stroke = stroke($dog2, $dog1, $aBlow);
		$aBattle[$i]['contr2'] = $stroke;
	}

	if ($dog1['health'] <= 0) { //если убили первую собаку бой закончен
		$winId = $dog2['enemyID'];
		break;
	}

	$stroke = stroke($dog2, $dog1, $aBlow);
	$aBattle[$i]['2'] = $stroke;

	if ($dog1['health'] <= 0) { //если убили первую собаку бой закончен
		$winId = $dog2['enemyID'];
		break;
	}

	if ($dog1['vitality'] >= $maxVitality && $stroke['pDamage'] < $maxPDamage && rand(1, 10) <= $dog1['coef']) {

		$stroke = stroke($dog1, $dog2, $aBlow);
		$aBattle[$i]['contr1'] = $stroke;
		$winId = $dog1['userID'];
	}
	$i++;
}



/* Вывод в формате xml */
/*
header('Content-Type: text/xml');
echo '<?xml version="1.0" encoding="UTF-8"?>';
*/
$responce = "<battle enemyID='{$enemy['VKuser']}'>";
foreach ($aBattle as $key => $stroke) {

	$responce .= '<block num="'.$key.'">'."\n";

	$responce .= "<dog id='{$user['VKuser']}'>";
	$responce .= '<stroke id="'.$stroke[1]['stroke'].'">'.$aBlow[$stroke[1]['stroke']]['name'].'</stroke>';
	$responce .= '<damage>'.$stroke[1]['damage'].'</damage>';
	$responce .= '<health>'.$stroke[1]['health'].'</health>';
//	$responce .= '<oldhealth>'.$stroke[1]['oldHealth'].'</oldhealth>';
	$responce .= '</dog>';

	if (isset($stroke['contr2'])) {
		$responce .= "<dog id='{$enemy['VKuser']}'>";
		$responce .= '<stroke id="'.$stroke['contr2']['stroke'].'">Контрудар '.$aBlow[$stroke['contr2']['stroke']]['name'].'</stroke>';
		$responce .= '<damage>'.$stroke['contr2']['damage'].'</damage>';
		$responce .= '<health>'.$stroke['contr2']['health'].'</health>';
//		$responce .= '<oldhealth>'.$stroke['contr2']['oldHealth'].'</oldhealth>';
		$responce .= '</dog>';
	}

	if (isset($stroke[2])) {
		$responce .= "<dog id='{$enemy['VKuser']}'>";
		$responce .= '<stroke id="'.$stroke[2]['stroke'].'">'.$aBlow[$stroke[2]['stroke']]['name'].'</stroke>';
		$responce .= '<damage>'.$stroke[2]['damage'].'</damage>';
		$responce .= '<health>'.$stroke[2]['health'].'</health>';
//		$responce .= '<oldhealth>'.$stroke[2]['oldHealth'].'</oldhealth>';
		$responce .= '</dog>';
	}

	if (isset($stroke['contr1'])) {
		$responce .= "<dog id='{$user['VKuser']}'>";
		$responce .= '<stroke id="'.$stroke['contr1']['stroke'].'">Контрудар '.$aBlow[$stroke['contr1']['stroke']]['name'].'</stroke>';
		$responce .= '<damage>'.$stroke['contr1']['damage'].'</damage>';
		$responce .= '<health>'.$stroke['contr1']['health'].'</health>';
//		$responce .= '<oldhealth>'.$stroke['contr1']['oldHealth'].'</oldhealth>';
		$responce .= '</dog>';
	}

	$responce .= '</block>'."\n";
}
$responce .= "<win>{$winId}</win>";
$responce .= '</battle>';

//echo $responce;
return $responce;

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
//    Global $fp;
//   fputs($fp, $msg . "\n");
}

?>
<?php

require_once("db_settings.php");
require_once("settings.php");
$host = DB_HOST;
$user = DB_LOGIN;
$pass = DB_PWD;
$db = DB_NAME;
set_exception_handler('ErrorHandler');
set_error_handler('ErrorHandler');\
$conn = @mysql_connect($host, $user, $pass);
if(!$conn) trigger_error(MSG_DB_NOT_CONNECT);
mysql_select_db($db);
mysql_query('SET NAMES UTF8');

$query = "SELECT `quantity` FROM `statistic` WHERE `id` = 0";
$res = mysql_query($query);
$res = mysql_fetch_assoc($res);
$quantity = $res['quantity'];


$queryCount = "SELECT COUNT(*) FROM `users`";
$count = mysql_fetch_row(mysql_query($queryCount, $conn));
$count = $count[0];

echo 'Number of visits: '.$quantity;
echo '<br>';
echo 'Number of users: '.$count;
?>
<?php

$lang = "en";

if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    setcookie('lang', $lang);
} else if (isset($_COOKIE['lang'])) {
    $lang = $_COOKIE['lang'];
}

if ($lang != "en" && $lang != "ru") {
    $lang = "en";
}

require 'server/fb-php-sdk/facebook.php';
require_once("db_settings.php");
require_once("settings.php");

$host          = DB_HOST;
$user          = DB_LOGIN;
$pass          = DB_PWD;
$db            = DB_NAME;
$app_id        = APP_ID;
$app_secret    = APP_SECRET;
$app_namespace = APP_NAMESPACE;


set_exception_handler('ErrorHandler');
set_error_handler('ErrorHandler');
$api_secret='';


$conn = @mysql_connect($host, $user, $pass);
if(!$conn) trigger_error(MSG_DB_NOT_CONNECT);
mysql_select_db($db);
mysql_query('SET NAMES UTF8');
//mysql_set_charset('utf8', $conn);

$query = "UPDATE `statistic` SET `quantity` = `quantity`+ 1 WHERE `id` = 0";
mysql_query($query);
// Production


$app_url = 'http://apps.facebook.com/' . $app_namespace . '/';
$scope = 'email';

// Init the Facebook SDK
$facebook = new Facebook(array(
    'appId'  => $app_id,
    'secret' => $app_secret,
));

   // Get the current user
$user = $facebook->getUser();

// If the user has not installed the app, redirect them to the Login Dialog

if (!$user) {
    $loginUrl = $facebook->getLoginUrl(array(
    'scope' => $scope,
    'redirect_uri' => $app_url,
    ));

    print('<script> top.location.href=\'' . $loginUrl . '\'</script>');
    echo '<img src="login.jpg" alt="Enter" width="100%"/>';
    die();
}


?>
<!DOCTYPE html>
<html>

<head>
    <title>DogFights</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div id="fb-root"></div>
    <script src="//connect.facebook.net/en_US/all.js"></script>
    <div class="container">
        <embed 
            src="./<? echo $lang ?>/DogFights.swf" 
            quality="high" 
            width="100%" 
            height="100%" 
            name="myFlashMovie" 
            FlashVars="<? if ($user > 0) echo "viewer_id=".$user."&"; ?>is_app_user=1&api_settings=262146" 
            align="middle" 
            allowScriptAccess="sameDomain" 
            allowFullScreen="false" 
            type="application/x-shockwave-flash" 
            pluginspage="http://www.adobe.com/go/getflash" 
        />
    </div>
    <div id="topbar">
        <center><a href="https://dogfightsgame.com/?lang=en">English</a> <a href="https://dogfightsgame.com/?lang=ru">Russian</a>
        </center>
    </div>


    <script>
        var appId = '<?php echo $facebook->getAppID() ?>';

        function print(msg) {
                alert(msg);
            }
            // Initialize the JS SDK
        FB.init({
            appId: appId,
            frictionlessRequests: true,
            cookie: true,
        });

        FB.getLoginStatus(function (response) {
            uid = response.authResponse.userID ? response.authResponse.userID : null;
        });

        //window.fbAsyncInit = function() {
        /*FB.init({
          appId      : appId,
          status     : true,
          cookie     : true,
          xfbml      : true
        });*/
        function invite() {
            FB.ui({
                method: 'apprequests',
                message: 'You should learn more about this awesome game.',
                data: 'tracking information for the user'
            });
        }

        function getSWF(movieName) {
            if (navigator.appName.indexOf("Microsoft") != -1) {
                return window[movieName];
            } else {
                return document[movieName];
            }
        }

        function requestCallback() {
            // print('requestCallback');
            getSWF("myFlashMovie").update(0);
        }

        function buy(num) {
            //alert(num);
            var obj = {
                method: 'pay',
                action: 'purchaseitem',
                product: 'http://dogfightsgame.com/coins_set.html',
                quantity: num,
                quantity_min: 1,
                quantity_max: 9999,
            };

            FB.ui(obj, requestCallback);
        }

        // Load the SDK Asynchronously
        (function (d) {
            var js, id = 'facebook-jssdk',
                ref = d.getElementsByTagName('script')[0];
            if (d.getElementById(id)) {
                return;
            }
            js = d.createElement('script');
            js.id = id;
            js.async = true;
            js.src = "//connect.facebook.net/en_US/all.js";
            ref.parentNode.insertBefore(js, ref);
        }(document));
    </script>

</body>

</html>
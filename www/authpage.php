<?php
/**
 * simplesamlphp-modules/toznyauth/www/authpage.php
 *
 * @package default
 */


require_once __DIR__ . "/../constants.php";

/**
 * Login page for TOZNY API.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
if (!isset($_REQUEST['ReturnTo'])) {
    throw new SimpleSAML_Error_Exception('Missing ReturnTo parameter.');
}

$returnTo = $_REQUEST['ReturnTo'];

session_start();

/*
 * The following piece of code would never be found in a real authentication page. Its
 * purpose in this example is to make this example safer in the case where the
 * administrator of * the IdP leaves the exampleauth-module enabled in a production
 * environment.
 *
 * What we do here is to extract the $state-array identifier, and check that it belongs to
 * the exampleauth:External process.
 */


if (!preg_match('@State=(.*)@', $returnTo, $matches)) {
    die('Invalid ReturnTo URL for this example.');
}


/*
 * The loadState-function will not return if the second parameter does not
 * match the parameter passed to saveState, so by now we know that we arrived here
 * through the exampleauth:External authentication page.
 */
$stateId = urldecode($matches[1]);
$state = SimpleSAML_Auth_State::loadState($stateId, 'toznyauth:External');


/*
 * This code handles the login response.
 */


$realm_key_id = $state['toznyauth:realm_key_id'];
$secret = $state['toznyauth:realm_secret_key'];
$api_url = $state['toznyauth:api_url'];


$userApi = new Tozny_Remote_User_API($realm_key_id, $api_url);
$siteApi = new Tozny_Remote_Realm_API($realm_key_id, $secret, $api_url);
$missingRealm = FALSE;

$noSetSession = FALSE;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!empty($_SESSION['tozny_session_id'])) {
        $check = $userApi->checkSessionStatus($_SESSION['tozny_session_id']);
        if (!empty($check['status']) && $check['status'] === 'pending') {
            //Pended too long.
        }
        if (!empty ($check['return']) && $check['return'] === 'error') {
            //Invalid login, give them a new code
        } else if (!empty($check['signature'])) {
            //Should be logged in
            $decoded = $siteApi->checkSigGetData($check);
            if ($decoded) {
                $user = $siteApi->userGet($decoded['user_id']);
                $_SESSION['uid'] = $decoded['user_id'];
                $_SESSION['user_meta'] = array();
                foreach ($user['meta'] as $key => $val) {
                    if (in_array(strtolower($key), ['secret_key'])) {
                        continue;
                    } else {
                        $_SESSION['user_meta'][$key] = $val;
                    }
                }

                header('Location: ' . $returnTo);
                exit();
            } else {
                SimpleSAML_Auth_State::throwException($state,
                        new SimpleSAML_Error_Exception('Unable to match payload signature with private key.'));
            }
        }
    } else {
        SimpleSAML_Auth_State::throwException($state,
                new SimpleSAML_Error_Exception('Expected a session_id in payload.'));
    }
}

/*
 * Fetch a login challenge, extract the session ID and the QR code
 *
 */
$challenge = $userApi->loginChallenge();

//Save the session ID for later when we receive the response.
$_SESSION['tozny_session_id'] = $challenge['session_id'];
$qrURL = $challenge['qr_url'];
$authUrl = "tozauth" . substr($api_url, strpos($api_url, ":"))
           . "?s=" . $challenge['session_id']
           . "&c=" . $challenge['challenge']
           . "&r=" . $challenge['realm_key_id'];

/*
 * If we get this far, we need to show the login page to the user.
 */
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!--<title>Authentication</title>-->



	<!-- tozny stuff -->
    <link rel="stylesheet" type="text/css" href="tozny.css" />
    <link href="https://s3-us-west-2.amazonaws.com/tozny/production/interface/tozny.css" rel="stylesheet" type="text/css" />

    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.js"></script>
    <script src="https://s3-us-west-2.amazonaws.com/tozny/production/interface/javascript/v2/jquery.tozny.js"></script>
<!--    <script src="--><?//= $api_url . 'interface/javascript/jquery.tozny.js'?><!--"></script>-->


<meta name="robots" content="noindex, nofollow" />


</head>
<body id="">

<div id="wrap">

<!--<div id="header">-->
<!--<div id="logo">-->
<!--<a style="text-decoration: none; color: white" href="/">-->
<!--<img src="tozny.png" class="logo" alt="Tozny Logo" />-->
<!--</a>-->
<!--</div>-->
<!--<div id="page_header">-->
<!--        	Authentication        </div>-->
<!--        <div class="clear"></div>-->
<!--	</div>-->

	
		<div id="content">




<!--<h2 class="main">Authentication</h2>-->

            <div id="qr_code_login"></div>
            <script type="text/javascript">
                $(document).ready(function() {

                    $('#qr_code_login').tozny({
                        'type': 'verify',
                        'style': 'box',
                        'button_theme': 'light',
                        'realm_key_id':'<?php echo $realm_key_id; ?>',
                        'session_id': '<?php echo $challenge['session_id']; ?>',
                        'qr_url': '<?php echo $qrURL; ?>',
                        'api_url': '<?= $api_url . 'index.php' ?>',
                        //'loading_image': '<?= $api_url ?>interface/javascript/images/loading.gif',
                        //'login_button_image': '<?= $api_url ?>interface/javascript/images/click-to-login-black.jpg',
                        'mobile_url': '<?= $challenge['mobile_url'] ?>',
                        'form_type': 'custom',
                        'form_id':'tozny-form',
                        'debug':false
                    });

                });
            </script>
<!--  LOGIN PART OF THE SITE  -->

<form method="post" action="?" id="tozny-form">
<input type="hidden" name="ReturnTo" value="<?php echo htmlspecialchars($returnTo); ?>">
<input type="hidden" name="realm_key_id" value="<?php echo htmlspecialchars($realm_key_id); ?>">
<input type="hidden" name="tozny_action" value="tozny_login">

</form>
<?php //echo "<a href=\"" . $authUrl . "\"><img src=\"logInWithBluetooth.png\"></a>" ?><!-- -->

<!--  END LOGIN PART OF THE SITE -->

<!--        <hr />-->
<!---->
<!--        Copyright &copy; 2013 <a href="http://tozny.com/">Tozny</a>-->
<!--        -->
<!--        <br style="clear: right" />-->
    
    </div><!-- #content -->

</div><!-- #wrap -->

</body>
</html>

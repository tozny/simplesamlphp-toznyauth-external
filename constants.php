<?php
/**
 * simplesamlphp-modules/seqrdauth/constants.php
 *
 * @package default
 */


# locate the tozny-client library on the include path.
$paths = explode(PATH_SEPARATOR, get_include_path());
$foundClient = false;
foreach ($paths as $path) {
    if (file_exists($path . '/ToznyRemoteUserAPI.php')) {
        $foundClient = true;
        break;
    }
}
# if we couldnt find it, add the /var/www/library/tozny_common directory exists and is readable, then add it to the include path.
if (!$foundClient) {
    if (!file_exists('/var/www/library/tozny_client/ToznyRemoteUserAPI.php')) {
        throw new Exception(sprintf("Could not locate Tozny Client library. Is it on the include path and readable? include_path: %s", get_include_path()));
    }
    set_include_path(get_include_path() . PATH_SEPARATOR . '/var/www/library/tozny_client');
}

require_once "ToznyRemoteUserAPI.php";
require_once "ToznyRemoteRealmAPI.php";

/*
 * TODO In reality, we would look up the priv/pub keys
 * from the site API, using the master key to authenticate.
 */

$secrets = array(
    'ROCKSTAR' => 'DEADBEEF2',
);


?>

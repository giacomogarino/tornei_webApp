<?php
require_once('../conf/security.php');
require_once '../conf/db_config.php';

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'access_type'   => 'online',
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit;
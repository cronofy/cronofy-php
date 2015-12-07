<?php
require_once('cronofy.php');
require_once('cronofy-usercreds.php');

session_start();

$state = $_GET['state'];
$code = $_GET['code'];

if((!empty($state)) && ($state!='#') && ($state != $_SESSION['state'])){
    throw new Exception('Error validating state.  Possible cross-site request forgery.');
}

$params = array(
	'redirect_uri' => $redirect_uri,
	'code' => $code
);

$cronofy = new Cronofy($client_id, $client_secret);

$token=$cronofy->request_token($client_id, $client_secret, $params);

if($token != true){
	echo $token;
}else{
	$_SESSION['access_token'] = $cronofy->access_token;
	$_SESSION['refresh_token'] = $cronofy->refresh_token;
}

header('Location: ' . $data_uri);

?>


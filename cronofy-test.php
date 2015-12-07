<?php
require_once('cronofy.php');
require_once('cronofy-usercreds.php');

session_start();

$cronofy = new Cronofy($client_id);

$params = array(
	'redirect_uri' => $redirect_uri,
	'scope' => array('read_account','list_calendars','read_events','create_event','delete_event')
);
$auth = $cronofy->getAuthorizationURL($client_id, $params);

var_dump($auth);
?>

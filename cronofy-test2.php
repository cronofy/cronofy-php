<?php
require_once('cronofy.php');
require_once('cronofy-usercreds.php');

session_start();

$access_token = $_SESSION['access_token'];
$refresh_token = $_SESSION['refresh_token'];


$cronofy = new Cronofy($client_id, $client_secret, $access_token, $refresh_token);

//$revoke = $cronofy->revoke_authorization($client_id, $client_secret, $access_token);

//$calendar = $cronofy->list_calendars();

/*$params = array(
	'tzid' => 'Europe/London',
);
$events = $cronofy->read_events($params);
var_dump($events);
*/
/*$params = array(
	'calendar_id' => 'cal_VmTnwPV9On8LACpZ_nI0xvz0g0hmyKHvKpxAWYA',
	'event_id' => 'event_test_12345679',
	'summary' => 'test event 2',
	'description' => 'some event data here',
	'start' => '2015-12-07T09:00:00Z',
	'end' => '2015-12-08T10:00:00Z'
	
);
$new_event = $cronofy->upsert_event($params);

var_dump($new_event);
*/

/*$params = array(
	'calendar_id' => 'cal_VmTnwPV9On8LACpZ_nI0xvz0g0hmyKHvKpxAWYA',
	'event_id' => 'evt_external_566561a8e7d68f801b8e8912'
);

$delete = $cronofy->delete_event($params);

var_dump($delete);
*/
?>
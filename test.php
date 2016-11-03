<?

include("cronofy.php");

if(true){
  $access_token = 'wiep4u9zB2TfCNlqqz0GtrFak0neTawX';

  $cronofy = new Cronofy('', '', $access_token, '');

  $calendars = $cronofy->list_channels();

  echo '<pre>' . var_export($calendars, true) . '</pre>';
}

if(false){
  $access_token = 'wiep4u9zB2TfCNlqqz0GtrFak0neTawX';

  $cronofy = new Cronofy('', '', $access_token, '');

  $cronofy->delete_event(array(
    'calendar_id' => 'cal_V@TGjY930wqwAAAB_ZUGnzTojVPsPwTkqcFePow',
    'event_id' => 'unique-event-id'
  ));

  echo '<pre>' . var_export($events, true) . '</pre>';
}

?>

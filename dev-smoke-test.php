<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Cronofy\Batch\Batch;
use Cronofy\Exception\CronofyException;
use Cronofy\Exception\PartialBatchFailureException;

$cronofy = new Cronofy\Cronofy([
  "client_id" => $_ENV["CLIENT_ID"],
  "client_secret" => $_ENV["CLIENT_SECRET"],
  "access_token" => $_ENV["ACCESS_TOKEN"],
  "refresh_token" => $_ENV["REFRESH_TOKEN"],
  "data_center" => $_ENV["DATACENTER"]
]);

$calendarId = $_ENV["CALENDAR_ID"];
$start = date("Y-m-d", strtotime('tomorrow')) . "T09:30:00Z";
$end   = date("Y-m-d", strtotime('tomorrow')) . "T10:00:00Z";

$testEventId = 'php-smoke-test-001';
$testEventData = [
  'calendar_id' => 'calendarID',
  'event_id' => $testEventId,
  'summary' => 'PHP SDK test event 001',
  'description' => 'Just checking this thing is on!',
  'start' => $start,
  'end' => $end,
];

$batch = Batch::create()
  ->upsertEvent($calendarId, $testEventData)
  ->deleteEvent($calendarId, $testEventId)
  ->deleteEvent("fake-calendar-id", "just-want-it-to-fail")
  ->upsertEvent($calendarId, []);

try {
  $result = $cronofy->executeBatch($batch);

} catch (PartialBatchFailureException $exception) {
  echo "PARTIAL FAILURE\n\n";
  $result = $exception->result();
} finally {
  foreach ($result->responses() as $index=>$response) {
    echo "Request " . $index . " - " . $response->request()->method() . " " . $response->request()->relativeUrl() . "\n";
    echo $response->hasSuccessStatus() ? "  Success" : "  Failed";
    echo "\n";
    echo "  status " . $response->status() . "\n";

    echo "  headers ";
    $headers = $response->headers();
    print_r($headers);
    echo "\n";

    echo "  data ";
    $data = $response->data();
    print_r($data);
    echo "\n\n";
  }
}



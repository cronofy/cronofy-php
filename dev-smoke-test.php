<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Cronofy\Batch\Batch;
use Cronofy\Exception\CronofyException;
use Cronofy\Exception\PartialBatchFailureException;

$testBatch = true;
$testAvailablePeriod = true;
$testRecurrence = true;
$testRTS = true;

$dataCenter = getenv("DATACENTER");

$cronofy = new Cronofy\Cronofy([
  "client_id" => getenv("CLIENT_ID"),
  "client_secret" => getenv("CLIENT_SECRET"),
  "access_token" => getenv("ACCESS_TOKEN"),
  "refresh_token" => getenv("REFRESH_TOKEN"),
  "data_center" => $dataCenter,
]);

$sub = getenv("SUB");
$calendarId = getenv("CALENDAR_ID");
$start = date("Y-m-d", strtotime('tomorrow')) . "T09:30:00Z";
$end   = date("Y-m-d", strtotime('tomorrow')) . "T10:00:00Z";

$yesterday = date("Y-m-d", strtotime('yesterday'));
$next_week = date("Y-m-d", strtotime('next week'));

$testEventId = 'php-smoke-test-001';
$testEventData = [
  'calendar_id' => $calendarId,
  'event_id' => $testEventId,
  'summary' => 'PHP SDK test event 001',
  'description' => 'Just checking this thing is on!',
  'start' => $start,
  'end' => $end,
];

echo "Writing test events to " . $dataCenter . ", account " . $sub . ", calendar " . $calendarId . "\n";

if ( $testBatch ) {
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
}

if( $testAvailablePeriod ) {
  echo "Creating AvailablePeriod\n";
  $ap_id = "test_available_period_001";

  $params = [
    "available_period_id" => $ap_id,
    "start" => $start,
    "end" => $end,
  ];

  $cronofy->createAvailablePeriod($params);

  echo "Reading Available Period\n";

  $readParams = [
    "from" => $yesterday,
    "to" => $next_week,
    "tzid" => "Europe/London",
  ];

  $periods = $cronofy->readAvailablePeriods($readParams);
  foreach($periods->each() as $available_period){
    print_r($available_period);
  }

  echo "\n";
  echo "Deleting Available Period\n";

  $params = [
    "available_period_id" => $ap_id,
  ];

  $result = $cronofy->deleteAvailablePeriod($params);
  print_r($result);

  $periods = $cronofy->readAvailablePeriods($readParams);
  foreach($periods->each() as $available_period){
    print_r($available_period);
  }
}

if( $testRecurrence ) {
  echo "\n";
  echo "Creating event with recurrence\n";

  $recurrenceEventParams = $testEventData;
  $recurrenceEventParams['recurrence'] = [
    "rules" => [
      [
        "frequency" => "daily",
        "interval" => 2,
        "count" => 3,
      ],
    ],
  ];

  $cronofy->upsertEvent($recurrenceEventParams);
  echo "\n";
}

if($testRTS){
  echo "Checking RTS\n";

  $event = [
    "event_id" => "php-smoke-test-002",
    "summary" => "Add to Calendar test event",
  ];

  $availability = [
    "participants" => [
      [
        "members" => [
          [
            "sub" => $sub,
            "calendar_ids" => [$calendarId]
          ]
        ],
        "required" => "all"
      ]
    ],
    "event" => $event,
    "required_duration" => [
      "minutes" => 60
    ],
    "available_periods" => [
      [
        "start" => $start,
        "end" => $end
      ]
    ]
  ];
  $target_calendars = [
    [
      "sub" => $sub,
      "calendar_id" => $calendarId
    ]
  ];
  $tzid = 'Europe/London';

  $params = [
    "event" => $event,
    "target_calendars" => $target_calendars,
    "availability" => $availability,
    "tzid" => $tzid,
    "callback_url" => "http://local.cronofy.com/callback",
    "oauth" => [
      "redirect_uri" => "http://local.cronofy.com/redirect"
    ],
    "redirect_urls" => [
      "completed_url" => "http://local.cronofy.com/complete",
    ],
    "formatting" => [
      "hour_format" => "H",
    ],
    "minimum_notice" => [
      "hours" => 2
    ],
    "event_creation" => "single",
  ];

  $cronofy->realTimeScheduling($params);
}

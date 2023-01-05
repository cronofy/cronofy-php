<?php
namespace Cronofy\Tests;

use Cronofy\Http\HttpRequest;
use PHPUnit\Framework\TestCase;
use Cronofy\Cronofy;

class UpsertEventTest extends TestCase
{
    public function testUpsertEvent()
    {
        $location = [
            "description" => "board room",
            "latitude" => "12.2344",
            "longitude" => "45.2444",
        ];

        $reminders = [
            ["minutes" => 30],
            ["minutes" => 1440]
        ];

        $attendees = [
            "invite" => [["email" => "new_invitee@test.com", "display_name" => "New Invitee"]],
            "reject" => [["email" => "old_invitee@test.com", "display_name" => "Old Invitee"]]
        ];

        $calendarId = "cal_123";

        $event = [
            "event_id" => "partner_event_id",
            "summary" => "Upsert Event Test",
            "description" => "description example",
            "start" => "2017-01-01T12:00:00Z",
            "end" => "2017-01-01T15:00:00Z",
            "tzid" => "Europe/London",
            "location" => $location,
            "reminders" => $reminders,
            "attendees" => $attendees,
            "event_private" => true,
            "reminders_create_only" => true,
            "transparency" => "opaque",
            "color" => "#c6040f",
            "conferencing" => [
                "profile_id" => "default"
            ],
            "locale" => "it",
            "event_classes" => "interview",
        ];

        $params = $event + ["calendar_id" => $calendarId];

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpPost')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/calendars/'.$calendarId.'/events'),
                $this->equalTo($event),
                $this->equalTo([
                    'Authorization: Bearer accessToken',
                    'Host: api.cronofy.com',
                    'Content-Type: application/json; charset=utf-8'
                ])
            )
            ->will($this->returnValue(["{'foo': 'bar'}", 200]));

        $cronofy = new Cronofy([
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ]);

        $actual = $cronofy->upsertEvent($params);
        $this->assertNotNull($actual);
    }

    public function testUpsertExternalEvent()
    {
        $location = [
            "description" => "board room",
            "latitude" => "12.2344",
            "longitude" => "45.2444",
        ];

        $reminders = [
            ["minutes" => 30],
            ["minutes" => 1440]
        ];

        $attendees = [
            "invite" => [["email" => "new_invitee@test.com", "display_name" => "New Invitee"]],
            "reject" => [["email" => "old_invitee@test.com", "display_name" => "Old Invitee"]]
        ];

        $calendarId = "cal_123";

        $event = [
            "event_uid" => "evt_external_22343948494",
            "summary" => "Upsert Event Test",
            "description" => "description example",
            "start" => "2017-01-01T12:00:00Z",
            "end" => "2017-01-01T15:00:00Z",
            "tzid" => "Europe/London",
            "location" => $location,
            "reminders" => $reminders,
            "attendees" => $attendees,
            "event_private" => true,
            "reminders_create_only" => true,
            "transparency" => "opaque",
        ];

        $params = $event + ["calendar_id" => $calendarId];

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpPost')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/calendars/'.$calendarId.'/events'),
                $this->equalTo($event),
                $this->equalTo([
                    'Authorization: Bearer accessToken',
                    'Host: api.cronofy.com',
                    'Content-Type: application/json; charset=utf-8'
                ])
            )
            ->will($this->returnValue(["{'foo': 'bar'}", 200]));

        $cronofy = new Cronofy([
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ]);

        $actual = $cronofy->upsertExternalEvent($params);
        $this->assertNotNull($actual);
    }
}

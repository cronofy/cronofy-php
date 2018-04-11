<?php
use PHPUnit\Framework\TestCase;

class UpsertEventTest extends TestCase
{
    public function testUpsertEvent()
    {
        $location = array(
            "description" => "board room",
            "latitude" => "12.2344",
            "longitude" => "45.2444",
        );

        $reminders = array(
            array("minutes" => 30),
            array("minutes" => 1440)
        );

        $attendees = array(
            "invite" => array(array("email" => "new_invitee@test.com", "display_name" => "New Invitee")),
            "reject" => array(array("email" => "old_invitee@test.com", "display_name" => "Old Invitee"))
        );

        $calendarId = "cal_123";

        $event = array(
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
        );

        $params = $event + array("calendar_id" => $calendarId);

        $http = $this->createMock('HttpRequest');
        $http->expects($this->once())
            ->method('http_post')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/calendars/'.$calendarId.'/events'),
                $this->equalTo($event),
                $this->equalTo(array(
                    'Authorization: Bearer accessToken',
                    'Host: api.cronofy.com',
                    'Content-Type: application/json; charset=utf-8'
                ))
            )
            ->will($this->returnValue(array("{'foo': 'bar'}", 200)));

        $cronofy = new Cronofy(array(
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ));

        $actual = $cronofy->upsert_event($params);
        $this->assertNotNull($actual);
    }

    public function testUpsertExternalEvent()
    {
        $location = array(
            "description" => "board room",
            "latitude" => "12.2344",
            "longitude" => "45.2444",
        );

        $reminders = array(
            array("minutes" => 30),
            array("minutes" => 1440)
        );

        $attendees = array(
            "invite" => array(array("email" => "new_invitee@test.com", "display_name" => "New Invitee")),
            "reject" => array(array("email" => "old_invitee@test.com", "display_name" => "Old Invitee"))
        );

        $calendarId = "cal_123";

        $event = array(
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
        );

        $params = $event + array("calendar_id" => $calendarId);

        $http = $this->createMock('HttpRequest');
        $http->expects($this->once())
            ->method('http_post')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/calendars/'.$calendarId.'/events'),
                $this->equalTo($event),
                $this->equalTo(array(
                    'Authorization: Bearer accessToken',
                    'Host: api.cronofy.com',
                    'Content-Type: application/json; charset=utf-8'
                ))
            )
            ->will($this->returnValue(array("{'foo': 'bar'}", 200)));

        $cronofy = new Cronofy(array(
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ));

        $actual = $cronofy->upsert_external_event($params);
        $this->assertNotNull($actual);
    }
}
?>

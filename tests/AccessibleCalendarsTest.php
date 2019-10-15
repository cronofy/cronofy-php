<?php
use PHPUnit\Framework\TestCase;

class AccessibleCalendarsTest extends TestCase
{
    public function testListAccessibleCalendars()
    {
        $application_calendar_id = "foo";

        $accessible_calendars_response = '{
            "accessible_calendars": [
                {
                    "calendar_type": "resource",
                    "email": "board-room-london@example.com",
                    "name": "Board room (London)"
                },
                {
                    "calendar_type": "unknown",
                    "email": "jane.doe@example.com",
                    "name": "Jane Doe"
                },
                {
                    "calendar_type": "unknown",
                    "email": "alpha.team@example.com",
                    "name": "Alpha Team"
                }
            ]
        }';

        $http = $this->createMock('HttpRequest');
        $http->expects($this->once())
            ->method('http_get')
            ->with(
                'https://api.cronofy.com/v1/accessible_calendars?'
                    . http_build_query(array('profile_id' => $profileId = 'profile-id')),
                array(
                    'Authorization: Bearer accessToken',
                    'Host: api.cronofy.com',
                )
            )
            ->willReturn(array($accessible_calendars_response, 200));

        $cronofy = new Cronofy(array(
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ));

        $cronofy->list_accessible_calendars($profileId);
    }
}

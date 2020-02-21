<?php
namespace Cronofy\Tests;

use Cronofy\Http\HttpRequest;
use PHPUnit\Framework\TestCase;
use Cronofy\Cronofy;

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

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpGet')
            ->with(
                'https://api.cronofy.com/v1/accessible_calendars?'
                    . http_build_query(['profile_id' => $profileId = 'profile-id']),
                [
                    'Authorization: Bearer accessToken',
                    'Host: api.cronofy.com',
                ]
            )
            ->willReturn([$accessible_calendars_response, 200]);

        $cronofy = new Cronofy([
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ]);

        $accesibleCalendars = $cronofy->listAccessibleCalendars($profileId);

        $this->assertEquals(
            json_decode($accessible_calendars_response, true),
            $accesibleCalendars
        );
    }
}

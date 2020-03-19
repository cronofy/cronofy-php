<?php
namespace Cronofy\Tests;

use Cronofy\Http\HttpRequest;
use PHPUnit\Framework\TestCase;
use Cronofy\Cronofy;

class ApplicationCalendarTest extends TestCase
{
    public function testCreateApplicationCalendar()
    {
        $application_calendar_id = "foo";

        $request_params = [
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "application_calendar_id" => $application_calendar_id,
        ];

        $application_calendar_response = '{
        "token_type":"bearer",
            "access_token":"fffff",
            "expires_in":3600,
            "refresh_token":"2222",
            "scope":"read_write",
            "application_calendar_id":"my-unique-string",
            "sub":"apc_567236000909002",
            "linking_profile":{
            "provider_name":"cronofy",
                "profile_id":"pro_n23kjnwrw2",
                "profile_name":"n23kjnwrw2"
            }
        }';

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpPost')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/application_calendars'),
                $this->equalTo($request_params),
                $this->equalTo([
                    'Host: api.cronofy.com',
                    'Content-Type: application/json; charset=utf-8'
                ])
            )
            ->will($this->returnValue([$application_calendar_response, 200]));

        $cronofy = new Cronofy([
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "http_client" => $http,
        ]);


        $actual = $cronofy->applicationCalendar($application_calendar_id);

        $this->assertEquals($actual['sub'], "apc_567236000909002");
        $this->assertEquals($cronofy->accessToken, "fffff");
        $this->assertEquals($cronofy->refreshToken, "2222");
        $this->assertEquals($cronofy->expiresIn, 3600);
    }
}

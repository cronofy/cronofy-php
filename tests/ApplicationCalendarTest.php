<?php
use PHPUnit\Framework\TestCase;

class ApplicationCalendarTest extends TestCase
{
    public function testCreateApplicationCalendar()
    {
        $application_calendar_id = "foo";

        $request_params = array(
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "application_calendar_id" => $application_calendar_id,
        );

        $token_response = '{
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

        $http = $this->createMock('HttpRequest');
        $http->expects($this->once())
            ->method('http_post')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/application_calendar'),
                $this->equalTo($request_params),
                $this->equalTo(array(
                    'Host: api.cronofy.com',
                    'Content-Type: application/json; charset=utf-8'
                ))
            )
            ->will($this->returnValue(array($token_response, 200)));

        $cronofy = new Cronofy(array(
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "http_client" => $http,
        ));


        $actual = $cronofy->application_calendar($application_calendar_id);
        $this->assertTrue($actual);
        $this->assertEquals($cronofy->access_token, "fffff");
        $this->assertEquals($cronofy->refresh_token, "2222");
        $this->assertEquals($cronofy->expires_in, 3600);
        $this->assertEquals($cronofy->tokens, json_decode($token_response, true));
    }
}

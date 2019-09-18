<?php
use PHPUnit\Framework\TestCase;

class SchedulingTest extends TestCase
{
    public function testAvailability()
    {
        $parsedParams = array(
            "available_periods" => "PERIODS",
            "participants" => "PARTICIPANTS",
            "required_duration" => "DURATION",
            "response_format" => "FORMAT"
        );
        
        $http = $this->createMock('HttpRequest');
        $http->expects($this->once())
            ->method('http_post')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/availability'),
                $this->equalTo($parsedParams)
            )
            ->will($this->returnValue(array(json_encode($parsedParams), 200)));

        $cronofy = new Cronofy(array(
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ));

        $params = array(
            "participants" => "PARTICIPANTS",
            "available_periods" => "PERIODS",
            "required_duration" => "DURATION",
            "response_format" => "FORMAT"
        );
        
        $response = $cronofy->availability($params);
        $this->assertNotNull($response);
    }

    public function testRealTimeScheduling()
    {
        $oauth = array(
            "redirect_uri" => "http://test.com/",
            "scope" => "test_scope"
        );
        $event = array(
            "event_id" => "test_event_id",
            "summary" => "Add to Calendar test event",
        );
        $availability = array(
            "participants" => array(
                array(
                    "members" => array(
                        array(
                            "sub" => "acc_567236000909002",
                            "calendar_ids" => array("cal_n23kjnwrw2_jsdfjksn234")
                        )
                    ),
                    "required" => "all"
                )
            ),
            "required_duration" => array(
                "minutes" => 60
            ),
            "start_interval" => array(
                "minutes" => 60
            ),
            "buffer" => array(
                "before" => array(
                    "minutes" => 60
                )
            ),
            "available_periods" => array(
                array(
                    "start" => "2017-01-01T09:00:00Z",
                    "end" => "2017-01-01T17:00:00Z"
                )
            )
        );
        $target_calendars = array(
            array(
                "sub" => "acc_567236000909002",
                "calendar_id" => "cal_n23kjnwrw2_jsdfjksn234"
            )
        );
        $tzid = 'Europe/London';

        $params = array(
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "event" => $event,
            "target_calendars" => $target_calendars,
            "availability" => $availability,
            "oauth" => $oauth,
            "tzid" => $tzid,
        );

        $http = $this->createMock('HttpRequest');
        $http->expects($this->once())
            ->method('http_post')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/real_time_scheduling'),
                $this->equalTo($params),
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

        $actual = $cronofy->real_time_scheduling($params);
        $this->assertNotNull($actual);
    }

    public function testRealTimeSequencing()
    {
        $oauth = array(
            "redirect_uri" => "http://test.com/",
            "scope" => "test_scope"
        );
        $event = array(
            "event_id" => "test_event_id",
            "summary" => "Add to Calendar test event",
        );
        $availability = array(
            "sequence" => array(
                array(
                    "sequence_id" => "123",
                    "ordinal" => 1,
                    "participants" => array(
                        array(
                            "members" => array(
                                array(
                                    "sub" => "acc_567236000909002",
                                    "calendar_ids" => array("cal_n23kjnwrw2_jsdfjksn234")
                                )
                            ),
                            "required" => "all"
                        )
                    ),
                    "event" => $event,
                    "required_duration" => array(
                        "minutes" => 60
                    ),
                ),
            ),
            "available_periods" => array(
                array(
                    "start" => "2017-01-01T09:00:00Z",
                    "end" => "2017-01-01T17:00:00Z"
                )
            )
        );
        $target_calendars = array(
            array(
                "sub" => "acc_567236000909002",
                "calendar_id" => "cal_n23kjnwrw2_jsdfjksn234"
            )
        );
        $tzid = 'Europe/London';

        $params = array(
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "event" => $event,
            "target_calendars" => $target_calendars,
            "availability" => $availability,
            "oauth" => $oauth,
            "tzid" => $tzid,
        );

        $http = $this->createMock('HttpRequest');
        $http->expects($this->once())
            ->method('http_post')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/real_time_sequencing'),
                $this->equalTo($params),
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

        $actual = $cronofy->real_time_sequencing($params);
        $this->assertNotNull($actual);
    }
}

<?php
namespace Cronofy\Tests;

use Cronofy\Http\HttpRequest;
use PHPUnit\Framework\TestCase;
use Cronofy\Cronofy;

class SchedulingTest extends TestCase
{
    public function testAvailability()
    {
        $parsedParams = [
            "available_periods" => "PERIODS",
            "participants" => "PARTICIPANTS",
            "required_duration" => "DURATION",
            "response_format" => "FORMAT"
        ];

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpPost')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/availability'),
                $this->equalTo($parsedParams)
            )
            ->will($this->returnValue([json_encode($parsedParams), 200]));

        $cronofy = new Cronofy([
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ]);

        $params = [
            "participants" => "PARTICIPANTS",
            "available_periods" => "PERIODS",
            "required_duration" => "DURATION",
            "response_format" => "FORMAT"
        ];

        $response = $cronofy->availability($params);
        $this->assertNotNull($response);
    }

    public function testRealTimeScheduling()
    {
        $oauth = [
            "redirect_uri" => "http://test.com/",
            "scope" => "test_scope"
        ];
        $event = [
            "event_id" => "test_event_id",
            "summary" => "Add to Calendar test event",
        ];
        $availability = [
            "participants" => [
                [
                    "members" => [
                        [
                            "sub" => "acc_567236000909002",
                            "calendar_ids" => ["cal_n23kjnwrw2_jsdfjksn234"]
                        ]
                    ],
                    "required" => "all"
                ]
            ],
            "required_duration" => [
                "minutes" => 60
            ],
            "start_interval" => [
                "minutes" => 60
            ],
            "buffer" => [
                "before" => [
                    "minutes" => 60
                ]
            ],
            "available_periods" => [
                [
                    "start" => "2017-01-01T09:00:00Z",
                    "end" => "2017-01-01T17:00:00Z"
                ]
            ]
        ];
        $target_calendars = [
            [
                "sub" => "acc_567236000909002",
                "calendar_id" => "cal_n23kjnwrw2_jsdfjksn234"
            ]
        ];
        $tzid = 'Europe/London';

        $params = [
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "event" => $event,
            "target_calendars" => $target_calendars,
            "availability" => $availability,
            "oauth" => $oauth,
            "tzid" => $tzid,
        ];

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpPost')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/real_time_scheduling'),
                $this->equalTo($params),
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

        $actual = $cronofy->realTimeScheduling($params);
        $this->assertNotNull($actual);
    }

    public function testRealTimeSequencing()
    {
        $oauth = [
            "redirect_uri" => "http://test.com/",
            "scope" => "test_scope"
        ];
        $event = [
            "event_id" => "test_event_id",
            "summary" => "Add to Calendar test event",
        ];
        $availability = [
            "sequence" => [
                [
                    "sequence_id" => "123",
                    "ordinal" => 1,
                    "participants" => [
                        [
                            "members" => [
                                [
                                    "sub" => "acc_567236000909002",
                                    "calendar_ids" => ["cal_n23kjnwrw2_jsdfjksn234"]
                                ]
                            ],
                            "required" => "all"
                        ]
                    ],
                    "event" => $event,
                    "required_duration" => [
                        "minutes" => 60
                    ],
                ],
            ],
            "available_periods" => [
                [
                    "start" => "2017-01-01T09:00:00Z",
                    "end" => "2017-01-01T17:00:00Z"
                ]
            ]
        ];
        $target_calendars = [
            [
                "sub" => "acc_567236000909002",
                "calendar_id" => "cal_n23kjnwrw2_jsdfjksn234"
            ]
        ];
        $tzid = 'Europe/London';

        $params = [
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "event" => $event,
            "target_calendars" => $target_calendars,
            "availability" => $availability,
            "oauth" => $oauth,
            "tzid" => $tzid,
        ];

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpPost')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/real_time_sequencing'),
                $this->equalTo($params),
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

        $actual = $cronofy->realTimeSequencing($params);
        $this->assertNotNull($actual);
    }
}

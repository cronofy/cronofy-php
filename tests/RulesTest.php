<?php
namespace Cronofy\Tests;

use Cronofy\Http\HttpRequest;
use PHPUnit\Framework\TestCase;
use Cronofy\Cronofy;

class RulesTest extends TestCase
{
    public function testGetAvailabilityRule()
    {
        $expected_rule = [
            "availability_rule" => [
                "availability_rule_id" => "default",
                "tzid" => "Etc/UTC",
                "weekly_periods" => [
                    [
                        "day" => "monday",
                        "start_time" => "09:00",
                        "end_time" => "13:00"
                    ],
                    [
                        "day" => "monday",
                        "start_time" => "14:00",
                        "end_time" => "17:00"
                    ],
                    [
                        "day" => "tuesday",
                        "start_time" => "09:00",
                        "end_time" => "17:00"
                    ]
                ]
            ]
        ];
        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpGet')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/availability_rules/default')
            )
            ->will($this->returnValue([json_encode($expected_rule), 200]));

        $cronofy = new Cronofy([
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ]);

        $response = $cronofy->getAvailabilityRule("default");

        $this->assertNotNull($response);
        $this->assertEquals(3, count($response['availability_rule']));
        $this->assertEquals("default", $response['availability_rule']['availability_rule_id']);
    }

    public function testListAvailabilityRules()
    {
        $expected_rules = [
            "availability_rules" => [
                [
                    "availability_rule_id" => "default",
                    "calendar_ids" => ["cal_123"],
                    "tzid" => "Etc/UTC",
                    "weekly_periods" => [
                        [
                            "day" => "monday",
                            "start_time" => "09:00",
                            "end_time" => "13:00"
                        ],
                        [
                            "day" => "monday",
                            "start_time" => "14:00",
                            "end_time" => "17:00"
                        ],
                        [
                            "day" => "tuesday",
                            "start_time" => "09:00",
                            "end_time" => "17:00"
                        ]
                    ]
                ],
                [
                    "availability_rule_id" => "work_hours",
                    "calendar_ids" => ["cal_321"],
                    "tzid" => "Etc/UTC",
                    "weekly_periods" => [
                        [
                            "day" => "tuesday",
                            "start_time" => "09:00",
                            "end_time" => "17:00"
                        ],
                        [
                            "day" => "wednesday",
                            "start_time" => "09:00",
                            "end_time" => "17:00"
                        ]
                    ]
                ],
            ]
        ];
        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpGet')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/availability_rules')
            )
            ->will($this->returnValue([json_encode($expected_rules), 200]));

        $cronofy = new Cronofy([
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ]);

        $response = $cronofy->listAvailabilityRules();

        $this->assertNotNull($response);
        $this->assertEquals(2, count($response['availability_rules']));
        $this->assertEquals(4, count($response['availability_rules'][0]));
        $this->assertEquals("default", $response['availability_rules'][0]['availability_rule_id']);
        $this->assertEquals("work_hours", $response['availability_rules'][1]['availability_rule_id']);
    }

    public function testDeleteAvailabilityRules()
    {

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpDelete')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/availability_rules/rule_123')
            )
            ->will($this->returnValue(["{'foo': 'bar'}", 200]));

        $cronofy = new Cronofy([
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ]);

        $actual = $cronofy->deleteAvailabilityRule("rule_123");
        $this->assertNotNull($actual);
    }

    public function testCreateAvailabilityRule()
    {
        $expected_output = [
            "availability_rule_id" => "default",
            "calendar_ids" => ["cal_123"],
            "tzid" => "America/Chicago",
            "weekly_periods" => [
                [
                    "day" => "monday",
                    "start_time" => "09:30",
                    "end_time" => "12:30"
                ],
                [
                    "day" => "wednesday",
                    "start_time" => "09:30",
                    "end_time" => "12:30"
                ]
            ]
        ];

        $params = [
            "availability_rule_id" => "default",
            "calendar_ids" => ["cal_123"],
            "tzid" => "America/Chicago",
            "weekly_periods" => [
                [
                    "day" => "monday",
                    "start_time" => "09:30",
                    "end_time" => "12:30"
                ],
                [
                    "day" => "wednesday",
                    "start_time" => "09:30",
                    "end_time" => "12:30"
                ]
            ]
        ];

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpPost')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/availability_rules'),
                $this->equalTo($params)
            )
            ->will($this->returnValue([json_encode($expected_output), 200]));

        $cronofy = new Cronofy([
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ]);

        $response = $cronofy->createAvailabilityRule($params);

        $this->assertNotNull($response);
        $this->assertEquals(4, count($response));
        $this->assertEquals("default", $response['availability_rule_id']);
        $this->assertEquals(2, count($response['weekly_periods']));
    }
}

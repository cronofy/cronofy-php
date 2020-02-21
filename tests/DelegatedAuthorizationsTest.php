<?php
namespace Cronofy\Tests;

use Cronofy\Exception\CronofyException;
use Cronofy\Http\HttpRequest;
use PHPUnit\Framework\TestCase;
use Cronofy\Cronofy;

class DelegatedAuthorizationsTest extends TestCase
{
    public function testDelegatedAuthorizations()
    {
        $profileId = "profileId";
        $email = "emailOfAccountToAccess";
        $callback_url = "http://www.example.com/callback";
        $scopes = ["list_calendars", "read_free_busy"];
        $state = "user-state";

        $args = [
            "profile_id" => $profileId,
            "email" => $email,
            "callback_url" => $callback_url,
            "scope" => $scopes,
            "state" => $state
        ];

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpPost')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/delegated_authorizations'),
                $this->equalTo([
                    "profile_id" => $profileId,
                    "email" => $email,
                    "callback_url" => $callback_url,
                    "scope" => "list_calendars read_free_busy",
                    "state" => $state
                ]),
                $this->equalTo([
                    'Authorization: Bearer accessToken',
                    'Host: api.cronofy.com',
                    'Content-Type: application/json; charset=utf-8',
                ])
            )
            ->will($this->returnValue(["", 202]));

        $cronofy = new Cronofy([
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ]);

        $cronofy->requestDelegatedAuthorization($args);
    }

    public function testErrorHandling()
    {
        $profileId = "profileId";
        $email = "emailOfAccountToAccess";
        $callback_url = "http://www.example.com/callback";
        $scopes = ["list_calendars", "read_free_busy"];
        $state = "user-state";

        $args = [
            "profile_id" => $profileId,
            "email" => $email,
            "callback_url" => $callback_url,
            "scope" => $scopes,
            "state" => $state
        ];

        $errorResponse = '{
            "errors": {
                "email": [
                    {
                        "key": "errors.required",
                        "description": "required"
                    }
                ]
            }
        }';

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpPost')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/delegated_authorizations'),
                $this->equalTo([
                    "profile_id" => $profileId,
                    "email" => $email,
                    "callback_url" => $callback_url,
                    "scope" => "list_calendars read_free_busy",
                    "state" => $state
                ]),
                $this->equalTo([
                    'Authorization: Bearer accessToken',
                    'Host: api.cronofy.com',
                    'Content-Type: application/json; charset=utf-8',
                ])
            )
            ->will($this->returnValue([$errorResponse, 422]));

        $cronofy = new Cronofy([
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ]);

        $raised_error = false;

        try {
            $cronofy->requestDelegatedAuthorization($args);
        } catch (CronofyException $exception) {
            $raised_error = true;
            $this->assertEquals(json_decode($errorResponse, true), $exception->error_details());
            $this->assertEquals(422, $exception->getCode());
        }

        $this->assertTrue($raised_error);
    }
}

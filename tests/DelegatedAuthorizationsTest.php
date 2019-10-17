<?php
use PHPUnit\Framework\TestCase;

class DelegatedAuthorizationsTest extends TestCase
{
    public function testDelegatedAuthorizations()
    {
        $profileId = "profileId";
        $email = "emailOfAccountToAccess";
        $callback_url = "http://www.example.com/callback";
        $scopes = ["list_calendars", "read_free_busy"];
        $state = "user-state";

        $args = array(
            "profile_id" => $profileId,
            "email" => $email,
            "callback_url" => $callback_url,
            "scope" => $scopes,
            "state" => $state
        );

        $http = $this->createMock('HttpRequest');
        $http->expects($this->once())
            ->method('http_post')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/delegated_authorizations'),
                $this->equalTo([
                    "profile_id" => $profileId,
                    "email" => $email,
                    "callback_url" => $callback_url,
                    "scope" => "list_calendars read_free_busy",
                    "state" => $state
                ]),
                $this->equalTo(array(
                    'Authorization: Bearer accessToken',
                    'Host: api.cronofy.com',
                    'Content-Type: application/json; charset=utf-8',
                ))
            )
            ->will($this->returnValue(array("", 202)));

        $cronofy = new Cronofy(array(
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ));

        $cronofy->request_delegated_authorization($args);
    }

    public function testErrorHandling()
    {
        $profileId = "profileId";
        $email = "emailOfAccountToAccess";
        $callback_url = "http://www.example.com/callback";
        $scopes = ["read_events"];
        $state = "user-state";

        $args = array(
            "profile_id" => $profileId,
            "email" => $email,
            "callback_url" => $callback_url,
            "scope" => $scopes,
            "state" => $state
        );

        $error_response = '{
            "errors": {
                "email": [
                    {
                        "key": "errors.required",
                        "description": "required"
                    }
                ]
            }
        }';

        $http = $this->createMock('HttpRequest');
        $http->expects($this->once())
            ->method('http_post')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/delegated_authorizations'),
                $this->equalTo($args),
                $this->equalTo(array(
                    'Authorization: Bearer accessToken',
                    'Host: api.cronofy.com',
                    'Content-Type: application/json; charset=utf-8',
                ))
            )
            ->will($this->returnValue(array($error_response, 422)));

        $cronofy = new Cronofy(array(
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ));

        $raised_error = false;

        try {
            $cronofy->request_delegated_authorization($args);
        } catch (CronofyException $exception) {
            $raised_error = true;
            $this->assertEquals(json_decode($error_response, true), $exception->error_details());
            $this->assertEquals(422, $exception->getCode());
        }

        $this->assertTrue($raised_error);
    }
}

<?php
namespace Cronofy\Tests;

use Cronofy\Exception\CronofyException;
use Cronofy\Http\HttpRequest;
use PHPUnit\Framework\TestCase;
use Cronofy\Cronofy;

class CronofyTest extends TestCase
{
    public function testAuthorizationUrl()
    {
        $redirect_uri = "http://yoursite.dev/oauth2/callback";

        $cronofy = new Cronofy(["client_id" => "clientId"]);
        $params = [
            'redirect_uri' => $redirect_uri,
            'scope' => ['read_account','list_calendars']
        ];
        $auth = $cronofy->getAuthorizationURL($params);

        $this->assertEquals("https://app.cronofy.com/oauth/authorize?response_type=code&client_id=clientId"
            . "&redirect_uri=http%3A%2F%2Fyoursite.dev%2Foauth2%2Fcallback&scope=read_account%20list_calendars", $auth);
    }

    public function testDelegatedScopeInAuthorizationUrl()
    {
        $redirect_uri = "http://yoursite.dev/oauth2/callback";

        $cronofy = new Cronofy(["client_id" => "clientId"]);
        $params = [
            'redirect_uri' => $redirect_uri,
            'scope' => ['read_account','list_calendars'],
            'delegated_scope' => ['create_calendar', 'read_free_busy']
        ];
        $auth = $cronofy->getAuthorizationURL($params);

        $this->assertEquals("https://app.cronofy.com/oauth/authorize?response_type=code&client_id=clientId"
            . "&redirect_uri=http%3A%2F%2Fyoursite.dev%2Foauth2%2Fcallback"
            . "&scope=read_account%20list_calendars&delegated_scope=create_calendar%20read_free_busy", $auth);
    }

    public function testProviderNameInAuthorizationUrl()
    {
        $redirect_uri = "http://yoursite.dev/oauth2/callback";

        $cronofy = new Cronofy(["client_id" => "clientId"]);
        $params = [
            'redirect_uri' => $redirect_uri,
            'scope' => ['read_account','list_calendars'],
            'provider_name' => 'office365'
        ];
        $auth = $cronofy->getAuthorizationURL($params);

        $this->assertEquals("https://app.cronofy.com/oauth/authorize?response_type=code&client_id=clientId"
            . "&redirect_uri=http%3A%2F%2Fyoursite.dev%2Foauth2%2Fcallback&scope=read_account%20list_calendars&provider_name=office365", $auth);
    }

    public function testErrorHandling()
    {
        $args = [
            "profile_id" => "pro_123",
            "name" => "My Calendar",
        ];

        $error_response = '{"errors":{"event_id":[{"key":"errors.required","description":"required"}]}}';

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpPost')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/calendars'),
                $this->equalTo($args),
                $this->equalTo([
                    'Authorization: Bearer accessToken',
                    'Host: api.cronofy.com',
                    'Content-Type: application/json; charset=utf-8',
                ])
            )
            ->will($this->returnValue([$error_response, 422]));

        $cronofy = new Cronofy([
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ]);

        $raised_error = false;

        try {
            $cronofy->createCalendar($args);
        } catch (CronofyException $exception) {
            $raised_error = true;
            $this->assertEquals(json_decode($error_response, true), $exception->error_details());
            $this->assertEquals(422, $exception->getCode());
        }

        $this->assertTrue($raised_error);
    }

    public function testRequestToken()
    {
        $request_params = [
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "grant_type" => "authorization_code",
            "code" => "MY_SECRET_CODE",
            "redirect_uri" => "http://example.com",
        ];

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

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpPost')
            ->with(
                $this->equalTo('https://api.cronofy.com/oauth/token'),
                $this->equalTo($request_params),
                $this->equalTo([
                    'Host: api.cronofy.com',
                    'Content-Type: application/json; charset=utf-8'
                ])
            )
            ->will($this->returnValue([$token_response, 200]));

        $cronofy = new Cronofy([
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "http_client" => $http,
        ]);

        $args = [
            "code" => "MY_SECRET_CODE",
            "redirect_uri" => "http://example.com",
        ];

        $actual = $cronofy->requestToken($args);
        $this->assertTrue($actual);
        $this->assertEquals($cronofy->accessToken, "fffff");
        $this->assertEquals($cronofy->refreshToken, "2222");
        $this->assertEquals($cronofy->expiresIn, 3600);
        $this->assertEquals($cronofy->tokens, json_decode($token_response, true));
    }

    public function testGetAccount()
    {
        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpGet')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/account'),
                $this->equalTo([
                    'Authorization: Bearer accessToken',
                    'Host: api.cronofy.com'
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

        $actual = $cronofy->getAccount();
        $this->assertNotNull($actual);
    }

    public function testDeleteEvent()
    {
        $params = ["event_id" => "evt_456"];

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpDelete')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/calendars/cal_123/events'),
                $this->equalTo($params),
                $this->equalTo([
                    'Authorization: Bearer accessToken',
                    'Host: api.cronofy.com',
                    'Content-Type: application/json; charset=utf-8',
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

        $actual = $cronofy->deleteEvent([
            "calendar_id" => "cal_123",
            "event_id" => "evt_456",
        ]);
        $this->assertNotNull($actual);
    }

    public function testDeleteExternalEvent()
    {
        $params = ["event_uid" => "evt_456"];

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpDelete')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/calendars/cal_123/events'),
                $this->equalTo($params),
                $this->equalTo([
                    'Authorization: Bearer accessToken',
                    'Host: api.cronofy.com',
                    'Content-Type: application/json; charset=utf-8',
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

        $actual = $cronofy->deleteExternalEvent([
            "calendar_id" => "cal_123",
            "event_uid" => "evt_456",
        ]);
        $this->assertNotNull($actual);
    }

    public function testFreeBusy()
    {
        $page_1 = '{
          "pages": {
            "current": 1,
            "total": 1,
          },
          "events": [
            {
              "calendar_id": "cal_U9uuErStTG@EAAAB_IsAsykA2DBTWqQTf-f0kJw",
              "event_uid": "evt_external_event_one",
              "summary": "Company Retreat"
            }
          ]
        }';

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->at(0))
            ->method('getPage')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/free_busy'),
                $this->equalTo([
                    'Authorization: Bearer accessToken',
                    'Host: api.cronofy.com'
                ]),
                "?localized_times=true"
            )
            ->will($this->returnValue([$page_1, 200]));

        $cronofy = new Cronofy([
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ]);

        $params = [ "localized_times" => true ];
        $actual = $cronofy->freeBusy($params);
        $this->assertNotNull($actual);
    }

    public function testGetSmartInvite()
    {
        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpGet')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/smart_invites?smart_invite_id=foo&recipient_email=foo%40example.com'),
                $this->equalTo([
                    'Authorization: Bearer clientSecret',
                    'Host: api.cronofy.com'
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

        $actual = $cronofy->getSmartInvite("foo", "foo@example.com");
        $this->assertNotNull($actual);
    }

    public function testCancelSmartInvite()
    {
        $recipient = ["email" => "example@example.com"];
        $smart_invite_id = "foo";

        $request_params = [
          "method" => "cancel",
          "recipient" => $recipient,
          "smart_invite_id" => $smart_invite_id,
        ];

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpPost')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/smart_invites'),
                $this->equalTo($request_params),
                $this->equalTo([
                    'Authorization: Bearer clientSecret',
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

        $params = [
          "recipient" => $recipient,
          "smart_invite_id" => $smart_invite_id,
        ];

        $actual = $cronofy->cancelSmartInvite($params);
        $this->assertNotNull($actual);
    }

    public function testCancelSmartInviteWithMultipleRecipients()
    {
        $recipients = array(
            array("email" => "example@example.com"),
            array("email" => "example@example.org"),
        );
        $smart_invite_id = "foo";

        $request_params = array(
          "method" => "cancel",
          "recipients" => $recipients,
          "smart_invite_id" => $smart_invite_id,
        );

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpPost')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/smart_invites'),
                $this->equalTo($request_params),
                $this->equalTo(array(
                    'Authorization: Bearer clientSecret',
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

        $params = array(
          "recipients" => $recipients,
          "smart_invite_id" => $smart_invite_id,
        );

        $actual = $cronofy->cancelSmartInvite($params);
        $this->assertNotNull($actual);
    }

    public function testCreateSmartInvite()
    {
        $event = [
            "summary" => "Add to Calendar test event",
            "start" => "2017-01-01T12:00:00Z",
            "end" => "2017-01-01T15:00:00Z"
        ];
        $recipient = ["email" => "example@example.com"];
        $organizer = ["name" => "Smart invite application"];
        $smart_invite_id = "foo";
        $callback_url = "http://www.example.com/callback";

        $params = [
          "recipient" => $recipient,
          "event" => $event,
          "smart_invite_id" => $smart_invite_id,
          "callback_url" => $callback_url,
          "organizer" => $organizer,
        ];

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpPost')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/smart_invites'),
                $this->equalTo($params),
                $this->equalTo([
                    'Authorization: Bearer clientSecret',
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

        $actual = $cronofy->createSmartInvite($params);
        $this->assertNotNull($actual);
    }

    public function testCreateSmartInviteWithMultipleRecipients()
    {
        $event = [
            "summary" => "Add to Calendar test event",
            "start" => "2017-01-01T12:00:00Z",
            "end" => "2017-01-01T15:00:00Z"
        ];
        $organizer = ["name" => "Smart invite application"];
        $smart_invite_id = "foo";
        $callback_url = "http://www.example.com/callback";

        $params = [
            "recipients" => [
                ["email" => "example@example.com"],
                ["email" => "example@example.org"],
            ],
            "event" => $event,
            "smart_invite_id" => $smart_invite_id,
            "callback_url" => $callback_url,
            "organizer" => $organizer,
        ];

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpPost')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/smart_invites'),
                $this->equalTo($params),
                $this->equalTo([
                    'Authorization: Bearer clientSecret',
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

        $actual = $cronofy->createSmartInvite($params);
        $this->assertNotNull($actual);
    }

    public function testRequestElementToken()
    {
        $params = [
            "version" => "1",
            "permissions" => ["agenda", "availability"],
            'subs' => ['acc_12345678'],
            "origin" => 'http://local.test'
        ];

        $response = [
            "element_token" =>  [
                "permissions" => ["agenda", "availability"],
                "origin" => 'http://local.test',
                "token" => "ELEMENT_TOKEN",
                "expires_in" => 64800
            ]
        ];

        $http = $this->createMock(HttpRequest::class);
        $http->expects($this->once())
            ->method('httpPost')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/element_tokens'),
                $this->equalTo($params),
                $this->equalTo([
                    'Authorization: Bearer clientSecret',
                    'Host: api.cronofy.com',
                    'Content-Type: application/json; charset=utf-8'
                ])
            )
            ->will($this->returnValue([json_encode($response), 200]))
        ;

        $cronofy = new Cronofy([
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ]);

        $actual = $cronofy->requestElementToken($params);
        $this->assertNotNull($actual);
    }

}

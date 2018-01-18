<?php
use PHPUnit\Framework\TestCase;

class CronofyTest extends TestCase
{

    public function testAuthorizationUrl()
    {
        $redirect_uri = "http://yoursite.dev/oauth2/callback";

        $cronofy = new Cronofy(array("client_id" => "clientId"));
        $params = array(
            'redirect_uri' => $redirect_uri,
            'scope' => array('read_account','list_calendars')
        );
        $auth = $cronofy->getAuthorizationURL($params);

        $this->assertEquals("https://app.cronofy.com/oauth/authorize?response_type=code&client_id=clientId&redirect_uri=http%3A%2F%2Fyoursite.dev%2Foauth2%2Fcallback&scope=read_account%20list_calendars", $auth);
    }

    public function testGetAccount()
    {
        $http = $this->createMock('HttpRequest');
        $http->expects($this->once())
            ->method('http_get')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/account'),
                $this->equalTo(array(
                    'Authorization: Bearer accessToken',
                    'Host: api.cronofy.com'
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

        $actual = $cronofy->get_account();
        $this->assertNotNull($actual);
    }

    public function testDeleteEvent()
    {
        $params = array("event_id" => "evt_456");

        $http = $this->createMock('HttpRequest');
        $http->expects($this->once())
            ->method('http_delete')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/calendars/cal_123/events'),
                $this->equalTo($params),
                $this->equalTo(array(
                    'Authorization: Bearer accessToken',
                    'Host: api.cronofy.com',
                    'Content-Type: application/json; charset=utf-8',
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

        $actual = $cronofy->delete_event(array(
            "calendar_id" => "cal_123",
            "event_id" => "evt_456",
        ));
        $this->assertNotNull($actual);
    }

    public function testGetSmartInvite()
    {
        $http = $this->createMock('HttpRequest');
        $http->expects($this->once())
            ->method('http_get')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/smart_invites?smart_invite_id=foo&recipient_email=foo%40example.com'),
                $this->equalTo(array(
                    'Authorization: Bearer clientSecret',
                    'Host: api.cronofy.com'
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

        $actual = $cronofy->get_smart_invite("foo", "foo@example.com");
        $this->assertNotNull($actual);
    }

    public function testCancelSmartInvite()
    {
        $recipient = array("email" => "example@example.com");
        $smart_invite_id = "foo";

        $request_params = array(
          "method" => "cancel",
          "recipient" => $recipient,
          "smart_invite_id" => $smart_invite_id,
        );

        $http = $this->createMock('HttpRequest');
        $http->expects($this->once())
            ->method('http_post')
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
          "recipient" => $recipient,
          "smart_invite_id" => $smart_invite_id,
        );

        $actual = $cronofy->cancel_smart_invite($params);
        $this->assertNotNull($actual);
    }

    public function testCreateSmartInvite()
    {
        $event = array(
            "summary" => "Add to Calendar test event",
            "start" => "2017-01-01T12:00:00Z",
            "end" => "2017-01-01T15:00:00Z"
        );
        $recipient = array("email" => "example@example.com");
        $smart_invite_id = "foo";
        $callback_url = "http://www.example.com/callback";

        $params = array(
          "recipient" => $recipient,
          "event" => $event,
          "smart_invite_id" => $smart_invite_id,
          "callback_url" => $callback_url
        );

        $http = $this->createMock('HttpRequest');
        $http->expects($this->once())
            ->method('http_post')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/smart_invites'),
                $this->equalTo($params),
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

        $actual = $cronofy->create_smart_invite($params);
        $this->assertNotNull($actual);
    }
}
?>

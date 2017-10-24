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

        $this->assertEquals("https://app.cronofy.com/oauth/authorize?response_type=code&client_id=clientId&redirect_uri=http%3A%2F%2Fyoursite.dev%2Foauth2%2Fcallback&scope=read_account list_calendars", $auth);
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
}
?>
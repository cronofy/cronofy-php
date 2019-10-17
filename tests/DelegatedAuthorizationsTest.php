<?php
use PHPUnit\Framework\TestCase;

class DelegatedAuthorizationsTest extends TestCase
{
    public function testDelegatedAuthorizations()
    {
        $args = array(
            // No for now.
        );

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
            ->will($this->returnValue(array("", 202)));

        $cronofy = new Cronofy(array(
            "client_id" => "clientId",
            "client_secret" => "clientSecret",
            "access_token" => "accessToken",
            "refresh_token" => "refreshToken",
            "http_client" => $http,
        ));

        $cronofy->request_delegated_authorization();
    }
}

<?php
use PHPUnit\Framework\TestCase;

class UpsertEventsTest extends TestCase
{
    public function testCreatePartnerEvent()
    {
        $calendar_id = "cal_123";
        $params = array(
            "event_id" => "event_id_123",
            "summary" => "Upsert Event Test",
            "description" => "Example description",
            "start" => "2017-01-01T12:00:00Z",
            "start" => "2017-01-01T12:00:00Z",
            "end" => "2017-01-01T15:00:00Z"
        );

        $http = $this->createMock('HttpRequest');
        $http->expects($this->once())
            ->method('http_post')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/calendars/'.$calendar_id.'/events'),
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

        $upsert_params = array_merge($params, array("calendar_id" => $calendar_id));

        $actual = $cronofy->upsert_event($upsert_params);
        $this->assertNotNull($actual);
    }

    public function testUpsertExternalEvent()
    {
        $calendar_id = "cal_123";
        $params = array(
            "event_uid" => "event_id_123",
            "summary" => "Upsert Event Test",
            "description" => "Example description",
            "start" => "2017-01-01T12:00:00Z",
            "start" => "2017-01-01T12:00:00Z",
            "end" => "2017-01-01T15:00:00Z"
        );

        $http = $this->createMock('HttpRequest');
        $http->expects($this->once())
            ->method('http_post')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/calendars/'.$calendar_id.'/events'),
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

        $upsert_params = array_merge($params, array("calendar_id" => $calendar_id));

        $actual = $cronofy->upsert_external_event($upsert_params);
        $this->assertNotNull($actual);
    }
}
?>

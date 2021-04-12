<?php declare(strict_types=1);

namespace Cronofy\Tests;

use Cronofy\Batch\BatchBuilder;
use Cronofy\Batch\BatchRequest;
use Cronofy\Batch\BatchResponse;
use Cronofy\Batch\BatchResult;
use Cronofy\Cronofy;
use Cronofy\Exception\PartialBatchFailureException;
use Cronofy\Http\HttpRequest;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class BatchTest extends TestCase
{
    /** @var HttpRequest|PHPUnit_Framework_MockObject_MockObject */
    private $httpClient;

    /** @var Cronofy */
    private $cronofy;

    protected function setUp()
    {
        parent::setUp();

        $this->httpClient = $this->createMock(HttpRequest::class);

        $this->cronofy = new Cronofy([
            'client_id' => 'clientId',
            'client_secret' => 'clientSecret',
            'access_token' => 'accessToken',
            'refresh_token' => 'refreshToken',
            'http_client' => $this->httpClient,
        ]);
    }

    public function testUpsertEvent()
    {
        $calendarId = 'calendar_id';
        $data = $this->getUpsertEventData();

        $expectedRequestMethod = 'POST';
        $expectedRequestRelativeUrl = sprintf('/v1/calendars/%s/events', $calendarId);
        $expectedRequestData = $data;

        $expectedRequests = [
            [
                'method' => $expectedRequestMethod,
                'relative_url' => $expectedRequestRelativeUrl,
                'data' => $expectedRequestData,
            ],
        ];

        $expectedResponseStatus = 202;

        $mockResponses = [
            [
                'status' => $expectedResponseStatus,
            ],
        ];

        $this->makeBatchRequestExpectation($expectedRequests, $mockResponses);

        $batch = BatchBuilder::create()->upsertEvent($calendarId, $data);
        $result = $this->cronofy->batch($batch);

        $this->assertInstanceOf(BatchResult::class, $result);

        $responses = $result->responses();

        $this->assertCount(1, $responses);

        $response = $responses[0];

        $this->assertEquals($expectedResponseStatus, $response->status());
        $this->assertNull($response->headers());
        $this->assertNull($response->data());

        $request = $response->request();

        $this->assertInstanceOf(BatchRequest::class, $request);
        $this->assertEquals($expectedRequestMethod, $request->method());
        $this->assertEquals($expectedRequestRelativeUrl, $request->relativeUrl());
        $this->assertEquals($expectedRequestData, $request->data());
    }

    public function testUpdateExternalEvent()
    {
        $calendarId = 'calendar_id';
        $data = $this->getUpdateExternalEventData();

        $expectedRequestMethod = 'POST';
        $expectedRequestRelativeUrl = sprintf('/v1/calendars/%s/events', $calendarId);
        $expectedRequestData = $data;

        $expectedRequests = [
            [
                'method' => $expectedRequestMethod,
                'relative_url' => $expectedRequestRelativeUrl,
                'data' => $expectedRequestData,
            ],
        ];

        $expectedResponseStatus = 202;

        $mockResponses = [
            [
                'status' => $expectedResponseStatus,
            ],
        ];

        $this->makeBatchRequestExpectation($expectedRequests, $mockResponses);

        $batch = BatchBuilder::create()->updateExternalEvent($calendarId, $data);
        $result = $this->cronofy->batch($batch);

        $this->assertInstanceOf(BatchResult::class, $result);

        $responses = $result->responses();

        $this->assertCount(1, $responses);

        $response = $responses[0];

        $this->assertEquals($expectedResponseStatus, $response->status());
        $this->assertNull($response->headers());
        $this->assertNull($response->data());

        $request = $response->request();

        $this->assertInstanceOf(BatchRequest::class, $request);
        $this->assertEquals($expectedRequestMethod, $request->method());
        $this->assertEquals($expectedRequestRelativeUrl, $request->relativeUrl());
        $this->assertEquals($expectedRequestData, $request->data());
    }

    public function testDeleteEvent()
    {
        $calendarId = 'calendar_id';
        $eventId = 'event_id';

        $expectedRequestMethod = 'DELETE';
        $expectedRequestRelativeUrl = sprintf('/v1/calendars/%s/events', $calendarId);
        $expectedRequestData = [
            'event_id' => $eventId,
        ];

        $expectedRequests = [
            [
                'method' => $expectedRequestMethod,
                'relative_url' => $expectedRequestRelativeUrl,
                'data' => $expectedRequestData,
            ],
        ];

        $expectedResponseStatus = 204;

        $mockResponses = [
            [
                'status' => $expectedResponseStatus,
            ],
        ];

        $this->makeBatchRequestExpectation($expectedRequests, $mockResponses);

        $batch = BatchBuilder::create()->deleteEvent($calendarId, $eventId);
        $result = $this->cronofy->batch($batch);

        $this->assertInstanceOf(BatchResult::class, $result);

        $responses = $result->responses();

        $this->assertCount(1, $responses);

        $response = $responses[0];

        $this->assertEquals($expectedResponseStatus, $response->status());
        $this->assertNull($response->headers());
        $this->assertNull($response->data());

        $request = $response->request();

        $this->assertInstanceOf(BatchRequest::class, $request);
        $this->assertEquals($expectedRequestMethod, $request->method());
        $this->assertEquals($expectedRequestRelativeUrl, $request->relativeUrl());
        $this->assertEquals($expectedRequestData, $request->data());
    }

    public function testDeleteExternalEvent()
    {
        $calendarId = 'calendar_id';
        $externalEventId = 'external_event_id';

        $expectedRequestMethod = 'DELETE';
        $expectedRequestRelativeUrl = sprintf('/v1/calendars/%s/events', $calendarId);
        $expectedRequestData = [
            'event_uid' => $externalEventId,
        ];

        $expectedRequests = [
            [
                'method' => $expectedRequestMethod,
                'relative_url' => $expectedRequestRelativeUrl,
                'data' => $expectedRequestData,
            ],
        ];

        $expectedResponseStatus = 204;
        $expectedResponseHeaders = [
            'Header' => 'Value',
        ];

        $mockResponses = [
            [
                'status' => $expectedResponseStatus,
                'headers' => $expectedResponseHeaders,
            ],
        ];

        $this->makeBatchRequestExpectation($expectedRequests, $mockResponses);

        $batch = BatchBuilder::create()->deleteExternalEvent($calendarId, $externalEventId);
        $result = $this->cronofy->batch($batch);

        $this->assertInstanceOf(BatchResult::class, $result);

        $responses = $result->responses();

        $this->assertCount(1, $responses);

        $response = $responses[0];

        $this->assertEquals($expectedResponseStatus, $response->status());
        $this->assertEquals($expectedResponseHeaders, $response->headers());
        $this->assertNull($response->data());

        $request = $response->request();

        $this->assertInstanceOf(BatchRequest::class, $request);
        $this->assertEquals($expectedRequestMethod, $request->method());
        $this->assertEquals($expectedRequestRelativeUrl, $request->relativeUrl());
        $this->assertEquals($expectedRequestData, $request->data());
    }

    public function testUpsertAvailablePeriod()
    {
        $data = $this->getUpsertAvailablePeriodData();

        $expectedRequestMethod = 'POST';
        $expectedRequestRelativeUrl = '/v1/available_periods';
        $expectedRequestData = $data;

        $expectedRequests = [
            [
                'method' => $expectedRequestMethod,
                'relative_url' => $expectedRequestRelativeUrl,
                'data' => $expectedRequestData,
            ],
        ];

        $expectedResponseStatus = 202;

        $mockResponses = [
            [
                'status' => $expectedResponseStatus,
            ],
        ];

        $this->makeBatchRequestExpectation($expectedRequests, $mockResponses);

        $batch = BatchBuilder::create()->upsertAvailablePeriod($data);
        $result = $this->cronofy->batch($batch);

        $this->assertInstanceOf(BatchResult::class, $result);

        $responses = $result->responses();

        $this->assertCount(1, $responses);

        $response = $responses[0];

        $this->assertEquals($expectedResponseStatus, $response->status());
        $this->assertNull($response->headers());
        $this->assertNull($response->data());

        $request = $response->request();

        $this->assertInstanceOf(BatchRequest::class, $request);
        $this->assertEquals($expectedRequestMethod, $request->method());
        $this->assertEquals($expectedRequestRelativeUrl, $request->relativeUrl());
        $this->assertEquals($expectedRequestData, $request->data());
    }

    public function testDeleteAvailablePeriod()
    {
        $availablePeriodId = 'available_period_id';

        $expectedRequestMethod = 'DELETE';
        $expectedRequestRelativeUrl = '/v1/available_periods';
        $expectedRequestData = [
            'available_period_id' => $availablePeriodId,
        ];

        $expectedRequests = [
            [
                'method' => $expectedRequestMethod,
                'relative_url' => $expectedRequestRelativeUrl,
                'data' => $expectedRequestData,
            ],
        ];

        $expectedResponseStatus = 204;

        $mockResponses = [
            [
                'status' => $expectedResponseStatus,
            ],
        ];

        $this->makeBatchRequestExpectation($expectedRequests, $mockResponses);

        $batch = BatchBuilder::create()->deleteAvailablePeriod($availablePeriodId);
        $result = $this->cronofy->batch($batch);

        $this->assertInstanceOf(BatchResult::class, $result);

        $responses = $result->responses();

        $this->assertCount(1, $responses);

        $response = $responses[0];

        $this->assertEquals($expectedResponseStatus, $response->status());
        $this->assertNull($response->headers());
        $this->assertNull($response->data());

        $request = $response->request();

        $this->assertInstanceOf(BatchRequest::class, $request);
        $this->assertEquals($expectedRequestMethod, $request->method());
        $this->assertEquals($expectedRequestRelativeUrl, $request->relativeUrl());
        $this->assertEquals($expectedRequestData, $request->data());
    }

    public function testPartialBatchFailure()
    {
        $data = $this->getUpsertAvailablePeriodData();
        unset($data['start']);

        $expectedRequestMethod = 'POST';
        $expectedRequestRelativeUrl = '/v1/available_periods';
        $expectedRequestData = $data;

        $expectedRequests = [
            [
                'method' => $expectedRequestMethod,
                'relative_url' => $expectedRequestRelativeUrl,
                'data' => $expectedRequestData,
            ],
        ];

        $expectedResponseStatus = 422;
        $expectedResponseData = [
            'errors' => [
                'start' => [
                    [
                        'key' => 'errors.required',
                        'description' => 'start must be specified',
                    ],
                ],
            ],
        ];

        $mockResponses = [
            [
                'status' => $expectedResponseStatus,
                'data' => $expectedResponseData,
            ],
        ];

        $this->makeBatchRequestExpectation($expectedRequests, $mockResponses);

        $batch = BatchBuilder::create()->upsertAvailablePeriod($data);

        try {
            $this->cronofy->batch($batch);
        } catch (PartialBatchFailureException $exception) {
            $result = $exception->result();

            $this->assertInstanceOf(BatchResult::class, $result);
            $this->assertTrue($result->hasErrors());

            $errors = $result->errors();

            $this->assertCount(1, $errors);

            $error = $errors[0];

            $this->assertInstanceOf(BatchResponse::class, $error);
            $this->assertEquals($expectedResponseStatus, $error->status());
            $this->assertNull($error->headers());
            $this->assertEquals($expectedResponseData, $error->data());

            $request = $error->request();

            $this->assertInstanceOf(BatchRequest::class, $request);
            $this->assertEquals($expectedRequestMethod, $request->method());
            $this->assertEquals($expectedRequestRelativeUrl, $request->relativeUrl());
            $this->assertEquals($expectedRequestData, $request->data());

            return;
        }

        $this->fail(sprintf('Expected exception of type "%s" to be thrown.', PartialBatchFailureException::class));
    }

    private function makeBatchRequestExpectation(array $requests, array $responses): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('httpPost')
            ->with(
                $this->equalTo('https://api.cronofy.com/v1/batch'),
                $this->equalTo([
                    'batch' => $requests,
                ])
            )
            ->will(
                $this->returnValue([
                    json_encode([
                        'batch' => $responses,
                    ]),
                    207,
                ])
            );
    }

    private function getUpsertEventData(): array
    {
        return array_merge(
            ['event_id' => 'event_id'],
            $this->getBaseEventData()
        );
    }

    private function getUpdateExternalEventData(): array
    {
        return array_merge(
            ['event_uid' => 'external_event_id'],
            $this->getBaseEventData()
        );
    }

    private function getBaseEventData(): array
    {
        return [
            'summary' => 'Upsert Event Test',
            'description' => 'description example',
            'start' => '2017-01-01T12:00:00Z',
            'end' => '2017-01-01T15:00:00Z',
            'tzid' => 'Europe/London',
            'location' => [
                'description' => 'board room',
                'latitude' => '12.2344',
                'longitude' => '45.2444',
            ],
            'reminders' => [
                ['minutes' => 30],
                ['minutes' => 1440],
            ],
            'attendees' => [
                'invite' => [
                    ['email' => 'new_invitee@test.com', 'display_name' => 'New Invitee'],
                ],
                'reject' => [
                    ['email' => 'old_invitee@test.com', 'display_name' => 'Old Invitee'],
                ],
            ],
            'event_private' => true,
            'reminders_create_only' => true,
            'transparency' => 'opaque',
            'color' => '#c6040f',
            'conferencing' => [
                'profile_id' => 'default',
            ],
        ];
    }

    private function getUpsertAvailablePeriodData(): array
    {
        return [
            'available_period_id' => 'available_period_id',
            'start' => '2021-04-14T15:30:00Z',
            'end' => '2021-04-14T17:00:00Z',
        ];
    }
}

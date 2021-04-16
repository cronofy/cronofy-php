<?php declare(strict_types=1);

namespace Cronofy\Batch;

use Cronofy\Cronofy;

class Batch
{
    /** @var BatchRequest[] */
    private $requests = [];

    public static function create(): self
    {
        return new self();
    }

    public function upsertEvent(string $calendarId, array $data): self
    {
        $path = $this->buildEventsPath($calendarId);

        return $this->addPostRequest($path, $data);
    }

    public function deleteEvent(string $calendarId, string $eventId): self
    {
        $path = $this->buildEventsPath($calendarId);
        $data = ['event_id' => $eventId];

        return $this->addDeleteRequest($path, $data);
    }

    public function updateExternalEvent(string $calendarId, array $data): self
    {
        $path = $this->buildEventsPath($calendarId);

        return $this->addPostRequest($path, $data);
    }

    public function deleteExternalEvent(string $calendarId, string $eventUid): self
    {
        $path = $this->buildEventsPath($calendarId);
        $data = ['event_uid' => $eventUid];

        return $this->addDeleteRequest($path, $data);
    }

    public function upsertAvailablePeriod(array $data): self
    {
        $path = $this->buildAvailablePeriodsPath();

        return $this->addPostRequest($path, $data);
    }

    public function deleteAvailablePeriod(string $availablePeriodId): self
    {
        $path = $this->buildAvailablePeriodsPath();
        $data = ['available_period_id' => $availablePeriodId];

        return $this->addDeleteRequest($path, $data);
    }

    private function buildEventsPath(string $calendarId): string
    {
        return sprintf('/%s/calendars/%s/events', Cronofy::API_VERSION, $calendarId);
    }

    private function buildAvailablePeriodsPath(): string
    {
        return sprintf('/%s/available_periods', Cronofy::API_VERSION);
    }

    private function addPostRequest(string $relativeUrl, array $data): self
    {
        return $this->addRequest('POST', $relativeUrl, $data);
    }

    private function addDeleteRequest(string $relativeUrl, array $data): self
    {
        return $this->addRequest('DELETE', $relativeUrl, $data);
    }

    private function addRequest(string $method, string $relativeUrl, array $data): self
    {
        $this->requests[] = new BatchRequest($method, $relativeUrl, $data);

        return $this;
    }

    public function requests(): array
    {
        return $this->requests;
    }
}

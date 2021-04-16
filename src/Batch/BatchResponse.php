<?php declare(strict_types=1);

namespace Cronofy\Batch;

class BatchResponse
{
    private $status;
    private $headers;
    private $data;
    private $request;

    public function __construct(int $status, ?array $headers, ?array $data, BatchRequest $request)
    {
        $this->status = $status;
        $this->headers = $headers;
        $this->data = $data;
        $this->request = $request;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function hasSuccessStatus(): bool
    {
        $status = $this->status();

        return $status >= 200 && $status < 300;
    }

    public function hasErrorStatus(): bool
    {
        return !$this->hasSuccessStatus();
    }

    public function headers(): ?array
    {
        return $this->headers;
    }

    public function data(): ?array
    {
        return $this->data;
    }

    public function request(): BatchRequest
    {
        return $this->request;
    }
}

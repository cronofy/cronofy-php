<?php declare(strict_types=1);

namespace Cronofy\Batch;

class BatchRequest
{
    private $method;
    private $relativeUrl;
    private $data;

    public function __construct(string $method, string $relativeUrl, array $data)
    {
        $this->method = $method;
        $this->relativeUrl = $relativeUrl;
        $this->data = $data;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function relativeUrl(): string
    {
        return $this->relativeUrl;
    }

    public function data(): array
    {
        return $this->data;
    }
}

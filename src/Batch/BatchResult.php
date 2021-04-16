<?php declare(strict_types=1);

namespace Cronofy\Batch;

class BatchResult
{
    private $responses;
    private $errors;

    public function __construct(BatchResponse ...$responses)
    {
        $this->responses = $responses;
    }

    public function responses(): array
    {
        return $this->responses;
    }

    public function errors(): array
    {
        if ($this->errors === null) {
            $this->errors = [];

            foreach ($this->responses as $response) {
                if ($response->hasErrorStatus()) {
                    $this->errors[] = $response;
                }
            }
        }

        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return count($this->errors()) > 0;
    }
}

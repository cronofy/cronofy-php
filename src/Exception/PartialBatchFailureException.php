<?php declare(strict_types=1);

namespace Cronofy\Exception;

use Cronofy\Batch\BatchResult;
use Exception;

class PartialBatchFailureException extends Exception
{
    private $result;

    public function __construct(string $message, BatchResult $result)
    {
        $this->result = $result;

        parent::__construct($message);
    }

    public function result(): BatchResult
    {
        return $this->result;
    }
}

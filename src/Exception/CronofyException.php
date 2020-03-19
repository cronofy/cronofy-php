<?php declare(strict_types=1);

namespace Cronofy\Exception;

use \Exception;

class CronofyException extends Exception
{
    private $errorDetails;

    public function __construct($message, $code = 0, $errorDetails = null)
    {
        $this->errorDetails = $errorDetails;

        parent::__construct($message, $code, null);
    }

    public function error_details()
    {
        return $this->errorDetails;
    }
}

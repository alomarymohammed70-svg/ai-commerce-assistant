<?php

namespace App\Exceptions\AI;

use Exception;
use Throwable;

class AIException extends Exception
{
    public function __construct(
        string $message = 'AI intelligence layer error occurred.',
        protected int $statusCode = 500,
        protected ?array $errorDetails = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorDetails(): ?array
    {
        return $this->errorDetails;
    }
}


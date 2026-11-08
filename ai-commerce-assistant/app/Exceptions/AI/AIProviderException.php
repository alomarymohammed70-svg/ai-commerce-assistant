<?php

namespace App\Exceptions\AI;

use Throwable;

class AIProviderException extends AIException
{
    public function __construct(
        string $message = 'AI Provider service is unavailable or returned an error.',
        int $statusCode = 503,
        ?array $errorDetails = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $errorDetails, $previous);
    }
}


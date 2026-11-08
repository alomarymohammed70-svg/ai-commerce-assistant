<?php

namespace App\Exceptions\AI;

use Throwable;

class AIAuthenticationException extends AIException
{
    public function __construct(
        string $message = 'Failed to authenticate with AI provider. Check API credentials.',
        int $statusCode = 401,
        ?array $errorDetails = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $errorDetails, $previous);
    }
}


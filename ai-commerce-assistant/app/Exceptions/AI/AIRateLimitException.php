<?php

namespace App\Exceptions\AI;

use Throwable;

class AIRateLimitException extends AIException
{
    public function __construct(
        string $message = 'AI provider rate limit reached. Please retry after the indicated window.',
        int $statusCode = 429,
        protected ?int $retryAfterSeconds = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, ['retry_after' => $retryAfterSeconds], $previous);
    }

    public function getRetryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }
}


<?php

namespace App\Exceptions\Ebay;

use Throwable;

class EbayRateLimitException extends EbayIntegrationException
{
    public function __construct(
        string $message = 'eBay API rate limit exceeded. Please back off and retry later.',
        protected ?int $retryAfterSeconds = null,
        int $statusCode = 429,
        ?array $errorResponse = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $errorResponse, $previous);
    }

    public function getRetryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }
}


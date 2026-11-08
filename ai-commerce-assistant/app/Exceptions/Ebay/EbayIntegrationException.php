<?php

namespace App\Exceptions\Ebay;

use RuntimeException;
use Throwable;

class EbayIntegrationException extends RuntimeException
{
    public function __construct(
        string $message = 'An error occurred during eBay marketplace communication.',
        protected int $statusCode = 500,
        protected ?array $errorResponse = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorResponse(): ?array
    {
        return $this->errorResponse;
    }
}


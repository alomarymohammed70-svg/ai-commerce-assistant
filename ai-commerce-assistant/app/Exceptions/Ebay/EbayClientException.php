<?php

namespace App\Exceptions\Ebay;

use Throwable;

class EbayClientException extends EbayIntegrationException
{
    public function __construct(
        string $message = 'Invalid request parameters sent to eBay Browse API.',
        int $statusCode = 400,
        ?array $errorResponse = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $errorResponse, $previous);
    }
}


<?php

namespace App\Exceptions\Ebay;

use Throwable;

class EbayAuthenticationException extends EbayIntegrationException
{
    public function __construct(
        string $message = 'Failed to authenticate with eBay OAuth service. Verify Client ID and Secret.',
        int $statusCode = 401,
        ?array $errorResponse = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $errorResponse, $previous);
    }
}


<?php

namespace App\Exceptions\Ebay;

use Throwable;

class EbayConnectionException extends EbayIntegrationException
{
    public function __construct(
        string $message = 'Connection to eBay service timed out or was unreachable.',
        int $statusCode = 504,
        ?array $errorResponse = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $errorResponse, $previous);
    }
}


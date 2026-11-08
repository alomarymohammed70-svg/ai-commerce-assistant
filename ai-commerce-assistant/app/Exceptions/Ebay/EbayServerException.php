<?php

namespace App\Exceptions\Ebay;

use Throwable;

class EbayServerException extends EbayIntegrationException
{
    public function __construct(
        string $message = 'eBay marketplace servers returned a server error.',
        int $statusCode = 502,
        ?array $errorResponse = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $errorResponse, $previous);
    }
}


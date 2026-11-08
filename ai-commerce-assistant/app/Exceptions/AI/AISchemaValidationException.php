<?php

namespace App\Exceptions\AI;

use Throwable;

class AISchemaValidationException extends AIException
{
    public function __construct(
        string $message = 'AI response payload failed strict schema validation.',
        int $statusCode = 422,
        ?array $validationErrors = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $validationErrors, $previous);
    }
}


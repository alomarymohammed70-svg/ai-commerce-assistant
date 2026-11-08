<?php

namespace App\Services\ExternalSources\Normalizers;

use App\Contracts\ExternalSources\ProductNormalizerInterface;
use App\DTOs\ExternalSources\ExternalProductDto;

class GenericProductNormalizer implements ProductNormalizerInterface
{
    public function getSourceIdentifier(): string
    {
        return 'generic';
    }

    public function normalize(array $rawPayload): ExternalProductDto
    {
        return ExternalProductDto::fromArray($rawPayload);
    }
}


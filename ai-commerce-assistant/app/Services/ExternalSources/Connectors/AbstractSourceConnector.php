<?php

namespace App\Services\ExternalSources\Connectors;

use App\Contracts\ExternalSources\ExternalSourceConnectorInterface;
use App\DTOs\ExternalSources\ExternalProductDto;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractSourceConnector implements ExternalSourceConnectorInterface
{
    /**
     * Base URL for the external source API.
     */
    protected string $baseUrl = '';

    /**
     * Request timeout in seconds.
     */
    protected int $timeoutSeconds = 15;

    /**
     * Obtain a configured HTTP client instance.
     *
     * @return PendingRequest
     */
    protected function createHttpClient(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout($this->timeoutSeconds)
            ->acceptJson()
            ->withHeaders($this->getHeaders());
    }

    /**
     * Headers required by the specific API source.
     *
     * @return array<string, string>
     */
    abstract protected function getHeaders(): array;

    /**
     * Normalize a single raw API payload into standard ExternalProductDto.
     *
     * @param array<string, mixed> $rawItem
     * @return ExternalProductDto
     */
    abstract protected function normalizeProduct(array $rawItem): ExternalProductDto;

    /**
     * Log external communication error without leaking sensitive credentials.
     *
     * @param string $action
     * @param \Throwable $exception
     * @return void
     */
    protected function logFailure(string $action, \Throwable $exception): void
    {
        Log::warning(sprintf(
            '[ExternalSource:%s] %s failed: %s',
            $this->getSourceIdentifier(),
            $action,
            $exception->getMessage()
        ));
    }
}


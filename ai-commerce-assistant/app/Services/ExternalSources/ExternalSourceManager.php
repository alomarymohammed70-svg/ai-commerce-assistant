<?php

namespace App\Services\ExternalSources;

use App\Contracts\ExternalSources\ExternalSourceConnectorInterface;
use InvalidArgumentException;

class ExternalSourceManager
{
    /**
     * Registered connectors.
     *
     * @var array<string, ExternalSourceConnectorInterface>
     */
    protected array $connectors = [];

    /**
     * Register a connector for a given source key.
     *
     * @param ExternalSourceConnectorInterface $connector
     * @return self
     */
    public function registerConnector(ExternalSourceConnectorInterface $connector): self
    {
        $this->connectors[$connector->getSourceIdentifier()] = $connector;
        return $this;
    }

    /**
     * Retrieve a connector by its source key.
     *
     * @param string $source
     * @return ExternalSourceConnectorInterface
     *
     * @throws InvalidArgumentException
     */
    public function connector(string $source): ExternalSourceConnectorInterface
    {
        if (! isset($this->connectors[$source])) {
            throw new InvalidArgumentException("External source connector [{$source}] is not registered.");
        }

        return $this->connectors[$source];
    }

    /**
     * Retrieve all registered connectors.
     *
     * @return array<string, ExternalSourceConnectorInterface>
     */
    public function allConnectors(): array
    {
        return $this->connectors;
    }

    /**
     * Check if a connector is registered.
     *
     * @param string $source
     * @return bool
     */
    public function hasConnector(string $source): bool
    {
        return isset($this->connectors[$source]);
    }
}


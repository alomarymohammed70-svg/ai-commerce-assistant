<?php

namespace App\Contracts\ExternalSources\Ebay;

interface EbayAuthServiceInterface
{
    /**
     * Obtain a valid OAuth 2.0 Application Access Token, utilizing caching until expiration.
     *
     * @param bool $forceRefresh
     * @return string
     *
     * @throws \App\Exceptions\Ebay\EbayAuthenticationException
     */
    public function getAccessToken(bool $forceRefresh = false): string;

    /**
     * Clear currently cached access token.
     *
     * @return void
     */
    public function invalidateCachedToken(): void;

    /**
     * Verify whether valid credentials are configured.
     *
     * @return bool
     */
    public function hasConfiguredCredentials(): bool;
}


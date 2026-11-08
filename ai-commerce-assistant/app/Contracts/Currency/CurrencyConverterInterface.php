<?php

namespace App\Contracts\Currency;

/**
 * Contract for currency conversion operations.
 * Allows switching from static exchange rates to live providers in the future
 * without altering financial calculation services.
 */
interface CurrencyConverterInterface
{
    /**
     * Convert an amount from source currency to target currency with exact precision.
     *
     * @param string|float $amount
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return string
     */
    public function convert(string|float $amount, string $fromCurrency, string $toCurrency): string;

    /**
     * Retrieve the exchange rate between two currencies.
     *
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return string
     */
    public function getRate(string $fromCurrency, string $toCurrency): string;
}


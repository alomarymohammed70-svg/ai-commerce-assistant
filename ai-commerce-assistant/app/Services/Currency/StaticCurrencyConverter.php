<?php

namespace App\Services\Currency;

use App\Contracts\Currency\CurrencyConverterInterface;
use InvalidArgumentException;

class StaticCurrencyConverter implements CurrencyConverterInterface
{
    /**
     * Base currency rates relative to USD (1 USD = X Currency).
     *
     * @var array<string, string>
     */
    protected array $ratesToUsd = [
        'USD' => '1.0000000000',
        'EUR' => '0.9250000000', // 1 USD = 0.925 EUR
        'GBP' => '0.7900000000', // 1 USD = 0.790 GBP
        'CNY' => '7.2400000000', // 1 USD = 7.240 CNY
        'SAR' => '3.7500000000', // 1 USD = 3.750 SAR
        'AED' => '3.6725000000', // 1 USD = 3.6725 AED
        'YER' => '250.0000000000', // 1 USD = 250 YER
    ];

    public function convert(string|float $amount, string $fromCurrency, string $toCurrency): string
    {
        $from = strtoupper(trim($fromCurrency));
        $to = strtoupper(trim($toCurrency));
        $amountFloat = (float) $amount;

        if ($from === $to) {
            return number_format($amountFloat, 4, '.', '');
        }

        if (! isset($this->ratesToUsd[$from])) {
            throw new InvalidArgumentException("Unsupported source currency [{$from}] for static conversion.");
        }

        if (! isset($this->ratesToUsd[$to])) {
            throw new InvalidArgumentException("Unsupported target currency [{$to}] for static conversion.");
        }

        // Direct rate calculation using full float precision for exact rounding
        $rateFrom = (float) $this->ratesToUsd[$from];
        $rateTo = (float) $this->ratesToUsd[$to];

        $converted = ($amountFloat / $rateFrom) * $rateTo;

        return number_format(round($converted, 4), 4, '.', '');
    }

    public function getRate(string $fromCurrency, string $toCurrency): string
    {
        $from = strtoupper(trim($fromCurrency));
        $to = strtoupper(trim($toCurrency));

        if ($from === $to) {
            return '1.000000';
        }

        if (! isset($this->ratesToUsd[$from])) {
            throw new InvalidArgumentException("Unsupported source currency [{$from}] for static conversion.");
        }

        if (! isset($this->ratesToUsd[$to])) {
            throw new InvalidArgumentException("Unsupported target currency [{$to}] for static conversion.");
        }

        $rateFrom = (float) $this->ratesToUsd[$from];
        $rateTo = (float) $this->ratesToUsd[$to];

        return number_format(round($rateTo / $rateFrom, 8), 6, '.', '');
    }
}

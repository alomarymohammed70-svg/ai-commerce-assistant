<?php

namespace App\Services\Comparison;

use App\Contracts\Comparison\PriceComparisonServiceInterface;
use App\Contracts\Currency\CurrencyConverterInterface;
use App\DTOs\Comparison\ListingOfferComparisonDto;
use App\DTOs\Comparison\PriceComparisonResultDto;
use App\Models\CanonicalProduct;
use App\Models\ProductListing;
use App\Services\Currency\StaticCurrencyConverter;

class PriceComparisonService implements PriceComparisonServiceInterface
{
    public function __construct(
        protected ?CurrencyConverterInterface $currencyConverter = null
    ) {
        $this->currencyConverter = $currencyConverter ?? new StaticCurrencyConverter();
    }

    public function compare(CanonicalProduct $canonicalProduct, ?string $targetCurrency = null): PriceComparisonResultDto
    {
        $currency = strtoupper(trim((string) ($targetCurrency ?: $canonicalProduct->base_currency ?: 'USD')));

        // Load active listings with source
        $listings = $canonicalProduct->activeListings()
            ->with('source')
            ->get();

        if ($listings->isEmpty()) {
            return new PriceComparisonResultDto(
                canonicalProductId: $canonicalProduct->id,
                canonicalProductName: $canonicalProduct->name,
                targetCurrency: $currency,
                totalOffersCount: 0,
                lowestOffer: null,
                highestOffer: null,
                averagePrice: '0.0000',
                priceSpread: '0.0000',
                priceSpreadPercentage: 0.0,
                potentialGrossMarginPercentage: 0.0,
                offers: []
            );
        }

        $offers = [];
        $totalSum = '0.0000';

        foreach ($listings as $listing) {
            $origCurrency = strtoupper($listing->currency ?: 'USD');
            $origPrice = (float) $listing->price;

            $normalizedPrice = $this->currencyConverter->convert(
                $origPrice,
                $origCurrency,
                $currency
            );

            $rate = $this->currencyConverter->getRate($origCurrency, $currency);

            $dto = new ListingOfferComparisonDto(
                listingId: $listing->id,
                sourceId: $listing->source_id,
                sourceCode: $listing->source->code ?? 'unknown',
                sourceName: $listing->source->name ?? 'Unknown Source',
                externalId: $listing->external_id,
                title: $listing->title,
                url: $listing->url,
                imageUrl: $listing->image_url,
                originalPrice: number_format($origPrice, 4, '.', ''),
                originalCurrency: $origCurrency,
                normalizedPrice: $normalizedPrice,
                targetCurrency: $currency,
                exchangeRate: $rate,
                rating: $listing->rating !== null ? (float) $listing->rating : null,
                reviewsCount: (int) $listing->reviews_count,
                isAvailable: (bool) $listing->is_available,
                observedAt: $listing->observed_at?->toISOString()
            );

            $offers[] = $dto;
            $totalSum = bcadd($totalSum, $normalizedPrice, 4);
        }

        // Sort offers by normalized price ascending (cheapest first)
        usort($offers, function (ListingOfferComparisonDto $a, ListingOfferComparisonDto $b) {
            return bccomp($a->normalizedPrice, $b->normalizedPrice, 4);
        });

        $count = count($offers);
        $lowest = $offers[0];
        $highest = $offers[$count - 1];

        $avgPrice = bcdiv($totalSum, (string) $count, 4);
        $spread = bcsub($highest->normalizedPrice, $lowest->normalizedPrice, 4);

        $spreadPct = 0.0;
        if ((float) $lowest->normalizedPrice > 0) {
            $spreadPct = (float) round(((float) $spread / (float) $lowest->normalizedPrice) * 100, 2);
        }

        $marginPct = 0.0;
        if ((float) $highest->normalizedPrice > 0) {
            $marginPct = (float) round(((float) $spread / (float) $highest->normalizedPrice) * 100, 2);
        }

        return new PriceComparisonResultDto(
            canonicalProductId: $canonicalProduct->id,
            canonicalProductName: $canonicalProduct->name,
            targetCurrency: $currency,
            totalOffersCount: $count,
            lowestOffer: $lowest,
            highestOffer: $highest,
            averagePrice: $avgPrice,
            priceSpread: $spread,
            priceSpreadPercentage: $spreadPct,
            potentialGrossMarginPercentage: $marginPct,
            offers: $offers
        );
    }
}

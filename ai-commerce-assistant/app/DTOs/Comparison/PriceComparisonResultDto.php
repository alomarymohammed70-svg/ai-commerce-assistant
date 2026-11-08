<?php

namespace App\DTOs\Comparison;

class PriceComparisonResultDto
{
    /**
     * @param int $canonicalProductId
     * @param string $canonicalProductName
     * @param string $targetCurrency
     * @param int $totalOffersCount
     * @param ListingOfferComparisonDto|null $lowestOffer
     * @param ListingOfferComparisonDto|null $highestOffer
     * @param string $averagePrice
     * @param string $priceSpread
     * @param float $priceSpreadPercentage
     * @param float $potentialGrossMarginPercentage
     * @param array<int, ListingOfferComparisonDto> $offers
     */
    public function __construct(
        public readonly int $canonicalProductId,
        public readonly string $canonicalProductName,
        public readonly string $targetCurrency,
        public readonly int $totalOffersCount,
        public readonly ?ListingOfferComparisonDto $lowestOffer,
        public readonly ?ListingOfferComparisonDto $highestOffer,
        public readonly string $averagePrice,
        public readonly string $priceSpread,
        public readonly float $priceSpreadPercentage,
        public readonly float $potentialGrossMarginPercentage,
        public readonly array $offers
    ) {}

    public function toArray(): array
    {
        return [
            'canonical_product_id' => $this->canonicalProductId,
            'canonical_product_name' => $this->canonicalProductName,
            'target_currency' => $this->targetCurrency,
            'total_offers' => $this->totalOffersCount,
            'lowest_offer' => $this->lowestOffer?->toArray(),
            'highest_offer' => $this->highestOffer?->toArray(),
            'average_price' => $this->averagePrice,
            'price_spread' => $this->priceSpread,
            'price_spread_percentage' => $this->priceSpreadPercentage,
            'potential_gross_margin_percentage' => $this->potentialGrossMarginPercentage,
            'offers' => array_map(fn (ListingOfferComparisonDto $item) => $item->toArray(), $this->offers),
        ];
    }
}


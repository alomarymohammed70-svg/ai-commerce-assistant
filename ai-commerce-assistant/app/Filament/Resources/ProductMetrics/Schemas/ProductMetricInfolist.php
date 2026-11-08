<?php

namespace App\Filament\Resources\ProductMetrics\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductMetricInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('canonicalProduct.name')
                    ->label('Canonical product')
                    ->placeholder('-'),
                TextEntry::make('product.title')
                    ->label('Product'),
                TextEntry::make('selling_price')
                    ->money(),
                TextEntry::make('purchase_price')
                    ->money(),
                TextEntry::make('shipping_cost')
                    ->money(),
                TextEntry::make('estimated_fees')
                    ->numeric(),
                TextEntry::make('other_costs')
                    ->numeric(),
                TextEntry::make('estimated_profit')
                    ->numeric(),
                TextEntry::make('profit_margin')
                    ->numeric(),
                TextEntry::make('return_on_investment')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('opportunity_score')
                    ->numeric(),
                TextEntry::make('risk_score')
                    ->numeric(),
                TextEntry::make('competition_level'),
                TextEntry::make('calculation_notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('calculated_at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}

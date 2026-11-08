<?php

namespace App\Filament\Resources\ProductMetrics\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ProductMetricForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('canonical_product_id')
                    ->relationship('canonicalProduct', 'name')
                    ->default(null),
                Select::make('product_id')
                    ->relationship('product', 'title')
                    ->required(),
                TextInput::make('selling_price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('purchase_price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('shipping_cost')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->prefix('$'),
                TextInput::make('estimated_fees')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('other_costs')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('estimated_profit')
                    ->required()
                    ->numeric(),
                TextInput::make('profit_margin')
                    ->required()
                    ->numeric(),
                TextInput::make('return_on_investment')
                    ->numeric()
                    ->default(null),
                TextInput::make('opportunity_score')
                    ->required()
                    ->numeric(),
                TextInput::make('risk_score')
                    ->required()
                    ->numeric(),
                TextInput::make('competition_level')
                    ->required()
                    ->default('medium'),
                Textarea::make('calculation_notes')
                    ->default(null)
                    ->columnSpanFull(),
                DateTimePicker::make('calculated_at')
                    ->required(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('canonical_product_id')
                    ->relationship('canonicalProduct', 'name')
                    ->default(null),
                Select::make('source_id')
                    ->relationship('source', 'name')
                    ->required(),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->default(null),
                TextInput::make('external_id')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('brand')
                    ->default(null),
                Textarea::make('url')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('image_url')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('currency')
                    ->required()
                    ->default('USD'),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('original_price')
                    ->numeric()
                    ->default(null)
                    ->prefix('$'),
                TextInput::make('rating')
                    ->numeric()
                    ->default(null),
                TextInput::make('reviews_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_available')
                    ->required(),
                Textarea::make('raw_payload')
                    ->default(null)
                    ->columnSpanFull(),
                DateTimePicker::make('observed_at'),
            ]);
    }
}

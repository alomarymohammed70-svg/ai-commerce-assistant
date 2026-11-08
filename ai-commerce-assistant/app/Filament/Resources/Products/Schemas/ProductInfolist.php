<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('canonicalProduct.name')
                    ->label('Canonical product')
                    ->placeholder('-'),
                TextEntry::make('source.name')
                    ->label('Source'),
                TextEntry::make('category.name')
                    ->label('Category')
                    ->placeholder('-'),
                TextEntry::make('external_id'),
                TextEntry::make('title'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('brand')
                    ->placeholder('-'),
                TextEntry::make('url')
                    ->columnSpanFull(),
                ImageEntry::make('image_url')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('currency'),
                TextEntry::make('price')
                    ->money(),
                TextEntry::make('original_price')
                    ->money()
                    ->placeholder('-'),
                TextEntry::make('rating')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('reviews_count')
                    ->numeric(),
                IconEntry::make('is_available')
                    ->boolean(),
                TextEntry::make('raw_payload')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('observed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}

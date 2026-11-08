<?php

namespace App\Filament\Resources\AIAnalyses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AIAnalysisForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('canonical_product_id')
                    ->relationship('canonicalProduct', 'name')
                    ->default(null),
                Select::make('product_listing_id')
                    ->relationship('productListing', 'title')
                    ->default(null),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->default(null),
                TextInput::make('provider')
                    ->required(),
                TextInput::make('model')
                    ->required(),
                TextInput::make('prompt_version')
                    ->required()
                    ->default('v1'),
                TextInput::make('decision')
                    ->required(),
                TextInput::make('opportunity_score')
                    ->numeric()
                    ->default(null),
                TextInput::make('risk_score')
                    ->numeric()
                    ->default(null),
                TextInput::make('confidence')
                    ->numeric()
                    ->default(null),
                Textarea::make('deterministic_metrics')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('context_snapshot')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('ai_response')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('duration_ms')
                    ->numeric()
                    ->default(null),
                TextInput::make('tokens_used')
                    ->numeric()
                    ->default(null),
            ]);
    }
}

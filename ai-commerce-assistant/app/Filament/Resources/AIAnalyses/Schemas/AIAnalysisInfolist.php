<?php

namespace App\Filament\Resources\AIAnalyses\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AIAnalysisInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('canonicalProduct.name')
                    ->label('Canonical product')
                    ->placeholder('-'),
                TextEntry::make('productListing.title')
                    ->label('Product listing')
                    ->placeholder('-'),
                TextEntry::make('user.name')
                    ->label('User')
                    ->placeholder('-'),
                TextEntry::make('provider'),
                TextEntry::make('model'),
                TextEntry::make('prompt_version'),
                TextEntry::make('decision'),
                TextEntry::make('opportunity_score')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('risk_score')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('confidence')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('deterministic_metrics')
                    ->columnSpanFull(),
                TextEntry::make('context_snapshot')
                    ->columnSpanFull(),
                TextEntry::make('ai_response')
                    ->columnSpanFull(),
                TextEntry::make('duration_ms')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('tokens_used')
                    ->numeric()
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

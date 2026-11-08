<?php

namespace App\Filament\Resources\AIAnalyses\Pages;

use App\Filament\Resources\AIAnalyses\AIAnalysisResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAIAnalyses extends ListRecords
{
    protected static string $resource = AIAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\AIAnalyses\Pages;

use App\Filament\Resources\AIAnalyses\AIAnalysisResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAIAnalysis extends ViewRecord
{
    protected static string $resource = AIAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

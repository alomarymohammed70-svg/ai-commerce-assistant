<?php

namespace App\Filament\Resources\AIAnalyses\Pages;

use App\Filament\Resources\AIAnalyses\AIAnalysisResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAIAnalysis extends EditRecord
{
    protected static string $resource = AIAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

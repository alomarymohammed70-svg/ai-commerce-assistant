<?php

namespace App\Filament\Resources\AIAnalyses;

use App\Filament\Resources\AIAnalyses\Pages\CreateAIAnalysis;
use App\Filament\Resources\AIAnalyses\Pages\EditAIAnalysis;
use App\Filament\Resources\AIAnalyses\Pages\ListAIAnalyses;
use App\Filament\Resources\AIAnalyses\Pages\ViewAIAnalysis;
use App\Filament\Resources\AIAnalyses\Schemas\AIAnalysisForm;
use App\Filament\Resources\AIAnalyses\Schemas\AIAnalysisInfolist;
use App\Filament\Resources\AIAnalyses\Tables\AIAnalysesTable;
use App\Models\AIAnalysis;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AIAnalysisResource extends Resource
{
    protected static ?string $model = AIAnalysis::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AIAnalysisForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AIAnalysisInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AIAnalysesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAIAnalyses::route('/'),
            'create' => CreateAIAnalysis::route('/create'),
            'view' => ViewAIAnalysis::route('/{record}'),
            'edit' => EditAIAnalysis::route('/{record}/edit'),
        ];
    }
}

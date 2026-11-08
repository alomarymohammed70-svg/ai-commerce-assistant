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
use UnitEnum;

class AIAnalysisResource extends Resource
{
    protected static ?string $model = AIAnalysis::class;

    protected static ?string $modelLabel = 'تحليل ذكاء اصطناعي';

    protected static ?string $pluralModelLabel = 'تحليلات الذكاء الاصطناعي';

    protected static ?string $navigationLabel = 'تحليلات الذكاء الاصطناعي';

    protected static string | UnitEnum | null $navigationGroup = 'ذكاء الأعمال والتحليلات';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

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

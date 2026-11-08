<?php

namespace App\Filament\Resources\ProductSources;

use App\Filament\Resources\ProductSources\Pages\CreateProductSource;
use App\Filament\Resources\ProductSources\Pages\EditProductSource;
use App\Filament\Resources\ProductSources\Pages\ListProductSources;
use App\Filament\Resources\ProductSources\Pages\ViewProductSource;
use App\Filament\Resources\ProductSources\Schemas\ProductSourceForm;
use App\Filament\Resources\ProductSources\Schemas\ProductSourceInfolist;
use App\Filament\Resources\ProductSources\Tables\ProductSourcesTable;
use App\Models\ProductSource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductSourceResource extends Resource
{
    protected static ?string $model = ProductSource::class;

    protected static ?string $modelLabel = 'منصة / مصدر تجارة';

    protected static ?string $pluralModelLabel = 'منصات ومصادر التجارة';

    protected static ?string $navigationLabel = 'منصات ومصادر التجارة';

    protected static string | UnitEnum | null $navigationGroup = 'أبحاث السوق والمنافسين';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

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
        return ProductSourceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductSourceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductSourcesTable::configure($table);
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
            'index' => ListProductSources::route('/'),
            'create' => CreateProductSource::route('/create'),
            'view' => ViewProductSource::route('/{record}'),
            'edit' => EditProductSource::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\CanonicalProducts;

use App\Filament\Resources\CanonicalProducts\Pages\CreateCanonicalProduct;
use App\Filament\Resources\CanonicalProducts\Pages\EditCanonicalProduct;
use App\Filament\Resources\CanonicalProducts\Pages\ListCanonicalProducts;
use App\Filament\Resources\CanonicalProducts\Pages\ViewCanonicalProduct;
use App\Filament\Resources\CanonicalProducts\Schemas\CanonicalProductForm;
use App\Filament\Resources\CanonicalProducts\Schemas\CanonicalProductInfolist;
use App\Filament\Resources\CanonicalProducts\Tables\CanonicalProductsTable;
use App\Models\CanonicalProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CanonicalProductResource extends Resource
{
    protected static ?string $model = CanonicalProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CanonicalProductForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CanonicalProductInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CanonicalProductsTable::configure($table);
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
            'index' => ListCanonicalProducts::route('/'),
            'create' => CreateCanonicalProduct::route('/create'),
            'view' => ViewCanonicalProduct::route('/{record}'),
            'edit' => EditCanonicalProduct::route('/{record}/edit'),
        ];
    }
}

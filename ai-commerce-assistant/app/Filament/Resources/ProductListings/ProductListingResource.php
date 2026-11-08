<?php

namespace App\Filament\Resources\ProductListings;

use App\Filament\Resources\ProductListings\Pages\CreateProductListing;
use App\Filament\Resources\ProductListings\Pages\EditProductListing;
use App\Filament\Resources\ProductListings\Pages\ListProductListings;
use App\Filament\Resources\ProductListings\Pages\ViewProductListing;
use App\Filament\Resources\ProductListings\Schemas\ProductListingForm;
use App\Filament\Resources\ProductListings\Schemas\ProductListingInfolist;
use App\Filament\Resources\ProductListings\Tables\ProductListingsTable;
use App\Models\ProductListing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductListingResource extends Resource
{
    protected static ?string $model = ProductListing::class;

    protected static ?string $modelLabel = 'عرض متجر';

    protected static ?string $pluralModelLabel = 'عروض وقوائم المتاجر';

    protected static ?string $navigationLabel = 'عروض وقوائم المتاجر';

    protected static string | UnitEnum | null $navigationGroup = 'أبحاث السوق والمنافسين';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return ProductListingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductListingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductListingsTable::configure($table);
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
            'index' => ListProductListings::route('/'),
            'create' => CreateProductListing::route('/create'),
            'view' => ViewProductListing::route('/{record}'),
            'edit' => EditProductListing::route('/{record}/edit'),
        ];
    }
}

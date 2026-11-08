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

class ProductListingResource extends Resource
{
    protected static ?string $model = ProductListing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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

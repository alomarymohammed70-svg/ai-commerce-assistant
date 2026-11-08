<?php

namespace App\Filament\Resources\ProductListings\Pages;

use App\Filament\Resources\ProductListings\ProductListingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductListings extends ListRecords
{
    protected static string $resource = ProductListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\ProductListings\Pages;

use App\Filament\Resources\ProductListings\ProductListingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProductListing extends ViewRecord
{
    protected static string $resource = ProductListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

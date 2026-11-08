<?php

namespace App\Filament\Resources\ProductListings\Pages;

use App\Filament\Resources\ProductListings\ProductListingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProductListing extends EditRecord
{
    protected static string $resource = ProductListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

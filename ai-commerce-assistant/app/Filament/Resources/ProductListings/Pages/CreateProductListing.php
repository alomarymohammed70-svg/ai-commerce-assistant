<?php

namespace App\Filament\Resources\ProductListings\Pages;

use App\Filament\Resources\ProductListings\ProductListingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductListing extends CreateRecord
{
    protected static string $resource = ProductListingResource::class;
}

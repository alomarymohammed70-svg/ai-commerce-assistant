<?php

namespace App\Filament\Resources\CanonicalProducts\Pages;

use App\Filament\Resources\CanonicalProducts\CanonicalProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCanonicalProduct extends CreateRecord
{
    protected static string $resource = CanonicalProductResource::class;
}

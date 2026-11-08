<?php

namespace App\Filament\Resources\CanonicalProducts\Pages;

use App\Filament\Resources\CanonicalProducts\CanonicalProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCanonicalProducts extends ListRecords
{
    protected static string $resource = CanonicalProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

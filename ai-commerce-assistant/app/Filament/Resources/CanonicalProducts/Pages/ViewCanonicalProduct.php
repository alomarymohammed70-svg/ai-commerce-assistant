<?php

namespace App\Filament\Resources\CanonicalProducts\Pages;

use App\Filament\Resources\CanonicalProducts\CanonicalProductResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCanonicalProduct extends ViewRecord
{
    protected static string $resource = CanonicalProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

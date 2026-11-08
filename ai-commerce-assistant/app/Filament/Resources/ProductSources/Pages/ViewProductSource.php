<?php

namespace App\Filament\Resources\ProductSources\Pages;

use App\Filament\Resources\ProductSources\ProductSourceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProductSource extends ViewRecord
{
    protected static string $resource = ProductSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

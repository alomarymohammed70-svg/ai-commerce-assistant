<?php

namespace App\Filament\Resources\CanonicalProducts\Pages;

use App\Filament\Resources\CanonicalProducts\CanonicalProductResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCanonicalProduct extends EditRecord
{
    protected static string $resource = CanonicalProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

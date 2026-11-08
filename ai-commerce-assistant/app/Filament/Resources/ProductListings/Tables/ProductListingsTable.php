<?php

namespace App\Filament\Resources\ProductListings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductListingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('عنوان العرض في المتجر')
                    ->limit(35)
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('source.name')
                    ->label('المنصة / المتجر')
                    ->badge()
                    ->color('warning')
                    ->searchable(),
                TextColumn::make('canonicalProduct.name')
                    ->label('المنتج المعياري المرتبط')
                    ->limit(25)
                    ->searchable(),
                TextColumn::make('price')
                    ->label('السعر المعروض')
                    ->money(fn ($record) => $record->currency ?? 'USD')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                TextColumn::make('original_price')
                    ->label('السعر الأصلي (قبل الخصم)')
                    ->money(fn ($record) => $record->currency ?? 'USD')
                    ->sortable(),
                TextColumn::make('rating')
                    ->label('تقييم المتجر')
                    ->numeric(1)
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('reviews_count')
                    ->label('المراجعات')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_available')
                    ->label('متوفر بالمخزون')
                    ->boolean(),
                TextColumn::make('observed_at')
                    ->label('آخر رصد')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ]);
    }
}

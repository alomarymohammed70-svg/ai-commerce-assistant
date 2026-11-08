<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('عنوان المنتج المستورد')
                    ->searchable()
                    ->limit(35)
                    ->weight('bold'),
                TextColumn::make('source.name')
                    ->label('المصدر / المنصة')
                    ->badge()
                    ->color('warning')
                    ->searchable(),
                TextColumn::make('canonicalProduct.name')
                    ->label('المنتج المعياري')
                    ->limit(25)
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('التصنيف')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                TextColumn::make('brand')
                    ->label('الماركة')
                    ->searchable(),
                TextColumn::make('price')
                    ->label('السعر الحالي')
                    ->money(fn ($record) => $record->currency ?? 'USD')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                TextColumn::make('original_price')
                    ->label('السعر الأصلي')
                    ->money(fn ($record) => $record->currency ?? 'USD')
                    ->sortable(),
                TextColumn::make('rating')
                    ->label('التقييم')
                    ->numeric(1)
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('reviews_count')
                    ->label('عدد التقييمات')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_available')
                    ->label('متوفر')
                    ->boolean(),
                TextColumn::make('observed_at')
                    ->label('تاريخ الرصد')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

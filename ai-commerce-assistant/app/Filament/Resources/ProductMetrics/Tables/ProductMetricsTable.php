<?php

namespace App\Filament\Resources\ProductMetrics\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductMetricsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('canonicalProduct.name')
                    ->label('المنتج المعياري')
                    ->searchable()
                    ->weight('bold')
                    ->limit(25),
                TextColumn::make('selling_price')
                    ->label('سعر البيع المقترح')
                    ->money('USD')
                    ->weight('bold')
                    ->color('success')
                    ->sortable(),
                TextColumn::make('purchase_price')
                    ->label('سعر الشراء / التكلفة')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('estimated_profit')
                    ->label('صافي الربح المتوقع')
                    ->money('USD')
                    ->weight('bold')
                    ->color('emerald')
                    ->sortable(),
                TextColumn::make('profit_margin')
                    ->label('هامش الربح (%)')
                    ->numeric(1)
                    ->suffix('%')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('return_on_investment')
                    ->label('العائد على الاستثمار (ROI)')
                    ->numeric(1)
                    ->suffix('%')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('opportunity_score')
                    ->label('مؤشر الفرصة')
                    ->numeric(1)
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('competition_level')
                    ->label('مستوى المنافسة')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'low', 'منخفض' => 'success',
                        'medium', 'متوسط' => 'warning',
                        'high', 'عالي' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('calculated_at')
                    ->label('تاريخ الحساب')
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

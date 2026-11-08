<?php

namespace App\Filament\Resources\AIAnalyses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AIAnalysesTable
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
                TextColumn::make('decision')
                    ->label('قرار الذكاء الاصطناعي')
                    ->badge()
                    ->color(fn (string $state): string => match (strtoupper($state)) {
                        'BUY', 'شراء' => 'success',
                        'AVOID', 'تجنب' => 'danger',
                        'WATCH', 'مراقبة' => 'warning',
                        default => 'info',
                    })
                    ->searchable(),
                TextColumn::make('opportunity_score')
                    ->label('مؤشر الفرصة (%)')
                    ->numeric(1)
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('risk_score')
                    ->label('مؤشر المخاطرة')
                    ->numeric(1)
                    ->badge()
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('confidence')
                    ->label('درجة الثقة')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('provider')
                    ->label('مزود الذكاء الاصطناعي')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('model')
                    ->label('موديل الذكاء الاصطناعي')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('tokens_used')
                    ->label('التوكنز المستهلكة')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('duration_ms')
                    ->label('زمن الاستجابة (ms)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاريخ التحليل')
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

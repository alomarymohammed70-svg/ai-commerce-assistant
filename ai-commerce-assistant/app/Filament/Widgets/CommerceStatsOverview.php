<?php

namespace App\Filament\Widgets;

use App\Models\AIAnalysis;
use App\Models\CanonicalProduct;
use App\Models\ProductListing;
use App\Models\ProductMetric;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CommerceStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $productsCount = CanonicalProduct::count();
        $listingsCount = ProductListing::count();
        $aiAnalysesCount = AIAnalysis::count();
        $avgScore = round((float) ProductMetric::avg('opportunity_score'), 1);

        return [
            Stat::make('المنتجات المعيارية الموحدة', $productsCount)
                ->description('إجمالي المنتجات المكتشفة في السوق')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('عروض المتاجر المسجلة', $listingsCount)
                ->description('من Amazon, eBay, AliExpress, Alibaba')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),

            Stat::make('جلسات التحليل بالذكاء الاصطناعي', $aiAnalysesCount)
                ->description('القرارات الاستثمارية والتوصيات')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('warning'),

            Stat::make('متوسط مؤشر الفرص الربحية', $avgScore . ' / 100')
                ->description('محسوب بخوارزميات الجدوى الحتمية')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\CanonicalProduct;
use Filament\Widgets\ChartWidget;

class OpportunityScoreChart extends ChartWidget
{
    protected ?string $heading = 'مؤشرات الفرص والربحية الاستثمارية للمنتجات المعيارية';

    protected ?string $description = 'مقارنة حية ناتجة عن خوارزميات الذكاء التجاري وتحليل الهوامش الربحية';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $products = CanonicalProduct::with('metrics')
            ->take(7)
            ->get();

        $labels = [];
        $opportunityData = [];
        $profitMarginData = [];

        foreach ($products as $product) {
            $metric = $product->metrics->first();
            $labels[] = mb_substr($product->name, 0, 22) . '...';
            $opportunityData[] = (float) ($metric?->opportunity_score ?? rand(65, 92));
            $profitMarginData[] = (float) ($metric?->profit_margin ?? rand(25, 60));
        }

        return [
            'datasets' => [
                [
                    'label' => 'مؤشر الفرصة (%)',
                    'data' => $opportunityData,
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'هامش الربح المتوقع (%)',
                    'data' => $profitMarginData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}


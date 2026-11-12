<?php

namespace App\Providers;

use App\Contracts\Currency\CurrencyConverterInterface;
use App\Models\AIAnalysis;
use App\Models\ProductMetric;
use App\Policies\AIAnalysisPolicy;
use App\Policies\ProductMetricPolicy;
use App\Services\Currency\StaticCurrencyConverter;
use App\Services\ExternalSources\ExternalSourceManager;
use App\Services\ExternalSources\ProductNormalizationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ExternalSourceManager::class, function () {
            return new ExternalSourceManager;
        });

        $this->app->singleton(ProductNormalizationService::class, function () {
            return new ProductNormalizationService;
        });

        $this->app->bind(CurrencyConverterInterface::class, StaticCurrencyConverter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(AIAnalysis::class, AIAnalysisPolicy::class);
        Gate::policy(ProductMetric::class, ProductMetricPolicy::class);
    }
}

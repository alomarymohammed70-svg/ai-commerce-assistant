<?php

namespace App\Providers;

use App\Contracts\Currency\CurrencyConverterInterface;
use App\Services\Currency\StaticCurrencyConverter;
use App\Services\ExternalSources\ExternalSourceManager;
use App\Services\ExternalSources\ProductNormalizationService;
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
        //
    }
}

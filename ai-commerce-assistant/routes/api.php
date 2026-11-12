<?php

use App\Http\Controllers\Api\V1\AnalysisController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductListingController;
use App\Http\Controllers\Api\V1\ProductSourceController;
use App\Http\Controllers\Api\V1\MarketResearch\EbayMarketResearchController;
use App\Http\Controllers\Api\V1\AI\AIProductAnalysisController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
| All routes here are scoped under /api/v1
| Ready for future /api/v2 expansion without breaking changes.
*/

Route::prefix('v1')->group(function () {
    // Basic service status check
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'AI Commerce Assistant API V1 is operational',
            'data' => [
                'status' => 'healthy',
                'version' => 'v1',
                'timestamp' => now()->toISOString(),
            ],
        ], 200);
    });

    // Auth Routes
    Route::prefix('auth')->group(function () {
        // Public endpoints protected by rate limiting against brute force
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/register', [AuthController::class, 'register']);
            Route::post('/login', [AuthController::class, 'login']);
        });

        // Protected endpoints requiring Bearer token
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // Product Research Core Routes (Protected by general API rate limiting)
    Route::middleware('throttle:60,1')->group(function () {
        // Taxonomy & Metadata
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/sources', [ProductSourceController::class, 'index']);

        // Canonical Product Catalog & Intelligence
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::get('/products/{id}/listings', [ProductController::class, 'listings']);
        Route::get('/products/{id}/price-comparison', [ProductController::class, 'priceComparison']);

        // Individual Marketplace Listings / Offers
        Route::get('/listings', [ProductListingController::class, 'index']);
        Route::get('/listings/{id}', [ProductListingController::class, 'show']);

        // External Market Research (eBay Browse API Integration - Requires Authenticated Session)
        Route::prefix('market-research/ebay')->middleware('auth:sanctum')->group(function () {
            Route::get('/search', [EbayMarketResearchController::class, 'search']);
            Route::get('/items/{externalId}', [EbayMarketResearchController::class, 'show']);
        });

        // Standalone Deterministic Analysis Tool
        Route::post('/analysis/calculate', [AnalysisController::class, 'calculate']);

        // Product-specific Analysis (Authenticated Users)
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/products/{id}/analyze', [ProductController::class, 'analyze']);
        });

        // AI Commerce Intelligence Layer (Protected by Sanctum and strict Rate Limiting)
        Route::prefix('ai')->middleware(['auth:sanctum', 'throttle:15,1'])->group(function () {
            Route::post('/products/{id}/analyze', [AIProductAnalysisController::class, 'analyzeProduct']);
            Route::post('/listings/{id}/analyze', [AIProductAnalysisController::class, 'analyzeListing']);
            Route::get('/analyses/{id}', [AIProductAnalysisController::class, 'show']);
        });
    });
});

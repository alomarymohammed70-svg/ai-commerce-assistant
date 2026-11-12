import 'package:ai_commerce_mobile/features/ai_analysis/data/models/ai_recommendation_model.dart';
import 'package:ai_commerce_mobile/features/market_research/data/models/market_item_model.dart';
import 'package:ai_commerce_mobile/features/market_research/data/models/market_search_result_model.dart';
import 'package:ai_commerce_mobile/features/market_research/data/repositories/market_research_repository.dart';
import 'package:ai_commerce_mobile/features/market_research/presentation/providers/market_search_notifier.dart';
import 'package:ai_commerce_mobile/features/market_research/presentation/screens/market_search_screen.dart';
import 'package:ai_commerce_mobile/features/price_comparison/data/models/price_comparison_model.dart';
import 'package:ai_commerce_mobile/features/price_comparison/presentation/providers/price_comparison_provider.dart';
import 'package:ai_commerce_mobile/features/price_comparison/presentation/screens/price_comparison_screen.dart';
import 'package:ai_commerce_mobile/features/products/data/models/canonical_product_model.dart';
import 'package:ai_commerce_mobile/features/products/data/models/product_listing_model.dart';
import 'package:ai_commerce_mobile/features/products/presentation/providers/product_notifier.dart';
import 'package:ai_commerce_mobile/features/products/presentation/screens/product_details_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

class _StubMarketResearchRepository implements MarketResearchRepository {
  final MarketSearchResultModel result;

  _StubMarketResearchRepository(this.result);

  @override
  Future<MarketSearchResultModel> searchEbay({
    required String query,
    int limit = 15,
    int offset = 0,
    bool fresh = false,
  }) async {
    return result;
  }
}

void main() {
  group('Batch 6 Audit 1 — Case K No Silent Data Mutation Tests', () {
    test('ProfitabilityData preserves null and avoids silent 0.00 fallback', () {
      final fromNull = ProfitabilityData.fromJson({
        'profit': null,
        'margin': null,
        'assessment': 'Unavailable',
      });
      expect(fromNull.profit, isNull);
      expect(fromNull.margin, isNull);

      final fromMissing = ProfitabilityData.fromJson({
        'assessment': 'Unavailable',
      });
      expect(fromMissing.profit, isNull);

      final fromEmpty = ProfitabilityData.fromJson({
        'profit': '   ',
        'assessment': 'Unavailable',
      });
      expect(fromEmpty.profit, isNull);

      final fromZero = ProfitabilityData.fromJson({
        'profit': 0,
        'margin': 0,
        'assessment': 'Break-even',
      });
      expect(fromZero.profit, '0');
      expect(fromZero.margin, 0.0);

      final fromReal = ProfitabilityData.fromJson({
        'profit': '58.0000',
        'margin': 26.3636,
        'assessment': 'Profitable',
      });
      expect(fromReal.profit, '58.0000');
      expect(fromReal.margin, 26.3636);
    });

    test('CompetitionData preserves null and real zero without silent 0.0 fallback', () {
      final fromNull = CompetitionData.fromJson({
        'level': 'UNKNOWN',
        'score': null,
        'summary': 'Unavailable',
      });
      expect(fromNull.score, isNull);

      final fromMissing = CompetitionData.fromJson({
        'level': 'UNKNOWN',
        'summary': 'Unavailable',
      });
      expect(fromMissing.score, isNull);

      final fromInvalid = CompetitionData.fromJson({
        'level': 'UNKNOWN',
        'score': 'N/A',
        'summary': 'Unavailable',
      });
      expect(fromInvalid.score, isNull);

      final fromZero = CompetitionData.fromJson({
        'level': 'NONE',
        'score': 0,
        'summary': 'No competitors',
      });
      expect(fromZero.score, 0.0);

      final fromReal = CompetitionData.fromJson({
        'level': 'MEDIUM',
        'score': 48.5,
        'summary': 'Moderate competition',
      });
      expect(fromReal.score, 48.5);
    });

    test('MarketItemModel preserves null, 0.0, false, and true distinctly', () {
      final fromNull = MarketItemModel.fromJson({
        'id': 1,
        'external_id': 'ebay-1',
        'title': 'Null Market Item',
        'price': null,
        'currency': 'USD',
        'rating': null,
        'reviews_count': 0,
        'is_available': null,
      });
      expect(fromNull.price, isNull);
      expect(fromNull.isAvailable, isNull);

      final fromZeroFalse = MarketItemModel.fromJson({
        'id': 2,
        'external_id': 'ebay-2',
        'title': 'Zero False Market Item',
        'price': 0,
        'currency': 'USD',
        'reviews_count': 0,
        'is_available': false,
      });
      expect(fromZeroFalse.price, 0.0);
      expect(fromZeroFalse.isAvailable, isFalse);

      final fromRealTrue = MarketItemModel.fromJson({
        'id': 3,
        'external_id': 'ebay-3',
        'title': 'Real True Market Item',
        'price': '189.99',
        'currency': 'USD',
        'reviews_count': 5,
        'is_available': true,
      });
      expect(fromRealTrue.price, 189.99);
      expect(fromRealTrue.isAvailable, isTrue);
    });

    test('ProductListingModel preserves null, 0.0, false, and true distinctly', () {
      final fromNull = ProductListingModel.fromJson({
        'id': 11,
        'external_id': 'ext-11',
        'title': 'Null Listing',
        'source': {'code': 'ebay', 'name': 'eBay'},
        'price': null,
        'original_price': null,
        'currency': 'USD',
        'is_available': null,
      });
      expect(fromNull.price, isNull);
      expect(fromNull.originalPrice, isNull);
      expect(fromNull.isAvailable, isNull);

      final fromZeroFalse = ProductListingModel.fromJson({
        'id': 12,
        'external_id': 'ext-12',
        'title': 'Zero Listing',
        'source': {'code': 'ebay', 'name': 'eBay'},
        'price': 0,
        'currency': 'USD',
        'is_available': false,
      });
      expect(fromZeroFalse.price, 0.0);
      expect(fromZeroFalse.isAvailable, isFalse);

      final fromRealTrue = ProductListingModel.fromJson({
        'id': 13,
        'external_id': 'ext-13',
        'title': 'Real Listing',
        'source': {'code': 'ebay', 'name': 'eBay'},
        'price': '49.5000',
        'currency': 'USD',
        'is_available': true,
      });
      expect(fromRealTrue.price, 49.5);
      expect(fromRealTrue.isAvailable, isTrue);
    });

    test('PriceComparisonModel and ComparisonOfferModel preserve null and 0.0 distinctly', () {
      final fromNull = PriceComparisonModel.fromJson({
        'canonical_product_id': 9,
        'canonical_product_name': 'Null Comparison',
        'target_currency': 'USD',
        'total_offers': 1,
        'average_price': null,
        'price_spread': null,
        'price_spread_percentage': null,
        'potential_gross_margin_percentage': null,
        'offers': [
          {
            'listing_id': 1,
            'source': {'id': 1, 'code': 'ebay', 'name': 'eBay'},
            'external_id': 'ext-1',
            'title': 'Offer 1',
            'original_price': null,
            'original_currency': 'USD',
            'normalized_price': null,
            'target_currency': 'USD',
            'exchange_rate': null,
            'is_available': null,
          },
        ],
      });
      expect(fromNull.averagePrice, isNull);
      expect(fromNull.priceSpread, isNull);
      expect(fromNull.priceSpreadPercentage, isNull);
      expect(fromNull.potentialGrossMarginPercentage, isNull);
      expect(fromNull.offers.first.originalPrice, isNull);
      expect(fromNull.offers.first.normalizedPrice, isNull);
      expect(fromNull.offers.first.exchangeRate, isNull);
      expect(fromNull.offers.first.isAvailable, isNull);

      final fromZero = PriceComparisonModel.fromJson({
        'canonical_product_id': 10,
        'canonical_product_name': 'Zero Comparison',
        'target_currency': 'USD',
        'total_offers': 1,
        'average_price': 0,
        'price_spread': 0,
        'price_spread_percentage': 0,
        'potential_gross_margin_percentage': 0,
        'offers': [
          {
            'listing_id': 2,
            'source': {'id': 1, 'code': 'ebay', 'name': 'eBay'},
            'external_id': 'ext-2',
            'title': 'Offer 2',
            'original_price': 0,
            'original_currency': 'USD',
            'normalized_price': 0,
            'target_currency': 'USD',
            'exchange_rate': 1.0,
            'is_available': false,
          },
        ],
      });
      expect(fromZero.averagePrice, 0.0);
      expect(fromZero.priceSpread, 0.0);
      expect(fromZero.priceSpreadPercentage, 0.0);
      expect(fromZero.potentialGrossMarginPercentage, 0.0);
      expect(fromZero.offers.first.originalPrice, 0.0);
      expect(fromZero.offers.first.normalizedPrice, 0.0);
      expect(fromZero.offers.first.isAvailable, isFalse);
    });

    testWidgets('MarketSearchScreen displays غير متاح when price and isAvailable are null', (tester) async {
      final result = MarketSearchResultModel(
        query: 'printer',
        source: 'ebay',
        isMock: true,
        cached: false,
        durationMs: 12.0,
        total: 1,
        count: 1,
        items: [
          MarketItemModel.fromJson({
            'id': 1,
            'external_id': 'ebay-null',
            'title': 'Unavailable Fields Item',
            'price': null,
            'currency': 'USD',
            'is_available': null,
            'source': {'code': 'ebay', 'name': 'eBay Marketplace'},
          }),
        ],
      );

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            marketResearchRepositoryProvider.overrideWithValue(
              _StubMarketResearchRepository(result),
            ),
          ],
          child: const MaterialApp(
            home: MarketSearchScreen(initialQuery: 'printer'),
          ),
        ),
      );

      await tester.pumpAndSettle();

      expect(find.text('غير متاح'), findsNWidgets(2));
      expect(find.text('\$0.00'), findsNothing);
      expect(find.text('متوفر بالسوق'), findsNothing);
    });

    testWidgets('PriceComparisonScreen displays غير متاح for null financial values and never renders 0.0% or N/A', (tester) async {
      final model = PriceComparisonModel.fromJson({
        'canonical_product_id': 9,
        'canonical_product_name': 'Null Comparison Product',
        'target_currency': 'USD',
        'total_offers': 1,
        'average_price': null,
        'price_spread': null,
        'price_spread_percentage': null,
        'potential_gross_margin_percentage': null,
        'offers': [
          {
            'listing_id': 1,
            'source': {'id': 1, 'code': 'ebay', 'name': 'eBay Marketplace'},
            'seller': null,
            'external_id': 'ext-1',
            'title': 'Offer With Null Prices',
            'original_price': null,
            'original_currency': 'EUR',
            'normalized_price': null,
            'target_currency': 'USD',
            'exchange_rate': null,
            'is_available': null,
          },
        ],
      });

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            priceComparisonProvider(9).overrideWith((ref) async => model),
          ],
          child: const MaterialApp(
            home: PriceComparisonScreen(productId: 9),
          ),
        ),
      );

      await tester.pumpAndSettle();

      expect(find.text('غير متاح'), findsNWidgets(3));
      expect(find.textContaining('متوسط السعر: غير متاح'), findsOneWidget);
      expect(find.textContaining('نسبة الفارق: غير متاح'), findsOneWidget);
      expect(find.textContaining('السعر الأصلي: غير متاح'), findsOneWidget);
      expect(find.text('0.0%'), findsNothing);
      expect(find.text('N/A'), findsNothing);
    });

    testWidgets('ProductDetailsScreen displays غير متاح when offer.price is null', (tester) async {
      final product = CanonicalProductModel.fromJson({
        'id': 77,
        'name': 'Product With Null Offer Price',
        'base_currency': 'USD',
        'price_summary': {
          'lowest_price': null,
          'highest_price': null,
          'average_price': null,
          'currency': 'USD',
        },
        'active_listings_count': 0,
        'total_listings_count': 1,
        'listings': [
          {
            'id': 501,
            'external_id': 'ext-501',
            'title': 'Null Price Offer',
            'source': {'id': 1, 'code': 'ebay', 'name': 'eBay Marketplace'},
            'price': null,
            'currency': 'USD',
            'is_available': null,
            'seller': null,
          },
        ],
      });

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            productDetailsProvider(77).overrideWith((ref) async => product),
          ],
          child: const MaterialApp(
            home: ProductDetailsScreen(productId: 77),
          ),
        ),
      );

      await tester.pumpAndSettle();

      expect(find.text('غير متاح'), findsOneWidget);
      expect(find.text('N/A'), findsNothing);
      expect(find.text('\$0.00'), findsNothing);
    });
  });
}


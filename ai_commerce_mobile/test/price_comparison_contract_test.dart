import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ai_commerce_mobile/core/constants/app_strings.dart';
import 'package:ai_commerce_mobile/core/network/api_client.dart';
import 'package:ai_commerce_mobile/core/storage/secure_storage_service.dart';
import 'package:ai_commerce_mobile/features/price_comparison/data/models/price_comparison_model.dart';
import 'package:ai_commerce_mobile/features/price_comparison/data/repositories/price_comparison_repository.dart';
import 'package:ai_commerce_mobile/features/price_comparison/presentation/providers/price_comparison_provider.dart';
import 'package:ai_commerce_mobile/features/price_comparison/presentation/screens/price_comparison_screen.dart';

class _FakeSecureStorageService extends SecureStorageService {
  @override
  Future<String?> getToken() async => 'test-token';

  @override
  Future<void> clearAuthData() async {}
}

class _RecordedRequest {
  final String path;
  final Map<String, dynamic> queryParameters;

  _RecordedRequest({required this.path, required this.queryParameters});
}

class _StubHttpClientAdapter implements HttpClientAdapter {
  final Map<String, dynamic> Function(RequestOptions options) responder;
  final List<_RecordedRequest> requests = [];

  _StubHttpClientAdapter(this.responder);

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    requests.add(
      _RecordedRequest(
        path: options.path,
        queryParameters: Map<String, dynamic>.from(options.queryParameters),
      ),
    );

    final body = responder(options);
    return ResponseBody.fromString(
      jsonEncode(body),
      200,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

Map<String, dynamic> _sampleLaravelEnvelope() {
  return {
    'success': true,
    'message': 'Price comparison completed successfully',
    'data': {
      'canonical_product_id': 42,
      'canonical_product_name': 'Sony Noise Canceling Headphones',
      'target_currency': 'USD',
      'total_offers': 2,
      'lowest_offer': {
        'listing_id': 101,
        'source': {
          'id': 2,
          'code': 'alibaba',
          'name': 'Alibaba Wholesale',
        },
        'seller': {
          'username': null,
          'store_name': 'Shenzhen Audio Co.',
          'feedback_percentage': null,
          'feedback_score': null,
        },
        'external_id': 'ALB-SONY-101',
        'title': 'Sony Headphones Wholesale Batch',
        'url': null,
        'image_url': null,
        'original_price': '144.8000',
        'original_currency': 'CNY',
        'normalized_price': '20.0000',
        'target_currency': 'USD',
        'exchange_rate': '0.138122',
        'rating': 4.7,
        'reviews_count': 320,
        'is_available': true,
        'observed_at': '2026-11-09T10:00:00.000000Z',
      },
      'highest_offer': {
        'listing_id': 102,
        'source': {
          'id': 1,
          'code': 'ebay',
          'name': 'eBay Marketplace',
        },
        'seller': {
          'username': 'TechStoreUSA',
          'store_name': null,
          'feedback_percentage': 99.4,
          'feedback_score': 4200,
        },
        'external_id': 'v1|987654|0',
        'title': 'Sony Headphones Retail Box',
        'url': 'https://www.ebay.com/itm/987654',
        'image_url': 'https://i.ebayimg.com/images/g/sample.jpg',
        'original_price': '200.0000',
        'original_currency': 'USD',
        'normalized_price': '200.0000',
        'target_currency': 'USD',
        'exchange_rate': '1.000000',
        'rating': 4.9,
        'reviews_count': 85,
        'is_available': true,
        'observed_at': '2026-11-09T10:05:00.000000Z',
      },
      'average_price': '110.0000',
      'price_spread': '180.0000',
      'price_spread_percentage': 900.0,
      'potential_gross_margin_percentage': 90.0,
      'offers': [
        {
          'listing_id': 101,
          'source': {
            'id': 2,
            'code': 'alibaba',
            'name': 'Alibaba Wholesale',
          },
          'seller': {
            'username': null,
            'store_name': 'Shenzhen Audio Co.',
            'feedback_percentage': null,
            'feedback_score': null,
          },
          'external_id': 'ALB-SONY-101',
          'title': 'Sony Headphones Wholesale Batch',
          'url': null,
          'image_url': null,
          'original_price': '144.8000',
          'original_currency': 'CNY',
          'normalized_price': '20.0000',
          'target_currency': 'USD',
          'exchange_rate': '0.138122',
          'rating': 4.7,
          'reviews_count': 320,
          'is_available': true,
          'observed_at': '2026-11-09T10:00:00.000000Z',
        },
        {
          'listing_id': 102,
          'source': {
            'id': 1,
            'code': 'ebay',
            'name': 'eBay Marketplace',
          },
          'seller': {
            'username': 'TechStoreUSA',
            'store_name': null,
            'feedback_percentage': 99.4,
            'feedback_score': 4200,
          },
          'external_id': 'v1|987654|0',
          'title': 'Sony Headphones Retail Box',
          'url': 'https://www.ebay.com/itm/987654',
          'image_url': 'https://i.ebayimg.com/images/g/sample.jpg',
          'original_price': '200.0000',
          'original_currency': 'USD',
          'normalized_price': '200.0000',
          'target_currency': 'USD',
          'exchange_rate': '1.000000',
          'rating': 4.9,
          'reviews_count': 85,
          'is_available': true,
          'observed_at': '2026-11-09T10:05:00.000000Z',
        },
      ],
    },
  };
}

void main() {
  group('Price Comparison API Contract Tests', () {
    test('PriceComparisonModel parses exact Laravel PriceComparisonResource data', () {
      final data = Map<String, dynamic>.from(_sampleLaravelEnvelope()['data'] as Map);
      final model = PriceComparisonModel.fromJson(data);

      expect(model.canonicalProductId, 42);
      expect(model.canonicalProductName, 'Sony Noise Canceling Headphones');
      expect(model.targetCurrency, 'USD');
      expect(model.totalOffers, 2);
      expect(model.averagePrice, 110.0);
      expect(model.priceSpread, 180.0);
      expect(model.priceSpreadPercentage, 900.0);
      expect(model.potentialGrossMarginPercentage, 90.0);

      expect(model.lowestOffer, isNotNull);
      expect(model.lowestOffer!.listingId, 101);
      expect(model.lowestOffer!.source.id, 2);
      expect(model.lowestOffer!.source.code, 'alibaba');
      expect(model.lowestOffer!.source.name, 'Alibaba Wholesale');
      expect(model.lowestOffer!.seller, isNotNull);
      expect(model.lowestOffer!.seller!.username, isNull);
      expect(model.lowestOffer!.seller!.storeName, 'Shenzhen Audio Co.');
      expect(model.lowestOffer!.seller!.displayName, 'Shenzhen Audio Co.');
      expect(model.lowestOffer!.originalPrice, 144.8);
      expect(model.lowestOffer!.originalCurrency, 'CNY');
      expect(model.lowestOffer!.normalizedPrice, 20.0);
      expect(model.lowestOffer!.exchangeRate, 0.138122);
      expect(model.lowestOffer!.url, isNull);

      expect(model.highestOffer, isNotNull);
      expect(model.highestOffer!.source.code, 'ebay');
      expect(model.highestOffer!.seller!.displayName, 'TechStoreUSA');
      expect(model.highestOffer!.seller!.feedbackPercentage, 99.4);
      expect(model.highestOffer!.seller!.feedbackScore, 4200);
      expect(model.offers.length, 2);
    });

    test('PriceComparisonModel handles nullable seller and zero active offers safely', () {
      final offerWithNullSeller = ComparisonOfferModel.fromJson({
        'listing_id': 200,
        'source': {'id': 3, 'code': 'amazon', 'name': 'Amazon Marketplace'},
        'seller': null,
        'external_id': 'B000001',
        'title': 'Amazon Direct Offer',
        'url': 'https://www.amazon.com/dp/B000001',
        'image_url': null,
        'original_price': '50.0000',
        'original_currency': 'USD',
        'normalized_price': '50.0000',
        'target_currency': 'USD',
        'exchange_rate': '1.000000',
        'rating': null,
        'reviews_count': 0,
        'is_available': true,
        'observed_at': null,
      });

      expect(offerWithNullSeller.seller, isNull);
      expect(offerWithNullSeller.rating, isNull);

      final emptyComparison = PriceComparisonModel.fromJson({
        'canonical_product_id': 99,
        'canonical_product_name': 'Zero Offer Item',
        'target_currency': 'USD',
        'total_offers': 0,
        'lowest_offer': null,
        'highest_offer': null,
        'average_price': '0.0000',
        'price_spread': '0.0000',
        'price_spread_percentage': 0.0,
        'potential_gross_margin_percentage': 0.0,
        'offers': [],
      });

      expect(emptyComparison.totalOffers, 0);
      expect(emptyComparison.lowestOffer, isNull);
      expect(emptyComparison.highestOffer, isNull);
      expect(emptyComparison.priceSpread, 0.0);
      expect(emptyComparison.offers, isEmpty);
    });

    test('PriceComparisonRepository sends target_currency and parses response envelope', () async {
      final adapter = _StubHttpClientAdapter((options) => _sampleLaravelEnvelope());
      final dio = Dio(BaseOptions(baseUrl: 'https://api.example.test/api/v1'));
      dio.httpClientAdapter = adapter;

      final client = ApiClient(
        storage: _FakeSecureStorageService(),
        customDio: dio,
      );
      final repository = PriceComparisonRepositoryImpl(apiClient: client);

      final resultWithCurrency = await repository.getPriceComparison(
        42,
        targetCurrency: ' eur ',
      );

      expect(adapter.requests.length, 1);
      expect(adapter.requests[0].path, '/products/42/price-comparison');
      expect(adapter.requests[0].queryParameters['target_currency'], 'EUR');
      expect(adapter.requests[0].queryParameters.containsKey('currency'), isFalse);
      expect(resultWithCurrency.canonicalProductId, 42);
      expect(resultWithCurrency.offers.length, 2);

      await repository.getPriceComparison(42);
      expect(adapter.requests.length, 2);
      expect(adapter.requests[1].path, '/products/42/price-comparison');
      expect(adapter.requests[1].queryParameters, isEmpty);
    });

    test('PriceComparisonRepository throws FormatException on invalid envelope', () async {
      final adapter = _StubHttpClientAdapter((_) => {'success': true, 'data': []});
      final dio = Dio(BaseOptions(baseUrl: 'https://api.example.test/api/v1'));
      dio.httpClientAdapter = adapter;

      final repository = PriceComparisonRepositoryImpl(
        apiClient: ApiClient(
          storage: _FakeSecureStorageService(),
          customDio: dio,
        ),
      );

      expect(
        () => repository.getPriceComparison(42),
        throwsA(isA<FormatException>()),
      );
    });

    testWidgets('PriceComparisonScreen renders canonical fields and unavailable seller fallback', (tester) async {
      final model = PriceComparisonModel.fromJson({
        'canonical_product_id': 42,
        'canonical_product_name': 'Sony Noise Canceling Headphones',
        'target_currency': 'USD',
        'total_offers': 2,
        'lowest_offer': null,
        'highest_offer': null,
        'average_price': '110.0000',
        'price_spread': '180.0000',
        'price_spread_percentage': 900.0,
        'potential_gross_margin_percentage': 90.0,
        'offers': [
          {
            'listing_id': 101,
            'source': {'id': 2, 'code': 'alibaba', 'name': 'Alibaba Wholesale'},
            'seller': null,
            'external_id': 'ALB-101',
            'title': 'Wholesale Offer',
            'url': null,
            'image_url': null,
            'original_price': '144.8000',
            'original_currency': 'CNY',
            'normalized_price': '20.0000',
            'target_currency': 'USD',
            'exchange_rate': '0.138122',
            'rating': 4.7,
            'reviews_count': 320,
            'is_available': true,
            'observed_at': null,
          },
          {
            'listing_id': 102,
            'source': {'id': 1, 'code': 'ebay', 'name': 'eBay Marketplace'},
            'seller': {
              'username': 'TechStoreUSA',
              'store_name': null,
              'feedback_percentage': 99.4,
              'feedback_score': 4200,
            },
            'external_id': 'EBAY-102',
            'title': 'Retail Offer',
            'url': 'https://ebay.com/itm/102',
            'image_url': null,
            'original_price': '200.0000',
            'original_currency': 'USD',
            'normalized_price': '200.0000',
            'target_currency': 'USD',
            'exchange_rate': '1.000000',
            'rating': 4.9,
            'reviews_count': 85,
            'is_available': true,
            'observed_at': null,
          },
        ],
      });

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            priceComparisonProvider(42).overrideWith((ref) async => model),
          ],
          child: const MaterialApp(
            home: PriceComparisonScreen(productId: 42),
          ),
        ),
      );

      await tester.pumpAndSettle();

      expect(find.text('Sony Noise Canceling Headphones'), findsOneWidget);
      expect(find.text('\$180.00'), findsOneWidget);
      expect(find.text('90.0%'), findsOneWidget);
      expect(find.text('Alibaba Wholesale'), findsOneWidget);
      expect(find.text('eBay Marketplace'), findsOneWidget);
      expect(find.text('TechStoreUSA'), findsOneWidget);
      expect(find.text(AppStrings.dataUnavailable), findsOneWidget);
      expect(find.textContaining('144.80 CNY'), findsOneWidget);
    });
  });
}


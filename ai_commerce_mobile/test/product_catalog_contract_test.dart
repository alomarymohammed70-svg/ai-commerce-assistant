import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ai_commerce_mobile/core/constants/app_strings.dart';
import 'package:ai_commerce_mobile/core/errors/failure.dart';
import 'package:ai_commerce_mobile/core/network/api_client.dart';
import 'package:ai_commerce_mobile/core/storage/secure_storage_service.dart';
import 'package:ai_commerce_mobile/features/products/data/models/canonical_product_model.dart';
import 'package:ai_commerce_mobile/features/products/data/models/product_listing_model.dart';
import 'package:ai_commerce_mobile/features/products/data/repositories/product_repository.dart';
import 'package:ai_commerce_mobile/features/products/presentation/providers/product_notifier.dart';
import 'package:ai_commerce_mobile/features/products/presentation/screens/product_details_screen.dart';

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

class _StubResponse {
  final int statusCode;
  final dynamic body;

  const _StubResponse({required this.statusCode, required this.body});
}

class _StubHttpClientAdapter implements HttpClientAdapter {
  final _StubResponse Function(RequestOptions options) responder;
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

    final stub = responder(options);
    return ResponseBody.fromString(
      jsonEncode(stub.body),
      stub.statusCode,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

Map<String, dynamic> _realListEnvelope() {
  return {
    'success': true,
    'message': 'Products retrieved successfully',
    'data': [
      {
        'id': 1,
        'name': 'Sony WH-1000XM5 Wireless Headphones',
        'slug': 'sony-wh-1000xm5',
        'brand': 'Sony',
        'model_number': 'WH-1000XM5',
        'description': 'Flagship noise canceling headphones.',
        'base_currency': 'EUR',
        'category': {
          'id': 3,
          'name': 'Electronics',
          'slug': 'electronics',
          'description': null,
          'parent_id': null,
        },
        'price_summary': {
          'lowest_price': '18.5000',
          'highest_price': '92.5000',
          'average_price': '55.5000',
          'currency': 'EUR',
        },
        'active_listings_count': 2,
        'total_listings_count': 3,
        'rating': 4.75,
        'reviews_count': 320,
        'source': {
          'id': 1,
          'code': 'ebay',
          'name': 'eBay Marketplace',
          'base_url': 'https://www.ebay.com',
          'is_active': true,
        },
        'external_id': 'v1|1001|0',
        'latest_opportunity_score': 82.5,
        'latest_metric': {
          'id': 9,
          'opportunity_score': '82.50',
          'risk_score': '21.00',
        },
        'listings': [
          {
            'id': 101,
            'canonical_product_id': 1,
            'external_id': 'v1|1001|0',
            'source': {
              'id': 1,
              'code': 'ebay',
              'name': 'eBay Marketplace',
              'base_url': 'https://www.ebay.com',
              'is_active': true,
            },
            'title': 'Sony Headphones eBay Offer',
            'brand': 'Sony',
            'price': '100.0000',
            'original_price': null,
            'currency': 'USD',
            'rating': '4.80',
            'reviews_count': 120,
            'is_available': true,
            'url': 'https://www.ebay.com/itm/1001',
            'image_url': null,
            'observed_at': '2026-11-10T10:00:00.000000Z',
            'seller': {
              'username': 'TechStoreUSA',
              'store_name': null,
              'feedback_percentage': 99.4,
              'feedback_score': 4200,
            },
            'created_at': '2026-11-10T10:00:00.000000Z',
            'updated_at': '2026-11-10T10:00:00.000000Z',
          },
          {
            'id': 102,
            'canonical_product_id': 1,
            'external_id': 'ALB-1002',
            'source': {
              'id': 2,
              'code': 'alibaba',
              'name': 'Alibaba Wholesale',
              'base_url': 'https://www.alibaba.com',
              'is_active': true,
            },
            'title': 'Sony Headphones Alibaba Offer',
            'brand': 'Sony',
            'price': '144.8000',
            'original_price': null,
            'currency': 'CNY',
            'rating': '4.70',
            'reviews_count': 200,
            'is_available': true,
            'url': 'https://www.alibaba.com/item/1002',
            'image_url': null,
            'observed_at': '2026-11-10T10:05:00.000000Z',
            'seller': {
              'username': null,
              'store_name': 'Shenzhen Audio Co.',
              'feedback_percentage': null,
              'feedback_score': null,
            },
            'created_at': '2026-11-10T10:05:00.000000Z',
            'updated_at': '2026-11-10T10:05:00.000000Z',
          },
        ],
        'created_at': '2026-11-10T10:00:00.000000Z',
        'updated_at': '2026-11-10T10:00:00.000000Z',
      },
      {
        'id': 2,
        'name': 'Empty Catalog Product',
        'slug': 'empty-catalog-product',
        'brand': null,
        'model_number': null,
        'description': null,
        'base_currency': 'USD',
        'category': null,
        'price_summary': {
          'lowest_price': null,
          'highest_price': null,
          'average_price': null,
          'currency': 'USD',
        },
        'active_listings_count': 0,
        'total_listings_count': 1,
        'rating': null,
        'reviews_count': 0,
        'source': null,
        'external_id': null,
        'latest_opportunity_score': null,
        'latest_metric': null,
        'listings': [
          {
            'id': 201,
            'canonical_product_id': 2,
            'external_id': 'B00INACTIVE',
            'source': {
              'id': 3,
              'code': 'amazon',
              'name': 'Amazon Marketplace',
              'base_url': 'https://www.amazon.com',
              'is_active': true,
            },
            'title': 'Out of stock listing',
            'brand': null,
            'price': '99.0000',
            'original_price': null,
            'currency': 'USD',
            'rating': null,
            'reviews_count': 0,
            'is_available': false,
            'url': null,
            'image_url': null,
            'observed_at': null,
            'seller': null,
            'created_at': '2026-11-10T10:00:00.000000Z',
            'updated_at': '2026-11-10T10:00:00.000000Z',
          },
        ],
        'created_at': '2026-11-10T10:00:00.000000Z',
        'updated_at': '2026-11-10T10:00:00.000000Z',
      },
    ],
    'meta': {
      'current_page': 2,
      'per_page': 15,
      'total': 42,
      'last_page': 3,
      'has_more': true,
    },
  };
}

Map<String, dynamic> _realDetailsEnvelope() {
  return {
    'success': true,
    'message': 'Product details retrieved successfully',
    'data': {
      ...(_realListEnvelope()['data'] as List)[0] as Map<String, dynamic>,
      'metrics_history': [
        {
          'id': 9,
          'opportunity_score': '82.50',
          'risk_score': '21.00',
        },
      ],
    },
  };
}

ProductRepositoryImpl _makeRepository(_StubResponse Function(RequestOptions) responder, {_StubHttpClientAdapter? adapterOut}) {
  final adapter = adapterOut ?? _StubHttpClientAdapter(responder);
  final dio = Dio(BaseOptions(baseUrl: 'https://api.example.test/api/v1'));
  dio.httpClientAdapter = adapter;

  return ProductRepositoryImpl(
    apiClient: ApiClient(
      storage: _FakeSecureStorageService(),
      customDio: dio,
    ),
  );
}

void main() {
  group('Product Catalog API Contract Tests', () {
    test('getProducts parses paginated envelope, meta, price_summary, active listings, score, and nullable values', () async {
      final adapter = _StubHttpClientAdapter(
        (_) => _StubResponse(statusCode: 200, body: _realListEnvelope()),
      );
      final dio = Dio(BaseOptions(baseUrl: 'https://api.example.test/api/v1'));
      dio.httpClientAdapter = adapter;
      final repository = ProductRepositoryImpl(
        apiClient: ApiClient(storage: _FakeSecureStorageService(), customDio: dio),
      );

      final result = await repository.getProducts(
        page: 2,
        perPage: 15,
        search: 'Sony',
        categoryId: 3,
        sortBy: 'price',
        sortDirection: 'asc',
      );

      expect(adapter.requests.length, 1);
      expect(adapter.requests[0].path, '/products');
      expect(adapter.requests[0].queryParameters['page'], 2);
      expect(adapter.requests[0].queryParameters['category'], '3');
      expect(adapter.requests[0].queryParameters.containsKey('category_id'), isFalse);

      // Pagination meta preserved
      expect(result.meta.currentPage, 2);
      expect(result.meta.perPage, 15);
      expect(result.meta.total, 42);
      expect(result.meta.lastPage, 3);
      expect(result.meta.hasMore, isTrue);

      expect(result.items.length, 2);

      // First product: currency normalized prices & private/visible score
      final first = result.items[0];
      expect(first.id, 1);
      expect(first.name, 'Sony WH-1000XM5 Wireless Headphones');
      expect(first.categoryName, 'Electronics');
      expect(first.baseCurrency, 'EUR');
      expect(first.priceSummary.lowestPrice, 18.5);
      expect(first.priceSummary.highestPrice, 92.5);
      expect(first.priceSummary.averagePrice, 55.5);
      expect(first.priceSummary.currency, 'EUR');
      expect(first.activeListingsCount, 2);
      expect(first.totalListingsCount, 3);
      expect(first.latestOpportunityScore, 82.5);
      expect(first.listings[0].seller?.displayName, 'TechStoreUSA');
      expect(first.listings[1].seller?.displayName, 'Shenzhen Audio Co.');

      // Second product: empty/inactive listings -> strictly null prices (NOT 0.0) & activeListingsCount == 0
      final second = result.items[1];
      expect(second.id, 2);
      expect(second.categoryName, isNull);
      expect(second.lowestPrice, isNull);
      expect(second.highestPrice, isNull);
      expect(second.averagePrice, isNull);
      expect(second.activeListingsCount, 0);
      expect(second.totalListingsCount, 1);
      expect(second.latestOpportunityScore, isNull);
      expect(second.listings[0].seller, isNull);
      expect(second.listings[0].isAvailable, isFalse);
    });

    test('getProductDetails parses details envelope and requires canonical keys without fallback aliases', () async {
      final repository = _makeRepository(
        (_) => _StubResponse(statusCode: 200, body: _realDetailsEnvelope()),
      );

      final details = await repository.getProductDetails(1);
      expect(details.id, 1);
      expect(details.priceSummary.lowestPrice, 18.5);
      expect(details.priceSummary.highestPrice, 92.5);
      expect(details.activeListingsCount, 2);
      expect(details.latestOpportunityScore, 82.5);

      // Legacy shape missing canonical keys must NOT silently succeed via fallback aliases
      expect(
        () => CanonicalProductModel.fromJson({
          'id': 9,
          'title': 'Legacy Title Only',
          'lowest_price': 10.0,
          'active_listings_count': 1,
        }),
        throwsA(isA<FormatException>()),
      );

      // ProductListingModel ignores raw_payload completely when canonical seller is null
      final listingFromRawOnly = ProductListingModel.fromJson({
        'id': 55,
        'external_id': 'EXT-55',
        'title': 'Offer Without Safe Seller',
        'source': {'id': 1, 'code': 'ebay', 'name': 'eBay Marketplace'},
        'price': '12.0000',
        'currency': 'USD',
        'is_available': true,
        'seller': null,
        'raw_payload': {
          'seller': {'username': 'ShouldNotBeReadFromRaw'},
        },
      });
      expect(listingFromRawOnly.seller, isNull);
    });

    test('ProductRepository throws FormatException on invalid response envelopes', () async {
      final badListRepo = _makeRepository(
        (_) => const _StubResponse(statusCode: 200, body: {'success': true, 'data': []}),
      );
      expect(() => badListRepo.getProducts(), throwsA(isA<FormatException>()));

      final badDetailsRepo = _makeRepository(
        (_) => const _StubResponse(statusCode: 200, body: {'success': true, 'data': []}),
      );
      expect(() => badDetailsRepo.getProductDetails(1), throwsA(isA<FormatException>()));
    });

    test('ApiClient maps 401, 403, 404, 422, and 500 to distinct Failures without fake data', () async {
      final repo401 = _makeRepository(
        (_) => const _StubResponse(statusCode: 401, body: {'success': false, 'message': 'Unauthenticated'}),
      );
      await expectLater(repo401.getProductDetails(1), throwsA(isA<AuthFailure>()));

      final repo403 = _makeRepository(
        (_) => const _StubResponse(statusCode: 403, body: {'success': false, 'message': 'Access denied'}),
      );
      await expectLater(repo403.getProductDetails(1), throwsA(isA<ForbiddenFailure>()));

      final repo404 = _makeRepository(
        (_) => const _StubResponse(statusCode: 404, body: {'success': false, 'message': 'Resource not found'}),
      );
      await expectLater(repo404.getProductDetails(999), throwsA(isA<NotFoundFailure>()));

      final repo422 = _makeRepository(
        (_) => const _StubResponse(
          statusCode: 422,
          body: {
            'success': false,
            'message': 'Validation failed',
            'errors': {'sort_by': ['Invalid sort column.']},
          },
        ),
      );
      await expectLater(repo422.getProducts(sortBy: 'invalid'), throwsA(isA<ValidationFailure>()));

      final repo500 = _makeRepository(
        (_) => const _StubResponse(statusCode: 500, body: {'success': false, 'message': 'Server error occurred'}),
      );
      await expectLater(
        repo500.getProducts(),
        throwsA(predicate((e) => e is ServerFailure && e.statusCode == 500)),
      );
    });

    testWidgets('ProductDetailsScreen renders unavailable labels for null prices and null seller', (tester) async {
      final emptyProduct = CanonicalProductModel.fromJson(
        Map<String, dynamic>.from((_realListEnvelope()['data'] as List)[1] as Map),
      );

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            productDetailsProvider(2).overrideWith((ref) async => emptyProduct),
          ],
          child: const MaterialApp(
            home: ProductDetailsScreen(productId: 2),
          ),
        ),
      );

      await tester.pumpAndSettle();

      expect(find.text('Empty Catalog Product'), findsOneWidget);
      expect(find.text('0 نشط / 1 إجمالي'), findsOneWidget);
      // Lowest price + highest price + seller name + seller rating unavailable -> all use AppStrings.dataUnavailable
      expect(find.text(AppStrings.dataUnavailable), findsAtLeastNWidgets(3));
      expect(find.text('بائع معتمد'), findsNothing);
      expect(find.text('N/A'), findsNothing);
    });
  });
}


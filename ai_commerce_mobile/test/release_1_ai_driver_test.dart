import 'dart:convert';
import 'dart:typed_data';

import 'package:ai_commerce_mobile/core/config/env_config.dart';
import 'package:ai_commerce_mobile/core/network/api_client.dart';
import 'package:ai_commerce_mobile/core/providers/core_providers.dart';
import 'package:ai_commerce_mobile/core/storage/secure_storage_service.dart';
import 'package:ai_commerce_mobile/features/ai_analysis/presentation/screens/ai_analysis_screen.dart';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

class _FakeSecureStorageService extends SecureStorageService {
  @override
  Future<String?> getToken() async => 'test-token';

  @override
  Future<void> clearAuthData() async {}
}

class _CapturingHttpClientAdapter implements HttpClientAdapter {
  final List<Map<String, dynamic>> capturedPayloads = [];

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    if (options.data is Map<String, dynamic>) {
      capturedPayloads.add(Map<String, dynamic>.from(options.data as Map));
    } else if (options.data is Map) {
      capturedPayloads.add(Map<String, dynamic>.from(options.data as Map));
    }

    final responsePayload = {
      'success': true,
      'message': 'OK',
      'data': {
        'id': 1,
        'canonical_product_id': 10,
        'product_listing_id': null,
        'provider': 'openai',
        'model': 'gpt-4o-mini',
        'prompt_version': 'v1',
        'decision': 'BUY',
        'confidence': 0.85,
        'opportunity_score': 80.0,
        'risk_score': 20.0,
        'analysis': {
          'recommendation': {
            'decision': 'BUY',
            'confidence': 0.85,
            'summary': 'Production-compatible analysis.',
          },
          'opportunity': {
            'score': 80.0,
            'level': 'HIGH',
            'justification': 'Solid demand.',
          },
          'profitability': {
            'profit': '50.0000',
            'margin': 25.0,
            'assessment': 'Profitable.',
          },
          'competition': {
            'level': 'LOW',
            'score': 25.0,
            'summary': 'Low competition.',
          },
          'risk': {
            'level': 'LOW',
            'score': 20.0,
            'factors': [],
          },
          'reasons': ['Good margin'],
          'warnings': [],
          'action_plan': [],
          'data_truth_summary': {
            'observed_data_available': true,
            'missing_critical_data': [],
          },
        },
        'deterministic_metrics': {
          'purchase_price': {'value': '100.0000', 'truth': 'ESTIMATED'},
          'selling_price': {'value': '200.0000', 'truth': 'CALCULATED'},
          'shipping_cost': {'value': '15.0000', 'truth': 'ESTIMATED', 'is_verified': true},
          'estimated_fees': {'value': '10.0000', 'truth': 'ESTIMATED'},
          'other_costs': {'value': '0.0000', 'truth': 'ESTIMATED'},
          'total_cost': {'value': '125.0000', 'truth': 'CALCULATED'},
          'estimated_profit': {
            'value': '75.0000',
            'truth': 'CALCULATED',
            'status': 'CALCULATED_CONFIRMED',
          },
          'profit_margin_percent': {'value': 37.5, 'truth': 'CALCULATED'},
          'return_on_investment_percent': {'value': 60.0, 'truth': 'CALCULATED'},
          'is_profitable': {'value': true, 'truth': 'CALCULATED'},
        },
        'duration_ms': 120.5,
        'tokens_used': 250,
      },
    };

    return ResponseBody.fromString(
      jsonEncode(responsePayload),
      200,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

void main() {
  tearDown(() {
    EnvConfig.environment = AppEnvironment.development;
  });

  group('Release.1 — AI Production Driver Safety', () {
    test('resolveDefaultAiDriver forces openai in release mode even if mock overridden', () {
      expect(
        EnvConfig.resolveDefaultAiDriver(
          isReleaseMode: true,
          environmentOverride: AppEnvironment.development,
          appEnvOverride: 'local',
          aiDriverOverride: 'mock',
        ),
        'openai',
      );
    });

    test('resolveDefaultAiDriver forces openai when environment is production', () {
      expect(
        EnvConfig.resolveDefaultAiDriver(
          isReleaseMode: false,
          environmentOverride: AppEnvironment.production,
          appEnvOverride: 'local',
          aiDriverOverride: 'mock',
        ),
        'openai',
      );
      expect(
        EnvConfig.resolveDefaultAiDriver(
          isReleaseMode: false,
          environmentOverride: AppEnvironment.development,
          appEnvOverride: 'production',
          aiDriverOverride: '',
        ),
        'openai',
      );
    });

    test('resolveDefaultAiDriver keeps mock available in development', () {
      expect(
        EnvConfig.resolveDefaultAiDriver(
          isReleaseMode: false,
          environmentOverride: AppEnvironment.development,
          appEnvOverride: 'local',
          aiDriverOverride: '',
        ),
        'mock',
      );
    });

    testWidgets('AiAnalysisScreen sends openai (never mock) and hides Mock Engine in production', (tester) async {
      EnvConfig.environment = AppEnvironment.production;
      final adapter = _CapturingHttpClientAdapter();
      final storage = _FakeSecureStorageService();
      final dio = Dio(BaseOptions(baseUrl: 'https://example.test/api/v1'));
      dio.httpClientAdapter = adapter;
      final apiClient = ApiClient(storage: storage, customDio: dio);

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            secureStorageProvider.overrideWithValue(storage),
            apiClientProvider.overrideWithValue(apiClient),
          ],
          child: const MaterialApp(
            home: AiAnalysisScreen(
              canonicalProductId: 10,
              productTitle: 'Test Product',
              defaultPurchasePrice: 100,
              defaultSellingPrice: 200,
            ),
          ),
        ),
      );

      await tester.pumpAndSettle();

      expect(adapter.capturedPayloads, isNotEmpty);
      expect(adapter.capturedPayloads.first['driver'], 'openai');

      await tester.tap(find.byIcon(Icons.tune_outlined));
      await tester.pumpAndSettle();

      expect(find.text('Mock Engine'), findsNothing);
      expect(find.text('OpenAI'), findsOneWidget);
    });

    testWidgets('AiAnalysisScreen keeps Mock Engine selectable in development', (tester) async {
      EnvConfig.environment = AppEnvironment.development;
      final adapter = _CapturingHttpClientAdapter();
      final storage = _FakeSecureStorageService();
      final dio = Dio(BaseOptions(baseUrl: 'http://127.0.0.1:8000/api/v1'));
      dio.httpClientAdapter = adapter;
      final apiClient = ApiClient(storage: storage, customDio: dio);

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            secureStorageProvider.overrideWithValue(storage),
            apiClientProvider.overrideWithValue(apiClient),
          ],
          child: const MaterialApp(
            home: AiAnalysisScreen(
              canonicalProductId: 10,
              productTitle: 'Test Product',
              defaultPurchasePrice: 100,
              defaultSellingPrice: 200,
            ),
          ),
        ),
      );

      await tester.pumpAndSettle();

      expect(adapter.capturedPayloads, isNotEmpty);
      expect(adapter.capturedPayloads.first['driver'], 'mock');

      await tester.tap(find.byIcon(Icons.tune_outlined));
      await tester.pumpAndSettle();

      expect(find.text('Mock Engine'), findsOneWidget);
      expect(find.text('OpenAI'), findsOneWidget);
    });
  });
}


import 'dart:convert';
import 'dart:typed_data';

import 'package:ai_commerce_mobile/core/network/api_client.dart';
import 'package:ai_commerce_mobile/core/storage/secure_storage_service.dart';
import 'package:ai_commerce_mobile/features/ai_analysis/data/models/ai_analysis_model.dart';
import 'package:ai_commerce_mobile/features/ai_analysis/data/repositories/ai_analysis_repository.dart';
import 'package:ai_commerce_mobile/features/ai_analysis/presentation/providers/ai_analysis_notifier.dart';
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

class _StubHttpClientAdapter implements HttpClientAdapter {
  final Map<String, dynamic> Function(RequestOptions options) responder;

  _StubHttpClientAdapter(this.responder);

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
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

Map<String, dynamic> _sampleAiAnalysisData({required Object? durationMs}) {
  return {
    'id': 55,
    'canonical_product_id': 10,
    'product_listing_id': null,
    'provider': 'mock',
    'model': 'mock-v1',
    'prompt_version': 'v1',
    'decision': 'BUY',
    'confidence': 0.85,
    'opportunity_score': 82.0,
    'risk_score': 24.5,
    'analysis': {
      'recommendation': {
        'decision': 'BUY',
        'confidence': 0.85,
        'summary': 'Verified profitable product.',
      },
      'opportunity': {
        'score': 82.0,
        'level': 'HIGH',
        'justification': 'Strong spread.',
      },
      'profitability': {
        'profit': '154.9900',
        'margin': 25.83,
        'assessment': 'Profitable.',
      },
      'competition': {
        'level': 'LOW',
        'score': 20.0,
        'summary': 'Low competition.',
      },
      'risk': {
        'level': 'LOW',
        'score': 24.5,
        'factors': ['Normal market fluctuation'],
      },
      'reasons': ['Healthy profit margin'],
      'warnings': [],
      'action_plan': ['Verify supplier stock'],
      'data_truth_summary': {
        'observed_data_available': true,
        'missing_critical_data': [],
      },
    },
    'deterministic_metrics': {
      'purchase_price': {'value': '380.0000', 'tag': 'OBSERVED'},
      'selling_price': {'value': '599.9900', 'tag': 'OBSERVED'},
      'shipping_cost': {'value': '20.0000', 'tag': 'OBSERVED', 'is_verified': true},
      'estimated_fees': {'value': '45.0000', 'tag': 'ESTIMATED'},
      'other_costs': {'value': '0.0000', 'tag': 'OBSERVED'},
      'total_cost': {'value': '445.0000', 'tag': 'CALCULATED'},
      'estimated_profit': {
        'value': '154.9900',
        'tag': 'CALCULATED',
        'status': 'CALCULATED_CONFIRMED',
      },
      'profit_margin_percent': {'value': 25.83, 'tag': 'CALCULATED'},
      'return_on_investment_percent': {'value': 34.83, 'tag': 'CALCULATED'},
      'is_profitable': {'value': true, 'tag': 'CALCULATED'},
    },
    'duration_ms': durationMs,
    'tokens_used': 120,
    'created_at': '2026-11-10T12:00:00.000000Z',
  };
}

AiAnalysisRepository _buildStubRepository({required Object? durationMs}) {
  final dio = Dio(BaseOptions(baseUrl: 'http://localhost/api/v1'));
  dio.httpClientAdapter = _StubHttpClientAdapter((options) {
    return {
      'success': true,
      'message': 'AI commerce intelligence analysis completed successfully.',
      'data': _sampleAiAnalysisData(durationMs: durationMs),
    };
  });
  final apiClient = ApiClient(storage: _FakeSecureStorageService(), customDio: dio);
  return AiAnalysisRepository(apiClient);
}

void main() {
  group('Batch 4 Step 1 — AI duration_ms Contract Tests', () {
    test('parses decimal number duration_ms into double without rounding loss', () {
      final model = AIAnalysisModel.fromJson(_sampleAiAnalysisData(durationMs: 14.56));
      expect(model.durationMs, isA<double>());
      expect(model.durationMs, 14.56);
    });

    test('parses integer duration_ms into double', () {
      final model = AIAnalysisModel.fromJson(_sampleAiAnalysisData(durationMs: 540));
      expect(model.durationMs, isA<double>());
      expect(model.durationMs, 540.0);
    });

    test('backward-compatibly parses numeric string duration_ms', () {
      final decimalStringModel = AIAnalysisModel.fromJson(_sampleAiAnalysisData(durationMs: '14.56'));
      expect(decimalStringModel.durationMs, 14.56);

      final intStringModel = AIAnalysisModel.fromJson(_sampleAiAnalysisData(durationMs: '14'));
      expect(intStringModel.durationMs, 14.0);
    });

    test('preserves genuine 0 as 0.0 distinctly from null', () {
      final zeroIntModel = AIAnalysisModel.fromJson(_sampleAiAnalysisData(durationMs: 0));
      expect(zeroIntModel.durationMs, 0.0);

      final zeroDoubleModel = AIAnalysisModel.fromJson(_sampleAiAnalysisData(durationMs: 0.0));
      expect(zeroDoubleModel.durationMs, 0.0);
    });

    test('maps null, missing, empty, or invalid duration_ms to null (never 0)', () {
      final nullModel = AIAnalysisModel.fromJson(_sampleAiAnalysisData(durationMs: null));
      expect(nullModel.durationMs, isNull);

      final omittedMap = _sampleAiAnalysisData(durationMs: null)..remove('duration_ms');
      final omittedModel = AIAnalysisModel.fromJson(omittedMap);
      expect(omittedModel.durationMs, isNull);

      final emptyStringModel = AIAnalysisModel.fromJson(_sampleAiAnalysisData(durationMs: ''));
      expect(emptyStringModel.durationMs, isNull);

      final invalidStringModel = AIAnalysisModel.fromJson(_sampleAiAnalysisData(durationMs: 'not-a-number'));
      expect(invalidStringModel.durationMs, isNull);
    });

    test('repository parses POST and GET Laravel payloads identically for decimal and null duration_ms', () async {
      final decimalRepo = _buildStubRepository(durationMs: 14.56);
      final postResult = await decimalRepo.analyzeProduct(canonicalProductId: 10);
      final getResult = await decimalRepo.getHistoricalAnalysis(55);
      expect(postResult.durationMs, 14.56);
      expect(getResult.durationMs, 14.56);

      final nullRepo = _buildStubRepository(durationMs: null);
      final nullPostResult = await nullRepo.analyzeProduct(canonicalProductId: 10);
      final nullGetResult = await nullRepo.getHistoricalAnalysis(55);
      expect(nullPostResult.durationMs, isNull);
      expect(nullGetResult.durationMs, isNull);
    });

    testWidgets('AiAnalysisScreen renders formatted decimal ms when present', (tester) async {
      final repo = _buildStubRepository(durationMs: 14.56);

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            aiAnalysisRepositoryProvider.overrideWithValue(repo),
          ],
          child: const MaterialApp(
            home: AiAnalysisScreen(
              canonicalProductId: 10,
              productTitle: 'Garmin Fenix 7 Solar',
            ),
          ),
        ),
      );

      await tester.pumpAndSettle();

      expect(find.text('الوقت: 14.56 ms'), findsOneWidget);
    });

    testWidgets('AiAnalysisScreen renders غير متاح when durationMs is null and never 0ms', (tester) async {
      final repo = _buildStubRepository(durationMs: null);

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            aiAnalysisRepositoryProvider.overrideWithValue(repo),
          ],
          child: const MaterialApp(
            home: AiAnalysisScreen(
              canonicalProductId: 10,
              productTitle: 'Garmin Fenix 7 Solar',
            ),
          ),
        ),
      );

      await tester.pumpAndSettle();

      expect(find.text('الوقت: غير متاح'), findsOneWidget);
      expect(find.textContaining('0ms'), findsNothing);
      expect(find.textContaining('0 ms'), findsNothing);
    });
  });
}


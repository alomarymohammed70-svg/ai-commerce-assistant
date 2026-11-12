import 'dart:convert';
import 'dart:typed_data';

import 'package:ai_commerce_mobile/core/network/api_client.dart';
import 'package:ai_commerce_mobile/core/storage/secure_storage_service.dart';
import 'package:ai_commerce_mobile/features/ai_analysis/data/models/ai_recommendation_model.dart';
import 'package:ai_commerce_mobile/features/ai_analysis/data/models/financial_metrics_model.dart';
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

Map<String, dynamic> _realLaravelAiPayload({
  Object? marginValue = 26.3636,
  Object? roiValue = 35.8024,
  Object? analysisMargin = 26.3636,
  bool wrapInTruthObjects = true,
}) {
  return {
    'id': 91,
    'canonical_product_id': 10,
    'product_listing_id': null,
    'provider': 'mock',
    'model': 'mock-commerce-intelligence-v1',
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
        'justification': 'Strong margin.',
      },
      'profitability': {
        'profit': '58.0000',
        'margin': analysisMargin,
        'assessment': 'Healthy spread above landed cost.',
      },
      'competition': {
        'level': 'LOW',
        'score': 20.0,
        'summary': 'Low competition.',
      },
      'risk': {
        'level': 'LOW',
        'score': 24.5,
        'factors': ['Normal variance'],
      },
      'reasons': ['Solid margin'],
      'warnings': [],
      'action_plan': ['Verify supplier stock'],
      'data_truth_summary': {
        'observed_data_available': true,
        'missing_critical_data': [],
      },
    },
    'deterministic_metrics': {
      'purchase_price': {'value': '120.0000', 'truth': 'ESTIMATED'},
      'selling_price': {'value': '220.0000', 'truth': 'CALCULATED'},
      'shipping_cost': {'value': '15.0000', 'truth': 'ESTIMATED', 'is_verified': true},
      'estimated_fees': {'value': '22.0000', 'truth': 'ESTIMATED'},
      'other_costs': {'value': '5.0000', 'truth': 'ESTIMATED'},
      'total_cost': {'value': '162.0000', 'truth': 'CALCULATED'},
      'estimated_profit': {
        'value': '58.0000',
        'truth': 'CALCULATED',
        'status': 'CALCULATED_CONFIRMED',
      },
      'profit_margin_percent': wrapInTruthObjects
          ? {'value': marginValue, 'truth': 'CALCULATED'}
          : marginValue,
      'return_on_investment_percent': wrapInTruthObjects
          ? {'value': roiValue, 'truth': 'CALCULATED'}
          : roiValue,
      'is_profitable': {'value': true, 'truth': 'CALCULATED'},
    },
    'duration_ms': 14.56,
    'tokens_used': 120,
    'created_at': '2026-11-10T12:00:00.000000Z',
  };
}

AiAnalysisRepository _buildStubRepository({
  required Map<String, dynamic> Function(RequestOptions options) responder,
}) {
  final dio = Dio(BaseOptions(baseUrl: 'http://localhost/api/v1'));
  dio.httpClientAdapter = _StubHttpClientAdapter(responder);
  final apiClient = ApiClient(storage: _FakeSecureStorageService(), customDio: dio);
  return AiAnalysisRepository(apiClient);
}

void main() {
  group('Batch 4 Step 2 Scope 2 — Percentage & Presentation Integrity Contract Tests', () {
    test('FinancialMetricsModel parses nested real Laravel percentages cleanly into double?', () {
      final metrics = FinancialMetricsModel.fromJson({
        'purchase_price': {'value': '120.0000', 'truth': 'ESTIMATED'},
        'selling_price': {'value': '220.0000', 'truth': 'CALCULATED'},
        'shipping_cost': {'value': '15.0000', 'truth': 'ESTIMATED', 'is_verified': true},
        'estimated_fees': {'value': '22.0000', 'truth': 'ESTIMATED'},
        'other_costs': {'value': '5.0000', 'truth': 'ESTIMATED'},
        'total_cost': {'value': '162.0000', 'truth': 'CALCULATED'},
        'estimated_profit': {'value': '58.0000', 'truth': 'CALCULATED'},
        'profit_margin_percent': {'value': 26.3636, 'truth': 'CALCULATED'},
        'return_on_investment_percent': {'value': 35.8024, 'truth': 'CALCULATED'},
        'is_profitable': {'value': true, 'truth': 'CALCULATED'},
      });

      expect(metrics.profitMarginPercent, 26.3636);
      expect(metrics.roiPercent, 35.8024);
    });

    test('FinancialMetricsModel parses flat numeric percentage payload and preserves null/zero distinctly', () {
      final flatNumeric = FinancialMetricsModel.fromJson({
        'profit_margin_percent': 26.3636,
        'roi_percent': 35.8024,
      });
      expect(flatNumeric.profitMarginPercent, 26.3636);
      expect(flatNumeric.roiPercent, 35.8024);

      final flatNull = FinancialMetricsModel.fromJson({
        'profit_margin_percent': null,
        'roi_percent': null,
      });
      expect(flatNull.profitMarginPercent, isNull);
      expect(flatNull.roiPercent, isNull);

      final nestedNull = FinancialMetricsModel.fromJson({
        'profit_margin_percent': {'value': null, 'truth': 'CALCULATED'},
        'return_on_investment_percent': {'value': null, 'truth': 'CALCULATED'},
      });
      expect(nestedNull.profitMarginPercent, isNull);
      expect(nestedNull.roiPercent, isNull);

      final zeroMetrics = FinancialMetricsModel.fromJson({
        'profit_margin_percent': 0,
        'roi_percent': 0.0,
      });
      expect(zeroMetrics.profitMarginPercent, 0.0);
      expect(zeroMetrics.profitMarginPercent, isNotNull);
      expect(zeroMetrics.roiPercent, 0.0);
      expect(zeroMetrics.roiPercent, isNotNull);
    });

    test('ProfitabilityData parses numeric margin and defensively strips legacy % without null collapse', () {
      final canonical = ProfitabilityData.fromJson({
        'profit': '58.0000',
        'margin': 26.3636,
        'assessment': 'Profitable',
      });
      expect(canonical.margin, 26.3636);

      final legacy = ProfitabilityData.fromJson({
        'profit': '58.0000',
        'margin': '26.3636%',
        'assessment': 'Profitable',
      });
      expect(legacy.margin, 26.3636);

      final unavailable = ProfitabilityData.fromJson({
        'profit': '0.0000',
        'margin': null,
        'assessment': 'Unavailable',
      });
      expect(unavailable.margin, isNull);
    });

    test('repository preserves identical numeric percentage doubles across POST and GET', () async {
      final repo = _buildStubRepository(
        responder: (_) => {
          'success': true,
          'message': 'OK',
          'data': _realLaravelAiPayload(
            marginValue: 26.3636,
            roiValue: 35.8024,
            analysisMargin: 26.3636,
          ),
        },
      );

      final postResult = await repo.analyzeProduct(canonicalProductId: 10);
      final getResult = await repo.getHistoricalAnalysis(91);

      expect(postResult.deterministicMetrics.profitMarginPercent, 26.3636);
      expect(getResult.deterministicMetrics.profitMarginPercent, 26.3636);
      expect(postResult.deterministicMetrics.roiPercent, 35.8024);
      expect(getResult.deterministicMetrics.roiPercent, 35.8024);
      expect(postResult.analysis.profitability.margin, 26.3636);
      expect(getResult.analysis.profitability.margin, 26.3636);
    });

    testWidgets('AiAnalysisScreen renders 26.36% once for real Laravel payload and never renders %%', (tester) async {
      final repo = _buildStubRepository(
        responder: (_) => {
          'success': true,
          'message': 'OK',
          'data': _realLaravelAiPayload(
            marginValue: 26.3636,
            roiValue: 35.8024,
            analysisMargin: 26.3636,
          ),
        },
      );

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            aiAnalysisRepositoryProvider.overrideWithValue(repo),
          ],
          child: const MaterialApp(
            home: AiAnalysisScreen(
              canonicalProductId: 10,
              productTitle: 'HP OfficeJet Pro 9015e',
            ),
          ),
        ),
      );

      await tester.pumpAndSettle();

      expect(find.text('26.36%'), findsOneWidget);
      expect(find.textContaining('%%'), findsNothing);
    });

    testWidgets('AiAnalysisScreen prevents %% even when receiving legacy "%" string payload', (tester) async {
      final repo = _buildStubRepository(
        responder: (_) => {
          'success': true,
          'message': 'OK',
          'data': _realLaravelAiPayload(
            marginValue: '26.3636%',
            roiValue: '35.8024%',
            analysisMargin: '26.3636%',
          ),
        },
      );

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            aiAnalysisRepositoryProvider.overrideWithValue(repo),
          ],
          child: const MaterialApp(
            home: AiAnalysisScreen(
              canonicalProductId: 10,
              productTitle: 'HP OfficeJet Pro 9015e',
            ),
          ),
        ),
      );

      await tester.pumpAndSettle();

      expect(find.text('26.36%'), findsOneWidget);
      expect(find.textContaining('%%'), findsNothing);
    });

    testWidgets('AiAnalysisScreen displays غير متاح when profitMarginPercent is null and never renders 0% or %', (tester) async {
      final repo = _buildStubRepository(
        responder: (_) => {
          'success': true,
          'message': 'OK',
          'data': _realLaravelAiPayload(
            marginValue: null,
            roiValue: null,
            analysisMargin: null,
          ),
        },
      );

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            aiAnalysisRepositoryProvider.overrideWithValue(repo),
          ],
          child: const MaterialApp(
            home: AiAnalysisScreen(
              canonicalProductId: 10,
              productTitle: 'HP OfficeJet Pro 9015e',
            ),
          ),
        ),
      );

      await tester.pumpAndSettle();

      expect(find.text('غير متاح'), findsOneWidget);
      expect(find.text('0%'), findsNothing);
      expect(find.text('0.00%'), findsNothing);
      expect(find.text('%'), findsNothing);
      expect(find.textContaining('%%'), findsNothing);
    });
  });
}


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

Map<String, dynamic> _sampleAiPayload({
  Object? confidence = 0.875,
  Object? opportunityScore = 42.5,
  Object? riskScore = 75,
  String decision = 'WATCH',
}) {
  return {
    'id': 88,
    'canonical_product_id': 10,
    'product_listing_id': null,
    'provider': 'mock',
    'model': 'mock-v1',
    'prompt_version': 'v1',
    'decision': decision,
    'confidence': confidence,
    'opportunity_score': opportunityScore,
    'risk_score': riskScore,
    'analysis': {
      'recommendation': {
        'decision': decision,
        'confidence': confidence,
        'summary': 'Deterministic scores enforced by Laravel.',
      },
      'opportunity': {
        'score': opportunityScore,
        'level': 'LOW',
        'justification': 'Deterministic opportunity assessment.',
      },
      'profitability': {
        'profit': '154.9900',
        'margin': 25.83,
        'assessment': 'Profitable.',
      },
      'competition': {
        'level': 'MEDIUM',
        'score': 50.0,
        'summary': 'Balanced competition.',
      },
      'risk': {
        'level': 'HIGH',
        'score': riskScore,
        'factors': ['Elevated composite risk'],
      },
      'reasons': ['Margin above threshold'],
      'warnings': [],
      'action_plan': ['Verify supplier'],
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
  group('Batch 4 Step 2 Scope 1 — AI Numeric Score Integrity Contract Tests', () {
    test('parses canonical JSON numbers and preserves 0.875 confidence and decimal scores without rounding loss', () {
      final model = AIAnalysisModel.fromJson(
        _sampleAiPayload(
          confidence: 0.875,
          opportunityScore: 42.5,
          riskScore: 75,
        ),
      );

      expect(model.confidence, 0.875);
      expect(model.opportunityScore, 42.5);
      expect(model.riskScore, 75.0);
      expect(model.analysis.recommendation.confidence, 0.875);
      expect(model.analysis.opportunity.score, 42.5);
      expect(model.analysis.risk.score, 75.0);
    });

    test('backward-compatibly parses numeric string scores while rejecting invalid strings', () {
      final model = AIAnalysisModel.fromJson(
        _sampleAiPayload(
          confidence: '0.875',
          opportunityScore: '82.00',
          riskScore: '24.50',
        ),
      );

      expect(model.confidence, 0.875);
      expect(model.opportunityScore, 82.0);
      expect(model.riskScore, 24.5);
      expect(model.analysis.recommendation.confidence, 0.875);
      expect(model.analysis.opportunity.score, 82.0);
      expect(model.analysis.risk.score, 24.5);
    });

    test('preserves genuine 0 / 0.0 scores distinctly from null', () {
      final zeroModel = AIAnalysisModel.fromJson(
        _sampleAiPayload(
          confidence: 0,
          opportunityScore: 0.0,
          riskScore: 0,
        ),
      );

      expect(zeroModel.confidence, 0.0);
      expect(zeroModel.confidence, isNotNull);
      expect(zeroModel.opportunityScore, 0.0);
      expect(zeroModel.opportunityScore, isNotNull);
      expect(zeroModel.riskScore, 0.0);
      expect(zeroModel.riskScore, isNotNull);
      expect(zeroModel.analysis.recommendation.confidence, 0.0);
      expect(zeroModel.analysis.opportunity.score, 0.0);
      expect(zeroModel.analysis.risk.score, 0.0);
    });

    test('maps null, missing keys, empty strings, and invalid strings to null (never 0.0)', () {
      final nullModel = AIAnalysisModel.fromJson(
        _sampleAiPayload(
          confidence: null,
          opportunityScore: null,
          riskScore: null,
          decision: 'INSUFFICIENT_DATA',
        ),
      );

      expect(nullModel.confidence, isNull);
      expect(nullModel.opportunityScore, isNull);
      expect(nullModel.riskScore, isNull);
      expect(nullModel.analysis.recommendation.confidence, isNull);
      expect(nullModel.analysis.opportunity.score, isNull);
      expect(nullModel.analysis.risk.score, isNull);

      final omittedJson = _sampleAiPayload(
        confidence: '',
        opportunityScore: 'N/A',
        riskScore: '',
      )
        ..remove('confidence')
        ..remove('opportunity_score')
        ..remove('risk_score');
      final omittedModel = AIAnalysisModel.fromJson(omittedJson);

      expect(omittedModel.confidence, isNull);
      expect(omittedModel.opportunityScore, isNull);
      expect(omittedModel.riskScore, isNull);
      expect(omittedModel.analysis.recommendation.confidence, isNull);
      expect(omittedModel.analysis.opportunity.score, isNull);
      expect(omittedModel.analysis.risk.score, isNull);
    });

    test('repository returns identical double scores for POST and GET canonical payloads', () async {
      final repo = _buildStubRepository(
        responder: (_) => {
          'success': true,
          'message': 'OK',
          'data': _sampleAiPayload(
            confidence: 0.875,
            opportunityScore: 82,
            riskScore: 24.5,
            decision: 'BUY',
          ),
        },
      );

      final postResult = await repo.analyzeProduct(canonicalProductId: 10);
      final getResult = await repo.getHistoricalAnalysis(88);

      expect(postResult.confidence, 0.875);
      expect(getResult.confidence, 0.875);
      expect(postResult.opportunityScore, 82.0);
      expect(getResult.opportunityScore, 82.0);
      expect(postResult.riskScore, 24.5);
      expect(getResult.riskScore, 24.5);
    });

    testWidgets('AiAnalysisScreen preserves decimal confidence 87.5% and deterministic scores cleanly', (tester) async {
      final repo = _buildStubRepository(
        responder: (_) => {
          'success': true,
          'message': 'OK',
          'data': _sampleAiPayload(
            confidence: 0.875,
            opportunityScore: 42.5,
            riskScore: 75,
            decision: 'WATCH',
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
              productTitle: 'Garmin Fenix 7 Solar',
            ),
          ),
        ),
      );

      await tester.pumpAndSettle();

      expect(find.text('مستوى الثقة: 87.5%'), findsOneWidget);
      expect(find.text('42.5'), findsOneWidget);
      expect(find.text('75'), findsOneWidget);
      expect(find.text('/100'), findsNWidgets(2));
    });

    testWidgets('AiAnalysisScreen displays غير متاح when scores are null and never renders 0% or 0/100', (tester) async {
      final repo = _buildStubRepository(
        responder: (_) => {
          'success': true,
          'message': 'OK',
          'data': _sampleAiPayload(
            confidence: null,
            opportunityScore: null,
            riskScore: null,
            decision: 'INSUFFICIENT_DATA',
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
              productTitle: 'Garmin Fenix 7 Solar',
            ),
          ),
        ),
      );

      await tester.pumpAndSettle();

      expect(find.text('مستوى الثقة: غير متاح'), findsOneWidget);
      expect(find.text('غير متاح'), findsNWidgets(2));
      expect(find.text('مستوى الثقة: 0%'), findsNothing);
      expect(find.text('/100'), findsNothing);
      expect(find.text('0'), findsNothing);
      expect(find.text('0.0'), findsNothing);
    });
  });
}


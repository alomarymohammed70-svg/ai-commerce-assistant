import 'package:ai_commerce_mobile/core/network/api_client.dart';
import 'package:ai_commerce_mobile/core/storage/secure_storage_service.dart';
import 'package:ai_commerce_mobile/features/ai_analysis/data/models/financial_metrics_model.dart';
import 'package:ai_commerce_mobile/features/ai_analysis/data/repositories/ai_analysis_repository.dart';
import 'package:ai_commerce_mobile/features/ai_analysis/presentation/providers/ai_analysis_notifier.dart';
import 'package:ai_commerce_mobile/features/ai_analysis/presentation/screens/ai_analysis_screen.dart';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'dart:convert';
import 'dart:typed_data';

class _FakeSecureStorageService extends SecureStorageService {
  @override
  Future<String?> getToken() async => 'test-token';
}

class _StubHttpClientAdapter implements HttpClientAdapter {
  final Map<String, dynamic> Function(RequestOptions options) responder;
  _StubHttpClientAdapter(this.responder);

  @override
  void close({bool force = false}) {}

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    final payload = responder(options);
    return ResponseBody.fromString(
      jsonEncode(payload),
      200,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }
}

void main() {
  group('E6.2.2 — AI Data Truth Source Alignment', () {
    test('FinancialMetricsModel parses Backend truth labels and rejects verified banner for ESTIMATED shipping', () {
      final metrics = FinancialMetricsModel.fromJson({
        'purchase_price': {'value': '99.5000', 'truth': 'ESTIMATED'},
        'selling_price': {'value': '149.9900', 'truth': 'CALCULATED'},
        'shipping_cost': {'value': '15.0000', 'truth': 'ESTIMATED', 'is_verified': false},
        'estimated_fees': {'value': '10.0000', 'truth': 'ESTIMATED'},
        'other_costs': {'value': '0.0000', 'truth': 'ESTIMATED'},
        'total_cost': {'value': '124.5000', 'truth': 'CALCULATED'},
        'estimated_profit': {'value': '25.4900', 'truth': 'CALCULATED', 'status': 'ESTIMATED_PENDING_SHIPPING'},
        'profit_margin_percent': {'value': 16.9944, 'truth': 'CALCULATED'},
        'return_on_investment_percent': {'value': 20.4738, 'truth': 'CALCULATED'},
        'is_profitable': {'value': true, 'truth': 'CALCULATED'},
      });

      expect(metrics.purchaseTruth, 'ESTIMATED');
      expect(metrics.sellingTruth, 'CALCULATED');
      expect(metrics.shippingTruth, 'ESTIMATED');
      expect(metrics.feesTruth, 'ESTIMATED');
      expect(metrics.totalCostTruth, 'CALCULATED');
      expect(metrics.hasVerifiedShippingSource, isFalse);

      // Even if a legacy payload passes is_verified: true with truth: ESTIMATED, never treat it as verified
      final legacyEstimated = FinancialMetricsModel.fromJson({
        'shipping_cost': {'value': '15.0000', 'truth': 'ESTIMATED', 'is_verified': true},
      });
      expect(legacyEstimated.hasVerifiedShippingSource, isFalse);
    });

    testWidgets('AiAnalysisScreen renders exact Backend truth badges and never forces OBSERVED or confirmed shipping', (tester) async {
      final dio = Dio(BaseOptions(baseUrl: 'http://localhost/api/v1'));
      dio.httpClientAdapter = _StubHttpClientAdapter((_) => {
            'success': true,
            'message': 'OK',
            'data': {
              'id': 8,
              'canonical_product_id': 2,
              'provider': 'mock',
              'model': 'mock-commerce-intelligence-v1',
              'prompt_version': 'v1',
              'decision': 'WATCH',
              'confidence': 0.70,
              'opportunity_score': 63.8,
              'risk_score': 17.0,
              'analysis': {
                'recommendation': {
                  'decision': 'WATCH',
                  'confidence': 0.70,
                  'summary': 'Moderate prospects.',
                },
                'opportunity': {'score': 63.8, 'level': 'MEDIUM', 'justification': 'OK'},
                'profitability': {'profit': '25.4900', 'margin': 16.9944, 'assessment': '[ESTIMATED] Healthy spread.'},
                'competition': {'level': 'high', 'score': 36.2, 'summary': 'High'},
                'risk': {'level': 'LOW', 'score': 17.0, 'factors': ['Volatility']},
                'reasons': ['Margin 16.9944%'],
                'warnings': ['Net profit is ESTIMATED and pending verified carrier shipping rate quote.'],
                'action_plan': ['Request verified carrier shipping quote.'],
                'data_truth_summary': {
                  'observed_data_available': true,
                  'missing_critical_data': ['verified_shipping_cost_estimate'],
                },
              },
              'deterministic_metrics': {
                'currency': {'value': 'USD', 'truth': 'CALCULATED'},
                'purchase_price': {'value': '99.5000', 'truth': 'ESTIMATED'},
                'selling_price': {'value': '149.9900', 'truth': 'CALCULATED'},
                'shipping_cost': {'value': '15.0000', 'truth': 'ESTIMATED', 'is_verified': false},
                'estimated_fees': {'value': '10.0000', 'truth': 'ESTIMATED'},
                'other_costs': {'value': '0.0000', 'truth': 'ESTIMATED'},
                'total_cost': {'value': '124.5000', 'truth': 'CALCULATED'},
                'estimated_profit': {
                  'value': '25.4900',
                  'truth': 'CALCULATED',
                  'status': 'ESTIMATED_PENDING_SHIPPING',
                },
                'profit_margin_percent': {'value': 16.9944, 'truth': 'CALCULATED'},
                'return_on_investment_percent': {'value': 20.4738, 'truth': 'CALCULATED'},
                'is_profitable': {'value': true, 'truth': 'CALCULATED'},
              },
              'duration_ms': 5.0,
              'tokens_used': 380,
              'created_at': '2026-11-12T18:20:00Z',
            },
          });

      final repo = AiAnalysisRepository(ApiClient(storage: _FakeSecureStorageService(), customDio: dio));

      await tester.pumpWidget(
        ProviderScope(
          overrides: [aiAnalysisRepositoryProvider.overrideWithValue(repo)],
          child: const MaterialApp(
            home: AiAnalysisScreen(
              canonicalProductId: 2,
              productTitle: 'Anker 737 Power Bank',
              defaultPurchasePrice: 99.50,
              defaultSellingPrice: 149.99,
            ),
          ),
        ),
      );

      await tester.pumpAndSettle();

      // Must NEVER display OBSERVED when Backend truth is ESTIMATED / CALCULATED
      expect(find.text('OBSERVED'), findsNothing);
      // Purchase (ESTIMATED), Shipping (ESTIMATED), Fees (ESTIMATED), Other Costs (ESTIMATED) => 4 ESTIMATED badges
      expect(find.text('ESTIMATED'), findsNWidgets(4));
      // Selling Price (CALCULATED), Total Cost (CALCULATED) => 2 CALCULATED badges
      expect(find.text('CALCULATED'), findsNWidgets(2));
      // Must NEVER claim verified shipping / confirmed profit
      expect(find.text('✓ أرباح مؤكدة — الشحن موثق'), findsNothing);
      expect(find.text('⚠️ أرباح تقديرية — الشحن غير مؤكد'), findsOneWidget);
    });
  });
}

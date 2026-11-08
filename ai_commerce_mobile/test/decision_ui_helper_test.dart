import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ai_commerce_mobile/core/constants/app_colors.dart';
import 'package:ai_commerce_mobile/core/utils/decision_ui_helper.dart';

void main() {
  group('DecisionUiHelper Tests', () {
    test('getDecisionColor returns correct semantic colors', () {
      expect(DecisionUiHelper.getDecisionColor('BUY'), AppColors.buy);
      expect(DecisionUiHelper.getDecisionColor('WATCH'), AppColors.watch);
      expect(DecisionUiHelper.getDecisionColor('AVOID'), AppColors.avoid);
      expect(DecisionUiHelper.getDecisionColor('INSUFFICIENT_DATA'), AppColors.decisionInsufficient);
      expect(DecisionUiHelper.getDecisionColor('UNKNOWN'), AppColors.decisionInsufficient);
    });

    test('getDecisionLabel returns correct Arabic label', () {
      expect(DecisionUiHelper.getDecisionLabel('BUY'), 'شراء (BUY)');
      expect(DecisionUiHelper.getDecisionLabel('WATCH'), 'مراقبة (WATCH)');
      expect(DecisionUiHelper.getDecisionLabel('AVOID'), 'تجنب (AVOID)');
      expect(DecisionUiHelper.getDecisionLabel('INSUFFICIENT_DATA'), 'بيانات غير كافية');
    });

    testWidgets('buildDecisionBadge renders badge widget without crashing', (WidgetTester tester) async {
      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: DecisionUiHelper.buildDecisionBadge('BUY'),
          ),
        ),
      );

      expect(find.text('شراء (BUY)'), findsOneWidget);
    });

    testWidgets('buildDataTruthBadge renders verified and unverified shipping badges', (WidgetTester tester) async {
      // Test unverified shipping warning
      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: DecisionUiHelper.buildDataTruthBadge('ESTIMATED_PENDING_SHIPPING', isShippingVerified: false),
          ),
        ),
      );
      expect(find.text('⚠️ أرباح تقديرية — الشحن غير مؤكد'), findsOneWidget);

      // Test confirmed shipping
      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: DecisionUiHelper.buildDataTruthBadge('CALCULATED_CONFIRMED', isShippingVerified: true),
          ),
        ),
      );
      expect(find.text('✓ أرباح مؤكدة — الشحن موثق'), findsOneWidget);
    });
  });
}

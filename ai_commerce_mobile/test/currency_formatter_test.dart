import 'package:flutter_test/flutter_test.dart';
import 'package:ai_commerce_mobile/core/utils/currency_formatter.dart';

void main() {
  group('CurrencyFormatter Tests', () {
    test('format returns formatted currency', () {
      expect(CurrencyFormatter.format(1234.56, currency: 'USD'), '\$1,234.56');
      expect(CurrencyFormatter.format(null, currency: 'USD'), 'N/A');
    });

    test('formatUsd formats standard numbers', () {
      expect(CurrencyFormatter.formatUsd(99.99), '\$99.99');
      expect(CurrencyFormatter.formatUsd(0.0), '\$0.00');
    });

    test('formatPercent formats percentages without mutating null to 0%', () {
      expect(CurrencyFormatter.formatPercent(null), 'غير متاح');
      expect(CurrencyFormatter.formatPercent(0), '0%');
      expect(CurrencyFormatter.formatPercent(0.0), '0%');
      expect(CurrencyFormatter.formatPercent('0'), '0%');
      expect(CurrencyFormatter.formatPercent('0%'), '0%');
      expect(CurrencyFormatter.formatPercent(''), 'غير متاح');
      expect(CurrencyFormatter.formatPercent(double.nan), 'غير متاح');
      expect(CurrencyFormatter.formatPercent(25.5), '25.5%');
      expect(CurrencyFormatter.formatPercent(26.3636), '26.4%');
      expect(CurrencyFormatter.formatPercent('26.36%'), '26.36%');
    });
  });
}


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

    test('formatPercent formats percentage string', () {
      expect(CurrencyFormatter.formatPercent(25.5), '25.5%');
      expect(CurrencyFormatter.formatPercent(null), '0.0%');
    });
  });
}


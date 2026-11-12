class CurrencyFormatter {
  static String format(dynamic amount, {String currency = 'USD'}) {
    if (amount == null) return 'N/A';
    double? val;
    if (amount is num) {
      val = amount.toDouble();
    } else if (amount is String) {
      val = double.tryParse(amount);
    }
    if (val == null) return 'N/A';
    if (currency == 'USD') {
      return '\$${_formatWithCommas(val)}';
    }
    return '${_formatWithCommas(val)} $currency';
  }

  static String formatUsd(dynamic amount) {
    if (amount == null || amount is bool) return 'غير متاح';
    double? val;
    if (amount is num) {
      val = amount.toDouble();
    } else if (amount is String) {
      final trimmed = amount.trim();
      if (trimmed.isNotEmpty) {
        val = double.tryParse(trimmed);
      }
    }
    if (val == null || !val.isFinite) return 'غير متاح';
    if (val == 0) val = 0.0;
    return '\$${_formatWithCommas(val)}';
  }

  static String formatPercent(dynamic percent) {
    if (percent == null || percent is bool) return 'غير متاح';
    if (percent is num) {
      final val = percent.toDouble();
      if (!val.isFinite) return 'غير متاح';
      if (val == 0) return '0%';
      return '${val.toStringAsFixed(1)}%';
    }
    if (percent is String) {
      final trimmed = percent.trim();
      if (trimmed.isEmpty) return 'غير متاح';
      if (trimmed.endsWith('%')) {
        final numericPart = trimmed.substring(0, trimmed.length - 1).trim();
        if (numericPart.isEmpty) return 'غير متاح';
        final parsed = double.tryParse(numericPart);
        if (parsed == null || !parsed.isFinite) return 'غير متاح';
        if (parsed == 0) return '0%';
        return trimmed;
      }
      final val = double.tryParse(trimmed);
      if (val == null || !val.isFinite) return 'غير متاح';
      if (val == 0) return '0%';
      return '${val.toStringAsFixed(1)}%';
    }
    return 'غير متاح';
  }

  static String _formatWithCommas(double value) {
    final parts = value.toStringAsFixed(2).split('.');
    final intPart = parts[0];
    final decPart = parts[1];

    final reg = RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))');
    final formattedInt = intPart.replaceAllMapped(reg, (Match m) => '${m[1]},');
    return '$formattedInt.$decPart';
  }
}

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
    if (amount == null) return '\$0.00';
    double? val;
    if (amount is num) {
      val = amount.toDouble();
    } else if (amount is String) {
      val = double.tryParse(amount);
    }
    val ??= 0.0;
    return '\$${_formatWithCommas(val)}';
  }

  static String formatPercent(dynamic percent) {
    if (percent == null) return '0.0%';
    if (percent is String) {
      if (percent.endsWith('%')) return percent;
      final val = double.tryParse(percent);
      return val != null ? '${val.toStringAsFixed(1)}%' : percent;
    }
    if (percent is num) {
      return '${percent.toStringAsFixed(1)}%';
    }
    return '$percent%';
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

class FinancialMetricsModel {
  final double purchasePrice;
  final double sellingPrice;
  final double shippingCost;
  final double estimatedFees;
  final double otherCosts;
  final double totalCost;
  final double estimatedProfit;
  final String profitMarginPercent;
  final String roiPercent;
  final bool isProfitable;
  final bool isShippingVerified;
  final String profitStatus; // CALCULATED_CONFIRMED | ESTIMATED_PENDING_SHIPPING

  const FinancialMetricsModel({
    required this.purchasePrice,
    required this.sellingPrice,
    required this.shippingCost,
    required this.estimatedFees,
    required this.otherCosts,
    required this.totalCost,
    required this.estimatedProfit,
    required this.profitMarginPercent,
    required this.roiPercent,
    required this.isProfitable,
    required this.isShippingVerified,
    required this.profitStatus,
  });

  factory FinancialMetricsModel.fromJson(Map<String, dynamic> json) {
    double extractVal(dynamic key) {
      if (key is Map) {
        return double.tryParse(key['value']?.toString() ?? '0') ?? 0.0;
      }
      return double.tryParse(key?.toString() ?? '0') ?? 0.0;
    }

    String extractStr(dynamic key) {
      if (key is Map) {
        return key['value']?.toString() ?? '';
      }
      return key?.toString() ?? '';
    }

    final shippingBlock = json['shipping_cost'] is Map ? json['shipping_cost'] as Map<String, dynamic> : {};
    final profitBlock = json['estimated_profit'] is Map ? json['estimated_profit'] as Map<String, dynamic> : {};

    return FinancialMetricsModel(
      purchasePrice: extractVal(json['purchase_price']),
      sellingPrice: extractVal(json['selling_price']),
      shippingCost: extractVal(json['shipping_cost']),
      estimatedFees: extractVal(json['estimated_fees']),
      otherCosts: extractVal(json['other_costs']),
      totalCost: extractVal(json['total_cost']),
      estimatedProfit: extractVal(json['estimated_profit']),
      profitMarginPercent: extractStr(json['profit_margin_percent']),
      roiPercent: extractStr(json['return_on_investment_percent']),
      isProfitable: json['is_profitable'] is Map
          ? (json['is_profitable']['value'] as bool? ?? true)
          : (json['is_profitable'] as bool? ?? true),
      isShippingVerified: shippingBlock['is_verified'] as bool? ?? false,
      profitStatus: profitBlock['status']?.toString() ??
          (shippingBlock['is_verified'] == true ? 'CALCULATED_CONFIRMED' : 'ESTIMATED_PENDING_SHIPPING'),
    );
  }
}


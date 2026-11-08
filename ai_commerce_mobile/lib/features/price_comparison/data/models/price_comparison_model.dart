class ComparisonOfferModel {
  final int listingId;
  final String source;
  final double originalPrice;
  final String originalCurrency;
  final double convertedPrice;
  final bool isAvailable;
  final String? url;
  final String? sellerUsername;
  final double? sellerFeedbackPercentage;

  const ComparisonOfferModel({
    required this.listingId,
    required this.source,
    required this.originalPrice,
    required this.originalCurrency,
    required this.convertedPrice,
    required this.isAvailable,
    this.url,
    this.sellerUsername,
    this.sellerFeedbackPercentage,
  });

  factory ComparisonOfferModel.fromJson(Map<String, dynamic> json) {
    final seller = json['seller'] is Map ? json['seller'] as Map<String, dynamic> : {};

    return ComparisonOfferModel(
      listingId: json['listing_id'] as int? ?? 0,
      source: json['source']?.toString() ?? 'ebay',
      originalPrice: double.tryParse(json['original_price']?.toString() ?? '0') ?? 0.0,
      originalCurrency: json['original_currency']?.toString() ?? 'USD',
      convertedPrice: double.tryParse(json['converted_price']?.toString() ?? '0') ?? 0.0,
      isAvailable: json['is_available'] as bool? ?? true,
      url: json['url']?.toString(),
      sellerUsername: seller['username']?.toString(),
      sellerFeedbackPercentage: seller['feedback_percentage'] != null
          ? double.tryParse(seller['feedback_percentage'].toString())
          : null,
    );
  }
}

class PriceComparisonModel {
  final int productId;
  final String productName;
  final String targetCurrency;
  final int offersCount;
  final double maxPriceSpread;
  final List<ComparisonOfferModel> offers;

  const PriceComparisonModel({
    required this.productId,
    required this.productName,
    required this.targetCurrency,
    required this.offersCount,
    required this.maxPriceSpread,
    required this.offers,
  });

  factory PriceComparisonModel.fromJson(Map<String, dynamic> json) {
    final prod = json['product'] is Map ? json['product'] as Map<String, dynamic> : {};
    
    List<ComparisonOfferModel> parsedOffers = [];
    if (json['offers'] is List) {
      parsedOffers = (json['offers'] as List)
          .whereType<Map<String, dynamic>>()
          .map((item) => ComparisonOfferModel.fromJson(item))
          .toList();
    }

    return PriceComparisonModel(
      productId: prod['id'] as int? ?? 0,
      productName: prod['name']?.toString() ?? '',
      targetCurrency: json['target_currency']?.toString() ?? 'USD',
      offersCount: json['offers_count'] as int? ?? parsedOffers.length,
      maxPriceSpread: double.tryParse(json['max_price_spread']?.toString() ?? '0') ?? 0.0,
      offers: parsedOffers,
    );
  }
}


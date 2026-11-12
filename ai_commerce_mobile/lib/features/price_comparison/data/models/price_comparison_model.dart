class ComparisonSourceModel {
  final int id;
  final String code;
  final String name;

  const ComparisonSourceModel({
    required this.id,
    required this.code,
    required this.name,
  });

  factory ComparisonSourceModel.fromJson(Map<String, dynamic> json) {
    return ComparisonSourceModel(
      id: _parseInt(json['id']),
      code: json['code']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
    );
  }
}

class ComparisonSellerModel {
  final String? username;
  final String? storeName;
  final double? feedbackPercentage;
  final int? feedbackScore;

  const ComparisonSellerModel({
    this.username,
    this.storeName,
    this.feedbackPercentage,
    this.feedbackScore,
  });

  String? get displayName => username ?? storeName;

  factory ComparisonSellerModel.fromJson(Map<String, dynamic> json) {
    return ComparisonSellerModel(
      username: _parseNullableString(json['username']),
      storeName: _parseNullableString(json['store_name']),
      feedbackPercentage: _parseNullableDouble(json['feedback_percentage']),
      feedbackScore: _parseNullableInt(json['feedback_score']),
    );
  }
}

class ComparisonOfferModel {
  final int listingId;
  final ComparisonSourceModel source;
  final ComparisonSellerModel? seller;
  final String externalId;
  final String title;
  final String? url;
  final String? imageUrl;
  final double? originalPrice;
  final String originalCurrency;
  final double? normalizedPrice;
  final String targetCurrency;
  final double? exchangeRate;
  final double? rating;
  final int reviewsCount;
  final bool? isAvailable;
  final String? observedAt;

  const ComparisonOfferModel({
    required this.listingId,
    required this.source,
    this.seller,
    required this.externalId,
    required this.title,
    this.url,
    this.imageUrl,
    this.originalPrice,
    required this.originalCurrency,
    this.normalizedPrice,
    required this.targetCurrency,
    this.exchangeRate,
    this.rating,
    required this.reviewsCount,
    this.isAvailable,
    this.observedAt,
  });

  factory ComparisonOfferModel.fromJson(Map<String, dynamic> json) {
    final rawSource = json['source'];
    if (rawSource is! Map) {
      throw const FormatException('ComparisonOfferModel requires source object.');
    }

    final rawSeller = json['seller'];
    final seller = rawSeller is Map
        ? ComparisonSellerModel.fromJson(Map<String, dynamic>.from(rawSeller))
        : null;

    return ComparisonOfferModel(
      listingId: _parseInt(json['listing_id']),
      source: ComparisonSourceModel.fromJson(Map<String, dynamic>.from(rawSource)),
      seller: seller,
      externalId: json['external_id']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      url: _parseNullableString(json['url']),
      imageUrl: _parseNullableString(json['image_url']),
      originalPrice: _parseNullableDouble(json['original_price']),
      originalCurrency: json['original_currency']?.toString() ?? 'USD',
      normalizedPrice: _parseNullableDouble(json['normalized_price']),
      targetCurrency: json['target_currency']?.toString() ?? 'USD',
      exchangeRate: _parseNullableDouble(json['exchange_rate']),
      rating: _parseNullableDouble(json['rating']),
      reviewsCount: _parseInt(json['reviews_count']),
      isAvailable: json['is_available'] is bool ? json['is_available'] as bool : null,
      observedAt: _parseNullableString(json['observed_at']),
    );
  }
}

class PriceComparisonModel {
  final int canonicalProductId;
  final String canonicalProductName;
  final String targetCurrency;
  final int totalOffers;
  final ComparisonOfferModel? lowestOffer;
  final ComparisonOfferModel? highestOffer;
  final double? averagePrice;
  final double? priceSpread;
  final double? priceSpreadPercentage;
  final double? potentialGrossMarginPercentage;
  final List<ComparisonOfferModel> offers;

  const PriceComparisonModel({
    required this.canonicalProductId,
    required this.canonicalProductName,
    required this.targetCurrency,
    required this.totalOffers,
    this.lowestOffer,
    this.highestOffer,
    this.averagePrice,
    this.priceSpread,
    this.priceSpreadPercentage,
    this.potentialGrossMarginPercentage,
    required this.offers,
  });

  factory PriceComparisonModel.fromJson(Map<String, dynamic> json) {
    final rawOffers = json['offers'];
    final parsedOffers = rawOffers is List
        ? rawOffers
            .whereType<Map>()
            .map((item) => ComparisonOfferModel.fromJson(Map<String, dynamic>.from(item)))
            .toList()
        : <ComparisonOfferModel>[];

    final rawLowest = json['lowest_offer'];
    final rawHighest = json['highest_offer'];

    return PriceComparisonModel(
      canonicalProductId: _parseInt(json['canonical_product_id']),
      canonicalProductName: json['canonical_product_name']?.toString() ?? '',
      targetCurrency: json['target_currency']?.toString() ?? 'USD',
      totalOffers: _parseInt(json['total_offers'], fallback: parsedOffers.length),
      lowestOffer: rawLowest is Map
          ? ComparisonOfferModel.fromJson(Map<String, dynamic>.from(rawLowest))
          : null,
      highestOffer: rawHighest is Map
          ? ComparisonOfferModel.fromJson(Map<String, dynamic>.from(rawHighest))
          : null,
      averagePrice: _parseNullableDouble(json['average_price']),
      priceSpread: _parseNullableDouble(json['price_spread']),
      priceSpreadPercentage: _parseNullableDouble(json['price_spread_percentage']),
      potentialGrossMarginPercentage: _parseNullableDouble(json['potential_gross_margin_percentage']),
      offers: parsedOffers,
    );
  }
}

int _parseInt(dynamic value, {int fallback = 0}) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value.trim()) ?? fallback;
  return fallback;
}

int? _parseNullableInt(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value.trim());
  return null;
}

double? _parseNullableDouble(dynamic value) {
  if (value == null || value is bool) return null;
  if (value is num) {
    final parsed = value.toDouble();
    return parsed.isFinite ? parsed : null;
  }
  if (value is String) {
    final trimmed = value.trim();
    if (trimmed.isEmpty) return null;
    final parsed = double.tryParse(trimmed);
    return (parsed != null && parsed.isFinite) ? parsed : null;
  }
  return null;
}

String? _parseNullableString(dynamic value) {
  if (value == null) return null;
  final str = value.toString().trim();
  return str.isEmpty ? null : str;
}

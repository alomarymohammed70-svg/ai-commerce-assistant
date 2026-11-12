class MarketItemModel {
  final int id;
  final int? canonicalProductId;
  final String externalId;
  final String title;
  final double? price;
  final String currency;
  final double? rating;
  final int reviewsCount;
  final bool? isAvailable;
  final String? url;
  final String sourceCode;
  final String sourceName;

  const MarketItemModel({
    required this.id,
    this.canonicalProductId,
    required this.externalId,
    required this.title,
    this.price,
    required this.currency,
    this.rating,
    required this.reviewsCount,
    this.isAvailable,
    this.url,
    required this.sourceCode,
    required this.sourceName,
  });

  factory MarketItemModel.fromJson(Map<String, dynamic> json) {
    return MarketItemModel(
      id: json['id'] as int? ?? 0,
      canonicalProductId: json['canonical_product_id'] as int?,
      externalId: json['external_id']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      price: _parseNullableDouble(json['price']),
      currency: json['currency']?.toString() ?? 'USD',
      rating: _parseNullableDouble(json['rating']),
      reviewsCount: json['reviews_count'] as int? ?? 0,
      isAvailable: json['is_available'] is bool ? json['is_available'] as bool : null,
      url: json['url']?.toString(),
      sourceCode: json['source']?['code']?.toString() ?? 'ebay',
      sourceName: json['source']?['name']?.toString() ?? 'eBay Marketplace',
    );
  }
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


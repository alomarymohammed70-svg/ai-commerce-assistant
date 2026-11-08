class MarketItemModel {
  final int id;
  final int? canonicalProductId;
  final String externalId;
  final String title;
  final double price;
  final String currency;
  final double? rating;
  final int reviewsCount;
  final bool isAvailable;
  final String? url;
  final String sourceCode;
  final String sourceName;

  const MarketItemModel({
    required this.id,
    this.canonicalProductId,
    required this.externalId,
    required this.title,
    required this.price,
    required this.currency,
    this.rating,
    required this.reviewsCount,
    required this.isAvailable,
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
      price: double.tryParse(json['price']?.toString() ?? '0') ?? 0.0,
      currency: json['currency']?.toString() ?? 'USD',
      rating: json['rating'] != null ? double.tryParse(json['rating'].toString()) : null,
      reviewsCount: json['reviews_count'] as int? ?? 0,
      isAvailable: json['is_available'] as bool? ?? true,
      url: json['url']?.toString(),
      sourceCode: json['source']?['code']?.toString() ?? 'ebay',
      sourceName: json['source']?['name']?.toString() ?? 'eBay Marketplace',
    );
  }
}


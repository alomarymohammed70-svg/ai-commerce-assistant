class ListingSellerModel {
  final String? username;
  final String? storeName;
  final double? feedbackPercentage;
  final int? feedbackScore;

  const ListingSellerModel({
    this.username,
    this.storeName,
    this.feedbackPercentage,
    this.feedbackScore,
  });

  String? get displayName => username ?? storeName;

  factory ListingSellerModel.fromJson(Map<String, dynamic> json) {
    return ListingSellerModel(
      username: _parseNullableString(json['username']),
      storeName: _parseNullableString(json['store_name']),
      feedbackPercentage: _parseNullableDouble(json['feedback_percentage']),
      feedbackScore: _parseNullableInt(json['feedback_score']),
    );
  }
}

class ProductListingModel {
  final int id;
  final int? canonicalProductId;
  final String externalId;
  final String title;
  final String? description;
  final String? brand;
  final String sourceCode;
  final String sourceName;
  final double? price;
  final String currency;
  final double? originalPrice;
  final double? rating;
  final int reviewsCount;
  final bool? isAvailable;
  final String? url;
  final String? imageUrl;
  final String? observedAt;
  final ListingSellerModel? seller;

  const ProductListingModel({
    required this.id,
    this.canonicalProductId,
    required this.externalId,
    required this.title,
    this.description,
    this.brand,
    required this.sourceCode,
    required this.sourceName,
    this.price,
    required this.currency,
    this.originalPrice,
    this.rating,
    required this.reviewsCount,
    this.isAvailable,
    this.url,
    this.imageUrl,
    this.observedAt,
    this.seller,
  });

  factory ProductListingModel.fromJson(Map<String, dynamic> json) {
    final sourceMap = json['source'] is Map
        ? Map<String, dynamic>.from(json['source'] as Map)
        : null;

    final rawSeller = json['seller'];
    final seller = rawSeller is Map
        ? ListingSellerModel.fromJson(Map<String, dynamic>.from(rawSeller))
        : null;

    return ProductListingModel(
      id: _parseInt(json['id']),
      canonicalProductId: _parseNullableInt(json['canonical_product_id']),
      externalId: json['external_id']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      description: _parseNullableString(json['description']),
      brand: _parseNullableString(json['brand']),
      sourceCode: sourceMap?['code']?.toString() ?? '',
      sourceName: sourceMap?['name']?.toString() ?? '',
      price: _parseNullableDouble(json['price']),
      currency: json['currency']?.toString() ?? 'USD',
      originalPrice: _parseNullableDouble(json['original_price']),
      rating: _parseNullableDouble(json['rating']),
      reviewsCount: _parseInt(json['reviews_count']),
      isAvailable: json['is_available'] is bool ? json['is_available'] as bool : null,
      url: _parseNullableString(json['url']),
      imageUrl: _parseNullableString(json['image_url']),
      observedAt: _parseNullableString(json['observed_at']),
      seller: seller,
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

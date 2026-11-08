class ProductListingModel {
  final int id;
  final int? canonicalProductId;
  final String externalId;
  final String title;
  final String? description;
  final String? brand;
  final String sourceCode;
  final String sourceName;
  final double price;
  final String currency;
  final double? originalPrice;
  final double? rating;
  final int reviewsCount;
  final bool isAvailable;
  final String? url;
  final String? imageUrl;
  final String? supplierUsername;
  final double? sellerFeedbackPercentage;
  final int? sellerFeedbackScore;

  const ProductListingModel({
    required this.id,
    this.canonicalProductId,
    required this.externalId,
    required this.title,
    this.description,
    this.brand,
    required this.sourceCode,
    required this.sourceName,
    required this.price,
    required this.currency,
    this.originalPrice,
    this.rating,
    required this.reviewsCount,
    required this.isAvailable,
    this.url,
    this.imageUrl,
    this.supplierUsername,
    this.sellerFeedbackPercentage,
    this.sellerFeedbackScore,
  });

  factory ProductListingModel.fromJson(Map<String, dynamic> json) {
    final raw = json['raw_payload'] is Map ? json['raw_payload'] as Map<String, dynamic> : {};
    final seller = raw['seller'] is Map ? raw['seller'] as Map<String, dynamic> : {};

    return ProductListingModel(
      id: json['id'] as int? ?? 0,
      canonicalProductId: json['canonical_product_id'] as int?,
      externalId: json['external_id']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      description: json['description']?.toString(),
      brand: json['brand']?.toString(),
      sourceCode: json['source']?['code']?.toString() ?? 'ebay',
      sourceName: json['source']?['name']?.toString() ?? 'eBay Marketplace',
      price: double.tryParse(json['price']?.toString() ?? '0') ?? 0.0,
      currency: json['currency']?.toString() ?? 'USD',
      originalPrice: json['original_price'] != null
          ? double.tryParse(json['original_price'].toString())
          : null,
      rating: json['rating'] != null ? double.tryParse(json['rating'].toString()) : null,
      reviewsCount: json['reviews_count'] as int? ?? 0,
      isAvailable: json['is_available'] as bool? ?? true,
      url: json['url']?.toString(),
      imageUrl: json['image_url']?.toString(),
      supplierUsername: seller['username']?.toString() ?? raw['seller_username']?.toString(),
      sellerFeedbackPercentage: raw['seller_feedback_percentage'] != null
          ? double.tryParse(raw['seller_feedback_percentage'].toString())
          : (seller['feedbackPercentage'] != null
              ? double.tryParse(seller['feedbackPercentage'].toString())
              : null),
      sellerFeedbackScore: raw['seller_feedback_score'] != null
          ? int.tryParse(raw['seller_feedback_score'].toString())
          : (seller['feedbackScore'] != null
              ? int.tryParse(seller['feedbackScore'].toString())
              : null),
    );
  }
}


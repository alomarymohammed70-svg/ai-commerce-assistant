import 'product_listing_model.dart';

class PriceSummaryModel {
  final double? lowestPrice;
  final double? highestPrice;
  final double? averagePrice;
  final String currency;

  const PriceSummaryModel({
    this.lowestPrice,
    this.highestPrice,
    this.averagePrice,
    required this.currency,
  });

  factory PriceSummaryModel.fromJson(Map<String, dynamic> json) {
    return PriceSummaryModel(
      lowestPrice: _parseNullableDouble(json['lowest_price']),
      highestPrice: _parseNullableDouble(json['highest_price']),
      averagePrice: _parseNullableDouble(json['average_price']),
      currency: json['currency']?.toString() ?? 'USD',
    );
  }
}

class PaginationMetaModel {
  final int currentPage;
  final int perPage;
  final int total;
  final int lastPage;
  final bool hasMore;

  const PaginationMetaModel({
    required this.currentPage,
    required this.perPage,
    required this.total,
    required this.lastPage,
    required this.hasMore,
  });

  factory PaginationMetaModel.fromJson(Map<String, dynamic> json) {
    return PaginationMetaModel(
      currentPage: _parseInt(json['current_page'], fallback: 1),
      perPage: _parseInt(json['per_page'], fallback: 15),
      total: _parseInt(json['total']),
      lastPage: _parseInt(json['last_page'], fallback: 1),
      hasMore: json['has_more'] as bool? ?? false,
    );
  }
}

class PaginatedProductsModel {
  final List<CanonicalProductModel> items;
  final PaginationMetaModel meta;

  const PaginatedProductsModel({
    required this.items,
    required this.meta,
  });
}

class CanonicalProductModel {
  final int id;
  final String name;
  final String slug;
  final String? brand;
  final String? modelNumber;
  final String? description;
  final String? categoryName;
  final String baseCurrency;
  final PriceSummaryModel priceSummary;
  final int activeListingsCount;
  final int totalListingsCount;
  final double? rating;
  final int reviewsCount;
  final double? latestOpportunityScore;
  final List<ProductListingModel> listings;
  final String? createdAt;
  final String? updatedAt;

  const CanonicalProductModel({
    required this.id,
    required this.name,
    required this.slug,
    this.brand,
    this.modelNumber,
    this.description,
    this.categoryName,
    required this.baseCurrency,
    required this.priceSummary,
    required this.activeListingsCount,
    this.totalListingsCount = 0,
    this.rating,
    this.reviewsCount = 0,
    this.latestOpportunityScore,
    this.listings = const [],
    this.createdAt,
    this.updatedAt,
  });

  double? get lowestPrice => priceSummary.lowestPrice;
  double? get highestPrice => priceSummary.highestPrice;
  double? get averagePrice => priceSummary.averagePrice;

  factory CanonicalProductModel.fromJson(Map<String, dynamic> json) {
    final rawPriceSummary = json['price_summary'];
    if (rawPriceSummary is! Map) {
      throw const FormatException('CanonicalProductModel requires price_summary object.');
    }

    final baseCurrency = json['base_currency']?.toString() ?? 'USD';
    final parsedSummary = PriceSummaryModel.fromJson(
      Map<String, dynamic>.from(rawPriceSummary),
    );

    final rawCategory = json['category'];
    final String? parsedCategoryName = rawCategory is Map
        ? _parseNullableString(rawCategory['name'])
        : null;

    final rawListings = json['listings'];
    final parsedListings = rawListings is List
        ? rawListings
            .whereType<Map>()
            .map((item) => ProductListingModel.fromJson(Map<String, dynamic>.from(item)))
            .toList()
        : <ProductListingModel>[];

    return CanonicalProductModel(
      id: _parseInt(json['id']),
      name: json['name']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
      brand: _parseNullableString(json['brand']),
      modelNumber: _parseNullableString(json['model_number']),
      description: _parseNullableString(json['description']),
      categoryName: parsedCategoryName,
      baseCurrency: baseCurrency,
      priceSummary: parsedSummary,
      activeListingsCount: _parseInt(json['active_listings_count']),
      totalListingsCount: _parseInt(json['total_listings_count']),
      rating: _parseNullableDouble(json['rating']),
      reviewsCount: _parseInt(json['reviews_count']),
      latestOpportunityScore: _parseNullableDouble(json['latest_opportunity_score']),
      listings: parsedListings,
      createdAt: _parseNullableString(json['created_at']),
      updatedAt: _parseNullableString(json['updated_at']),
    );
  }
}

int _parseInt(dynamic value, {int fallback = 0}) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value.trim()) ?? fallback;
  return fallback;
}

double? _parseNullableDouble(dynamic value) {
  if (value == null) return null;
  if (value is double) return value;
  if (value is num) return value.toDouble();
  if (value is String) return double.tryParse(value.trim());
  return null;
}

String? _parseNullableString(dynamic value) {
  if (value == null) return null;
  final str = value.toString().trim();
  return str.isEmpty ? null : str;
}

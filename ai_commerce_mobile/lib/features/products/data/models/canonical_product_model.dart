import 'product_listing_model.dart';

class CanonicalProductModel {
  final int id;
  final String name;
  final String slug;
  final String? brand;
  final String? description;
  final String? categoryName;
  final String baseCurrency;
  final int activeListingsCount;
  final double? lowestPrice;
  final double? highestPrice;
  final double? latestOpportunityScore;
  final List<ProductListingModel> listings;

  const CanonicalProductModel({
    required this.id,
    required this.name,
    required this.slug,
    this.brand,
    this.description,
    this.categoryName,
    required this.baseCurrency,
    required this.activeListingsCount,
    this.lowestPrice,
    this.highestPrice,
    this.latestOpportunityScore,
    this.listings = const [],
  });

  factory CanonicalProductModel.fromJson(Map<String, dynamic> json) {
    final priceSummary = json['price_summary'] is Map ? json['price_summary'] as Map<String, dynamic> : {};
    
    List<ProductListingModel> parsedListings = [];
    if (json['listings'] is List) {
      parsedListings = (json['listings'] as List)
          .whereType<Map<String, dynamic>>()
          .map((item) => ProductListingModel.fromJson(item))
          .toList();
    }

    final double? rawLowest = priceSummary['lowest_price'] != null
        ? double.tryParse(priceSummary['lowest_price'].toString())
        : (json['lowest_price'] != null ? double.tryParse(json['lowest_price'].toString()) : null);

    final double? rawHighest = priceSummary['highest_price'] != null
        ? double.tryParse(priceSummary['highest_price'].toString())
        : (json['highest_price'] != null ? double.tryParse(json['highest_price'].toString()) : null);

    return CanonicalProductModel(
      id: json['id'] as int? ?? 0,
      name: json['name']?.toString() ?? json['title']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
      brand: json['brand']?.toString(),
      description: json['description']?.toString(),
      categoryName: json['category'] is Map ? json['category']['name']?.toString() : json['category_name']?.toString(),
      baseCurrency: json['base_currency']?.toString() ?? 'USD',
      activeListingsCount: json['active_listings_count'] as int? ?? parsedListings.length,
      lowestPrice: rawLowest,
      highestPrice: rawHighest,
      latestOpportunityScore: json['latest_opportunity_score'] != null
          ? double.tryParse(json['latest_opportunity_score'].toString())
          : null,
      listings: parsedListings,
    );
  }
}

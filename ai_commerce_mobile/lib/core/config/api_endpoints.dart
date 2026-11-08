class ApiEndpoints {
  // Authentication
  static const String login = '/auth/login';
  static const String register = '/auth/register';
  static const String me = '/auth/me';
  static const String logout = '/auth/logout';

  // Products & Offers
  static const String products = '/products';
  static String productDetails(int id) => '/products/$id';
  static String productListings(int id) => '/products/$id/listings';
  static String priceComparison(int id) => '/products/$id/price-comparison';

  // Market Research
  static const String ebaySearch = '/market-research/ebay/search';
  static String ebayItemDetails(String externalId) =>
      '/market-research/ebay/items/${Uri.encodeComponent(externalId)}';

  // AI Commerce Intelligence
  static String analyzeProduct(int id) => '/ai/products/$id/analyze';
  static String analyzeListing(int id) => '/ai/listings/$id/analyze';
  static String getAiAnalysis(int id) => '/ai/analyses/$id';

  // Standalone Financial Calculator
  static const String calculateFinancials = '/analysis/calculate';

  // Taxonomy & Metadata
  static const String categories = '/categories';
  static const String sources = '/sources';
  static const String health = '/health';
}


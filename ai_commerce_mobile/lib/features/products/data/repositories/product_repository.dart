import '../../../../core/config/api_endpoints.dart';
import '../../../../core/network/api_client.dart';
import '../models/canonical_product_model.dart';
import '../models/product_listing_model.dart';

abstract class ProductRepository {
  Future<List<CanonicalProductModel>> getProducts({
    int page = 1,
    int perPage = 15,
    String? search,
    String? brand,
    int? categoryId,
  });

  Future<CanonicalProductModel> getProductDetails(int id);
  Future<List<ProductListingModel>> getProductListings(int id);
}

class ProductRepositoryImpl implements ProductRepository {
  final ApiClient apiClient;

  ProductRepositoryImpl({required this.apiClient});

  @override
  Future<List<CanonicalProductModel>> getProducts({
    int page = 1,
    int perPage = 15,
    String? search,
    String? brand,
    int? categoryId,
  }) async {
    final queryParams = <String, dynamic>{
      'page': page,
      'per_page': perPage,
    };
    if (search != null && search.trim().isNotEmpty) {
      queryParams['search'] = search.trim();
    }
    if (brand != null && brand.trim().isNotEmpty) {
      queryParams['brand'] = brand.trim();
    }
    if (categoryId != null) {
      queryParams['category_id'] = categoryId;
    }

    final response = await apiClient.get(
      ApiEndpoints.products,
      queryParameters: queryParams,
    );

    final dataList = response['data'] as List? ?? [];
    return dataList
        .whereType<Map<String, dynamic>>()
        .map((json) => CanonicalProductModel.fromJson(json))
        .toList();
  }

  @override
  Future<CanonicalProductModel> getProductDetails(int id) async {
    final response = await apiClient.get(ApiEndpoints.productDetails(id));
    return CanonicalProductModel.fromJson(response['data'] as Map<String, dynamic>);
  }

  @override
  Future<List<ProductListingModel>> getProductListings(int id) async {
    final response = await apiClient.get(ApiEndpoints.productListings(id));
    final dataList = response['data'] as List? ?? [];
    return dataList
        .whereType<Map<String, dynamic>>()
        .map((json) => ProductListingModel.fromJson(json))
        .toList();
  }
}

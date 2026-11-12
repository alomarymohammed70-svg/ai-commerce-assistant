import '../../../../core/config/api_endpoints.dart';
import '../../../../core/network/api_client.dart';
import '../models/canonical_product_model.dart';
import '../models/product_listing_model.dart';

abstract class ProductRepository {
  Future<PaginatedProductsModel> getProducts({
    int page = 1,
    int perPage = 15,
    String? search,
    String? brand,
    int? categoryId,
    String? sortBy,
    String? sortDirection,
  });

  Future<CanonicalProductModel> getProductDetails(int id);
  Future<List<ProductListingModel>> getProductListings(int id);
}

class ProductRepositoryImpl implements ProductRepository {
  final ApiClient apiClient;

  ProductRepositoryImpl({required this.apiClient});

  @override
  Future<PaginatedProductsModel> getProducts({
    int page = 1,
    int perPage = 15,
    String? search,
    String? brand,
    int? categoryId,
    String? sortBy,
    String? sortDirection,
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
      queryParams['category'] = categoryId.toString();
    }
    if (sortBy != null && sortBy.trim().isNotEmpty) {
      queryParams['sort_by'] = sortBy.trim();
    }
    if (sortDirection != null && sortDirection.trim().isNotEmpty) {
      queryParams['sort_direction'] = sortDirection.trim();
    }

    final response = await apiClient.get(
      ApiEndpoints.products,
      queryParameters: queryParams,
    );

    if (response is! Map || response['data'] is! List || response['meta'] is! Map) {
      throw const FormatException('Invalid product catalog paginated response envelope.');
    }

    final rawList = response['data'] as List;
    final items = rawList.map((entry) {
      if (entry is! Map) {
        throw const FormatException('Invalid canonical product entry in data list.');
      }
      return CanonicalProductModel.fromJson(Map<String, dynamic>.from(entry));
    }).toList();

    final meta = PaginationMetaModel.fromJson(
      Map<String, dynamic>.from(response['meta'] as Map),
    );

    return PaginatedProductsModel(items: items, meta: meta);
  }

  @override
  Future<CanonicalProductModel> getProductDetails(int id) async {
    final response = await apiClient.get(ApiEndpoints.productDetails(id));

    if (response is! Map || response['data'] is! Map) {
      throw const FormatException('Invalid product details response envelope.');
    }

    return CanonicalProductModel.fromJson(
      Map<String, dynamic>.from(response['data'] as Map),
    );
  }

  @override
  Future<List<ProductListingModel>> getProductListings(int id) async {
    final response = await apiClient.get(ApiEndpoints.productListings(id));

    if (response is! Map || response['data'] is! List) {
      throw const FormatException('Invalid product listings response envelope.');
    }

    final rawList = response['data'] as List;
    return rawList.map((entry) {
      if (entry is! Map) {
        throw const FormatException('Invalid product listing entry in data list.');
      }
      return ProductListingModel.fromJson(Map<String, dynamic>.from(entry));
    }).toList();
  }
}

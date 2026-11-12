import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../core/providers/core_providers.dart';
import '../../data/models/canonical_product_model.dart';
import '../../data/models/product_listing_model.dart';
import '../../data/repositories/product_repository.dart';

final productRepositoryProvider = Provider<ProductRepository>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return ProductRepositoryImpl(apiClient: apiClient);
});

final productsListProvider = FutureProvider.autoDispose
    .family<PaginatedProductsModel, String?>((ref, search) async {
  final repository = ref.watch(productRepositoryProvider);
  return await repository.getProducts(search: search);
});

final productDetailsProvider = FutureProvider.autoDispose
    .family<CanonicalProductModel, int>((ref, productId) async {
  final repository = ref.watch(productRepositoryProvider);
  return await repository.getProductDetails(productId);
});

final productListingsProvider = FutureProvider.autoDispose
    .family<List<ProductListingModel>, int>((ref, productId) async {
  final repository = ref.watch(productRepositoryProvider);
  return await repository.getProductListings(productId);
});

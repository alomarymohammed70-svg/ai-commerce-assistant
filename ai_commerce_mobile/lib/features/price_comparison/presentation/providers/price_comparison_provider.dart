import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../core/providers/core_providers.dart';
import '../../data/models/price_comparison_model.dart';
import '../../data/repositories/price_comparison_repository.dart';

final priceComparisonRepositoryProvider =
    Provider<PriceComparisonRepository>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return PriceComparisonRepositoryImpl(apiClient: apiClient);
});

final priceComparisonProvider = FutureProvider.autoDispose
    .family<PriceComparisonModel, int>((ref, productId) async {
  final repository = ref.watch(priceComparisonRepositoryProvider);
  return await repository.getPriceComparison(productId);
});


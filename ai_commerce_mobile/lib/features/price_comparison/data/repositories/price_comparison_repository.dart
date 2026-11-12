import '../../../../core/config/api_endpoints.dart';
import '../../../../core/network/api_client.dart';
import '../models/price_comparison_model.dart';

abstract class PriceComparisonRepository {
  Future<PriceComparisonModel> getPriceComparison(int productId, {String? targetCurrency});
}

class PriceComparisonRepositoryImpl implements PriceComparisonRepository {
  final ApiClient apiClient;

  PriceComparisonRepositoryImpl({required this.apiClient});

  @override
  Future<PriceComparisonModel> getPriceComparison(
    int productId, {
    String? targetCurrency,
  }) async {
    final queryParams = <String, dynamic>{};
    if (targetCurrency != null && targetCurrency.trim().isNotEmpty) {
      queryParams['target_currency'] = targetCurrency.trim().toUpperCase();
    }

    final response = await apiClient.get(
      ApiEndpoints.priceComparison(productId),
      queryParameters: queryParams.isEmpty ? null : queryParams,
    );

    if (response is! Map || response['data'] is! Map) {
      throw const FormatException('Invalid price comparison response envelope.');
    }

    return PriceComparisonModel.fromJson(
      Map<String, dynamic>.from(response['data'] as Map),
    );
  }
}

import '../../../../core/config/api_endpoints.dart';
import '../../../../core/network/api_client.dart';
import '../models/market_search_result_model.dart';

abstract class MarketResearchRepository {
  Future<MarketSearchResultModel> searchEbay({
    required String query,
    int limit = 15,
    int offset = 0,
    bool fresh = false,
  });
}

class MarketResearchRepositoryImpl implements MarketResearchRepository {
  final ApiClient apiClient;

  MarketResearchRepositoryImpl({required this.apiClient});

  @override
  Future<MarketSearchResultModel> searchEbay({
    required String query,
    int limit = 15,
    int offset = 0,
    bool fresh = false,
  }) async {
    final response = await apiClient.get(
      ApiEndpoints.ebaySearch,
      queryParameters: {
        'q': query.trim(),
        'limit': limit,
        'offset': offset,
        'fresh': fresh,
        'store': true,
      },
    );

    return MarketSearchResultModel.fromJson(response['data'] as Map<String, dynamic>);
  }
}

import '../../../../core/config/api_endpoints.dart';
import '../../../../core/network/api_client.dart';
import '../models/ai_analysis_model.dart';

class AiAnalysisRepository {
  final ApiClient client;

  AiAnalysisRepository(this.client);

  /// Analyze a Canonical Product
  Future<AIAnalysisModel> analyzeProduct({
    required int canonicalProductId,
    double? purchasePrice,
    double? sellingPrice,
    double? shippingCost,
    double? estimatedFees,
    String? driver,
    bool save = true,
  }) async {
    final payload = <String, dynamic>{
      'save': save,
    };

    if (purchasePrice != null && purchasePrice > 0) {
      payload['purchase_price'] = purchasePrice;
    }
    if (sellingPrice != null && sellingPrice > 0) {
      payload['selling_price'] = sellingPrice;
    }
    if (shippingCost != null) {
      payload['shipping_cost'] = shippingCost;
    }
    if (estimatedFees != null) {
      payload['estimated_fees'] = estimatedFees;
    }
    if (driver != null && driver.isNotEmpty) {
      payload['driver'] = driver;
    }

    final response = await client.post(
      ApiEndpoints.analyzeProduct(canonicalProductId),
      data: payload,
    );

    final data = (response as Map<String, dynamic>)['data'] as Map<String, dynamic>;
    return AIAnalysisModel.fromJson(data);
  }

  /// Analyze a specific Product Listing Offer
  Future<AIAnalysisModel> analyzeListing({
    required int productListingId,
    double? purchasePrice,
    double? sellingPrice,
    double? shippingCost,
    double? estimatedFees,
    String? driver,
    bool save = true,
  }) async {
    final payload = <String, dynamic>{
      'save': save,
    };

    if (purchasePrice != null && purchasePrice > 0) {
      payload['purchase_price'] = purchasePrice;
    }
    if (sellingPrice != null && sellingPrice > 0) {
      payload['selling_price'] = sellingPrice;
    }
    if (shippingCost != null) {
      payload['shipping_cost'] = shippingCost;
    }
    if (estimatedFees != null) {
      payload['estimated_fees'] = estimatedFees;
    }
    if (driver != null && driver.isNotEmpty) {
      payload['driver'] = driver;
    }

    final response = await client.post(
      ApiEndpoints.analyzeListing(productListingId),
      data: payload,
    );

    final data = (response as Map<String, dynamic>)['data'] as Map<String, dynamic>;
    return AIAnalysisModel.fromJson(data);
  }

  /// Retrieve a past historical AI analysis record
  Future<AIAnalysisModel> getHistoricalAnalysis(int id) async {
    final response = await client.get(ApiEndpoints.getAiAnalysis(id));
    final data = (response as Map<String, dynamic>)['data'] as Map<String, dynamic>;
    return AIAnalysisModel.fromJson(data);
  }
}

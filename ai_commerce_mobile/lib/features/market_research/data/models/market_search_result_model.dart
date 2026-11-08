import 'market_item_model.dart';

class MarketSearchResultModel {
  final String query;
  final String source;
  final bool isMock;
  final bool cached;
  final double durationMs;
  final int total;
  final int count;
  final List<MarketItemModel> items;

  const MarketSearchResultModel({
    required this.query,
    required this.source,
    required this.isMock,
    required this.cached,
    required this.durationMs,
    required this.total,
    required this.count,
    required this.items,
  });

  factory MarketSearchResultModel.fromJson(Map<String, dynamic> json) {
    final pagination = json['pagination'] is Map ? json['pagination'] as Map<String, dynamic> : {};
    
    List<MarketItemModel> parsedItems = [];
    if (json['items'] is List) {
      parsedItems = (json['items'] as List)
          .whereType<Map<String, dynamic>>()
          .map((item) => MarketItemModel.fromJson(item))
          .toList();
    }

    return MarketSearchResultModel(
      query: json['query']?.toString() ?? '',
      source: json['source']?.toString() ?? 'ebay',
      isMock: json['is_mock'] as bool? ?? false,
      cached: json['cached'] as bool? ?? false,
      durationMs: double.tryParse(json['duration_ms']?.toString() ?? '0') ?? 0.0,
      total: pagination['total'] as int? ?? parsedItems.length,
      count: pagination['count'] as int? ?? parsedItems.length,
      items: parsedItems,
    );
  }
}


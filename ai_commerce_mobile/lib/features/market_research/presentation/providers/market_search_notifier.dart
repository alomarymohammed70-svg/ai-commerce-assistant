import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../core/errors/failure.dart';
import '../../../../core/providers/core_providers.dart';
import '../../data/models/market_search_result_model.dart';
import '../../data/repositories/market_research_repository.dart';

final marketResearchRepositoryProvider = Provider<MarketResearchRepository>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return MarketResearchRepositoryImpl(apiClient: apiClient);
});

sealed class MarketSearchState {
  const MarketSearchState();
}

class MarketSearchInitial extends MarketSearchState {
  const MarketSearchInitial();
}

class MarketSearchLoading extends MarketSearchState {
  const MarketSearchLoading();
}

class MarketSearchSuccess extends MarketSearchState {
  final MarketSearchResultModel result;
  const MarketSearchSuccess(this.result);
}

class MarketSearchError extends MarketSearchState {
  final String message;
  const MarketSearchError(this.message);
}

class MarketSearchNotifier extends StateNotifier<MarketSearchState> {
  final MarketResearchRepository repository;

  MarketSearchNotifier({required this.repository})
      : super(const MarketSearchInitial());

  Future<void> search(String query, {bool fresh = false}) async {
    if (query.trim().isEmpty) return;

    state = const MarketSearchLoading();
    try {
      final res = await repository.searchEbay(query: query, fresh: fresh);
      state = MarketSearchSuccess(res);
    } on Failure catch (f) {
      state = MarketSearchError(f.message);
    } catch (e) {
      state = MarketSearchError(e.toString());
    }
  }

  void reset() {
    state = const MarketSearchInitial();
  }
}

final marketSearchNotifierProvider =
    StateNotifierProvider<MarketSearchNotifier, MarketSearchState>((ref) {
  final repository = ref.watch(marketResearchRepositoryProvider);
  return MarketSearchNotifier(repository: repository);
});

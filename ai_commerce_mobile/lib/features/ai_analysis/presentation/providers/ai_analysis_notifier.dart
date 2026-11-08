import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../core/providers/core_providers.dart';
import '../../data/models/ai_analysis_model.dart';
import '../../data/repositories/ai_analysis_repository.dart';

final aiAnalysisRepositoryProvider = Provider<AiAnalysisRepository>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return AiAnalysisRepository(apiClient);
});

class AiAnalysisState {
  final AsyncValue<AIAnalysisModel?> status;
  final AIAnalysisModel? currentAnalysis;

  const AiAnalysisState({
    this.status = const AsyncData(null),
    this.currentAnalysis,
  });

  AiAnalysisState copyWith({
    AsyncValue<AIAnalysisModel?>? status,
    AIAnalysisModel? currentAnalysis,
  }) {
    return AiAnalysisState(
      status: status ?? this.status,
      currentAnalysis: currentAnalysis ?? this.currentAnalysis,
    );
  }
}

class AiAnalysisNotifier extends StateNotifier<AsyncValue<AIAnalysisModel?>> {
  final AiAnalysisRepository _repository;

  AiAnalysisNotifier(this._repository) : super(const AsyncData(null));

  Future<void> analyzeProduct({
    required int canonicalProductId,
    double? purchasePrice,
    double? sellingPrice,
    double? shippingCost,
    double? estimatedFees,
    String? driver,
  }) async {
    state = const AsyncLoading();
    try {
      final analysis = await _repository.analyzeProduct(
        canonicalProductId: canonicalProductId,
        purchasePrice: purchasePrice,
        sellingPrice: sellingPrice,
        shippingCost: shippingCost,
        estimatedFees: estimatedFees,
        driver: driver,
      );
      state = AsyncData(analysis);
    } catch (e, st) {
      state = AsyncError(e, st);
    }
  }

  Future<void> analyzeListing({
    required int productListingId,
    double? purchasePrice,
    double? sellingPrice,
    double? shippingCost,
    double? estimatedFees,
    String? driver,
  }) async {
    state = const AsyncLoading();
    try {
      final analysis = await _repository.analyzeListing(
        productListingId: productListingId,
        purchasePrice: purchasePrice,
        sellingPrice: sellingPrice,
        shippingCost: shippingCost,
        estimatedFees: estimatedFees,
        driver: driver,
      );
      state = AsyncData(analysis);
    } catch (e, st) {
      state = AsyncError(e, st);
    }
  }

  Future<void> loadHistorical(int id) async {
    state = const AsyncLoading();
    try {
      final analysis = await _repository.getHistoricalAnalysis(id);
      state = AsyncData(analysis);
    } catch (e, st) {
      state = AsyncError(e, st);
    }
  }

  void reset() {
    state = const AsyncData(null);
  }
}

final aiAnalysisNotifierProvider =
    StateNotifierProvider.autoDispose<AiAnalysisNotifier, AsyncValue<AIAnalysisModel?>>((ref) {
  final repository = ref.watch(aiAnalysisRepositoryProvider);
  return AiAnalysisNotifier(repository);
});


import 'ai_recommendation_model.dart';
import 'financial_metrics_model.dart';

class AIAnalysisModel {
  final int id;
  final int? canonicalProductId;
  final int? productListingId;
  final String provider;
  final String model;
  final String promptVersion;
  final String decision;
  final double? confidence;
  final double? opportunityScore;
  final double? riskScore;
  final AIAnalysisDetail analysis;
  final FinancialMetricsModel deterministicMetrics;
  final double? durationMs;
  final int tokensUsed;
  final DateTime? createdAt;

  const AIAnalysisModel({
    required this.id,
    this.canonicalProductId,
    this.productListingId,
    required this.provider,
    required this.model,
    required this.promptVersion,
    required this.decision,
    this.confidence,
    this.opportunityScore,
    this.riskScore,
    required this.analysis,
    required this.deterministicMetrics,
    this.durationMs,
    required this.tokensUsed,
    this.createdAt,
  });

  static double? _parseNullableDouble(dynamic value) {
    if (value == null) return null;
    if (value is num) {
      final parsed = value.toDouble();
      return parsed.isFinite ? parsed : null;
    }
    if (value is String) {
      final trimmed = value.trim();
      if (trimmed.isEmpty) return null;
      final parsed = double.tryParse(trimmed);
      return (parsed != null && parsed.isFinite) ? parsed : null;
    }
    return null;
  }

  factory AIAnalysisModel.fromJson(Map<String, dynamic> json) {
    return AIAnalysisModel(
      id: json['id'] is int ? json['id'] as int : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      canonicalProductId: json['canonical_product_id'] != null
          ? int.tryParse(json['canonical_product_id'].toString())
          : null,
      productListingId: json['product_listing_id'] != null
          ? int.tryParse(json['product_listing_id'].toString())
          : null,
      provider: json['provider']?.toString() ?? 'openai',
      model: json['model']?.toString() ?? 'gpt-4o-mini',
      promptVersion: json['prompt_version']?.toString() ?? 'v1',
      decision: json['decision']?.toString() ?? 'INSUFFICIENT_DATA',
      confidence: _parseNullableDouble(json['confidence']),
      opportunityScore: _parseNullableDouble(json['opportunity_score']),
      riskScore: _parseNullableDouble(json['risk_score']),
      analysis: AIAnalysisDetail.fromJson(
        json['analysis'] is Map<String, dynamic> ? json['analysis'] : {},
      ),
      deterministicMetrics: FinancialMetricsModel.fromJson(
        json['deterministic_metrics'] is Map<String, dynamic>
            ? json['deterministic_metrics']
            : {},
      ),
      durationMs: _parseNullableDouble(json['duration_ms']),
      tokensUsed: json['tokens_used'] is int
          ? json['tokens_used'] as int
          : int.tryParse(json['tokens_used']?.toString() ?? '0') ?? 0,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
    );
  }
}


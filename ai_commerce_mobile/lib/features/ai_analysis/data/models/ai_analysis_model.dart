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
  final double confidence;
  final double opportunityScore;
  final double riskScore;
  final AIAnalysisDetail analysis;
  final FinancialMetricsModel deterministicMetrics;
  final int durationMs;
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
    required this.confidence,
    required this.opportunityScore,
    required this.riskScore,
    required this.analysis,
    required this.deterministicMetrics,
    required this.durationMs,
    required this.tokensUsed,
    this.createdAt,
  });

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
      confidence: (json['confidence'] is num)
          ? (json['confidence'] as num).toDouble()
          : double.tryParse(json['confidence']?.toString() ?? '0') ?? 0.0,
      opportunityScore: (json['opportunity_score'] is num)
          ? (json['opportunity_score'] as num).toDouble()
          : double.tryParse(json['opportunity_score']?.toString() ?? '0') ?? 0.0,
      riskScore: (json['risk_score'] is num)
          ? (json['risk_score'] as num).toDouble()
          : double.tryParse(json['risk_score']?.toString() ?? '0') ?? 0.0,
      analysis: AIAnalysisDetail.fromJson(
        json['analysis'] is Map<String, dynamic> ? json['analysis'] : {},
      ),
      deterministicMetrics: FinancialMetricsModel.fromJson(
        json['deterministic_metrics'] is Map<String, dynamic>
            ? json['deterministic_metrics']
            : {},
      ),
      durationMs: json['duration_ms'] is int
          ? json['duration_ms'] as int
          : int.tryParse(json['duration_ms']?.toString() ?? '0') ?? 0,
      tokensUsed: json['tokens_used'] is int
          ? json['tokens_used'] as int
          : int.tryParse(json['tokens_used']?.toString() ?? '0') ?? 0,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
    );
  }
}


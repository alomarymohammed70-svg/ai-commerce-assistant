double? _parseNullableScore(dynamic value) {
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

double? _parseNullablePercentage(dynamic value) {
  if (value == null) return null;
  if (value is num) {
    final parsed = value.toDouble();
    return parsed.isFinite ? parsed : null;
  }
  if (value is String) {
    var trimmed = value.trim();
    if (trimmed.isEmpty) return null;
    if (trimmed.endsWith('%')) {
      trimmed = trimmed.substring(0, trimmed.length - 1).trim();
    }
    if (trimmed.isEmpty) return null;
    final parsed = double.tryParse(trimmed);
    return (parsed != null && parsed.isFinite) ? parsed : null;
  }
  return null;
}

class RecommendationData {
  final String decision;
  final double? confidence;
  final String summary;

  const RecommendationData({
    required this.decision,
    this.confidence,
    required this.summary,
  });

  factory RecommendationData.fromJson(Map<String, dynamic> json) {
    return RecommendationData(
      decision: json['decision']?.toString() ?? 'INSUFFICIENT_DATA',
      confidence: _parseNullableScore(json['confidence']),
      summary: json['summary']?.toString() ?? '',
    );
  }
}

class OpportunityData {
  final double? score;
  final String level;
  final String justification;

  const OpportunityData({
    this.score,
    required this.level,
    required this.justification,
  });

  factory OpportunityData.fromJson(Map<String, dynamic> json) {
    return OpportunityData(
      score: _parseNullableScore(json['score']),
      level: json['level']?.toString() ?? 'LOW',
      justification: json['justification']?.toString() ?? '',
    );
  }
}

String? _parseNullableProfitText(dynamic value) {
  if (value == null || value is bool) return null;
  final trimmed = value.toString().trim();
  return trimmed.isEmpty ? null : trimmed;
}

class ProfitabilityData {
  final String? profit;
  final double? margin;
  final String assessment;

  const ProfitabilityData({
    this.profit,
    this.margin,
    required this.assessment,
  });

  factory ProfitabilityData.fromJson(Map<String, dynamic> json) {
    return ProfitabilityData(
      profit: _parseNullableProfitText(json['profit']),
      margin: _parseNullablePercentage(json['margin']),
      assessment: json['assessment']?.toString() ?? '',
    );
  }
}

class CompetitionData {
  final String level;
  final double? score;
  final String summary;

  const CompetitionData({
    required this.level,
    this.score,
    required this.summary,
  });

  factory CompetitionData.fromJson(Map<String, dynamic> json) {
    return CompetitionData(
      level: json['level']?.toString() ?? 'MEDIUM',
      score: _parseNullableScore(json['score']),
      summary: json['summary']?.toString() ?? '',
    );
  }
}

class RiskData {
  final String level;
  final double? score;
  final List<String> factors;

  const RiskData({
    required this.level,
    this.score,
    required this.factors,
  });

  factory RiskData.fromJson(Map<String, dynamic> json) {
    final factorsRaw = json['factors'];
    final List<String> factorsList = [];
    if (factorsRaw is List) {
      for (final f in factorsRaw) {
        if (f != null) factorsList.add(f.toString());
      }
    }
    return RiskData(
      level: json['level']?.toString() ?? 'MEDIUM',
      score: _parseNullableScore(json['score']),
      factors: factorsList,
    );
  }
}

class DataTruthSummary {
  final bool observedDataAvailable;
  final List<String> missingCriticalData;

  const DataTruthSummary({
    required this.observedDataAvailable,
    required this.missingCriticalData,
  });

  factory DataTruthSummary.fromJson(Map<String, dynamic> json) {
    final missingRaw = json['missing_critical_data'];
    final List<String> missingList = [];
    if (missingRaw is List) {
      for (final m in missingRaw) {
        if (m != null) missingList.add(m.toString());
      }
    }
    return DataTruthSummary(
      observedDataAvailable: json['observed_data_available'] as bool? ?? false,
      missingCriticalData: missingList,
    );
  }
}

class AIAnalysisDetail {
  final RecommendationData recommendation;
  final OpportunityData opportunity;
  final ProfitabilityData profitability;
  final CompetitionData competition;
  final RiskData risk;
  final List<String> reasons;
  final List<String> warnings;
  final List<String> actionPlan;
  final DataTruthSummary dataTruthSummary;

  const AIAnalysisDetail({
    required this.recommendation,
    required this.opportunity,
    required this.profitability,
    required this.competition,
    required this.risk,
    required this.reasons,
    required this.warnings,
    required this.actionPlan,
    required this.dataTruthSummary,
  });

  factory AIAnalysisDetail.fromJson(Map<String, dynamic> json) {
    List<String> parseStringList(dynamic raw) {
      if (raw is List) {
        return raw.map((e) => e?.toString() ?? '').where((s) => s.isNotEmpty).toList();
      }
      return [];
    }

    return AIAnalysisDetail(
      recommendation: RecommendationData.fromJson(
        json['recommendation'] is Map<String, dynamic> ? json['recommendation'] : {},
      ),
      opportunity: OpportunityData.fromJson(
        json['opportunity'] is Map<String, dynamic> ? json['opportunity'] : {},
      ),
      profitability: ProfitabilityData.fromJson(
        json['profitability'] is Map<String, dynamic> ? json['profitability'] : {},
      ),
      competition: CompetitionData.fromJson(
        json['competition'] is Map<String, dynamic> ? json['competition'] : {},
      ),
      risk: RiskData.fromJson(
        json['risk'] is Map<String, dynamic> ? json['risk'] : {},
      ),
      reasons: parseStringList(json['reasons']),
      warnings: parseStringList(json['warnings']),
      actionPlan: parseStringList(json['action_plan']),
      dataTruthSummary: DataTruthSummary.fromJson(
        json['data_truth_summary'] is Map<String, dynamic> ? json['data_truth_summary'] : {},
      ),
    );
  }
}


<?php

namespace App\Services\AI\Validation;

use App\Exceptions\AI\AISchemaValidationException;

class AIResponseValidator
{
    public const ALLOWED_DECISIONS = ['BUY', 'WATCH', 'AVOID', 'INSUFFICIENT_DATA'];
    public const ALLOWED_OPPORTUNITY_LEVELS = ['HIGH', 'MEDIUM', 'LOW'];
    public const ALLOWED_COMPETITION_LEVELS = ['LOW', 'MEDIUM', 'HIGH'];
    public const ALLOWED_RISK_LEVELS = ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];

    /**
     * Validate raw structured AI response against schema, types, ranges, and business truth rules.
     *
     * @param array<string, mixed> $response
     * @param array<string, mixed> $context
     * @return array<string, mixed> Validated and reconciled analysis payload
     *
     * @throws AISchemaValidationException
     */
    public function validate(array $response, array $context): array
    {
        $errors = [];

        // 1. Verify all top-level required sections exist
        $requiredSections = [
            'recommendation',
            'opportunity',
            'profitability',
            'competition',
            'risk',
            'reasons',
            'warnings',
            'action_plan',
        ];

        foreach ($requiredSections as $section) {
            if (! isset($response[$section]) || ! is_array($response[$section])) {
                $errors[] = "Missing or invalid top-level section: [{$section}].";
            }
        }

        if (! empty($errors)) {
            throw new AISchemaValidationException('AI structured output failed schema validation: ' . implode('; ', $errors), 422, $errors);
        }

        // 2. Validate Recommendation block
        $rec = $response['recommendation'];
        if (! isset($rec['decision']) || ! in_array(strtoupper((string) $rec['decision']), self::ALLOWED_DECISIONS, true)) {
            $errors[] = 'recommendation.decision must be one of: ' . implode(', ', self::ALLOWED_DECISIONS);
        }

        if (! isset($rec['confidence']) || ! is_numeric($rec['confidence']) || ! is_finite((float) $rec['confidence']) || (float) $rec['confidence'] < 0.0 || (float) $rec['confidence'] > 1.0) {
            $errors[] = 'recommendation.confidence must be a number between 0.0 and 1.0';
        }

        if (empty($rec['summary']) || ! is_string($rec['summary'])) {
            $errors[] = 'recommendation.summary must be a non-empty string.';
        }

        // 3. Validate Opportunity block
        $opp = $response['opportunity'];
        if (! isset($opp['score']) || ! is_numeric($opp['score']) || ! is_finite((float) $opp['score']) || (float) $opp['score'] < 0 || (float) $opp['score'] > 100) {
            $errors[] = 'opportunity.score must be a numeric value between 0 and 100.';
        }

        if (! isset($opp['level']) || ! in_array(strtoupper((string) $opp['level']), self::ALLOWED_OPPORTUNITY_LEVELS, true)) {
            $errors[] = 'opportunity.level must be one of: ' . implode(', ', self::ALLOWED_OPPORTUNITY_LEVELS);
        }

        // 4. Validate Competition block
        $comp = $response['competition'];
        if (! isset($comp['level']) || ! in_array(strtoupper((string) $comp['level']), self::ALLOWED_COMPETITION_LEVELS, true)) {
            $errors[] = 'competition.level must be one of: ' . implode(', ', self::ALLOWED_COMPETITION_LEVELS);
        }

        if (isset($comp['score']) && (! is_numeric($comp['score']) || ! is_finite((float) $comp['score']) || (float) $comp['score'] < 0 || (float) $comp['score'] > 100)) {
            $errors[] = 'competition.score must be between 0 and 100.';
        }

        // 5. Validate Risk block
        $risk = $response['risk'];
        if (! isset($risk['level']) || ! in_array(strtoupper((string) $risk['level']), self::ALLOWED_RISK_LEVELS, true)) {
            $errors[] = 'risk.level must be one of: ' . implode(', ', self::ALLOWED_RISK_LEVELS);
        }

        if (! isset($risk['score']) || ! is_numeric($risk['score']) || ! is_finite((float) $risk['score']) || (float) $risk['score'] < 0 || (float) $risk['score'] > 100) {
            $errors[] = 'risk.score must be a numeric value between 0 and 100.';
        }

        if (! isset($risk['factors']) || ! is_array($risk['factors'])) {
            $errors[] = 'risk.factors must be an array of strings.';
        }

        // 6. Validate Reasons, Warnings, Action Plan lists
        if (empty($response['reasons']) || ! is_array($response['reasons'])) {
            $errors[] = 'reasons must be a non-empty array of strings.';
        }

        if (! isset($response['warnings']) || ! is_array($response['warnings'])) {
            $errors[] = 'warnings must be an array of strings.';
        }

        if (! isset($response['action_plan']) || ! is_array($response['action_plan'])) {
            $errors[] = 'action_plan must be an array of strings.';
        }

        if (! empty($errors)) {
            throw new AISchemaValidationException('AI structured output validation failed: ' . implode('; ', $errors), 422, $errors);
        }

        // 7. Data Truth Arbitration: Reconcile AI recommendation against deterministic Laravel facts
        return $this->enforceDataTruthSupremacy($response, $context);
    }

    /**
     * Enforce that deterministic calculations in Laravel strictly overrule any contradictory AI hallucination.
     *
     * @param array<string, mixed> $response
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function enforceDataTruthSupremacy(array $response, array $context): array
    {
        $reconciled = $response;
        $isProfitable = ($context['financial_analysis']['is_profitable']['value'] ?? null) === true;
        $profit = (float) ($context['financial_analysis']['estimated_profit']['value'] ?? 0.0);
        $purchasePrice = (float) ($context['financial_analysis']['purchase_price']['value'] ?? 0.0);
        $sellingPrice = (float) ($context['financial_analysis']['selling_price']['value'] ?? 0.0);
        $offersCount = (int) ($context['market']['offers_count'] ?? 0);
        $riskScore = (float) ($context['deterministic_heuristics']['risk_score']['value'] ?? 0.0);
        $decision = strtoupper((string) $response['recommendation']['decision']);

        // Rule A: Missing Critical Financial Data (Purchase or Selling Price <= 0)
        if (($purchasePrice <= 0.0 || $sellingPrice <= 0.0) && $decision === 'BUY') {
            $reconciled['recommendation']['decision'] = 'INSUFFICIENT_DATA';
            $reconciled['recommendation']['summary'] = '[Arbiter Override] Critical financial parameters (purchase or selling price) are unverified. Profitability cannot be confirmed.';
            $reconciled['warnings'][] = 'AI generated a BUY recommendation on unverified baseline pricing. Overruled by Laravel financial truth engine.';
            $decision = 'INSUFFICIENT_DATA';
        }

        // Rule B: An objectively unprofitable unit cannot be recommended as BUY
        if ((! $isProfitable || $profit <= 0.0) && $decision === 'BUY') {
            $reconciled['recommendation']['decision'] = 'AVOID';
            $reconciled['recommendation']['summary'] = '[Arbiter Override] Unit is objectively unprofitable according to deterministic BCMath calculations. Recommendation forced to AVOID.';
            $reconciled['warnings'][] = 'AI generated a contradictory BUY recommendation which was overruled by Laravel deterministic financial rules.';
            $decision = 'AVOID';
        }

        // Rule C: Zero active marketplace offers cannot be recommended as BUY
        if ($offersCount === 0 && $decision === 'BUY') {
            $reconciled['recommendation']['decision'] = 'INSUFFICIENT_DATA';
            $reconciled['recommendation']['summary'] = '[Arbiter Override] Zero verified marketplace offers exist. Recommendation forced to INSUFFICIENT_DATA.';
            $reconciled['warnings'][] = 'AI generated a BUY recommendation without observed marketplace offers. Overruled by Laravel data truth engine.';
            $decision = 'INSUFFICIENT_DATA';
        }

        // Rule D: Elevated Risk Score (> 60) prevents margin-only BUY recommendation
        if ($riskScore >= 60.0 && $decision === 'BUY') {
            $reconciled['recommendation']['decision'] = 'WATCH';
            $reconciled['recommendation']['summary'] = "[Arbiter Override] Elevated composite risk score ({$riskScore}/100) prevents immediate BUY recommendation. Downgraded to WATCH.";
            $reconciled['warnings'][] = 'BUY recommendation based solely on margin was downgraded due to high composite risk score.';
            $decision = 'WATCH';
        }

        $reconciled['recommendation']['confidence'] = round((float) $reconciled['recommendation']['confidence'], 4);

        // Critical Missing Data / INSUFFICIENT_DATA confidence cap
        // Aligns with MockAIProvider baseline (0.35) when offers or baseline pricing are missing.
        $hasInsufficientCriticalData = $offersCount === 0
            || $purchasePrice <= 0.0
            || $sellingPrice <= 0.0
            || $decision === 'INSUFFICIENT_DATA';
        if ($hasInsufficientCriticalData && (float) $reconciled['recommendation']['confidence'] > 0.35) {
            $reconciled['recommendation']['confidence'] = 0.35;
        }

        // Rule E: Unverified Shipping Handling (Degrade confidence and label estimated profit)
        $isShippingVerified = (bool) ($context['financial_analysis']['shipping_cost']['is_verified'] ?? false);
        if (! $isShippingVerified) {
            if ((float) $reconciled['recommendation']['confidence'] > 0.75) {
                $reconciled['recommendation']['confidence'] = 0.75;
            }
            $reconciled['warnings'][] = 'Net profit is ESTIMATED and pending verified carrier shipping rate quote.';
            $reconciled['profitability']['assessment'] = '[ESTIMATED] ' . ($reconciled['profitability']['assessment'] ?? '');
        }

        // Rule F: Strict Mathematical & Deterministic Score Supremacy - Always enforce Laravel calculated figures
        $rawDeterministicProfit = $context['financial_analysis']['estimated_profit']['value'] ?? null;
        $reconciled['profitability']['profit'] = $rawDeterministicProfit !== null
            ? (string) $rawDeterministicProfit
            : 'DATA_UNAVAILABLE';

        $financialAnalysis = is_array($context['financial_analysis'] ?? null) ? $context['financial_analysis'] : [];
        if (array_key_exists('profit_margin_percent', $financialAnalysis)) {
            $rawDeterministicMargin = $financialAnalysis['profit_margin_percent'];
            $deterministicMarginValue = is_array($rawDeterministicMargin)
                ? ($rawDeterministicMargin['value'] ?? null)
                : $rawDeterministicMargin;
            $reconciled['profitability']['margin'] = $this->normalizeNullablePercentage($deterministicMarginValue);
        } else {
            $reconciled['profitability']['margin'] = $this->normalizeNullablePercentage(
                $reconciled['profitability']['margin'] ?? null
            );
        }

        $deterministicOpp = $context['deterministic_heuristics']['opportunity_score']['value'] ?? null;
        if ($deterministicOpp !== null && is_numeric($deterministicOpp) && is_finite((float) $deterministicOpp)) {
            $canonicalOpportunityScore = round((float) $deterministicOpp, 2);
            $reconciled['opportunity']['score'] = $canonicalOpportunityScore;
            $reconciled['opportunity']['level'] = $canonicalOpportunityScore >= 70.0
                ? 'HIGH'
                : ($canonicalOpportunityScore >= 45.0 ? 'MEDIUM' : 'LOW');
        } else {
            $reconciled['opportunity']['score'] = round((float) $reconciled['opportunity']['score'], 2);
        }

        $deterministicRisk = $context['deterministic_heuristics']['risk_score']['value'] ?? null;
        if ($deterministicRisk !== null && is_numeric($deterministicRisk) && is_finite((float) $deterministicRisk)) {
            $canonicalRiskScore = round((float) $deterministicRisk, 2);
            $reconciled['risk']['score'] = $canonicalRiskScore;
            $aiRiskLevel = strtoupper((string) ($reconciled['risk']['level'] ?? ''));
            $reconciled['risk']['level'] = $canonicalRiskScore >= 60.0
                ? ($aiRiskLevel === 'CRITICAL' ? 'CRITICAL' : 'HIGH')
                : ($canonicalRiskScore >= 35.0 ? 'MEDIUM' : 'LOW');
        } else {
            $reconciled['risk']['score'] = round((float) $reconciled['risk']['score'], 2);
        }

        return $reconciled;
    }

    protected function normalizeNullablePercentage(mixed $raw, int $precision = 4): ?float
    {
        if ($raw === null || is_bool($raw) || is_array($raw) || is_object($raw)) {
            return null;
        }

        if (is_int($raw) || is_float($raw)) {
            $float = (float) $raw;

            return is_finite($float) ? round($float, $precision) : null;
        }

        if (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed === '') {
                return null;
            }
            if (str_ends_with($trimmed, '%')) {
                $trimmed = trim(substr($trimmed, 0, -1));
            }
            if ($trimmed === '' || ! is_numeric($trimmed)) {
                return null;
            }

            $float = (float) $trimmed;

            return is_finite($float) ? round($float, $precision) : null;
        }

        return null;
    }
}

<?php

namespace App\Services\AI\Providers;

use App\Contracts\AI\AIProviderInterface;
use App\DTOs\AI\AIProviderResultDto;
use App\Exceptions\AI\AIProviderException;
use App\Exceptions\AI\AISchemaValidationException;
use App\Services\AI\Prompts\ProductAnalysisPromptV1;

class MockAIProvider implements AIProviderInterface
{
    protected ?string $simulatedFailure = null;
    protected int $simulatedFailureCode = 503;
    protected bool $simulatedInvalidSchema = false;
    protected bool $simulatedTimeout = false;
    protected bool $simulatedContradiction = false;

    public function __construct(
        protected string $model = 'mock-commerce-intelligence-v1'
    ) {}

    public function getProviderName(): string
    {
        return 'mock';
    }

    public function getModelName(): string
    {
        return $this->model;
    }

    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * Simulate intelligence synthesis deterministically from the input context.
     */
    public function analyze(array $context, ?string $promptVersion = null): AIProviderResultDto
    {
        $startTime = microtime(true);

        if ($this->simulatedTimeout) {
            throw new AIProviderException('Mock AI Provider timeout simulated.', 504);
        }

        if ($this->simulatedFailure !== null) {
            throw new AIProviderException($this->simulatedFailure, $this->simulatedFailureCode);
        }

        if ($this->simulatedInvalidSchema) {
            $invalidPayload = [
                'unexpected_format' => true,
                'note' => 'Malformed mock schema lacking required recommendation block.',
            ];

            return new AIProviderResultDto(
                provider: $this->getProviderName(),
                model: $this->getModelName(),
                rawResponse: $invalidPayload,
                structuredAnalysis: $invalidPayload,
                durationMs: 5.0,
                tokensUsed: 42
            );
        }

        $structured = $this->synthesizeRealisticAnalysis($context);

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        return new AIProviderResultDto(
            provider: $this->getProviderName(),
            model: $this->getModelName(),
            rawResponse: [
                'id' => 'mock-cmpl-' . md5(serialize($context)),
                'object' => 'chat.completion',
                'created' => time(),
                'model' => $this->getModelName(),
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => json_encode($structured),
                        ],
                    ],
                ],
            ],
            structuredAnalysis: $structured,
            durationMs: max(1.0, $durationMs),
            tokensUsed: 380
        );
    }

    /**
     * Synthesize realistic, schema-compliant commerce intelligence.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function synthesizeRealisticAnalysis(array $context): array
    {
        $productName = $context['product']['name']['value'] ?? 'Target Product';
        $category = $context['product']['category']['value'] ?? 'General';
        $offersCount = (int) ($context['market']['offers_count'] ?? 0);
        
        $fin = $context['financial_analysis'] ?? [];
        $isProfitable = ($fin['is_profitable']['value'] ?? null) === true;
        $rawProfit = $fin['estimated_profit']['value'] ?? null;
        $profit = $rawProfit !== null ? (string) $rawProfit : 'DATA_UNAVAILABLE';
        $rawMargin = $fin['profit_margin_percent']['value'] ?? ($fin['profit_margin_percent'] ?? null);
        $marginNumeric = $this->normalizeNullablePercentage($rawMargin);
        $margin = $marginNumeric !== null ? $marginNumeric . '%' : 'DATA_UNAVAILABLE';
        $purchasePrice = (float) ($fin['purchase_price']['value'] ?? 0.0);
        $sellingPrice = (float) ($fin['selling_price']['value'] ?? 0.0);
        $hasPricingData = $purchasePrice > 0.0 && $sellingPrice > 0.0 && $rawProfit !== null;
        $isShippingVerified = (bool) ($fin['shipping_cost']['is_verified'] ?? false);
        
        $heuristics = $context['deterministic_heuristics'] ?? [];
        $opportunityScore = (float) ($heuristics['opportunity_score']['value'] ?? 50.0);
        $riskScore = (float) ($heuristics['risk_score']['value'] ?? 40.0);
        $competitionLevel = (string) ($heuristics['competition_level']['value'] ?? 'MEDIUM');

        // Contradiction simulation for test purposes
        if ($this->simulatedContradiction) {
            $decision = 'BUY';
            $confidence = 0.95;
            $summary = 'Contradictory buy recommendation on unprofitable unit.';
        } elseif ($offersCount === 0 || $purchasePrice <= 0.0 || $sellingPrice <= 0.0) {
            $decision = 'INSUFFICIENT_DATA';
            $confidence = 0.35;
            $summary = $offersCount === 0
                ? "Zero active marketplace offers found for [{$productName}]. Commercial feasibility cannot be validated."
                : "Critical financial parameters (purchase or selling price) are unverified for [{$productName}]. Profitability cannot be substantiated.";
        } elseif (! $isProfitable || (float) $profit <= 0.0) {
            $decision = 'AVOID';
            $confidence = 0.88;
            $summary = "Negative or zero net margin ({$margin}) renders [{$productName}] commercially unviable under current cost parameters.";
        } elseif ($riskScore >= 60.0) {
            $decision = 'WATCH';
            $confidence = 0.70;
            $summary = "High composite risk ({$riskScore}/100) prevents immediate BUY recommendation despite favorable gross margin.";
        } elseif ($opportunityScore >= 75.0 && $riskScore <= 35.0) {
            $decision = 'BUY';
            $confidence = $isShippingVerified ? 0.85 : 0.75;
            $summary = "Solid margin profile ({$margin}) and favorable opportunity score ({$opportunityScore}) support testing commercial viability.";
        } else {
            $decision = 'WATCH';
            $confidence = $isShippingVerified ? 0.72 : 0.70;
            $summary = "Moderate commercial prospects for [{$productName}]. Margin is acceptable ({$margin}) but competition is {$competitionLevel}.";
        }

        $opportunityLevel = $opportunityScore >= 70 ? 'HIGH' : ($opportunityScore >= 45 ? 'MEDIUM' : 'LOW');
        $riskLevel = $riskScore >= 60 ? 'HIGH' : ($riskScore >= 35 ? 'MEDIUM' : 'LOW');

        $reasons = [
            $hasPricingData
                ? "Deterministic financial analysis calculates net profit of \${$profit} ({$margin} margin)."
                : 'Critical pricing data (purchase or selling price) is unavailable; deterministic profit and margin cannot be calculated.',
            "Market research tracks {$offersCount} active listing(s) in category [{$category}].",
            "Competition density is evaluated at level [{$competitionLevel}].",
        ];

        $warnings = [];
        if ($offersCount < 2) {
            $warnings[] = 'Sparse marketplace distribution: limited multi-market price arbitrage data.';
        }
        if (empty($context['financial_analysis']['shipping_cost']['value']) || (float) $context['financial_analysis']['shipping_cost']['value'] <= 0) {
            $warnings[] = 'Shipping costs are estimated at baseline; verify carrier rates before committing.';
        }
        $warnings[] = 'Sales volume and units sold are observed as DATA_UNAVAILABLE; demand elasticity must be tested incrementally.';

        $actionPlan = [
            'Request verified sample or shipping quote from verified supplier.',
            'Audit seller credentials and return policy prior to volume acquisition.',
            'Monitor price fluctuations on external marketplaces for 7 business days.',
        ];

        return [
            'recommendation' => [
                'decision' => $decision,
                'confidence' => $confidence,
                'summary' => $summary,
            ],
            'opportunity' => [
                'score' => round($opportunityScore, 2),
                'level' => $opportunityLevel,
                'justification' => "Calculated from profit margin of {$margin} combined with category stability.",
            ],
            'profitability' => [
                'profit' => $profit,
                'margin' => $marginNumeric,
                'assessment' => $hasPricingData
                    ? ($isProfitable ? 'Healthy estimated spread above total landed cost.' : 'Unprofitable after accounting for baseline overheads.')
                    : 'Insufficient pricing data: selling price is unavailable.',
            ],
            'competition' => [
                'level' => $competitionLevel,
                'score' => round(100 - $opportunityScore, 2),
                'summary' => "Observed {$offersCount} listing(s) reflecting {$competitionLevel} competitive intensity.",
            ],
            'risk' => [
                'level' => $riskLevel,
                'score' => round($riskScore, 2),
                'factors' => [
                    'Price volatility across third-party marketplaces.',
                    'Overhead fee variance depending on final platform category fees.',
                ],
            ],
            'reasons' => $reasons,
            'warnings' => $warnings,
            'action_plan' => $actionPlan,
            'data_truth_summary' => [
                'observed_data_available' => $offersCount > 0,
                'missing_critical_data' => $context['data_truth_meta']['missing_critical_data'] ?? [],
            ],
        ];
    }

    // Test Harness Hooks
    public function setSimulatedFailure(?string $message = 'Provider service unavailable', int $code = 503): self
    {
        $this->simulatedFailure = $message;
        $this->simulatedFailureCode = $code;
        return $this;
    }

    public function setSimulatedInvalidSchema(bool $invalid = true): self
    {
        $this->simulatedInvalidSchema = $invalid;
        return $this;
    }

    public function setSimulatedTimeout(bool $timeout = true): self
    {
        $this->simulatedTimeout = $timeout;
        return $this;
    }

    public function setSimulatedContradiction(bool $contradict = true): self
    {
        $this->simulatedContradiction = $contradict;
        return $this;
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


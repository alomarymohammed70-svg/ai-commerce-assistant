<?php

namespace App\Http\Resources\Api\V1\AI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AIAnalysisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $confidence = $this->normalizeNullableFloat('confidence', 4);
        $opportunityScore = $this->normalizeNullableFloat('opportunity_score', 2);
        $riskScore = $this->normalizeNullableFloat('risk_score', 2);
        $durationMs = $this->normalizeNullableFloat('duration_ms', 2);

        $createdAt = null;
        if (is_array($this->resource)) {
            $createdAt = $this->resource['created_at'] ?? null;
        } elseif ($this->resource instanceof Model) {
            $createdAt = $this->resource->created_at?->toISOString();
        } elseif (is_object($this->resource)) {
            $rawCreatedAt = $this->resource->created_at ?? null;
            $createdAt = is_object($rawCreatedAt) && method_exists($rawCreatedAt, 'toISOString')
                ? $rawCreatedAt->toISOString()
                : $rawCreatedAt;
        }

        $rawAnalysis = is_array($this->resource)
            ? ($this->resource['analysis'] ?? [])
            : ($this->resource->ai_response ?? []);

        $rawDeterministicMetrics = is_array($this->resource)
            ? ($this->resource['deterministic_metrics'] ?? [])
            : ($this->resource->deterministic_metrics ?? []);

        return [
            'id' => $this['id'] ?? $this->resource->id ?? null,
            'canonical_product_id' => $this['canonical_product_id'] ?? $this->resource->canonical_product_id ?? null,
            'product_listing_id' => $this['product_listing_id'] ?? $this->resource->product_listing_id ?? null,
            'provider' => $this['provider'] ?? $this->resource->provider ?? null,
            'model' => $this['model'] ?? $this->resource->model ?? null,
            'prompt_version' => $this['prompt_version'] ?? $this->resource->prompt_version ?? 'v1',
            'decision' => $this['decision'] ?? $this->resource->decision ?? null,
            'confidence' => $confidence,
            'opportunity_score' => $opportunityScore,
            'risk_score' => $riskScore,
            'analysis' => $this->normalizeAnalysis($rawAnalysis),
            'deterministic_metrics' => $this->normalizeDeterministicMetrics($rawDeterministicMetrics),
            'duration_ms' => $durationMs,
            'tokens_used' => $this['tokens_used'] ?? $this->resource->tokens_used ?? null,
            'created_at' => $createdAt,
        ];
    }

    protected function normalizeAnalysis(mixed $analysis): array
    {
        if (! is_array($analysis)) {
            return [];
        }

        if (isset($analysis['profitability']) && is_array($analysis['profitability'])) {
            if (array_key_exists('margin', $analysis['profitability'])) {
                $analysis['profitability']['margin'] = $this->normalizeNullablePercentage(
                    $analysis['profitability']['margin'],
                    4
                );
            }
        }

        return $analysis;
    }

    protected function normalizeDeterministicMetrics(mixed $metrics): array
    {
        if (! is_array($metrics)) {
            return [];
        }

        foreach (['profit_margin_percent', 'return_on_investment_percent', 'roi_percent'] as $key) {
            if (! array_key_exists($key, $metrics)) {
                continue;
            }

            $entry = $metrics[$key];
            if (is_array($entry)) {
                if (array_key_exists('value', $entry)) {
                    $entry['value'] = $this->normalizeNullablePercentage($entry['value'], 4);
                }
                $metrics[$key] = $entry;
            } else {
                $metrics[$key] = $this->normalizeNullablePercentage($entry, 4);
            }
        }

        return $metrics;
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

    protected function normalizeNullableFloat(string $key, int $precision): ?float
    {
        $raw = is_array($this->resource)
            ? ($this->resource[$key] ?? null)
            : ($this->resource->{$key} ?? null);

        if ($raw === null || ! is_numeric($raw)) {
            return null;
        }

        $float = (float) $raw;

        return is_finite($float) ? round($float, $precision) : null;
    }
}


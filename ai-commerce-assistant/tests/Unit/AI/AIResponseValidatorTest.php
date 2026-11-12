<?php

namespace Tests\Unit\AI;

use App\Exceptions\AI\AISchemaValidationException;
use App\Services\AI\Validation\AIResponseValidator;
use Tests\TestCase;

class AIResponseValidatorTest extends TestCase
{
    protected AIResponseValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new AIResponseValidator();
    }

    protected function getSampleValidResponse(): array
    {
        return [
            'recommendation' => [
                'decision' => 'BUY',
                'confidence' => 0.82,
                'summary' => 'Strong profit margin and verified supplier credentials.',
            ],
            'opportunity' => [
                'score' => 78.5,
                'level' => 'HIGH',
                'justification' => 'Calculated margin exceeds 25% threshold.',
            ],
            'profitability' => [
                'profit' => '45.0000',
                'margin' => 32.14,
                'assessment' => 'Profitable spread.',
            ],
            'competition' => [
                'level' => 'MEDIUM',
                'score' => 45.0,
                'summary' => 'Moderate competitive pressure.',
            ],
            'risk' => [
                'level' => 'LOW',
                'score' => 28.0,
                'factors' => ['Slight supplier lead time variance.'],
            ],
            'reasons' => [
                'Consistent profit spread across tested market listings.',
                'Reputable seller track record.',
            ],
            'warnings' => [
                'Sales volume is unavailable; test small order first.',
            ],
            'action_plan' => [
                'Order test batch of 10 units.',
                'Audit inbound delivery lead time.',
            ],
            'data_truth_summary' => [
                'observed_data_available' => true,
                'missing_critical_data' => [],
            ],
        ];
    }

    protected function getSampleContext(
        bool $isProfitable = true,
        int $offersCount = 2,
        float $purchase = 100.0,
        float $selling = 145.0,
        float $risk = 30.0,
        bool $shippingVerified = true
    ): array {
        return [
            'market' => [
                'offers_count' => $offersCount,
            ],
            'financial_analysis' => [
                'is_profitable' => ['value' => $isProfitable],
                'purchase_price' => ['value' => (string) $purchase],
                'selling_price' => ['value' => (string) $selling],
                'shipping_cost' => ['value' => '15.00', 'is_verified' => $shippingVerified],
                'estimated_profit' => ['value' => $isProfitable ? '45.0000' : '-12.5000'],
                'profit_margin_percent' => ['value' => $isProfitable ? 32.14 : -10.00],
            ],
            'deterministic_heuristics' => [
                'opportunity_score' => ['value' => 78.5],
                'risk_score' => ['value' => $risk],
                'competition_level' => ['value' => 'MEDIUM'],
            ],
        ];
    }

    public function test_valid_schema_passes_validation_cleanly(): void
    {
        $response = $this->getSampleValidResponse();
        $context = $this->getSampleContext(true);

        $validated = $this->validator->validate($response, $context);

        $this->assertEquals('BUY', $validated['recommendation']['decision']);
        $this->assertEquals(0.82, $validated['recommendation']['confidence']);
        $this->assertEquals('45.0000', $validated['profitability']['profit']);
    }

    public function test_missing_required_section_throws_schema_validation_exception(): void
    {
        $response = $this->getSampleValidResponse();
        unset($response['risk']); // Missing required section

        $this->expectException(AISchemaValidationException::class);
        $this->expectExceptionMessage('AI structured output failed schema validation');

        $this->validator->validate($response, $this->getSampleContext());
    }

    public function test_invalid_decision_enum_throws_schema_validation_exception(): void
    {
        $response = $this->getSampleValidResponse();
        $response['recommendation']['decision'] = 'UNKNOWN'; // Invalid enum as specified in prompt

        $this->expectException(AISchemaValidationException::class);
        $this->expectExceptionMessage('recommendation.decision must be one of: BUY, WATCH, AVOID, INSUFFICIENT_DATA');

        $this->validator->validate($response, $this->getSampleContext());
    }

    public function test_out_of_range_confidence_throws_schema_validation_exception(): void
    {
        $response = $this->getSampleValidResponse();
        $response['recommendation']['confidence'] = 2.5; // Must be between 0.0 and 1.0 (prompt example)

        $this->expectException(AISchemaValidationException::class);
        $this->expectExceptionMessage('recommendation.confidence must be a number between 0.0 and 1.0');

        $this->validator->validate($response, $this->getSampleContext());
    }

    public function test_missing_recommendation_summary_throws_validation_exception(): void
    {
        $response = $this->getSampleValidResponse();
        unset($response['recommendation']['summary']); // Missing summary

        $this->expectException(AISchemaValidationException::class);
        $this->expectExceptionMessage('recommendation.summary must be a non-empty string');

        $this->validator->validate($response, $this->getSampleContext());
    }

    public function test_out_of_range_score_throws_schema_validation_exception(): void
    {
        $response = $this->getSampleValidResponse();
        $response['opportunity']['score'] = 150; // Must be between 0 and 100

        $this->expectException(AISchemaValidationException::class);
        $this->expectExceptionMessage('opportunity.score must be a numeric value between 0 and 100');

        $this->validator->validate($response, $this->getSampleContext());
    }

    public function test_arbiter_overrules_contradictory_buy_on_unprofitable_unit(): void
    {
        $response = $this->getSampleValidResponse();
        $response['recommendation']['decision'] = 'BUY';

        // Context reflects negative profit (-12.50)
        $unprofitableContext = $this->getSampleContext(isProfitable: false, offersCount: 2);

        $validated = $this->validator->validate($response, $unprofitableContext);

        // Strict Data Truth: Must be overridden to AVOID!
        $this->assertEquals('AVOID', $validated['recommendation']['decision']);
        $this->assertStringContainsString('[Arbiter Override]', $validated['recommendation']['summary']);
        $this->assertStringContainsString('overruled by Laravel deterministic financial rules', end($validated['warnings']));
    }

    public function test_arbiter_overrules_buy_when_zero_market_offers_observed(): void
    {
        $response = $this->getSampleValidResponse();
        $response['recommendation']['decision'] = 'BUY';

        // Context reflects zero market listings
        $zeroOffersContext = $this->getSampleContext(isProfitable: true, offersCount: 0);

        $validated = $this->validator->validate($response, $zeroOffersContext);

        // Must be overridden to INSUFFICIENT_DATA!
        $this->assertEquals('INSUFFICIENT_DATA', $validated['recommendation']['decision']);
        $this->assertStringContainsString('Zero verified marketplace offers exist', $validated['recommendation']['summary']);
    }

    public function test_arbiter_overrules_buy_when_purchase_price_is_missing_or_zero(): void
    {
        $response = $this->getSampleValidResponse();
        $response['recommendation']['decision'] = 'BUY';

        // Context reflects unverified zero purchase price
        $missingPriceContext = $this->getSampleContext(isProfitable: true, offersCount: 2, purchase: 0.0);

        $validated = $this->validator->validate($response, $missingPriceContext);

        $this->assertEquals('INSUFFICIENT_DATA', $validated['recommendation']['decision']);
        $this->assertStringContainsString('Critical financial parameters (purchase or selling price) are unverified', $validated['recommendation']['summary']);
    }

    public function test_arbiter_overrules_buy_when_risk_score_is_elevated_above_60(): void
    {
        $response = $this->getSampleValidResponse();
        $response['recommendation']['decision'] = 'BUY';

        // Context reflects elevated risk score of 75/100
        $highRiskContext = $this->getSampleContext(isProfitable: true, offersCount: 2, risk: 75.0);

        $validated = $this->validator->validate($response, $highRiskContext);

        // Margin-only BUY is downgraded to WATCH
        $this->assertEquals('WATCH', $validated['recommendation']['decision']);
        $this->assertStringContainsString('Elevated composite risk score (75/100)', $validated['recommendation']['summary']);
    }

    public function test_arbiter_degrades_confidence_and_warns_when_shipping_cost_unverified(): void
    {
        $response = $this->getSampleValidResponse();
        $response['recommendation']['confidence'] = 0.95; // Unwarranted high confidence

        $unverifiedShippingContext = $this->getSampleContext(shippingVerified: false);

        $validated = $this->validator->validate($response, $unverifiedShippingContext);

        // Confidence capped at 0.75
        $this->assertEquals(0.75, $validated['recommendation']['confidence']);
        $this->assertStringContainsString('Net profit is ESTIMATED and pending verified carrier shipping rate quote', end($validated['warnings']));
        $this->assertStringStartsWith('[ESTIMATED]', $validated['profitability']['assessment']);
    }

    public function test_arbiter_overwrites_ai_hallucinated_profit_with_laravel_calculated_amount(): void
    {
        $response = $this->getSampleValidResponse();
        // AI hallucinates a crazy profit of 999.00
        $response['profitability']['profit'] = '999.00';
        $response['profitability']['margin'] = '90.00%';

        $context = $this->getSampleContext(isProfitable: true); // Exact profit is 45.0000 and margin 32.14%

        $validated = $this->validator->validate($response, $context);

        // Exact Laravel calculated figures overwrite AI hallucination as numeric JSON type
        $this->assertEquals('45.0000', $validated['profitability']['profit']);
        $this->assertIsFloat($validated['profitability']['margin']);
        $this->assertSame(32.14, $validated['profitability']['margin']);
    }

    public function test_arbiter_normalizes_ai_percent_string_and_preserves_null_margin(): void
    {
        // Case C: AI sends "26.36%" without deterministic margin in context -> normalized to float 26.36
        $response = $this->getSampleValidResponse();
        $response['profitability']['margin'] = '26.36%';

        $contextWithoutMargin = $this->getSampleContext(isProfitable: true);
        unset($contextWithoutMargin['financial_analysis']['profit_margin_percent']);

        $validated = $this->validator->validate($response, $contextWithoutMargin);
        $this->assertIsFloat($validated['profitability']['margin']);
        $this->assertSame(26.36, $validated['profitability']['margin']);

        // Case E: Missing / null deterministic margin preserves null rather than falling back to "0%" or 0
        $contextWithNullMargin = $this->getSampleContext(isProfitable: true);
        $contextWithNullMargin['financial_analysis']['profit_margin_percent'] = ['value' => null];
        $validatedNull = $this->validator->validate($response, $contextWithNullMargin);
        $this->assertNull($validatedNull['profitability']['margin']);
    }

    public function test_arbiter_enforces_deterministic_opportunity_and_risk_scores_over_ai_values(): void
    {
        $response = $this->getSampleValidResponse();
        $response['recommendation']['decision'] = 'BUY';
        $response['recommendation']['confidence'] = 0.875;
        $response['opportunity']['score'] = 99;
        $response['opportunity']['level'] = 'HIGH';
        $response['risk']['score'] = 1;
        $response['risk']['level'] = 'LOW';

        $context = $this->getSampleContext(isProfitable: true, offersCount: 2, purchase: 100.0, selling: 145.0, risk: 75.0, shippingVerified: true);
        $context['deterministic_heuristics']['opportunity_score']['value'] = 42.5;

        $validated = $this->validator->validate($response, $context);

        $this->assertSame(42.5, $validated['opportunity']['score']);
        $this->assertSame('LOW', $validated['opportunity']['level']);
        $this->assertSame(75.0, $validated['risk']['score']);
        $this->assertSame('HIGH', $validated['risk']['level']);
        $this->assertSame('WATCH', $validated['recommendation']['decision']);
        $this->assertSame(0.875, $validated['recommendation']['confidence']);
    }

    public function test_arbiter_caps_confidence_when_critical_data_is_insufficient(): void
    {
        // Case 1: Zero market offers with high AI confidence (even if shipping verified)
        $response = $this->getSampleValidResponse();
        $response['recommendation']['decision'] = 'INSUFFICIENT_DATA';
        $response['recommendation']['confidence'] = 0.95;

        $zeroOffersContext = $this->getSampleContext(isProfitable: true, offersCount: 0, shippingVerified: true);
        $validatedZeroOffers = $this->validator->validate($response, $zeroOffersContext);
        $this->assertSame(0.35, $validatedZeroOffers['recommendation']['confidence']);

        // Case 2: Missing purchase price with AI trying WATCH + 0.90 confidence
        $responseWatch = $this->getSampleValidResponse();
        $responseWatch['recommendation']['decision'] = 'WATCH';
        $responseWatch['recommendation']['confidence'] = 0.90;

        $missingPurchaseContext = $this->getSampleContext(isProfitable: true, offersCount: 2, purchase: 0.0, shippingVerified: true);
        $validatedMissingPurchase = $this->validator->validate($responseWatch, $missingPurchaseContext);
        $this->assertSame(0.35, $validatedMissingPurchase['recommendation']['confidence']);

        // Case 3: Missing selling price
        $missingSellingContext = $this->getSampleContext(isProfitable: true, offersCount: 2, purchase: 100.0, selling: 0.0, shippingVerified: true);
        $validatedMissingSelling = $this->validator->validate($responseWatch, $missingSellingContext);
        $this->assertSame(0.35, $validatedMissingSelling['recommendation']['confidence']);

        // Case 4: Valid AI confidence already <= 0.35 is preserved verbatim (e.g. 0.25)
        $responseLow = $this->getSampleValidResponse();
        $responseLow['recommendation']['decision'] = 'INSUFFICIENT_DATA';
        $responseLow['recommendation']['confidence'] = 0.25;
        $validatedLow = $this->validator->validate($responseLow, $zeroOffersContext);
        $this->assertSame(0.25, $validatedLow['recommendation']['confidence']);
    }

    public function test_arbiter_overrules_buy_and_clears_hallucinated_financials_when_selling_price_is_null(): void
    {
        $response = $this->getSampleValidResponse();
        $response['recommendation']['decision'] = 'BUY';
        $response['recommendation']['confidence'] = 0.92;
        $response['profitability']['profit'] = '269.9955';
        $response['profitability']['margin'] = '31.03%';

        $contextMissingSelling = [
            'market' => [
                'offers_count' => 1,
            ],
            'financial_analysis' => [
                'is_profitable' => ['value' => null, 'truth' => null],
                'purchase_price' => ['value' => '599.9900', 'truth' => 'OBSERVED'],
                'selling_price' => ['value' => null, 'truth' => null, 'status' => 'DATA_UNAVAILABLE'],
                'shipping_cost' => ['value' => '0.0000', 'is_verified' => false],
                'estimated_profit' => ['value' => null, 'truth' => null, 'status' => 'INSUFFICIENT_DATA'],
                'profit_margin_percent' => ['value' => null, 'truth' => null],
                'return_on_investment_percent' => ['value' => null, 'truth' => null],
            ],
            'deterministic_heuristics' => [
                'opportunity_score' => ['value' => 45.0],
                'risk_score' => ['value' => 75.0],
                'competition_level' => ['value' => 'LOW'],
            ],
        ];

        $validated = $this->validator->validate($response, $contextMissingSelling);

        $this->assertSame('INSUFFICIENT_DATA', $validated['recommendation']['decision']);
        $this->assertSame(0.35, $validated['recommendation']['confidence']);
        $this->assertSame('DATA_UNAVAILABLE', $validated['profitability']['profit']);
        $this->assertNull($validated['profitability']['margin']);
    }
}


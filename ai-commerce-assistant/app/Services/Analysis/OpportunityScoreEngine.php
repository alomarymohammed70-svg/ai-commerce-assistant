<?php

namespace App\Services\Analysis;

use App\Contracts\Analysis\OpportunityScoreStrategyInterface;
use App\Services\Analysis\Strategies\HeuristicScoreStrategy;
use InvalidArgumentException;

/**
 * Strategy coordinator for opportunity scoring.
 * Encapsulates the strategy pattern so future AI / Machine Learning strategies
 * can be registered and selected without modifying analysis consumers.
 */
class OpportunityScoreEngine
{
    /**
     * @var array<string, OpportunityScoreStrategyInterface>
     */
    protected array $strategies = [];

    protected string $defaultStrategy;

    public function __construct(?OpportunityScoreStrategyInterface $defaultStrategy = null)
    {
        $default = $defaultStrategy ?? new HeuristicScoreStrategy();
        $this->registerStrategy($default);
        $this->defaultStrategy = $default->getName();
    }

    public function registerStrategy(OpportunityScoreStrategyInterface $strategy): self
    {
        $this->strategies[$strategy->getName()] = $strategy;
        return $this;
    }

    public function setDefaultStrategy(string $name): self
    {
        if (! isset($this->strategies[$name])) {
            throw new InvalidArgumentException("Strategy [{$name}] is not registered.");
        }

        $this->defaultStrategy = $name;
        return $this;
    }

    public function strategy(?string $name = null): OpportunityScoreStrategyInterface
    {
        $selected = $name ?? $this->defaultStrategy;

        if (! isset($this->strategies[$selected])) {
            throw new InvalidArgumentException("Strategy [{$selected}] is not registered.");
        }

        return $this->strategies[$selected];
    }

    /**
     * Compute opportunity score using selected or default strategy.
     */
    public function compute(
        float $margin,
        ?float $rating,
        int $reviewsCount,
        float $purchaseCost,
        array $context = [],
        ?string $strategyName = null
    ): float {
        return $this->strategy($strategyName)->compute(
            $margin,
            $rating,
            $reviewsCount,
            $purchaseCost,
            $context
        );
    }
}


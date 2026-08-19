<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\DTO;

use DateTimeImmutable;
use JsonSerializable;

/**
 * Represents a combined/aggregated rate calculated from multiple sources.
 */
final class AggregatedRate implements JsonSerializable
{
    private string $query;
    private string $strategy;
    private float $averagePrice;
    private float $minPrice;
    private float $maxPrice;
    /** @var Rate[] */
    private array $rates;
    private DateTimeImmutable $calculatedAt;

    /**
     * @param Rate[] $rates
     */
    public function __construct(
        string $query,
        string $strategy,
        array $rates,
        ?DateTimeImmutable $calculatedAt = null
    ) {
        $this->query = $query;
        $this->strategy = $strategy;
        $this->rates = $rates;
        $this->calculatedAt = $calculatedAt ?? new DateTimeImmutable();

        if (empty($rates)) {
            $this->averagePrice = 0.0;
            $this->minPrice = 0.0;
            $this->maxPrice = 0.0;
            return;
        }

        $prices = array_map(fn (Rate $r): float => $r->getPrice(), $rates);
        $this->minPrice = min($prices);
        $this->maxPrice = max($prices);
        $this->averagePrice = array_sum($prices) / count($prices);
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getStrategy(): string
    {
        return $this->strategy;
    }

    public function getAveragePrice(): float
    {
        return $this->averagePrice;
    }

    public function getAveragePriceInTomans(): float
    {
        // If rates are in IRR, divide by 10
        $first = $this->getFirstRate();
        if ($first !== null && strtoupper($first->getUnit()) === 'IRR') {
            return $this->averagePrice / 10.0;
        }

        return $this->averagePrice;
    }

    public function getMinPrice(): float
    {
        return $this->minPrice;
    }

    public function getMaxPrice(): float
    {
        return $this->maxPrice;
    }

    /**
     * @return Rate[]
     */
    public function getRates(): array
    {
        return $this->rates;
    }

    public function getFirstRate(): ?Rate
    {
        return $this->rates[0] ?? null;
    }

    public function getSourcesCount(): int
    {
        return count($this->rates);
    }

    public function getCalculatedAt(): DateTimeImmutable
    {
        return $this->calculatedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'strategy' => $this->strategy,
            'average_price' => $this->averagePrice,
            'average_price_toman' => $this->getAveragePriceInTomans(),
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'sources_count' => $this->getSourcesCount(),
            'rates' => array_map(fn (Rate $r): array => $r->toArray(), $this->rates),
            'calculated_at' => $this->calculatedAt->format(DateTimeImmutable::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

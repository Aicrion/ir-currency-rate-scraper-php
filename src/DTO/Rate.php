<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\DTO;

use DateTimeImmutable;
use JsonSerializable;

/**
 * Immutable Data Transfer Object representing a scraped rate or asset price.
 */
final class Rate implements JsonSerializable
{
    private string $id;
    private string $title;
    private ?string $symbol;
    private string $category;
    private float $price;
    private ?float $buyPrice;
    private ?float $sellPrice;
    private ?float $change;
    private ?float $changePercent;
    private ?float $minPrice;
    private ?float $maxPrice;
    private string $unit;
    private string $source;
    private DateTimeImmutable $updatedAt;
    /** @var array<string, mixed> */
    private array $metadata;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $id,
        string $title,
        ?string $symbol,
        string $category,
        float $price,
        ?float $buyPrice = null,
        ?float $sellPrice = null,
        ?float $change = null,
        ?float $changePercent = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        string $unit = 'IRR',
        string $source = 'tgju',
        ?DateTimeImmutable $updatedAt = null,
        array $metadata = []
    ) {
        $this->id = strtolower(trim($id));
        $this->title = trim($title);
        $this->symbol = $symbol !== null && $symbol !== '' ? strtoupper(trim($symbol)) : null;
        $this->category = $category;
        $this->price = $price;
        $this->buyPrice = $buyPrice;
        $this->sellPrice = $sellPrice;
        $this->change = $change;
        $this->changePercent = $changePercent;
        $this->minPrice = $minPrice;
        $this->maxPrice = $maxPrice;
        $this->unit = $unit;
        $this->source = $source;
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
        $this->metadata = $metadata;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getPriceInTomans(): float
    {
        if (strtoupper($this->unit) === 'IRR') {
            return $this->price / 10.0;
        }

        return $this->price;
    }

    public function getBuyPrice(): ?float
    {
        return $this->buyPrice;
    }

    public function getSellPrice(): ?float
    {
        return $this->sellPrice;
    }

    public function getChange(): ?float
    {
        return $this->change;
    }

    public function getChangePercent(): ?float
    {
        return $this->changePercent;
    }

    public function getMinPrice(): ?float
    {
        return $this->minPrice;
    }

    public function getMaxPrice(): ?float
    {
        return $this->maxPrice;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'symbol' => $this->symbol,
            'category' => $this->category,
            'price' => $this->price,
            'price_toman' => $this->getPriceInTomans(),
            'buy_price' => $this->buyPrice,
            'sell_price' => $this->sellPrice,
            'change' => $this->change,
            'change_percent' => $this->changePercent,
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'unit' => $this->unit,
            'source' => $this->source,
            'updated_at' => $this->updatedAt->format(DateTimeImmutable::ATOM),
            'metadata' => $this->metadata,
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

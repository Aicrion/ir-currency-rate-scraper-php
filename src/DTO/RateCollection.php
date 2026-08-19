<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\DTO;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Collection of Rate DTOs with fluent querying, filtering, and search capabilities.
 *
 * @implements IteratorAggregate<int, Rate>
 */
final class RateCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var Rate[] */
    private array $items = [];

    /**
     * @param Rate[] $items
     */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function add(Rate $rate): self
    {
        $this->items[] = $rate;
        return $this;
    }

    /**
     * Find rate by ID, slug, symbol, or Persian title.
     */
    public function get(string $key): ?Rate
    {
        $normalizedKey = strtolower(trim($key));

        foreach ($this->items as $item) {
            if (
                strtolower($item->getId()) === $normalizedKey ||
                ($item->getSymbol() !== null && strtolower($item->getSymbol()) === $normalizedKey) ||
                mb_strtolower($item->getTitle()) === mb_strtolower(trim($key))
            ) {
                return $item;
            }
        }

        return null;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function findById(string $id): ?Rate
    {
        $normalizedId = strtolower(trim($id));
        foreach ($this->items as $item) {
            if (strtolower($item->getId()) === $normalizedId) {
                return $item;
            }
        }
        return null;
    }

    public function findBySymbol(string $symbol): ?Rate
    {
        $normalizedSymbol = strtoupper(trim($symbol));
        foreach ($this->items as $item) {
            if ($item->getSymbol() !== null && strtoupper($item->getSymbol()) === $normalizedSymbol) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Search within collection by query matching against title, symbol, ID, or category.
     */
    public function search(string $query): RateCollection
    {
        $q = trim($query);
        if ($q === '') {
            return new self($this->items);
        }

        $lowerQ = mb_strtolower($q, 'UTF-8');

        $filtered = array_filter($this->items, function (Rate $item) use ($lowerQ) {
            $titleMatch = mb_stripos($item->getTitle(), $lowerQ, 0, 'UTF-8') !== false;
            $idMatch = stripos($item->getId(), $lowerQ) !== false;
            $symbolMatch = $item->getSymbol() !== null && stripos($item->getSymbol(), $lowerQ) !== false;
            $categoryMatch = stripos($item->getCategory(), $lowerQ) !== false;

            return $titleMatch || $idMatch || $symbolMatch || $categoryMatch;
        });

        return new self(array_values($filtered));
    }

    public function filterByCategory(string $category): RateCollection
    {
        $filtered = array_filter($this->items, fn (Rate $r): bool => $r->getCategory() === $category);
        return new self(array_values($filtered));
    }

    /**
     * @param callable(Rate): bool $callback
     */
    public function filter(callable $callback): RateCollection
    {
        $filtered = array_filter($this->items, $callback);
        return new self(array_values($filtered));
    }

    public function first(): ?Rate
    {
        return $this->items[0] ?? null;
    }

    /**
     * @return Rate[]
     */
    public function all(): array
    {
        return $this->items;
    }

    public function merge(RateCollection $other): RateCollection
    {
        return new self(array_merge($this->items, $other->all()));
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * @return Traversable<int, Rate>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(fn (Rate $r): array => $r->toArray(), $this->items);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

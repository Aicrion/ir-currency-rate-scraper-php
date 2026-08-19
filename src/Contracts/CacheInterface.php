<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Contracts;

/**
 * Lightweight Cache Interface compatible with standard key-value cache operations and PSR-16.
 */
interface CacheInterface
{
    /**
     * Fetches a value from the cache.
     *
     * @param string $key The unique cache key.
     * @param mixed $default Default value to return if the key does not exist.
     * @return mixed The cached value, or $default if not found.
     */
    public function get(string $key, $default = null);

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store. Must be serializable.
     * @param int|null $ttl Optional. Expiration time in seconds. Null means default or permanent.
     * @return bool True on success and false on failure.
     */
    public function set(string $key, $value, ?int $ttl = null): bool;

    /**
     * Determines whether an item is present in the cache.
     */
    public function has(string $key): bool;

    /**
     * Delete an item from the cache by its unique key.
     */
    public function delete(string $key): bool;

    /**
     * Wipes clean the entire cache repository.
     */
    public function clear(): bool;
}

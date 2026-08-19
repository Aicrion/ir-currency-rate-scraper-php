<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Cache;

use Aicrion\IrCurrencyRateScraper\Contracts\CacheInterface;
use Psr\SimpleCache\CacheInterface as Psr16CacheInterface;

/**
 * Adapter allowing users to plug in any PSR-16 compliant cache (e.g. Redis, Memcached, Laravel Cache, Symfony Cache).
 */
final class Psr16CacheAdapter implements CacheInterface
{
    private Psr16CacheInterface $cache;

    public function __construct(Psr16CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function get(string $key, $default = null)
    {
        return $this->cache->get($key, $default);
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        return $this->cache->set($key, $value, $ttl);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }

    public function clear(): bool
    {
        return $this->cache->clear();
    }
}

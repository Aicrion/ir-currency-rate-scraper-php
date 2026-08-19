<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Cache;

use Aicrion\IrCurrencyRateScraper\Contracts\CacheInterface;

/**
 * No-op cache implementation when caching is disabled.
 */
final class NullCache implements CacheInterface
{
    public function get(string $key, $default = null)
    {
        return $default;
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        return true;
    }

    public function has(string $key): bool
    {
        return false;
    }

    public function delete(string $key): bool
    {
        return true;
    }

    public function clear(): bool
    {
        return true;
    }
}

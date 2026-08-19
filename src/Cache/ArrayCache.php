<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Cache;

use Aicrion\IrCurrencyRateScraper\Contracts\CacheInterface;

/**
 * Fast in-memory array cache for single-request lifecycles.
 */
final class ArrayCache implements CacheInterface
{
    /** @var array<string, array{value: mixed, expires_at: ?int}> */
    private array $storage = [];

    public function get(string $key, $default = null)
    {
        if (!isset($this->storage[$key])) {
            return $default;
        }

        $item = $this->storage[$key];
        if ($item['expires_at'] !== null && time() >= $item['expires_at']) {
            unset($this->storage[$key]);
            return $default;
        }

        return $item['value'];
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $expiresAt = $ttl !== null && $ttl > 0 ? time() + $ttl : null;
        $this->storage[$key] = [
            'value' => $value,
            'expires_at' => $expiresAt,
        ];
        return true;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): bool
    {
        unset($this->storage[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->storage = [];
        return true;
    }
}

<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Cache;

use Aicrion\IrCurrencyRateScraper\Contracts\CacheInterface;

/**
 * File-based Cache Driver with TTL expiration and atomic file locking.
 * Suitable for persistent caching across multiple requests and CLI/Cron tasks without external cache servers.
 */
final class FileCache implements CacheInterface
{
    private string $cacheDir;
    private int $defaultTtl;

    public function __construct(?string $cacheDir = null, int $defaultTtl = 300)
    {
        $this->cacheDir = rtrim($cacheDir ?? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'currency_rate_scraper_cache', DIRECTORY_SEPARATOR);
        $this->defaultTtl = $defaultTtl;

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }
    }

    public function get(string $key, $default = null)
    {
        $filePath = $this->getFilePath($key);
        if (!file_exists($filePath)) {
            return $default;
        }

        $content = @file_get_contents($filePath);
        if ($content === false) {
            return $default;
        }

        $data = @unserialize($content);
        if (!is_array($data) || !isset($data['expires_at'], $data['value'])) {
            $this->delete($key);
            return $default;
        }

        if ($data['expires_at'] !== null && time() >= $data['expires_at']) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $filePath = $this->getFilePath($key);
        $effectiveTtl = $ttl !== null ? $ttl : $this->defaultTtl;
        $expiresAt = $effectiveTtl > 0 ? time() + $effectiveTtl : null;

        $payload = serialize([
            'expires_at' => $expiresAt,
            'value' => $value,
        ]);

        $tmpFile = $filePath . '.' . uniqid('tmp', true);
        if (@file_put_contents($tmpFile, $payload, LOCK_EX) === false) {
            return false;
        }

        return @rename($tmpFile, $filePath);
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): bool
    {
        $filePath = $this->getFilePath($key);
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }
        return true;
    }

    public function clear(): bool
    {
        if (!is_dir($this->cacheDir)) {
            return true;
        }

        $files = glob($this->cacheDir . DIRECTORY_SEPARATOR . '*.cache');
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }

        return true;
    }

    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    private function getFilePath(string $key): string
    {
        $safeName = md5($key) . '.cache';
        return $this->cacheDir . DIRECTORY_SEPARATOR . $safeName;
    }
}

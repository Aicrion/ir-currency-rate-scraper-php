<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Load a raw fixture file from the tests/Fixtures directory.
     */
    protected function loadFixture(string $relativePath): string
    {
        $path = __DIR__ . '/Fixtures/' . ltrim($relativePath, '/');
        if (!file_exists($path)) {
            throw new \RuntimeException("Fixture file not found: {$path}");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Failed to read fixture: {$path}");
        }

        return $content;
    }
}

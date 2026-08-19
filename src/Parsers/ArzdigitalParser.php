<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Parsers;

use DateTimeImmutable;
use Aicrion\IrCurrencyRateScraper\Contracts\ParserInterface;
use Aicrion\IrCurrencyRateScraper\DTO\Rate;
use Aicrion\IrCurrencyRateScraper\DTO\RateCollection;
use Aicrion\IrCurrencyRateScraper\Enums\RateCategory;
use Aicrion\IrCurrencyRateScraper\Exceptions\ParseException;
use Aicrion\IrCurrencyRateScraper\Support\DomHelper;
use Aicrion\IrCurrencyRateScraper\Support\PriceNormalizer;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Resilient parser for Arzdigital crypto rates (HTML table and embedded JSON).
 */
final class ArzdigitalParser implements ParserInterface
{
    public function parse(string $content, string $category = RateCategory::CRYPTO): RateCollection
    {
        if (trim($content) === '') {
            throw new ParseException('Cannot parse empty Arzdigital content');
        }

        // Check if content is JSON
        $firstChar = substr(trim($content), 0, 1);
        if ($firstChar === '{' || $firstChar === '[') {
            $json = json_decode($content, true);
            if (is_array($json)) {
                return $this->parseJson($json, $category);
            }
        }

        // HTML Parsing
        $crawler = new Crawler($content);
        $collection = new RateCollection();

        // 1. Try table rows
        $rows = $crawler->filter('table tbody tr, .arz-table tbody tr, tr.arz-coin-table__tr');
        if ($rows->count() > 0) {
            $rows->each(function (Crawler $row) use (&$collection, $category) {
                $rate = $this->parseHtmlRow($row, $category);
                if ($rate !== null && $rate->getPrice() > 0) {
                    $collection->add($rate);
                }
            });
        }

        // 2. Fallback: Search for JSON state in script tags (e.g. window.__INITIAL_STATE__ or __NEXT_DATA__)
        if ($collection->isEmpty()) {
            $crawler->filter('script')->each(function (Crawler $script) use (&$collection, $category) {
                $scriptText = $script->text('');
                if (strpos($scriptText, 'coins') !== false || strpos($scriptText, 'items') !== false) {
                    if (preg_match('/(?:coins|items|data)\s*:\s*(\[\{.*?\}\])/s', $scriptText, $match)) {
                        $items = json_decode($match[1], true);
                        if (is_array($items)) {
                            $parsed = $this->parseJsonItems($items, $category);
                            foreach ($parsed as $r) {
                                $collection->add($r);
                            }
                        }
                    }
                }
            });
        }

        return $collection;
    }

    private function parseHtmlRow(Crawler $row, string $category): ?Rate
    {
        $cells = $row->filter('td');
        if ($cells->count() < 3) {
            return null;
        }

        // Find symbol / title
        $titleNode = $row->filter('.arz-coin-title, .coin-name, td:nth-child(2), td:nth-child(1)');
        $title = PriceNormalizer::cleanText($titleNode->first()->text(''));

        $symbolNode = $row->filter('.arz-coin-symbol, .coin-symbol, span.symbol');
        $symbol = $symbolNode->count() > 0 ? trim($symbolNode->first()->text('')) : null;

        if ($symbol === null) {
            // Extract from title if e.g. "Bitcoin (BTC)"
            if (preg_match('/(.*?)\s*\(([A-Za-z0-9]+)\)/', $title, $m)) {
                $title = trim($m[1]);
                $symbol = strtoupper(trim($m[2]));
            } else {
                $symbol = DomHelper::mapPersianTitleToSymbol($title);
            }
        }

        // Price extraction
        $price = null;
        $priceNode = $row->filter('.arz-coin-price, .coin-price, td.price, td:nth-child(3)');
        if ($priceNode->count() > 0) {
            $price = PriceNormalizer::parsePrice($priceNode->first()->text(''));
        }

        if ($price === null || $price <= 0) {
            return null;
        }

        // Change percent
        $changePercent = null;
        $changeNode = $row->filter('.arz-coin-change, .change-24h, td:nth-child(4)');
        if ($changeNode->count() > 0) {
            $changePercent = PriceNormalizer::parsePercentage($changeNode->first()->text(''));
        }

        $slug = $symbol ? strtolower($symbol) : 'arz_' . md5($title);

        return new Rate(
            $slug,
            $title,
            $symbol,
            $category,
            $price,
            null,
            null,
            null,
            $changePercent,
            null,
            null,
            'USD',
            'arzdigital',
            new DateTimeImmutable()
        );
    }

    /**
     * @param array<string, mixed> $json
     */
    private function parseJson(array $json, string $category): RateCollection
    {
        $items = $json['data']['items'] ?? $json['data'] ?? $json['coins'] ?? $json['items'] ?? $json;
        if (!is_array($items)) {
            return new RateCollection();
        }

        return new RateCollection($this->parseJsonItems($items, $category));
    }

    /**
     * @param array<int|string, mixed> $items
     * @return Rate[]
     */
    private function parseJsonItems(array $items, string $category): array
    {
        $rates = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = $item['name'] ?? $item['title'] ?? $item['fa_name'] ?? '';
            $symbol = $item['symbol'] ?? $item['code'] ?? null;
            $price = $item['price'] ?? $item['usd_price'] ?? $item['price_usd'] ?? $item['current_price'] ?? null;

            if ($price === null) {
                continue;
            }

            $priceFloat = (float) $price;
            if ($priceFloat <= 0) {
                continue;
            }

            $changePercent = isset($item['change_24h']) ? (float) $item['change_24h'] : (isset($item['percent_change_24h']) ? (float) $item['percent_change_24h'] : null);
            $slug = $symbol ? strtolower((string) $symbol) : 'arz_' . md5((string) $title);

            $rates[] = new Rate(
                $slug,
                (string) $title,
                $symbol !== null ? strtoupper((string) $symbol) : null,
                $category,
                $priceFloat,
                null,
                null,
                null,
                $changePercent,
                isset($item['low_24h']) ? (float) $item['low_24h'] : null,
                isset($item['high_24h']) ? (float) $item['high_24h'] : null,
                'USD',
                'arzdigital',
                new DateTimeImmutable(),
                ['raw' => $item]
            );
        }

        return $rates;
    }
}

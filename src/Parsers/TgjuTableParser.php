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
 * Resilient parser for TGJU market tables across Currencies, Bank, Sana, Nima, Transfer, Gold, Coin, and Crypto.
 */
final class TgjuTableParser implements ParserInterface
{
    public function parse(string $content, string $category): RateCollection
    {
        if (trim($content) === '') {
            throw new ParseException('Cannot parse empty content');
        }

        $crawler = new Crawler($content);
        $collection = new RateCollection();

        // 1. First attempt: Find tables with data-market-row or standard market-table
        $tables = $crawler->filter('table.market-table, table.table-data, table.table, table');

        if ($tables->count() === 0) {
            // Check if it's a profile page mistakenly passed
            $profileParser = new TgjuProfileParser();
            $singleRate = $profileParser->parseSingle($crawler, $category);
            if ($singleRate !== null) {
                return new RateCollection([$singleRate]);
            }

            throw new ParseException("No valid table structure found in HTML content for category '{$category}'");
        }

        $tables->each(function (Crawler $table) use (&$collection, $category) {
            $colMap = DomHelper::detectTableColumnMap($table);
            $rows = $table->filter('tbody tr, tr[data-market-row]');

            if ($rows->count() === 0) {
                $rows = $table->filter('tr')->slice(1); // skip header row if no tbody
            }

            $rows->each(function (Crawler $row) use (&$collection, $category, $colMap) {
                $rate = $this->parseRow($row, $category, $colMap);
                if ($rate !== null && $rate->getPrice() > 0) {
                    $collection->add($rate);
                }
            });
        });

        return $collection;
    }

    /**
     * @param array<string, int> $colMap
     */
    private function parseRow(Crawler $row, string $category, array $colMap): ?Rate
    {
        // 1. Check data-market-row attribute
        $rowSlug = $row->attr('data-market-row') ?? $row->attr('data-slug') ?? $row->attr('id');

        // Extract cells
        $cells = $row->filter('td, th');
        if ($cells->count() < 2) {
            return null;
        }

        // Title & Slug extraction
        $titleIndex = $colMap['title'] >= 0 && $colMap['title'] < $cells->count() ? $colMap['title'] : 0;
        $titleCell = $cells->eq($titleIndex);
        $titleLink = $titleCell->filter('a');

        $title = PriceNormalizer::cleanText($titleCell->text(''));
        $linkHref = $titleLink->count() > 0 ? $titleLink->attr('href') : null;

        $slug = $rowSlug ?? DomHelper::extractSlugFromUrl($linkHref);
        if ($slug === null || $slug === '') {
            $slug = 'item_' . md5($title);
        }

        if ($title === '') {
            return null;
        }

        // Symbol resolution
        $symbol = DomHelper::mapPersianTitleToSymbol($title);

        // Price extraction
        $price = null;
        // Priority 1: Check data-price attribute on row or cells
        $dataPrice = $row->attr('data-price');
        if ($dataPrice !== null) {
            $price = PriceNormalizer::parsePrice($dataPrice);
        }

        // Priority 2: Use column map
        if ($price === null) {
            $priceIndex = $colMap['price'] >= 0 && $colMap['price'] < $cells->count() ? $colMap['price'] : 1;
            $priceCell = $cells->eq($priceIndex);
            $price = PriceNormalizer::parsePrice($priceCell->text(''));
        }

        if ($price === null || $price <= 0) {
            return null;
        }

        // Buy / Sell prices (especially relevant for SANA, NIMA, Bank)
        $buyPrice = null;
        $sellPrice = null;
        if ($colMap['buy'] >= 0 && $colMap['buy'] < $cells->count()) {
            $buyPrice = PriceNormalizer::parsePrice($cells->eq($colMap['buy'])->text(''));
        }
        if ($colMap['sell'] >= 0 && $colMap['sell'] < $cells->count()) {
            $sellPrice = PriceNormalizer::parsePrice($cells->eq($colMap['sell'])->text(''));
        }

        // Change & Change Percent
        $change = null;
        $changePercent = null;
        if ($colMap['change'] >= 0 && $colMap['change'] < $cells->count()) {
            [$change, $extractedPercent] = PriceNormalizer::parseChangeAndPercentage($cells->eq($colMap['change'])->text(''));
            $changePercent = $extractedPercent;
        }
        if ($changePercent === null && $colMap['percent'] >= 0 && $colMap['percent'] < $cells->count()) {
            $changePercent = PriceNormalizer::parsePercentage($cells->eq($colMap['percent'])->text(''));
        }

        // Min & Max
        $minPrice = null;
        $maxPrice = null;
        if ($colMap['min'] >= 0 && $colMap['min'] < $cells->count()) {
            $minPrice = PriceNormalizer::parsePrice($cells->eq($colMap['min'])->text(''));
        }
        if ($colMap['max'] >= 0 && $colMap['max'] < $cells->count()) {
            $maxPrice = PriceNormalizer::parsePrice($cells->eq($colMap['max'])->text(''));
        }

        // Currency Unit
        $unit = $category === RateCategory::CRYPTO ? 'USD' : 'IRR';

        return new Rate(
            $slug,
            $title,
            $symbol,
            $category,
            $price,
            $buyPrice,
            $sellPrice,
            $change,
            $changePercent,
            $minPrice,
            $maxPrice,
            $unit,
            'tgju',
            new DateTimeImmutable(),
            [
                'url' => $linkHref,
                'raw_title' => $title,
            ]
        );
    }
}

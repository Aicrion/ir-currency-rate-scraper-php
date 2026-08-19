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
 * Parses individual profile pages on TGJU (e.g. /profile/price_dollar_rl, /profile/geram18).
 */
final class TgjuProfileParser implements ParserInterface
{
    public function parse(string $content, string $category = RateCategory::FREE_CURRENCY): RateCollection
    {
        if (trim($content) === '') {
            throw new ParseException('Cannot parse empty profile content');
        }

        $crawler = new Crawler($content);
        $rate = $this->parseSingle($crawler, $category);

        if ($rate === null) {
            throw new ParseException('Failed to extract rate from profile page');
        }

        return new RateCollection([$rate]);
    }

    public function parseSingle(Crawler $crawler, string $category): ?Rate
    {
        // 1. Title Extraction
        $titleNode = $crawler->filter('h1.title, .profile-header h1, h1, .header-title');
        $rawTitle = $titleNode->count() > 0 ? $titleNode->first()->text('') : '';
        $title = PriceNormalizer::cleanText($rawTitle);

        if ($title === '') {
            $titleTag = $crawler->filter('title');
            if ($titleTag->count() > 0) {
                $titleText = explode('|', $titleTag->text(''))[0] ?? '';
                $title = PriceNormalizer::cleanText($titleText);
            }
        }

        if ($title === '') {
            $title = 'Unknown Asset';
        }

        // 2. Price Extraction (Cascading multi-selector strategy)
        $price = null;

        $selectors = [
            'span[data-col="price"]',
            'td[data-col="price"]',
            '.data-value',
            '.header-info .price',
            '.info-price span.value',
            '.info-price',
            '.value',
            'span.info-price-value',
            'td.nf',
        ];

        foreach ($selectors as $sel) {
            $node = $crawler->filter($sel);
            if ($node->count() > 0) {
                $candidate = PriceNormalizer::parsePrice($node->first()->text(''));
                if ($candidate !== null && $candidate > 0) {
                    $price = $candidate;
                    break;
                }
            }
        }

        // Fallback: search via regex in script tags or raw HTML for price
        if ($price === null) {
            $html = $crawler->html();
            if (preg_match('/"price":\s*"([\d,]+)"/i', $html, $m) || preg_match('/data-price="([\d,]+)"/i', $html, $m)) {
                $price = PriceNormalizer::parsePrice($m[1]);
            }
        }

        if ($price === null || $price <= 0) {
            return null;
        }

        // 3. Change & Percent
        $change = null;
        $changePercent = null;

        $changeNode = $crawler->filter('span[data-col="change"], td[data-col="change"]');
        if ($changeNode->count() > 0) {
            $change = PriceNormalizer::parsePrice($changeNode->first()->text(''));
        } elseif (($infoChange = $crawler->filter('.info-change'))->count() > 0) {
            [$change, $changePercent] = PriceNormalizer::parseChangeAndPercentage($infoChange->first()->text(''));
        }

        if ($changePercent === null) {
            $percentNode = $crawler->filter('span[data-col="percent"], td[data-col="percent"], .info-percent');
            if ($percentNode->count() > 0) {
                $changePercent = PriceNormalizer::parsePercentage($percentNode->first()->text(''));
            }
        }

        // 4. Min & Max
        $minPrice = null;
        $maxPrice = null;
        $lowNode = $crawler->filter('span[data-col="low"], td[data-col="low"], .info-low');
        if ($lowNode->count() > 0) {
            $minPrice = PriceNormalizer::parsePrice($lowNode->first()->text(''));
        }

        $highNode = $crawler->filter('span[data-col="high"], td[data-col="high"], .info-high');
        if ($highNode->count() > 0) {
            $maxPrice = PriceNormalizer::parsePrice($highNode->first()->text(''));
        }

        // 5. Symbol & Slug
        $symbol = DomHelper::mapPersianTitleToSymbol($title);
        $slug = 'profile_' . ($symbol ? strtolower($symbol) : md5($title));

        return new Rate(
            $slug,
            $title,
            $symbol,
            $category,
            $price,
            null,
            null,
            $change,
            $changePercent,
            $minPrice,
            $maxPrice,
            $category === RateCategory::CRYPTO ? 'USD' : 'IRR',
            'tgju',
            new DateTimeImmutable(),
            ['is_profile_fallback' => true]
        );
    }
}

<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Support;

use Symfony\Component\DomCrawler\Crawler;

final class DomHelper
{
    /**
     * Map table column indices based on header keywords.
     *
     * @return array<string, int>
     */
    public static function detectTableColumnMap(Crawler $tableCrawler): array
    {
        $map = [
            'title' => 0,
            'price' => 1,
            'change' => 2,
            'percent' => 3,
            'min' => -1,
            'max' => -1,
            'time' => -1,
            'buy' => -1,
            'sell' => -1,
        ];

        $headers = $tableCrawler->filter('thead th, tr:first-child th');
        if ($headers->count() === 0) {
            $headers = $tableCrawler->filter('tr:first-child td');
        }

        if ($headers->count() === 0) {
            return $map;
        }

        $headers->each(function (Crawler $th, int $i) use (&$map) {
            $text = PriceNormalizer::cleanText($th->text(''));

            if (self::containsAny($text, ['عنوان', 'نام', 'ارز', 'شاخص', 'نماد', 'کالا', 'name', 'title', 'symbol'])) {
                $map['title'] = $i;
            } elseif (self::containsAny($text, ['خرید', 'buy'])) {
                $map['buy'] = $i;
            } elseif (self::containsAny($text, ['فروش', 'sell'])) {
                $map['sell'] = $i;
            } elseif (self::containsAny($text, ['درصد', '%', 'percent'])) {
                $map['percent'] = $i;
            } elseif (self::containsAny($text, ['تغییر', 'نوسان', 'change'])) {
                $map['change'] = $i;
            } elseif (self::containsAny($text, ['کمترین', 'پایین', 'min', 'low'])) {
                $map['min'] = $i;
            } elseif (self::containsAny($text, ['بیشترین', 'بالا', 'max', 'high'])) {
                $map['max'] = $i;
            } elseif (self::containsAny($text, ['زمان', 'تاریخ', 'ساعت', 'time', 'date'])) {
                $map['time'] = $i;
            } elseif (self::containsAny($text, ['قیمت', 'زنده', 'نرخ', 'آخرین', 'ریال', 'تومان', 'ارزش', 'price', 'rate', 'value'])) {
                if (!isset($map['price_set'])) {
                    $map['price'] = $i;
                    $map['price_set'] = 1;
                }
            }
        });

        unset($map['price_set']);
        return $map;
    }

    /**
     * @param string[] $needles
     */
    public static function containsAny(string $haystack, array $needles): bool
    {
        $lowerHaystack = mb_strtolower($haystack, 'UTF-8');
        foreach ($needles as $needle) {
            if (mb_stripos($lowerHaystack, mb_strtolower($needle, 'UTF-8'), 0, 'UTF-8') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extract symbol or slug from a profile URL (e.g. /profile/price_dollar_rl -> price_dollar_rl, /coins/bitcoin/ -> bitcoin).
     */
    public static function extractSlugFromUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $parsedPath = parse_url($url, PHP_URL_PATH);
        $path = is_string($parsedPath) ? trim($parsedPath, '/') : '';
        $segments = explode('/', $path);

        $last = end($segments);
        return $last !== false && $last !== '' ? $last : null;
    }

    /**
     * Match currency symbol from standard Persian titles.
     */
    public static function mapPersianTitleToSymbol(string $title): ?string
    {
        $map = [
            'دلار' => 'USD',
            'دلار آمریکا' => 'USD',
            'دلار استرالیا' => 'AUD',
            'دلار کانادا' => 'CAD',
            'دلار نیوزیلند' => 'NZD',
            'دلار سنگاپور' => 'SGD',
            'دلار هنگ کنگ' => 'HKD',
            'یورو' => 'EUR',
            'پوند' => 'GBP',
            'پوند انگلیس' => 'GBP',
            'درهم' => 'AED',
            'درهم امارات' => 'AED',
            'لیر' => 'TRY',
            'لیر ترکیه' => 'TRY',
            'یوان' => 'CNY',
            'یوان چین' => 'CNY',
            'ین' => 'JPY',
            'ین ژاپن' => 'JPY',
            'دینار' => 'IQD',
            'دینار عراق' => 'IQD',
            'دینار کویت' => 'KWD',
            'دینار بحرین' => 'BHD',
            'ریال عربستان' => 'SAR',
            'ریال عمان' => 'OMR',
            'ریال قطر' => 'QAR',
            'فرانک' => 'CHF',
            'فرانک سوئیس' => 'CHF',
            'کرون سوئد' => 'SEK',
            'کرون نروژ' => 'NOK',
            'کرون دانمارک' => 'DKK',
            'روبل' => 'RUB',
            'روبل روسیه' => 'RUB',
            'روپیه' => 'INR',
            'روپیه هند' => 'INR',
            'روپیه پاکستان' => 'PKR',
            'بات تایلند' => 'THB',
            'رینگیت مالزی' => 'MYR',
            'افغانی' => 'AFN',
            'منات آذربایجان' => 'AZN',
            'لاری گرجستان' => 'GEL',
            'درام ارمنستان' => 'AMD',
            // Gold & Coin
            'طلای 18 عیار' => 'IR18K',
            'طلای ۱۸ عیار' => 'IR18K',
            'طلای 24 عیار' => 'IR24K',
            'طلای ۲۴ عیار' => 'IR24K',
            'مثقال طلا' => 'MESGHAL',
            'آبشده نقدی' => 'MELTED_GOLD',
            'سکه امامی' => 'SEKEE',
            'سکه بهار آزادی' => 'SEKEB',
            'نیم سکه' => 'NIM',
            'ربع سکه' => 'ROB',
            'سکه گرمی' => 'GERAMI',
            // Crypto
            'بیت کوین' => 'BTC',
            'بیت‌کوین' => 'BTC',
            'اتریوم' => 'ETH',
            'تتر' => 'USDT',
            'بایننس کوین' => 'BNB',
            'سولانا' => 'SOL',
            'ریپل' => 'XRP',
            'کاردانو' => 'ADA',
            'دوج کوین' => 'DOGE',
            'تون کوین' => 'TON',
            'ترون' => 'TRX',
        ];

        $clean = PriceNormalizer::cleanText($title);
        foreach ($map as $fa => $sym) {
            if (mb_stripos($clean, $fa, 0, 'UTF-8') !== false) {
                return $sym;
            }
        }

        return null;
    }
}

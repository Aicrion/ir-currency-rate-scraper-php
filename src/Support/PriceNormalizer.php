<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Support;

/**
 * Normalizes Persian/Arabic numerals, removes currency labels, and parses raw price strings into clean floats.
 */
final class PriceNormalizer
{
    private const FA_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    private const AR_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    private const EN_DIGITS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /**
     * Convert Persian and Arabic digits to standard ASCII English digits.
     */
    public static function toEnglishDigits(string $input): string
    {
        $converted = str_replace(self::FA_DIGITS, self::EN_DIGITS, $input);
        return str_replace(self::AR_DIGITS, self::EN_DIGITS, $converted);
    }

    /**
     * Parse any messy price string (e.g. "۱۵۸,۲۵۰ ریال", "$ 65,420.50", "(% ۲.۴-)", "-15,000") into a float or null.
     */
    public static function parsePrice(?string $input): ?float
    {
        if ($input === null) {
            return null;
        }

        $clean = self::toEnglishDigits(trim($input));
        if ($clean === '' || $clean === '-' || $clean === '---') {
            return null;
        }

        // Check if negative
        $isNegative = strpos($clean, '-') !== false || strpos($clean, '(') !== false;

        // Keep only digits, periods and commas
        $numeric = preg_replace('/[^\d.,]/', '', $clean);
        if ($numeric === null || $numeric === '') {
            return null;
        }

        // Standardize separators: if both comma and dot exist, comma is thousands and dot is decimal
        if (strpos($numeric, ',') !== false && strpos($numeric, '.') !== false) {
            $numeric = str_replace(',', '', $numeric);
        } elseif (strpos($numeric, ',') !== false) {
            // Check if comma is decimal (e.g. 15,5) or thousands (e.g. 15,000)
            if (preg_match('/,\d{3}$/', $numeric)) {
                $numeric = str_replace(',', '', $numeric);
            } else {
                $numeric = str_replace(',', '.', $numeric);
            }
        }

        if (!is_numeric($numeric)) {
            return null;
        }

        $val = (float) $numeric;
        return $isNegative ? -abs($val) : $val;
    }

    /**
     * Parse change percentage (e.g. "+ 1.25 %", "۰.۵- %", "(%۰.۲۸)").
     */
    public static function parsePercentage(?string $input): ?float
    {
        if ($input === null) {
            return null;
        }

        $clean = self::toEnglishDigits(trim($input));
        $isNegative = strpos($clean, '-') !== false;

        $numeric = preg_replace('/[^\d.,]/', '', $clean);
        if ($numeric === null || $numeric === '') {
            return null;
        }

        $numeric = str_replace(',', '.', $numeric);
        if (!is_numeric($numeric)) {
            return null;
        }

        $val = (float) $numeric;
        return $isNegative ? -abs($val) : $val;
    }

    /**
     * Extract clean value change and percentage when both or either exist in a string like "+۲,۵۰۰ (%۰.۲۸)" or "-۱,۱۰۰ (%۰.۱۱-)".
     *
     * @return array{0: ?float, 1: ?float} [change, changePercent]
     */
    public static function parseChangeAndPercentage(?string $input): array
    {
        if ($input === null) {
            return [null, null];
        }

        $clean = self::cleanText($input);
        if ($clean === '') {
            return [null, null];
        }

        $change = null;
        $percent = null;

        if (preg_match('/\(([^)]+)\)/', $clean, $m)) {
            $percent = self::parsePercentage($m[1]);
            $cleanWithoutParen = trim(str_replace($m[0], '', $clean));
            if ($cleanWithoutParen !== '') {
                $change = self::parsePrice($cleanWithoutParen);
            }
        } elseif (strpos($clean, '%') !== false) {
            $percent = self::parsePercentage($clean);
        } else {
            $change = self::parsePrice($clean);
        }

        return [$change, $percent];
    }

    /**
     * Clean string from excessive whitespace, zero-width non-joiners (ZWNJ), and non-printable characters.
     */
    public static function cleanText(?string $input): string
    {
        if ($input === null) {
            return '';
        }

        // Replace ZWNJ (\u{200c}) with space or keep it clean
        $text = str_replace(["\xc2\xa0", "\u{200c}", "\r", "\n", "\t"], ' ', $input);
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}

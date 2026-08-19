# Resilient HTML Scraping Engine

Web scraping frequently breaks when websites update their layout, redesign their tables, or alter CSS class names. The `aicrion/ir-currency-rate-scraper` library implements a multi-layer adaptive scraping engine to ensure high availability and self-healing extraction.

---

## 🛡️ Multi-Layer Extraction Pipeline

```
Raw HTML Content
       │
       ▼
[Layer 1: Semantic Data Attributes] ──────► Found data-market-row / data-price? ──► Extract
       │ (Not found or missing items)
       ▼
[Layer 2: Dynamic Header Scanning]  ──────► Matches "عنوان", "قیمت", "خرید"? ─────► Extract
       │ (Layout completely altered)
       ▼
[Layer 3: Profile Fallback URLs]   ──────► Fetch direct profile page (e.g. /profile/price_dollar_rl)
       │ (Profile card parsing)
       ▼
[Layer 4: Script & Regex Parser]    ──────► Parse embedded JSON / window.__data
```

---

## 1. Dynamic Column Auto-Detection (`DomHelper`)
Rather than assuming prices are always in the 2nd column (`<td>:nth-child(2)`), `DomHelper::detectTableColumnMap` scans the table header (`<thead>` or `<tr>`) for semantic Persian/English keywords:

- **Title / Name**: `عنوان`, `نام`, `ارز`, `شاخص`, `نماد`, `title`, `name`, `symbol`
- **Live Price**: `قیمت`, `زنده`, `نرخ`, `آخرین`, `price`, `rate`, `value`
- **Buy Price**: `خرید`, `buy`
- **Sell Price**: `فروش`, `sell`
- **Change**: `تغییر`, `نوسان`, `change`
- **Percent**: `درصد`, `%`, `percent`
- **Min / Low**: `کمترین`, `پایین`, `min`, `low`
- **Max / High**: `بیشترین`, `بالا`, `max`, `high`
- **Time**: `زمان`, `ساعت`, `تاریخ`, `time`, `date`

If a website rearranges or inserts a new column (such as "volume" or "chart button"), the parser automatically shifts column indices without breaking.

---

## 2. Dedicated Profile URL Fallbacks
If a major asset (such as the US Dollar, 18 Karat Gold, or Emami Coin) is temporarily removed from a general market table or relocated behind a tab:
- `TgjuProvider` automatically triggers a secondary request to that asset's dedicated profile endpoint:
  - Dollar: `https://www.tgju.org/profile/price_dollar_rl`
  - 18K Gold: `https://www.tgju.org/profile/geram18`
  - 24K Gold: `https://www.tgju.org/profile/geram24`
- `TgjuProfileParser` parses individual profile widgets (`.info-price`, `span[data-col="price"]`, `.data-value`).

---

## 3. Persian & Arabic Numeral Normalization (`PriceNormalizer`)
Iranian websites render prices in Persian digits (`۱۲۳,۴۵۰`), Arabic digits (`١٢٣,٤٥٠`), with Persian currency suffixes (`ریال`, `تومان`), zero-width non-joiners (`\u{200c}`), and parenthesized signs (`(%۰.۳۴-)`).

`PriceNormalizer` performs:
1. Converting `۰-۹` and `٠-٩` to `0-9`.
2. Stripping thousands separators (`150,000` $\to$ `150000`).
3. Correctly resolving signs (`(۲,۵۰۰-)` $\to$ `-2500.0`).
4. Separating value change from percentage change when combined in a single cell (`+۲,۵۰۰ (%۰.۲۸)` $\to$ `change: 2500.0`, `percent: 0.28`).
5. Standardizing unit conversions: `Rate::getPriceInTomans()` seamlessly converts Rials to Tomans.

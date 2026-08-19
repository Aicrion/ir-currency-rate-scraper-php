# CLI Console Tool Guide

The package includes a standalone command-line executable at `bin/currency` for retrieving rates, converting currencies, running warmup routines, and viewing terminal tables without writing any custom PHP scripts.

---

## 💻 Available Commands

### 1. View Rate for Any Currency, Gold, or Crypto
```bash
# Get US Dollar rate
php bin/currency rate USD

# Get 18K Gold rate
php bin/currency rate geram18

# Get Emami Coin rate
php bin/currency rate sekee

# Get Bitcoin rate
php bin/currency rate BTC
```

---

### 2. View Market Rates Table
```bash
# Display free currency table
php bin/currency table free_currency

# Display gold market table
php bin/currency table gold

# Display coin market table
php bin/currency table coin

# Display cryptocurrency market table
php bin/currency table crypto
```

---

### 3. Currency Conversion in Terminal
```bash
# Convert 100 USD to Tomans
php bin/currency convert 100 USD TOMAN

# Convert 50,000,000 Rials to Euros
php bin/currency convert 50000000 IRR EUR

# Convert 0.25 Bitcoin to Tomans
php bin/currency convert 0.25 BTC TOMAN
```

---

### 4. Gold Jewelry Value Breakdown
```bash
# Calculate 5 grams of 18K gold with 0% wage
php bin/currency gold 5 18

# Calculate 3.2 grams of 18K gold with 12% craftsmanship wage
php bin/currency gold 3.2 18 12
```

---

### 5. Global Search Across Markets
```bash
php bin/currency search دلار
php bin/currency search سکه
php bin/currency search Ethereum
```

---

### 6. Cache Warmup in Background
```bash
# Warm up and cache all markets
php bin/currency warmup
```

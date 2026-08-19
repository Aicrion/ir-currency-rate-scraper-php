# PHP 7.4 Compatibility & Modern PHP Matrix

When developing packages intended to run seamlessly from PHP 7.4 through PHP 8.4+:

## Supported Features in PHP 7.4 (Use Freely)

- **Typed Properties**:
  ```php
  public string $name;
  protected ?int $age = null;
  private array $items = [];
  ```
- **Arrow Functions**:
  ```php
  $names = array_map(fn(Item $item): string => $item->getName(), $items);
  ```
- **Null Coalescing Assignment**:
  ```php
  $this->cache[$key] ??= $this->fetch($key);
  ```
- **Array Spread Operator**:
  ```php
  $merged = [...$defaults, ...$customOptions];
  ```
- **Return Type Declarations & Covariant/Contravariant Types**:
  ```php
  public function getRate(): ?ExchangeRate
  public function count(): int
  public function execute(): void
  ```

---

## Prohibited PHP 8.x Syntax (Do NOT use directly in code)

| Feature (PHP 8.0+) | PHP 7.4 Compatible Alternative |
|---|---|
| Constructor Property Promotion (`__construct(public string $x)`) | Traditional assignment: `public function __construct(string $x) { $this->x = $x; }` |
| `match` expression | Traditional `switch` statement or lookup dictionary/array `const MAP = [...]` |
| Native Union Types (`int\|string`) | Use PHPDoc annotations: `/** @param int\|string $id */` |
| Native Enums (`enum Currency: string`) | Abstract class with public constants or Value Objects with validation |
| Named Arguments (`fetch(url: $u, timeout: 5)`) | Standard positional arguments or an Options/Config DTO |
| Nullsafe Operator (`$obj?->method()`) | Explicit null checks: `$obj !== null ? $obj->method() : null` |
| `readonly` class / properties | Private properties with getter methods and no setters |
| Attributes (`#[Attribute]`) | PHPDoc annotations (`@param`, `@return`, `@throws`, `@deprecated`) |

---

## Strict Typing Standard

Always include strict types at the very top of every PHP file:
```php
<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper;
```

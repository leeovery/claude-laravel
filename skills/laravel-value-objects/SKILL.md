---
name: laravel-value-objects
description: Immutable value objects for domain values. Use when working with domain values, immutable objects, or when user mentions value objects, immutable values, domain values, money objects, coordinate objects.
---

# Laravel Value Objects

Immutable value objects encapsulate domain values with validation and behavior.

## Core Concept

**[value-objects.md](references/value-objects.md)** - Value object patterns:
- Immutable domain values
- Static factory methods
- Domain logic encapsulation
- When to use vs DTOs
- Comparison and equality

## Pattern

```php
final readonly class Money
{
    private function __construct(
        public int $amount,
        public string $currency,
    ) {}

    public static function fromCents(int $cents, string $currency = 'USD'): self
    {
        return new self($cents, $currency);
    }

    public static function fromDollars(float $dollars, string $currency = 'USD'): self
    {
        return new self((int) ($dollars * 100), $currency);
    }

    public function add(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Currency mismatch');
        }

        return new self($this->amount + $other->amount, $this->currency);
    }
}
```

Value objects vs DTOs: Value objects have domain behavior and validation, DTOs are for data transfer.

---
name: laravel-enums
description: Backed enums with labels and business logic. Use when working with enums, status values, fixed sets of options, or when user mentions enums, backed enums, enum cases, status enums.
---

# Laravel Enums

Backed enums with labels, attributes, and business logic.

## Core Concept

**[enums.md](references/enums.md)** - Enum patterns:
- Backed enums (string or int)
- Label methods for display
- Attributes for metadata
- Business logic methods
- When to use enums vs state machines
- Enum casts in models

## Pattern

```php
enum OrderStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending Payment',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match($this) {
            self::Draft => in_array($status, [self::Pending, self::Cancelled]),
            self::Pending => in_array($status, [self::Processing, self::Cancelled]),
            self::Processing => in_array($status, [self::Completed, self::Cancelled]),
            default => false,
        };
    }
}
```

Use enums for simple state. For complex state transitions, use Spatie Model States.

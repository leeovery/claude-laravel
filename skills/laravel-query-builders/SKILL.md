---
name: laravel-query-builders
description: Custom query builders for type-safe, composable database queries. Use when working with database queries, query scoping, or when user mentions query builders, custom query builder, query objects, query scopes, database queries.
---

# Laravel Query Builders

Custom query builders: type-safe, composable alternative to scopes.

## Core Concept

**[query-builders.md](references/query-builders.md)** - Custom builder patterns:
- Why custom builders over scopes
- Type-safe nested queries
- Builder traits for reusability
- Composable query methods
- Integration with models

## Pattern

```php
final class OrderQueryBuilder extends Builder
{
    public function forUser(User $user): self
    {
        return $this->where('user_id', $user->id);
    }

    public function pending(): self
    {
        return $this->where('status', OrderStatus::Pending);
    }

    public function placedAfter(Carbon $date): self
    {
        return $this->where('placed_at', '>', $date);
    }
}

// Usage with full type safety
Order::query()
    ->forUser($user)
    ->pending()
    ->placedAfter(now()->subDays(7))
    ->get();
```

Custom builders provide type hints, better IDE support, and cleaner composition than scopes.

---
name: laravel-models
description: Eloquent model patterns and database layer. Use when working with models, database entities, Eloquent ORM, or when user mentions models, eloquent, relationships, casts, observers, database entities.
---

# Laravel Models

Eloquent model structure and database integration patterns.

## Core Concept

**[models.md](references/models.md)** - Complete model guide:
- Model structure and organization
- Relationships (hasMany, belongsTo, etc.)
- Attribute casts (including enum casts)
- Observers for lifecycle events
- Custom query builder integration
- Guarded vs fillable (prefer guarded)
- Soft deletes
- Model factories

## Pattern

```php
final class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'placed_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function newEloquentBuilder($query): OrderQueryBuilder
    {
        return new OrderQueryBuilder($query);
    }
}
```

Models handle database structure, not business logic. Business logic lives in actions.

---
name: laravel-policies
description: Authorization policies for resource access control. Use when working with authorization, permissions, access control, or when user mentions policies, authorization, permissions, can, ability checks.
---

# Laravel Policies

Authorization policies define resource access rules.

## Core Concept

**[policies.md](references/policies.md)** - Policy patterns:
- Policy structure and methods
- Permission enums
- Standard authorization methods
- Ownership checks
- State-based authorization
- Integration with routes (`->can()`)

## Pattern

```php
final readonly class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id
            || $user->hasPermission(Permission::ViewAllOrders);
    }

    public function update(User $user, Order $order): bool
    {
        return $user->id === $order->user_id
            && $order->status === OrderStatus::Draft;
    }
}
```

Use on routes: `Route::get('/orders/{order}', ...)->can('view', 'order')`

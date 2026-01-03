---
name: laravel-actions
description: Action-oriented architecture for Laravel. Invokable classes that contain domain logic. Use when working with business logic, domain operations, or when user mentions actions, invokable classes, or needs to organize domain logic outside controllers.
---

# Laravel Actions

Action-oriented architecture: domain logic lives in invokable classes, not controllers.

## Core Concepts

**[actions.md](references/actions.md)** - Complete action pattern guide:
- Invokable classes with `__invoke()` method
- Guard methods for validation
- Context storage patterns
- Composition techniques
- When to create actions

## Pattern

```php
final readonly class CreateOrderAction
{
    public function __invoke(CreateOrderDto $dto): Order
    {
        $this->guard($dto);

        // Domain logic here
    }

    private function guard(CreateOrderDto $dto): void
    {
        // Validation logic
    }
}
```

Controllers delegate to actions. Actions contain all business logic.

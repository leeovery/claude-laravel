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

**[philosophy.md](references/philosophy.md)** - Foundational principles:
- Actions contain domain logic
- DTOs for data transfer
- Strict typing
- Thin HTTP layer
- Composability

## Pattern

```php
final readonly class CreateOrderAction
{
    public function __invoke(CreateOrderDto $dto): Order
    {
        $this->guardInventoryAvailable($dto);

        // Domain logic here
    }

    private function guardInventoryAvailable(CreateOrderDto $dto): void
    {
        // Validation logic
    }
}
```

Controllers delegate to actions. Actions contain all business logic.

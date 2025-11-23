---
name: laravel-controllers
description: Thin HTTP layer controllers. Controllers contain zero domain logic, only HTTP concerns. Use when working with controllers, HTTP layer, web vs API patterns, or when user mentions controllers, routes, HTTP responses.
---

# Laravel Controllers

Thin HTTP layer: controllers contain zero domain logic, only HTTP concerns.

## Core Concept

**[controllers.md](references/controllers.md)** - Complete controller guide:
- Zero domain logic in controllers
- Web layer vs public API distinction
- Invokable controller pattern
- Query objects for filtering/sorting
- Proper delegation to actions
- Response patterns

## Pattern

```php
final readonly class CreateOrderController
{
    public function __invoke(
        CreateOrderRequest $request,
        CreateOrderAction $action,
    ): RedirectResponse {
        $order = $action($request->toDto());

        return redirect()->route('orders.show', $order);
    }
}
```

Controller responsibilities:
- HTTP request/response transformation
- Routing to appropriate action
- Authentication/authorization checks (via middleware/policies)
- Nothing else

Domain logic lives in actions.

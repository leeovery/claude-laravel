---
name: laravel-testing
description: Comprehensive testing patterns with Pest. Use when working with tests, testing patterns, or when user mentions testing, tests, Pest, PHPUnit, mocking, factories, test patterns.
---

# Laravel Testing

Testing patterns with Pest: Arrange-Act-Assert, proper mocking, null drivers.

## Core Concepts

**[testing.md](references/testing.md)** - Core testing guide:
- Triple-A pattern (Arrange, Act, Assert)
- Testing actions in isolation
- Only mock what you own
- Using factories for test data
- Null driver pattern
- Avoiding brittle tests
- Feature vs unit tests

**[testing-conventions.md](references/testing-conventions.md)** - Testing conventions:
- Test structure and organization
- Naming conventions
- Dataset usage
- Common patterns

## Pattern

```php
it('creates order successfully', function () {
    // Arrange
    $user = User::factory()->create();
    $dto = CreateOrderDto::from([
        'user_id' => $user->id,
        'items' => [
            ['product_id' => 1, 'quantity' => 2],
        ],
    ]);

    // Act
    $order = app(CreateOrderAction::class)($dto);

    // Assert
    expect($order)
        ->user_id->toBe($user->id)
        ->items->toHaveCount(1);

    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
    ]);
});
```

Key principles:
- Test actions in isolation (unit tests)
- Test controllers for HTTP integration (feature tests)
- Use null drivers to avoid external API calls
- Use factories for all test data
- Only mock external dependencies, not your own code

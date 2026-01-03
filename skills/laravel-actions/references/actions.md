# Action Pattern - Complete Guide

Actions are the **heart of your domain logic**. Every business operation lives in an action.

**Related guides:**
- [DTOs](../../laravel-dtos/SKILL.md) - DTOs for passing data to actions
- [dto-factories.md](../../laravel-dtos/references/dto-factories.md) - Factory-created DTOs consumed by actions
- [controllers.md](../../laravel-controllers/references/controllers.md) - Controllers delegate to actions
- [models.md](../../laravel-models/references/models.md) - Models accessed by actions
- [testing.md](../../laravel-testing/references/testing.md) - Comprehensive testing guide following triple-A pattern, mocking only what you own
- [testing-conventions.md](../../laravel-testing/references/testing-conventions.md) - Test structure and ordering
- [multi-tenancy.md](../../laravel-multi-tenancy/references/multi-tenancy.md) - Central vs Tenanted action organization
- [package-extraction.md](../../laravel-packages/references/package-extraction.md) - Creating action base classes for packages

## Philosophy

**Controllers, Jobs, and Listeners contain ZERO domain logic** - they only delegate to actions.

Actions are:
- **Invokable classes** - Single `__invoke()` method
- **Single responsibility** - Each action does exactly one thing
- **Composable** - Actions call other actions to build workflows
- **Stateless** - Each invocation is independent (but can store invocation context)
- **Type-safe** - Strict parameter and return types
- **Transactional** - Wrap database modifications in transactions

## Basic Structure

```php
<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Data\CreateOrderData;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateOrderAction
{
    public function __invoke(User $user, CreateOrderData $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $order = $this->createOrder($user, $data);
            $this->attachOrderItems($order, $data);

            return $order->fresh(['items']);
        });
    }

    private function createOrder(User $user, CreateOrderData $data): Order
    {
        return $user->orders()->create([
            'status' => $data->status,
            'notes' => $data->notes,
        ]);
    }

    private function attachOrderItems(Order $order, CreateOrderData $data): void
    {
        $order->items()->createMany(
            $data->items->map(fn ($item) => [
                'product_id' => $item->productId,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ])->all()
        );
    }
}
```

## Key Patterns

### 1. Dependency Injection for Action Composition

**Inject other actions** to build complex workflows:

```php
class CreateOrderAction
{
    public function __construct(
        private readonly CalculateOrderTotalAction $calculateTotal,
        private readonly NotifyOrderCreatedAction $notifyOrderCreated,
    ) {}

    public function __invoke(User $user, CreateOrderData $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $order = $this->createOrder($user, $data);

            // Compose with other actions
            $total = ($this->calculateTotal)($order);
            $order->update(['total' => $total]);

            ($this->notifyOrderCreated)($order);

            return $order->fresh();
        });
    }
}
```

**Why this works:**
- Each action is independently testable
- Clear separation of concerns
- Easy to modify workflow by swapping actions

### 2. Guard Methods for Validation

**Validate business rules** before executing:

```php
class CancelOrderAction
{
    public function __invoke(Order $order): Order
    {
        $this->guard($order);

        return DB::transaction(function () use ($order) {
            $order->updateToCancelled();
            $this->refundPayment($order);
            return $order;
        });
    }

    private function guard(Order $order): void
    {
        throw_unless(
            $order->canBeCancelled(),
            OrderException::cannotCancelOrder($order)
        );
    }

    private function refundPayment(Order $order): void
    {
        // Refund logic
    }
}
```

### 3. Private Helper Methods

**Break complex operations** into smaller, focused private methods:

```php
public function __invoke(User $user, CreateApplicationData $data): Application
{
    return DB::transaction(function () use ($user, $data) {
        $application = $this->createApplication($user, $data);
        $this->createContacts($application, $data);
        $this->createAddresses($application, $data);
        $this->createDocuments($application, $data);

        return $application;
    });
}

private function createApplication(User $user, CreateApplicationData $data): Application
{
    return $user->applications()->create([
        'type' => $data->type,
        'status' => ApplicationStatus::Draft,
    ]);
}

private function createContacts(Application $application, CreateApplicationData $data): void
{
    $application->contacts()->createMany(
        $data->contacts->map->toArray()->all()
    );
}
```

**Benefits:**
- Each method has single responsibility
- Easy to test individual steps
- Clear workflow in `__invoke()`
- Readable and maintainable

### 4. Readonly Properties for Context

**Store invocation context** in readonly properties to avoid parameter passing:

```php
class ProcessOrderAction
{
    private readonly Order $order;

    public function __invoke(Order $order): void
    {
        $this->order = $order;
        $this->guard();

        DB::transaction(function (): void {
            $this->processPayment();
            $this->updateInventory();
            $this->sendNotifications();
        });
    }

    private function guard(): void
    {
        throw_unless($this->order->isPending(), 'Order must be pending');
    }

    private function processPayment(): void
    {
        // Access $this->order without passing it
    }

    private function updateInventory(): void
    {
        // Access $this->order without passing it
    }
}
```

**When to use:**
- Many private methods need same context
- Reduces parameter repetition
- Makes private methods cleaner

## Naming Conventions

**Format:** `{Verb}{Entity}Action`

**Examples:**
- `CreateOrderAction`
- `UpdateUserProfileAction`
- `DeleteDocumentAction`
- `CalculateOrderTotalAction`
- `SendEmailNotificationAction`
- `ProcessPaymentAction`
- `FetchProductsAction`
- `ArchiveExpiredInvoicesAction`

## When to Create an Action

### ✅ Create an action when:

- **Any** domain operation (including simple CRUD)
- Implementing business logic of any complexity
- Building reusable operations used across multiple places
- Composing multiple steps into a workflow
- Job or listener needs to perform domain logic
- **Any operation that touches your models or data**

### ❌ Don't create an action for:

- Pure data retrieval for display (use queries/query builders)
- HTTP-specific concerns (belongs in middleware/controllers)
- Formatting/presentation logic (use resources/transformers)

**Critical Rule:** Controllers should contain **zero domain logic**. Even a simple `$user->update($data)` should be delegated to `UpdateUserAction`.

## Invocation Patterns

### Via Dependency Injection

```php
public function store(
    CreateOrderRequest $request,
    CreateOrderAction $action
) {
    $order = $action(user(), CreateOrderData::from($request));
    return OrderResource::make($order);
}
```

### Via `resolve()` Helper

```php
// In controllers
public function store(CreateOrderRequest $request)
{
    $order = resolve(CreateOrderAction::class)(
        user(),
        CreateOrderData::from($request)
    );

    return OrderResource::make($order);
}

// Inside another action
public function __invoke(Order $order)
{
    $result = resolve(ProcessPaymentAction::class)($order, $paymentData);
    return $result;
}
```

**Important:** Use `resolve()` not `app()` for consistency.

## Database Transactions

**Always wrap data modifications** in transactions:

```php
public function __invoke(CreateOrderData $data): Order
{
    return DB::transaction(function () use ($data) {
        $order = Order::create($data->toArray());
        $order->items()->createMany($data->items->toArray());

        return $order;
    });
}
```

**When to use transactions:**
- Creating multiple related records
- Updating multiple tables
- Any operation that must be atomic
- Operations that might fail partway through

## Error Handling

**Throw domain exceptions** for business rule violations:

```php
class CreateOrderAction
{
    public function __invoke(User $user, CreateOrderData $data): Order
    {
        if ($user->orders()->pending()->count() >= 5) {
            throw OrderException::tooManyPendingOrders($user);
        }

        return DB::transaction(function () use ($user, $data) {
            return $user->orders()->create($data->toArray());
        });
    }
}
```

**Exception location:** `app/Exceptions/OrderException.php`

## Testing Actions

**Unit tests** should test actions in isolation using the triple-A pattern (Arrange, Act, Assert):

```php
use function Pest\Laravel\assertDatabaseHas;

it('creates an order', function () {
    // Arrange - Set up the world with factories
    $user = User::factory()->create();
    $data = CreateOrderData::testFactory()->make();

    // Act - Perform the operation
    $order = resolve(CreateOrderAction::class)($user, $data);

    // Assert - Verify the results
    expect($order)->toBeInstanceOf(Order::class);
    assertDatabaseHas('orders', ['id' => $order->id]);
});

it('throws exception when user has too many pending orders', function () {
    // Arrange
    $user = User::factory()
        ->has(Order::factory()->pending()->count(5))
        ->create();

    $data = CreateOrderData::testFactory()->make();

    // Act & Assert
    expect(fn () => resolve(CreateOrderAction::class)($user, $data))
        ->toThrow(OrderException::class);
});
```

**For comprehensive testing guidance**, including:
- How to properly use the triple-A pattern
- Testing action composition with mocking
- Only mocking what you own (never mock external dependencies)
- Using factories for realistic test data
- Avoiding brittle tests

**See [testing.md](../../laravel-testing/references/testing.md) for the complete testing guide.**

## Common Patterns

### Simple CRUD Action

```php
class UpdateUserAction
{
    public function __invoke(User $user, UpdateUserData $data): User
    {
        $user->update($data->toArray());
        return $user->fresh();
    }
}
```

### Multi-Step Workflow

```php
class OnboardUserAction
{
    public function __construct(
        private readonly CreateUserProfileAction $createProfile,
        private readonly SendWelcomeEmailAction $sendWelcome,
        private readonly AssignDefaultRoleAction $assignRole,
    ) {}

    public function __invoke(RegisterUserData $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create($data->toArray());

            ($this->createProfile)($user, $data->profileData);
            ($this->assignRole)($user);
            ($this->sendWelcome)($user);

            return $user;
        });
    }
}
```

### External Service Integration

```php
class ProcessPaymentAction
{
    public function __construct(
        private readonly StripeService $stripe,
    ) {}

    public function __invoke(Order $order, PaymentData $data): Payment
    {
        $this->guard($order);

        return DB::transaction(function () use ($order, $data) {
            $stripePayment = $this->stripe->charge($data);

            $payment = $order->payments()->create([
                'amount' => $data->amount,
                'stripe_id' => $stripePayment->id,
                'status' => PaymentStatus::Completed,
            ]);

            $order->markAsPaid();

            return $payment;
        });
    }

    private function guard(Order $order): void
    {
        throw_if($order->isPaid(), 'Order already paid');
    }
}
```

## Action Organization

**Group by domain entity:**

```
app/Actions/
├── Order/
│   ├── CreateOrderAction.php
│   ├── CancelOrderAction.php
│   ├── ProcessOrderAction.php
│   └── CalculateOrderTotalAction.php
├── User/
│   ├── CreateUserAction.php
│   ├── UpdateUserProfileAction.php
│   └── DeleteUserAction.php
└── Payment/
    ├── ProcessPaymentAction.php
    └── RefundPaymentAction.php
```

**Not by action type** (avoid CreateActions/, UpdateActions/, etc.)

## Multi-Tenancy

**Separate Central and Tenanted actions:**

```
app/Actions/
├── Central/
│   ├── CreateTenantAction.php
│   └── ProvisionDatabaseAction.php
└── Tenanted/
    ├── CreateOrderAction.php
    └── UpdateUserAction.php
```

**See [multi-tenancy.md](../../laravel-multi-tenancy/references/multi-tenancy.md) for comprehensive multi-tenancy patterns**, including tenant context helpers, route configuration, database isolation, and queue integration.

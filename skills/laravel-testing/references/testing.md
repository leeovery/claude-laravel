# Testing Guide

Comprehensive guide to testing Laravel applications following the action-oriented architecture with proper isolation, mocking, and factory usage.

**Related guides:**
- [testing-conventions.md](testing-conventions.md) - Test file structure and RESTful ordering
- [validation-testing.md](../../laravel-validation/references/validation-testing.md) - Form request validation testing with datasets
- [actions.md](../../laravel-actions/references/actions.md) - Action pattern and structure
- [controllers.md](../../laravel-controllers/references/controllers.md) - Controller patterns for feature testing HTTP layer
- [dto-factories.md](../../laravel-dtos/references/dto-factories.md) - Testing factory transformation logic
- [services.md](../../laravel-services/references/services.md) - Service layer with null drivers for testing
- [quality.md](../../laravel-quality/references/quality.md) - Architecture tests and quality enforcement
- [DTOs](../../laravel-dtos/SKILL.md) - DTO test factories

## Philosophy

Testing should be:
- **Isolated** - Test one thing at a time
- **Reliable** - Consistent results every time
- **Maintainable** - Easy to update when code changes
- **Fast** - Quick feedback loop
- **Realistic** - Use factories, not hardcoded values

## The Triple-A Pattern

Every test should follow the **Arrange-Act-Assert** pattern:

### 1. Arrange the World

Set up all the data and dependencies needed for your test using **factories**:

```php
it('creates an order with items', function () {
    // Arrange: Create the world state
    $user = User::factory()->create();
    $product = Product::factory()->active()->create(['price' => 1000]);

    $data = CreateOrderData::from([
        'customer_email' => 'customer@example.com',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ]);

    // Act: Perform the operation
    $order = resolve(CreateOrderAction::class)($user, $data);

    // Assert: Verify the results
    expect($order)
        ->toBeInstanceOf(Order::class)
        ->and($order->items)->toHaveCount(1)
        ->and($order->total)->toBe(2000);
});
```

### 2. Act on the World

Perform the **single operation** you're testing:

```php
// ✅ Good - Single, clear action
$order = resolve(CreateOrderAction::class)($user, $data);

// ❌ Bad - Multiple actions mixed with assertions
$order = resolve(CreateOrderAction::class)($user, $data);
expect($order)->toBeInstanceOf(Order::class);
$order->refresh();
expect($order->total)->toBe(2000);
```

### 3. Assert on the Results

Verify the **outcomes** of your action:

```php
// ✅ Good - Clear, focused assertions
expect($order)
    ->toBeInstanceOf(Order::class)
    ->and($order->status)->toBe(OrderStatus::Pending)
    ->and($order->items)->toHaveCount(2);

assertDatabaseHas('orders', [
    'id' => $order->id,
    'user_id' => $user->id,
]);

// ❌ Bad - Testing implementation details
expect($order->getAttribute('status'))->toBe('pending');
```

## Testing Actions

Actions are the **heart of your domain logic** and should be thoroughly tested in isolation.

### Basic Action Test

```php
use App\Actions\Order\CreateOrderAction;
use App\Data\CreateOrderData;
use App\Enums\OrderStatus;
use App\Models\User;
use function Pest\Laravel\assertDatabaseHas;

it('creates an order', function () {
    // Arrange
    $user = User::factory()->create();
    $data = CreateOrderData::testFactory()->make([
        'status' => OrderStatus::Pending,
    ]);

    // Act
    $order = resolve(CreateOrderAction::class)($user, $data);

    // Assert
    expect($order)->toBeInstanceOf(Order::class);
    assertDatabaseHas('orders', [
        'id' => $order->id,
        'user_id' => $user->id,
        'status' => OrderStatus::Pending->value,
    ]);
});
```

### Testing Action Guard Methods

```php
it('throws exception when user has too many pending orders', function () {
    // Arrange
    $user = User::factory()
        ->has(Order::factory()->pending()->count(5))
        ->create();

    $data = CreateOrderData::testFactory()->make();

    // Act & Assert
    expect(fn () => resolve(CreateOrderAction::class)($user, $data))
        ->toThrow(OrderException::class, 'Too many pending orders');
});
```

### Testing Action Composition

When actions depend on other actions, **mock only the actions you own**.

**Critical pattern:** Always resolve actions from the container using `resolve()` so dependencies are recursively resolved. Use `swap()` to replace dependencies in the container with mocked versions.

```php
use App\Actions\Order\CalculateOrderTotalAction;
use App\Actions\Order\NotifyOrderCreatedAction;
use App\Actions\Order\ProcessOrderAction;
use App\Models\Order;
use App\Models\User;
use function Pest\Laravel\mock;
use function Pest\Laravel\swap;

it('processes order and sends notification', function () {
    // Arrange
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    // Mock the dependency actions and swap them into the container
    $calculateTotal = mock(CalculateOrderTotalAction::class);
    $calculateTotal->shouldReceive('__invoke')
        ->once()
        ->with($order)
        ->andReturn(10000);
    swap(CalculateOrderTotalAction::class, $calculateTotal);

    $notifyOrder = mock(NotifyOrderCreatedAction::class);
    $notifyOrder->shouldReceive('__invoke')
        ->once()
        ->with($order);
    swap(NotifyOrderCreatedAction::class, $notifyOrder);

    // Act - resolve() from container so mocked dependencies are injected
    $result = resolve(ProcessOrderAction::class)($order);

    // Assert
    expect($result->total)->toBe(10000);
});
```

**Why this pattern:**
- `resolve()` ensures the action is pulled from the container with all dependencies
- `swap()` replaces the dependency in the container with your mock
- Container handles recursive dependency resolution automatically
- Avoids brittle tests where you manually inject dependencies
- If a dependency adds a new dependency, your tests don't break

## Mocking Guidelines

### Only Mock What You Own

**Critical principle:** Only mock code that you control. Never mock external services directly.

#### ✅ Good - Mock Your Own Actions

```php
use App\Actions\User\SendWelcomeEmailAction;
use function Pest\Laravel\mock;
use function Pest\Laravel\swap;

// Mock an action you own and swap it into the container
$sendEmail = mock(SendWelcomeEmailAction::class);
$sendEmail->shouldReceive('__invoke')
    ->once()
    ->with(Mockery::type(User::class));
swap(SendWelcomeEmailAction::class, $sendEmail);

// Then resolve the action under test - it will receive the mocked dependency
$result = resolve(RegisterUserAction::class)($data);
```

#### ✅ Advanced - Verify Mock Arguments with Assertions

Use `withArgs()` with a closure to verify the **exact instances and values** being passed:

```php
use App\Actions\Match\CreateMatchResultAction;
use App\Data\MatchData;
use App\Models\MatchAttempt;
use function Pest\Laravel\mock;
use function Pest\Laravel\swap;

it('processes match with correct arguments', function () {
    $matchAttempt = MatchAttempt::factory()->create();
    $data = MatchData::testFactory()->make();

    // Mock and verify exact arguments using expect() assertions
    $mockAction = mock(CreateMatchResultAction::class);
    $mockAction->shouldReceive('__invoke')
        ->once()
        ->withArgs(function (MatchAttempt $_matchAttempt, MatchData $_data) use ($data, $matchAttempt) {
            // Verify the exact model instance is passed
            expect($_matchAttempt->is($matchAttempt))->toBeTrue()
                // Verify the exact DTO value is passed
                ->and($_data)->toBe($data->matches->first());

            return true; // Return true to pass the assertion
        });
    swap(CreateMatchResultAction::class, $mockAction);

    // Act - resolve and invoke the action under test
    resolve(ProcessMatchAction::class)($matchAttempt, $data);
});
```

**Benefits of this pattern:**
- Verifies **exact model instances** are passed (not just type checking)
- Uses Pest's `expect()` assertions for clear, readable verification
- Ensures the action chain is executing correctly
- Catches bugs where wrong instances are passed

**When to use:**
- Testing action composition where exact instances matter
- Verifying model relationships are maintained through action chains
- Ensuring DTOs are passed correctly between actions

#### ✅ Good - Mock Your Own Services (via Facade)

```php
// Mock your own service through its facade
Payment::shouldReceive('createPaymentIntent')
    ->once()
    ->with(10000, 'usd')
    ->andReturn(PaymentIntentData::from([
        'id' => 'pi_test_123',
        'status' => 'succeeded',
    ]));
```

#### ❌ Bad - Mocking External Libraries Directly

```php
// ❌ DON'T DO THIS - Mocking Stripe SDK directly
$stripe = Mockery::mock(\Stripe\StripeClient::class);
$stripe->shouldReceive('paymentIntents->create')
    ->andReturn(/* ... */);

// This is brittle and breaks when Stripe updates their SDK
```

### When You Need to Mock Something You Don't Own

If you find yourself needing to mock an external service, **create an abstraction**:

1. **Create a Service Layer** with the Manager pattern
2. **Define a Driver Contract** (interface)
3. **Implement the Real Driver** (wraps external API)
4. **Create a Null Driver** for testing
5. **Add a Facade** for convenience

**See [services.md](../../laravel-services/references/services.md) for complete implementation examples.**

### Using Null Drivers

The null driver pattern provides **deterministic, fast tests** without external dependencies:

```php
it('processes payment successfully', function () {
    // Arrange - Use null driver (configured in phpunit.xml or .env.testing)
    Config::set('payment.default', 'null');

    $order = Order::factory()->create(['total' => 10000]);
    $data = PaymentData::from(['amount' => 10000, 'currency' => 'usd']);

    // Act - No mocking needed, null driver returns test data
    $payment = resolve(ProcessPaymentAction::class)($order, $data);

    // Assert
    expect($payment)
        ->toBeInstanceOf(Payment::class)
        ->and($payment->status)->toBe(PaymentStatus::Completed);
});
```

**Benefits of null drivers:**
- No mocking required
- Fast execution (no network calls)
- Deterministic results
- Can test error scenarios by extending null driver
- Matches real driver interface exactly

### Facade-Based Testing

When you create a facade for your service, you can easily swap implementations:

```php
use App\Services\Payment\Facades\Payment;

it('refunds payment when order is cancelled', function () {
    // Arrange
    Payment::shouldReceive('refundPayment')
        ->once()
        ->with('pi_123', null)
        ->andReturn(true);

    $order = Order::factory()->paid()->create([
        'payment_intent_id' => 'pi_123',
    ]);

    // Act
    resolve(CancelOrderAction::class)($order);

    // Assert
    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});
```

## Using Factories

Factories create **realistic, randomized test data** that makes tests more robust.

### Model Factories

```php
// Arrange with factories
$user = User::factory()->create();
$product = Product::factory()->active()->create();
$order = Order::factory()->for($user)->create();

// Factory with state
$pendingOrder = Order::factory()->pending()->create();
$paidOrder = Order::factory()->paid()->create();

// Factory with relationships
$user = User::factory()
    ->has(Order::factory()->count(3))
    ->create();
```

### DTO Test Factories

DTOs should provide **test factories** for consistent test data:

```php
// In your DTO class
use Spatie\LaravelData\Data;

class CreateOrderData extends Data
{
    public function __construct(
        public string $customerEmail,
        public OrderStatus $status,
        public array $items,
    ) {}

    public static function testFactory(): self
    {
        return new self(
            customerEmail: fake()->email(),
            status: OrderStatus::Pending,
            items: [
                [
                    'product_id' => Product::factory()->create()->id,
                    'quantity' => fake()->numberBetween(1, 5),
                ],
            ],
        );
    }
}

// Usage in tests
it('creates an order', function () {
    // Arrange - Use DTO test factory
    $user = User::factory()->create();
    $data = CreateOrderData::testFactory();

    // Act
    $order = resolve(CreateOrderAction::class)($user, $data);

    // Assert
    expect($order)->toBeInstanceOf(Order::class);
});
```

**See [DTOs](../../laravel-dtos/SKILL.md) for more on DTO test factories.**

### Declarative Factory Methods

**Critical principle:** Make tests **declarative and readable** by hiding database implementation details behind factory methods.

#### The Problem: Leaking Database Schema

When you expose database columns directly in tests, you create brittle, hard-to-read tests:

```php
// ❌ Bad - Database schema leaks into test
it('sends reminder for accepted calendars', function () {
    $calendar = Calendar::factory()->create([
        'status' => 'accepted',
        'reminder_sent_at' => null,
        'approved_by' => User::factory()->create()->id,
        'approved_at' => now(),
    ]);

    // Test logic...
});
```

**Problems with this approach:**
- Exposes database column names (`status`, `reminder_sent_at`, `approved_by`, `approved_at`)
- Not obvious what "accepted" means in business context
- Hard to read and understand intent
- Breaks when database schema changes
- Forces test authors to know exact column names and values

#### The Solution: Declarative Factory Methods

Create **named methods** on your factory that express business intent:

```php
// ✅ Good - Declarative and readable
it('sends reminder for accepted calendars', function () {
    $calendar = Calendar::factory()->accepted()->create();

    // Test logic...
});
```

**Benefits:**
- Reads like plain English
- Hides database implementation
- Single place to update when schema changes
- Expresses business domain, not database structure
- Self-documenting tests

#### Implementation: State Methods

Create methods for each meaningful state:

```php
// database/factories/CalendarFactory.php
class CalendarFactory extends Factory
{
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'approved_by' => User::factory(),
            'approved_at' => now(),
            'reminder_sent_at' => null,
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'declined',
            'declined_by' => User::factory(),
            'declined_at' => now(),
            'declined_reason' => fake()->sentence(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'declined_by' => null,
            'declined_at' => null,
        ]);
    }
}

// Usage in tests - perfectly readable
$acceptedCalendar = Calendar::factory()->accepted()->create();
$declinedCalendar = Calendar::factory()->declined()->create();
$pendingCalendar = Calendar::factory()->pending()->create();
```

#### Beyond States: Behavioral Methods

Factory methods aren't just for status columns - use them for **any meaningful business scenario**:

```php
class OrderFactory extends Factory
{
    // Order states
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Pending,
            'paid_at' => null,
            'shipped_at' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
            'payment_intent_id' => 'pi_' . uniqid(),
        ]);
    }

    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Shipped,
            'paid_at' => now()->subDays(2),
            'shipped_at' => now(),
            'tracking_number' => fake()->uuid(),
        ]);
    }

    // Behavioral scenarios
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Pending,
            'created_at' => now()->subDays(30),
            'due_date' => now()->subDays(5),
        ]);
    }

    public function withGiftMessage(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_gift' => true,
            'gift_message' => fake()->sentence(),
            'gift_wrap' => true,
        ]);
    }

    public function international(): static
    {
        return $this->state(fn (array $attributes) => [
            'shipping_country' => 'CA',
            'requires_customs' => true,
            'currency' => 'CAD',
        ]);
    }

    // Common scenarios with complex setup
    public function fullyProcessed(): static
    {
        return $this->paid()
            ->shipped()
            ->has(OrderItem::factory()->count(3));
    }
}
```

**Usage - tests read like business requirements:**

```php
it('marks overdue orders', function () {
    $order = Order::factory()->overdue()->create();
    // ...
});

it('adds gift wrap fee for gift orders', function () {
    $order = Order::factory()->withGiftMessage()->create();
    // ...
});

it('calculates customs fees for international orders', function () {
    $order = Order::factory()->international()->create();
    // ...
});
```

#### Complex Scenarios: Relationships and Setup

Factory methods can handle complex relationship setup:

```php
class UserFactory extends Factory
{
    public function withSubscription(): static
    {
        return $this->has(
            Subscription::factory()->active(),
            'subscription'
        );
    }

    public function withExpiredSubscription(): static
    {
        return $this->has(
            Subscription::factory()->expired(),
            'subscription'
        );
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->roles()->attach(Role::where('name', 'admin')->first());
            $user->permissions()->attach(Permission::all());
        });
    }

    public function withOrders(int $count = 3): static
    {
        return $this->has(
            Order::factory()->paid()->count($count),
            'orders'
        );
    }

    public function activeCustomer(): static
    {
        return $this->withSubscription()
            ->withOrders(5)
            ->state(fn (array $attributes) => [
                'last_login_at' => now(),
                'email_verified_at' => now(),
            ]);
    }
}

// Usage - complex setup in one readable line
$user = User::factory()->activeCustomer()->create();
$admin = User::factory()->admin()->create();
$expiredUser = User::factory()->withExpiredSubscription()->create();
```

#### Chainable Methods for Flexibility

Make methods chainable for maximum flexibility:

```php
// Combine multiple states
$order = Order::factory()
    ->paid()
    ->international()
    ->withGiftMessage()
    ->create();

// Start with one state, chain modifications
$calendar = Calendar::factory()
    ->accepted()
    ->create(['title' => 'Special Event']);
```

#### Real-World Example: Before and After

**❌ Before - Hard to read, brittle:**

```php
it('processes payment for order', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'subscription_status' => 'active',
        'subscription_expires_at' => now()->addMonth(),
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
        'total' => 10000,
        'currency' => 'usd',
        'payment_intent_id' => null,
        'paid_at' => null,
    ]);

    $order->items()->createMany([
        [
            'product_id' => Product::factory()->create(['active' => true, 'stock' => 10])->id,
            'quantity' => 2,
            'price' => 5000,
        ],
    ]);

    // Test logic...
});
```

**✅ After - Readable, maintainable:**

```php
it('processes payment for order', function () {
    $user = User::factory()->withActiveSubscription()->create();
    $order = Order::factory()->pending()->withItems(2)->for($user)->create();

    // Test logic...
});
```

#### Guidelines for Factory Methods

**✅ Do create methods for:**
- Common states (`pending()`, `active()`, `cancelled()`)
- Business scenarios (`overdue()`, `international()`, `premium()`)
- Complex setup (`withItems()`, `fullyProcessed()`, `activeCustomer()`)
- Testing edge cases (`expired()`, `invalid()`, `almostFull()`)

**❌ Don't create methods for:**
- One-off test scenarios (use inline array instead)
- Overly specific cases (`withExactly47Items()`)
- Things that should be parameters (`withItems(int $count)` not `with3Items()`)

#### Naming Conventions

**State methods:**
- Use adjectives: `active()`, `pending()`, `expired()`, `cancelled()`
- Past tense for completed states: `paid()`, `shipped()`, `verified()`

**Behavioral methods:**
- Use descriptive phrases: `withItems()`, `withSubscription()`, `asAdmin()`
- Boolean properties: `featured()`, `published()`, `archived()`

**Scenario methods:**
- Use business terms: `overdue()`, `international()`, `premium()`
- Combine states meaningfully: `activeCustomer()`, `fullyProcessed()`

## Avoiding Brittle Tests

Brittle tests break when implementation changes, even if behavior is correct.

### Signs of Brittle Tests

- Too many mocks
- Testing implementation details
- Hardcoded values everywhere
- Complex setup with many steps
- Tests break with refactoring

### How to Avoid Brittleness

#### 1. Use Real Instances When Possible

```php
// ✅ Good - Use real instances
it('calculates order total', function () {
    $order = Order::factory()->create();
    $order->items()->createMany([
        ['price' => 1000, 'quantity' => 2],
        ['price' => 500, 'quantity' => 1],
    ]);

    $total = resolve(CalculateOrderTotalAction::class)($order);

    expect($total)->toBe(2500);
});

// ❌ Bad - Mock everything
it('calculates order total', function () {
    $item1 = Mockery::mock(OrderItem::class);
    $item1->shouldReceive('getPrice')->andReturn(1000);
    $item1->shouldReceive('getQuantity')->andReturn(2);

    $item2 = Mockery::mock(OrderItem::class);
    $item2->shouldReceive('getPrice')->andReturn(500);
    $item2->shouldReceive('getQuantity')->andReturn(1);

    // ... too much mocking
});
```

#### 2. Test Behavior, Not Implementation

```php
// ✅ Good - Test the behavior
it('sends welcome email when user registers', function () {
    Mail::fake();

    $data = RegisterUserData::testFactory();
    $user = resolve(RegisterUserAction::class)($data);

    Mail::assertSent(WelcomeEmail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

// ❌ Bad - Test implementation details
it('sends welcome email when user registers', function () {
    $mailer = Mockery::mock(Mailer::class);
    $mailer->shouldReceive('send')
        ->once()
        ->with(Mockery::on(function ($email) {
            return $email instanceof WelcomeEmail
                && $email->to === 'test@example.com'
                && $email->subject === 'Welcome'
                && $email->template === 'emails.welcome';
        }));

    // Too specific, breaks if template name changes
});
```

#### 3. Use Factories Instead of Hardcoded Data

```php
// ✅ Good - Use factories
it('creates user profile', function () {
    $user = User::factory()->create();
    $data = ProfileData::testFactory();

    $profile = resolve(CreateProfileAction::class)($user, $data);

    expect($profile->user_id)->toBe($user->id);
});

// ❌ Bad - Hardcoded data
it('creates user profile', function () {
    $user = User::factory()->create();
    $data = new ProfileData(
        firstName: 'John',
        lastName: 'Doe',
        phone: '555-1234',
        bio: 'Test bio',
    );

    // Brittle - breaks if validation rules change
});
```

#### 4. Minimize Mocking

**Rule of thumb:** Mock collaborators, not data.

```php
use App\Actions\Order\ShipOrderAction;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderShippedNotification;
use App\Services\NotificationService;
use function Pest\Laravel\mock;
use function Pest\Laravel\swap;

// ✅ Good - Mock the notification service (collaborator)
it('notifies user when order ships', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    $notifier = mock(NotificationService::class);
    $notifier->shouldReceive('send')
        ->once()
        ->with(Mockery::type(User::class), Mockery::type(OrderShippedNotification::class));
    swap(NotificationService::class, $notifier);

    resolve(ShipOrderAction::class)($order);
});

// ❌ Bad - Mock the data (order, user)
it('notifies user when order ships', function () {
    $user = Mockery::mock(User::class);
    $order = Mockery::mock(Order::class);
    // ... mocking data objects makes test brittle
});
```

## Testing External Services

Never mock external services directly. Use the **service layer pattern** instead.

### Service Layer Structure

```
Services/
└── Payment/
    ├── PaymentManager.php          # Manager
    ├── Contracts/
    │   └── PaymentDriver.php       # Interface
    ├── Drivers/
    │   ├── StripeDriver.php        # Real implementation
    │   └── NullDriver.php          # For testing
    └── Facades/
        └── Payment.php             # Facade
```

### Testing with Null Driver

Configure test environment to use null driver:

```php
// phpunit.xml
<env name="PAYMENT_DRIVER" value="null"/>
```

```php
it('processes payment', function () {
    // Arrange - Null driver is automatically used in tests
    $order = Order::factory()->create(['total' => 10000]);
    $data = PaymentData::from(['amount' => 10000, 'currency' => 'usd']);

    // Act - No mocking, just use the service normally
    $payment = resolve(ProcessPaymentAction::class)($order, $data);

    // Assert
    expect($payment)
        ->toBeInstanceOf(Payment::class)
        ->and($payment->status)->toBe(PaymentStatus::Completed);
});
```

### Testing Error Scenarios

Extend the null driver for specific test scenarios:

```php
// tests/Fakes/FailingPaymentDriver.php
class FailingPaymentDriver implements PaymentDriver
{
    public function createPaymentIntent(int $amount, string $currency): PaymentIntentData
    {
        throw PaymentException::failedToCharge('Card declined');
    }
}

// In test
it('handles payment failure gracefully', function () {
    // Arrange
    $this->app->bind(PaymentManager::class, function () {
        $manager = new PaymentManager($this->app);
        $manager->extend('failing', fn () => new FailingPaymentDriver);
        return $manager;
    });

    Config::set('payment.default', 'failing');

    $order = Order::factory()->create();
    $data = PaymentData::testFactory();

    // Act & Assert
    expect(fn () => resolve(ProcessPaymentAction::class)($order, $data))
        ->toThrow(PaymentException::class, 'Card declined');
});
```

**See [services.md](../../laravel-services/references/services.md) for complete service layer implementation.**

## Testing Strategy

Different types of tests serve different purposes:

### Feature Tests (HTTP Layer)

Test the **complete request/response cycle**:

```php
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

it('creates an order via API', function () {
    // Arrange
    $user = User::factory()->create();
    $product = Product::factory()->create();

    // Act
    $response = actingAs($user)
        ->postJson('/api/orders', [
            'customer_email' => 'test@example.com',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

    // Assert
    $response->assertCreated()
        ->assertJsonStructure([
            'data' => ['id', 'status', 'items'],
        ]);
});
```

### Unit Tests (Actions)

Test **domain logic in isolation**:

```php
it('calculates order total correctly', function () {
    // Arrange
    $order = Order::factory()->create();
    $order->items()->createMany([
        ['price' => 1000, 'quantity' => 2],
        ['price' => 1500, 'quantity' => 1],
    ]);

    // Act
    $total = resolve(CalculateOrderTotalAction::class)($order);

    // Assert
    expect($total)->toBe(3500);
});
```

### Validation Tests

Test **form request validation** with datasets:

```php
test(
    'fails to create order with invalid data',
    function (RequestDataProviderItem $item): void {
        actingAs(User::factory()->create())
            ->postJson('/orders', $item->buildRequest())
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                $item->attribute => $item->error,
            ]);
    }
)->with('order create validation');
```

**See [validation-testing.md](../../laravel-validation/references/validation-testing.md) for comprehensive validation testing guide.**

### Integration Tests (External Services)

Test **service integration** with null drivers:

```php
it('integrates with payment service', function () {
    // Uses null driver automatically in test environment
    $order = Order::factory()->create();

    $payment = resolve(ProcessPaymentAction::class)($order, PaymentData::testFactory());

    expect($payment)->toBeInstanceOf(Payment::class);
});
```

## Common Testing Patterns

### Testing State Transitions

```php
it('transitions order from pending to paid', function () {
    // Arrange
    $order = Order::factory()->pending()->create();

    // Act
    resolve(MarkOrderAsPaidAction::class)($order);

    // Assert
    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->fresh()->paid_at)->not->toBeNull();
});
```

### Testing Relationships

```php
it('creates order with items', function () {
    // Arrange
    $user = User::factory()->create();
    $products = Product::factory()->count(3)->create();

    $data = CreateOrderData::from([
        'customer_email' => 'test@example.com',
        'items' => $products->map(fn ($p) => [
            'product_id' => $p->id,
            'quantity' => 2,
        ])->all(),
    ]);

    // Act
    $order = resolve(CreateOrderAction::class)($user, $data);

    // Assert
    expect($order->items)->toHaveCount(3);
});
```

### Testing Transactions

```php
it('rolls back transaction on failure', function () {
    // Arrange
    $user = User::factory()->create();

    // Create invalid data that will fail
    $data = CreateOrderData::from([
        'customer_email' => 'test@example.com',
        'items' => [
            ['product_id' => 99999, 'quantity' => 1], // Non-existent product
        ],
    ]);

    // Act & Assert
    expect(fn () => resolve(CreateOrderAction::class)($user, $data))
        ->toThrow(Exception::class);

    // Verify nothing was created
    assertDatabaseCount('orders', 0);
    assertDatabaseCount('order_items', 0);
});
```

### Testing Email/Notifications

```php
use Illuminate\Support\Facades\Mail;

it('sends welcome email to new user', function () {
    // Arrange
    Mail::fake();
    $data = RegisterUserData::testFactory();

    // Act
    $user = resolve(RegisterUserAction::class)($data);

    // Assert
    Mail::assertSent(WelcomeEmail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});
```

### Testing Jobs

```php
use Illuminate\Support\Facades\Queue;

it('dispatches job to process order', function () {
    // Arrange
    Queue::fake();
    $order = Order::factory()->create();

    // Act
    resolve(ProcessOrderAction::class)($order);

    // Assert
    Queue::assertPushed(ProcessOrderJob::class, function ($job) use ($order) {
        return $job->order->id === $order->id;
    });
});
```

## Best Practices Summary

### ✅ Do This

- **Follow triple-A pattern** - Arrange, Act, Assert
- **Use factories** for all test data
- **Create declarative factory methods** - Hide database schema behind readable methods (`Calendar::factory()->accepted()` not `['status' => 'accepted']`)
- **Test actions in isolation** - Unit test your domain logic
- **Mock what you own** - Actions, services you control
- **Create abstractions** when you need to mock external services
- **Use null drivers** for external service testing
- **Test behavior, not implementation**
- **Keep tests simple** - One concept per test
- **Use DTO test factories** for consistent data
- **Make tests readable** - Tests should read like business requirements

### ❌ Don't Do This

- **Mock external libraries** - Create service layer instead
- **Hardcode test data** - Use factories
- **Leak database schema into tests** - Use declarative factory methods
- **Test implementation details** - Test behavior
- **Create brittle tests** - Too many mocks, too specific
- **Skip factories** - Always use factories for models and DTOs
- **Mix arrange and act** - Keep them separate
- **Over-mock** - Use real instances when possible

## Quick Reference

### Test Structure

```php
it('does something', function () {
    // Arrange - Set up the world with declarative factories
    $model = Model::factory()->active()->create();
    $data = Data::testFactory();

    // Act - Perform the operation
    $result = resolve(Action::class)($model, $data);

    // Assert - Verify the results
    expect($result)->/* assertions */;
});
```

### Declarative Factory Pattern

```php
// In Factory
public function accepted(): static
{
    return $this->state(fn (array $attributes) => [
        'status' => 'accepted',
        'approved_by' => User::factory(),
        'approved_at' => now(),
    ]);
}

// In Test - Readable and maintainable
$calendar = Calendar::factory()->accepted()->create();
```

### Mocking Pattern

```php
use function Pest\Laravel\mock;
use function Pest\Laravel\swap;

// Mock a dependency action
$mockAction = mock(YourDependencyAction::class);
$mockAction->shouldReceive('__invoke')
    ->once()
    ->with(/* expected params */)
    ->andReturn(/* return value */);

// Swap the mock into the container
swap(YourDependencyAction::class, $mockAction);

// Resolve the action under test - container injects mocked dependencies
$result = resolve(ActionUnderTest::class)(/* params */);
```

**Critical:** Always use `resolve()` to get the action from the container, and `swap()` to replace dependencies. This ensures the container handles dependency injection recursively - if your dependency has dependencies, they're resolved automatically.

### Facade Mocking

```php
// Mock your service facade
YourService::shouldReceive('method')
    ->once()
    ->with(/* params */)
    ->andReturn(/* value */);
```

### Database Assertions

```php
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseCount;

assertDatabaseHas('orders', ['id' => $order->id]);
assertDatabaseCount('orders', 1);
```

---

## Related Testing Documentation

- **[validation-testing.md](../../laravel-validation/references/validation-testing.md)** - Comprehensive form request validation testing with RequestDataProviderItem and datasets
- **[quality.md](../../laravel-quality/references/quality.md)** - Architecture tests, PHPStan, Pint, CI/CD quality checks
- **[services.md](../../laravel-services/references/services.md)** - Service layer pattern with null drivers for testing external APIs
- **[actions.md](../../laravel-actions/references/actions.md)** - Action pattern structure and basic testing examples
- **[DTOs](../../laravel-dtos/SKILL.md)** - DTO structure and test factory implementation

---

**Remember:** The goal is to write tests that are **reliable, maintainable, and give you confidence** that your code works correctly. Follow these principles and your tests will remain valuable as your codebase evolves.

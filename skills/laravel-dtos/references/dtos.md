# Data Transfer Objects (DTOs) - Complete Guide

**Never pass multiple primitive values.** Always wrap data in Data objects.

**Related guides:**
- [actions.md](../../laravel-actions/references/actions.md) - Actions accept DTOs as parameters
- [controllers.md](../../laravel-controllers/references/controllers.md) - Controllers transform requests to DTOs
- [form-requests.md](../../laravel-validation/references/form-requests.md) - Validation before transformation
- [dto-transformers.md](dto-transformers.md) - **Transformers for external system data**
- [models.md](../../laravel-models/references/models.md) - Casting model attributes to DTOs
- [testing.md](../../laravel-testing/references/testing.md) - Using DTO test factories in tests, avoiding hardcoded data
- [package-extraction.md](../../laravel-packages/references/package-extraction.md) - Creating DTO base classes for packages

## Philosophy

DTOs provide:
- **Type safety** and IDE autocomplete
- **Clear contracts** between layers
- **Test factories** for easy test data generation
- **Validation** integration
- **Transformation** from requests to domain objects

## Spatie Laravel Data Package

This project uses [Spatie Laravel Data](https://spatie.be/docs/laravel-data). Refer to the official documentation for comprehensive package features. This guide covers project-specific patterns and preferences.

### Preferred: Use `::from()` with Arrays

**Always prefer the `::from()` static method** with an array where keys match constructor property names. Let the package handle casting based on property types.

```php
// ✅ PREFERRED - Let the package cast automatically
$data = CreateOrderData::from([
    'customerEmail' => $request->input('customer_email'),
    'deliveryDate' => $request->input('delivery_date'),  // String → CarbonImmutable
    'status' => $request->input('status'),               // String → OrderStatus enum
    'items' => $request->collect('items'),               // Array → Collection<OrderItemData>
]);

// ❌ AVOID - Manual casting in calling code
$data = new CreateOrderData(
    customerEmail: $request->input('customer_email'),
    deliveryDate: CarbonImmutable::parse($request->input('delivery_date')),
    status: OrderStatus::from($request->input('status')),
    items: OrderItemData::collect($request->input('items')),
);
```

**Why prefer `::from()`:**
- Package handles type casting automatically based on constructor property types
- Cleaner calling code without manual casting
- Consistent transformation behavior
- Leverages the full power of the package

**When `new` is acceptable:**
- In test factories where you control all values
- When values are already the correct type
- In formatters inside the DTO constructor

### Avoid: Case Mapper Attributes

**Do not use `#[MapInputName]` or case mapper attributes.** Instead, map field names explicitly in the array passed to `::from()`.

```php
// ❌ AVOID - Case mapper attributes on the class
#[MapInputName(SnakeCaseMapper::class)]
class CreateOrderData extends Data
{
    public function __construct(
        public string $customerEmail,    // Auto-maps from 'customer_email'
        public string $deliveryDate,
    ) {}
}

// ✅ PREFERRED - Explicit mapping in calling code
class OrderDataTransformer
{
    public static function fromRequest(CreateOrderRequest $request): CreateOrderData
    {
        return CreateOrderData::from([
            'customerEmail' => $request->input('customer_email'),
            'deliveryDate' => $request->input('delivery_date'),
        ]);
    }
}
```

**Why avoid case mappers:**
- Explicit mapping is clearer and more maintainable
- Different API versions may have different field names
- Transformers provide a single place to see all mappings
- Avoids magic behavior that's hard to trace

### Date Casting is Automatic

The package automatically casts date strings to `Carbon` or `CarbonImmutable` based on property types. **Configure the expected date format in the package config** rather than parsing manually.

```php
// config/data.php
return [
    'date_format' => 'Y-m-d H:i:s',  // Or ISO 8601: 'Y-m-d\TH:i:s.u\Z'
];
```

```php
class OrderData extends Data
{
    public function __construct(
        public CarbonImmutable $createdAt,   // Automatically cast from string
        public ?CarbonImmutable $shippedAt,  // Nullable dates work too
    ) {}
}

// ✅ Just pass the string - package handles casting
$data = OrderData::from([
    'createdAt' => '2024-01-15 10:30:00',
    'shippedAt' => null,
]);
```

### Complex Transformations: Use Transformers

For complex applications with intricate data transformations, create dedicated transformer classes with static factory methods. See **[dto-transformers.md](dto-transformers.md)** for complete patterns.

```php
// For external system data with complex mapping
$data = PaymentDataTransformer::fromStripePaymentIntent($webhook['data']);

// For request data with version-specific field names
$data = OrderDataTransformer::fromRequest($request);
```

**Hierarchy of preference:**
1. `Data::from($array)` - Simple cases, direct mapping
2. `Transformer::from*()` - Complex transformations with typed parameters

## Using Spatie Laravel Data

All DTOs extend `Spatie\LaravelData\Data` through a custom base class.

## Base Data Class

**[View full implementation →](./Data.php)**

**Location:** `app/Data/Data.php`

## Basic DTO Structure

```php
<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\OrderStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * @see \Database\Factories\Data\CreateOrderDataFactory
 */
class CreateOrderData extends Data
{
    public function __construct(
        public string $customerEmail,
        public ?string $notes,
        public ?CarbonImmutable $deliveryDate,
        public OrderStatus $status,
        /** @var Collection<int, OrderItemData> */
        public Collection $items,
        public ShippingAddressData $shippingAddress,
        public BillingAddressData $billingAddress,
    ) {
        // Apply formatters in constructor
        $this->customerEmail = EmailFormatter::format($this->customerEmail);
    }
}
```

## Key Patterns

### 1. Constructor Property Promotion

**Always use promoted properties:**

```php
public function __construct(
    public string $name,
    public ?string $description,
    public bool $active = true,
) {}
```

**Not:**
```php
public string $name;
public ?string $description;

public function __construct(string $name, ?string $description)
{
    $this->name = $name;
    $this->description = $description;
}
```

### 2. Type Everything

```php
public string $email;                          // Required string
public ?string $phone;                         // Nullable string
public CarbonImmutable $createdAt;             // DateTime (immutable)
public OrderStatus $status;                    // Enum
public Collection $items;                      // Collection
public AddressData $address;                   // Nested DTO
```

### 3. Collections with PHPDoc

```php
/** @var int[] */
public array $productIds;

/** @var Collection<int, OrderItemData> */
public Collection $items;
```

### 4. Nested Data Objects

```php
class OrderData extends Data
{
    public function __construct(
        public CustomerData $customer,
        public ShippingAddressData $shipping,
        public BillingAddressData $billing,
        /** @var Collection<int, OrderItemData> */
        public Collection $items,
    ) {}
}
```

### 5. Formatters

**Apply formatting in the constructor:**

```php
public function __construct(
    public string $email,
    public ?string $phone,
    public ?string $postcode,
) {
    $this->email = EmailFormatter::format($this->email);
    $this->phone = $this->phone ? PhoneFormatter::format($this->phone) : null;
    $this->postcode = $this->postcode ? PostcodeFormatter::format($this->postcode) : null;
}
```

**Example formatter:**

**[View full implementation →](./EmailFormatter.php)**

**Location:** `app/Data/Formatters/EmailFormatter.php`

### 6. Static Factory Methods on DTOs

For smaller applications or when starting out, add static `from*` methods directly on the DTO class. This provides factory-like behavior while leveraging the package's automatic type casting.

**Method naming:** `from{SourceType}` - e.g., `fromArray`, `fromRequest`, `fromModel`

```php
<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\CreateOrderRequest;
use App\Models\Order;

class OrderData extends Data
{
    public function __construct(
        public string $customerEmail,
        public ?string $notes,
        public OrderStatus $status,
        /** @var Collection<int, OrderItemData> */
        public Collection $items,
    ) {}

    public static function fromRequest(CreateOrderRequest $request): self
    {
        return self::from([
            'customerEmail' => $request->input('customer_email'),
            'notes' => $request->input('notes'),
            'status' => $request->input('status'),
            'items' => $request->input('items'),
        ]);
    }

    public static function fromModel(Order $order): self
    {
        return self::from([
            'customerEmail' => $order->customer_email,
            'notes' => $order->notes,
            'status' => $order->status,
            'items' => $order->items->toArray(),
        ]);
    }
}
```

**Usage:**

```php
// From request
$data = OrderData::fromRequest($request);

// From model
$data = OrderData::fromModel($order);

// Still works with ::from() for simple cases
$data = OrderData::from(['customerEmail' => 'test@example.com', ...]);
```

**When to use this pattern:**
- Smaller applications with fewer DTOs
- When starting out before complexity warrants separate transformers
- Simple transformations that don't need dedicated test coverage
- When the mapping is tightly coupled to a single DTO

**When to use separate transformers instead:**
- Multiple external sources map to the same DTO
- Complex transformation logic requiring extensive testing
- Shared transformation logic across multiple DTOs
- Larger applications with clear separation of concerns

### 7. Test Factories

**Link DTOs to test factories** for easy test data generation:

```php
/**
 * @see \Database\Factories\Data\CreateOrderDataFactory
 * @method static CreateOrderDataFactory testFactory()
 */
class CreateOrderData extends Data
{
    // ...
}
```

**Usage in tests:**

```php
$data = CreateOrderData::testFactory()->make();
$collection = OrderItemData::testFactory()->collect(count: 5);
```

**For comprehensive guidance on using DTO test factories in your tests**, see [testing.md](../../laravel-testing/references/testing.md) - includes how to avoid hardcoded test data and use factories for more reliable tests.

### 8. Model Casts

**Cast model JSON columns to DTOs:**

```php
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => OrderMetadataData::class,
            'status' => OrderStatus::class,
        ];
    }
}
```

**Usage:**

```php
// Store
$order = Order::create([
    'metadata' => $metadataData,  // OrderMetadataData instance
]);

// Retrieve
$metadata = $order->metadata;  // Returns OrderMetadataData instance
```

### 9. Transformers

**Transform external system data** into internal DTOs using dedicated transformer classes with static factory methods.

**[→ Complete guide: dto-transformers.md](dto-transformers.md)**

**Quick example:**

```php
<?php

declare(strict_types=1);

namespace App\Data\Transformers;

use App\Data\PaymentData;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;

class PaymentDataTransformer
{
    public static function fromStripePaymentIntent(array $paymentIntent): PaymentData
    {
        return PaymentData::from([
            'id' => data_get($paymentIntent, 'id'),
            'amount' => data_get($paymentIntent, 'amount'),
            'currency' => data_get($paymentIntent, 'currency'),
            'status' => match (data_get($paymentIntent, 'status')) {
                'succeeded' => PaymentStatus::Succeeded,
                'pending' => PaymentStatus::Pending,
                'failed' => PaymentStatus::Failed,
                default => PaymentStatus::Unknown,
            },
            'createdAt' => CarbonImmutable::createFromTimestamp(
                data_get($paymentIntent, 'created')
            ),
            'rawData' => $paymentIntent,  // Preserve original
        ]);
    }
}
```

**Use transformers when:**
- Integrating external systems (APIs, webhooks, message queues)
- Multiple data sources map to same DTO
- Complex field transformations with business logic
- Transformation logic needs dedicated testing

**See [dto-transformers.md](dto-transformers.md) for complete patterns, testing strategies, and real-world examples.**

## Data Transformers

**Transform Form Request data to DTOs.** Transformers are specific to each layer/version since request structures differ between Web, API v1, API v2, etc.

### Web Layer Transformer

```php
<?php

declare(strict_types=1);

namespace App\Data\Transformers\Web;

use App\Data\CreateOrderData;
use App\Http\Web\Requests\CreateOrderRequest;

class OrderDataTransformer
{
    public static function fromRequest(CreateOrderRequest $request): CreateOrderData
    {
        return CreateOrderData::from([
            'customerEmail' => $request->input('customer_email'),
            'notes' => $request->input('notes'),
            'status' => $request->input('status'),
            'items' => $request->input('items'),
            'shippingAddress' => $request->input('shipping'),
            'billingAddress' => $request->input('billing'),
        ]);
    }
}
```

### API v1 Transformer

```php
<?php

declare(strict_types=1);

namespace App\Data\Transformers\Api\V1;

use App\Data\CreateOrderData;
use App\Http\Api\V1\Requests\CreateOrderRequest;

class OrderDataTransformer
{
    public static function fromRequest(CreateOrderRequest $request): CreateOrderData
    {
        // API v1 has different field names - map them here
        return CreateOrderData::from([
            'customerEmail' => $request->input('email'),
            'notes' => $request->input('notes'),
            'status' => $request->input('order_status'),
            'items' => $request->input('line_items'),
            'shippingAddress' => $request->input('shipping_details'),
            'billingAddress' => $request->input('billing_details'),
        ]);
    }
}
```

**Key principle:** Each API version and web layer has its own transformer to handle different request structures.

## DTO Organization

### By Domain

```
app/Data/
├── CreateOrderData.php
├── UpdateOrderData.php
├── OrderData.php
├── CreateUserData.php
├── UpdateUserData.php
├── UserData.php
├── Concerns/
│   └── HasTestFactory.php
├── Formatters/
│   ├── EmailFormatter.php
│   ├── PhoneFormatter.php
│   └── PostcodeFormatter.php
└── Transformers/
    ├── PaymentDataTransformer.php
    ├── Web/
    │   ├── OrderDataTransformer.php
    │   └── UserDataTransformer.php
    └── Api/
        └── V1/
            ├── OrderDataTransformer.php
            └── UserDataTransformer.php
```

## Naming Conventions

**Response DTOs:** `{Entity}Data`
- `OrderData`
- `UserData`
- `ProductData`

**Request DTOs:** `{Action}{Entity}Data`
- `CreateOrderData`
- `UpdateUserData`
- `RegisterUserData`

**Nested/Specific DTOs:** `{Descriptor}{Entity}Data`
- `ShippingAddressData`
- `BillingAddressData`
- `OrderMetadataData`

## Common Patterns

### Simple DTO

```php
<?php

declare(strict_types=1);

namespace App\Data;

final readonly class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
    ) {}
}
```

### DTO with Defaults

```php
public function __construct(
    public string $name,
    public string $email,
    public bool $active = true,
    public ?string $phone = null,
) {}
```

### DTO with Nested DTOs

```php
public function __construct(
    public string $name,
    public AddressData $address,
    public ContactData $contact,
) {}
```

### DTO with Collections

```php
public function __construct(
    public string $name,
    /** @var Collection<int, OrderItemData> */
    public Collection $items,
) {}
```

## Usage in Controllers

**Controllers transform requests to DTOs:**

```php
<?php

declare(strict_types=1);

namespace App\Http\Web\Controllers;

use App\Actions\Order\CreateOrderAction;
use App\Data\Transformers\Web\OrderDataTransformer;
use App\Http\Web\Requests\CreateOrderRequest;
use App\Http\Web\Resources\OrderResource;

class OrderController extends Controller
{
    public function store(
        CreateOrderRequest $request,
        CreateOrderAction $action
    ): OrderResource {
        $order = $action(
            user(),
            OrderDataTransformer::fromRequest($request)
        );

        return OrderResource::make($order);
    }
}
```

## Usage in Actions

**Actions accept DTOs:**

```php
class CreateOrderAction
{
    public function __invoke(User $user, CreateOrderData $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            return $user->orders()->create([
                'customer_email' => $data->customerEmail,
                'notes' => $data->notes,
                'status' => $data->status,
            ]);
        });
    }
}
```

## Testing DTOs

```php
use App\Data\CreateOrderData;

it('can create DTO from array', function () {
    $data = CreateOrderData::from([
        'customerEmail' => 'test@example.com',
        'notes' => 'Test notes',
        'status' => 'pending',
    ]);

    expect($data)
        ->customerEmail->toBe('test@example.com')
        ->notes->toBe('Test notes');
});

it('formats email in constructor', function () {
    $data = new CreateOrderData(
        customerEmail: '  TEST@EXAMPLE.COM  ',
        notes: null,
        status: OrderStatus::Pending,
    );

    expect($data->customerEmail)->toBe('test@example.com');
});
```

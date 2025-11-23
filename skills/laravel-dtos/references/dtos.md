# Data Transfer Objects (DTOs) - Complete Guide

**Never pass multiple primitive values.** Always wrap data in Data objects.

**Related guides:**
- [actions.md](../../laravel-actions/references/actions.md) - Actions accept DTOs as parameters
- [controllers.md](../../laravel-controllers/references/controllers.md) - Controllers transform requests to DTOs
- [form-requests.md](../../laravel-validation/references/form-requests.md) - Validation before transformation
- [dto-factories.md](dto-factories.md) - **Transform external system data to DTOs**
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

## Using Spatie Laravel Data

All DTOs extend `Spatie\LaravelData\Data` through a custom base class.

## Base Data Class

**[View full implementation →](./dtos/Data.php)**

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

**[View full implementation →](./dtos/EmailFormatter.php)**

**Location:** `app/Data/Formatters/EmailFormatter.php`

### 6. Test Factories

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

### 7. Model Casts

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

### 8. Domain Factories

**Transform external system data** into internal DTOs using dedicated factory classes.

**[→ Complete guide: dto-factories.md](dto-factories.md)**

**Quick example:**

```php
<?php

declare(strict_types=1);

namespace App\Data\Factories;

use App\Data\PaymentData;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;

class PaymentDataFactory
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

**Use factories when:**
- Integrating external systems (APIs, webhooks, message queues)
- Multiple data sources map to same DTO
- Complex field transformations with business logic
- Transformation logic needs dedicated testing

**See [dto-factories.md](dto-factories.md) for complete patterns, testing strategies, and real-world examples.**

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
            'status' => OrderStatus::from($request->input('status')),
            'items' => OrderItemData::collect(
                $request->input('items'),
                OrderItemData::class
            ),
            'shippingAddress' => ShippingAddressData::from($request->input('shipping')),
            'billingAddress' => BillingAddressData::from($request->input('billing')),
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
        // API v1 might have different field names
        return CreateOrderData::from([
            'customerEmail' => $request->input('email'), // Different field name
            'notes' => $request->input('notes'),
            'status' => OrderStatus::from($request->input('order_status')),
            'items' => OrderItemData::collect(
                $request->input('line_items'), // Different field name
                OrderItemData::class
            ),
            'shippingAddress' => ShippingAddressData::from($request->input('shipping_details')),
            'billingAddress' => BillingAddressData::from($request->input('billing_details')),
        ]);
    }
}
```

**Key principle:** Each API version and web layer has its own transformer to handle different request structures.

## Input Name Mapping (Optional)

If your project requires mapping snake_case API inputs to camelCase properties:

```php
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CreateOrderData extends Data
{
    public function __construct(
        public string $firstName,       // Maps from 'first_name'
        public string $lastName,        // Maps from 'last_name'
        public string $emailAddress,    // Maps from 'email_address'
    ) {}
}
```

**However, prefer using Data Transformers** for explicit control over input mapping.

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
├── Factories/
│   └── PaymentDataFactory.php
└── Transformers/
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

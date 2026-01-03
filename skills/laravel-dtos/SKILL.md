---
name: laravel-dtos
description: Data Transfer Objects using Spatie Laravel Data. Use when handling data transfer, API requests/responses, or when user mentions DTOs, data objects, Spatie Data, formatters, transformers, or structured data handling.
---

# Laravel DTOs

**Never pass multiple primitive values.** Always wrap data in Data objects.

**Related guides:**
- [dto-transformers.md](references/dto-transformers.md) - Transform external data into DTOs
- [test-factories.md](references/test-factories.md) - Create hydrated DTOs for tests

## Spatie Laravel Data Package

Uses [Spatie Laravel Data](https://spatie.be/docs/laravel-data). Refer to official docs for package features.

### Use `::from()` with Arrays

**Always prefer `::from()`** with arrays. Let the package handle casting based on property types.

```php
// PREFERRED - Let package cast automatically
$data = CreateOrderData::from([
    'customerEmail' => $request->input('customer_email'),
    'deliveryDate' => $request->input('delivery_date'),  // String → CarbonImmutable
    'status' => $request->input('status'),               // String → Enum
    'items' => $request->collect('items'),               // Array → Collection<OrderItemData>
]);

// AVOID - Manual casting
$data = new CreateOrderData(
    customerEmail: $request->input('customer_email'),
    deliveryDate: CarbonImmutable::parse($request->input('delivery_date')),
    status: OrderStatus::from($request->input('status')),
);
```

**When `new` is acceptable:** In test factories, when values are already correct type, in formatters.

### Avoid Case Mapper Attributes

**Don't use `#[MapInputName]` or case mappers.** Map field names explicitly in calling code.

```php
// AVOID
#[MapInputName(SnakeCaseMapper::class)]
class CreateOrderData extends Data { ... }

// PREFERRED - Explicit mapping
CreateOrderData::from([
    'customerEmail' => $request->input('customer_email'),
]);
```

### Date Casting is Automatic

Configure date format in `config/data.php`. Package casts strings to Carbon automatically.

```php
// config/data.php
return ['date_format' => 'Y-m-d H:i:s'];
```

## Basic Structure

```php
<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

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
    ) {
        // Apply formatters in constructor
        $this->customerEmail = EmailFormatter::format($this->customerEmail);
    }
}
```

## Key Patterns

### Type Everything

```php
public string $email;                          // Required string
public ?string $phone;                         // Nullable string
public CarbonImmutable $createdAt;             // DateTime (immutable)
public OrderStatus $status;                    // Enum
public AddressData $address;                   // Nested DTO
```

### Collections with PHPDoc

```php
/** @var int[] */
public array $productIds;

/** @var Collection<int, OrderItemData> */
public Collection $items;
```

### Formatters

Apply in constructor. Location: `app/Data/Formatters/`

```php
public function __construct(
    public string $email,
    public ?string $postcode,
) {
    $this->email = EmailFormatter::format($this->email);
    $this->postcode = $this->postcode ? PostcodeFormatter::format($this->postcode) : null;
}
```

### Model Casts

Cast JSON columns to DTOs:

```php
class Order extends Model
{
    protected function casts(): array
    {
        return ['metadata' => OrderMetadataData::class];
    }
}
```

## Naming Conventions

| Type | Pattern | Examples |
|------|---------|----------|
| Response DTOs | `{Entity}Data` | `OrderData`, `UserData` |
| Request DTOs | `{Action}{Entity}Data` | `CreateOrderData`, `UpdateUserData` |
| Nested DTOs | `{Descriptor}{Entity}Data` | `ShippingAddressData`, `OrderMetadataData` |

## Directory Structure

```
app/Data/
├── CreateOrderData.php
├── OrderData.php
├── Concerns/
│   └── HasTestFactory.php
├── Formatters/
│   ├── EmailFormatter.php
│   └── PostcodeFormatter.php
└── Transformers/
    ├── PaymentDataTransformer.php
    └── Web/
        └── OrderDataTransformer.php
```

## Transformers

For complex transformations (external APIs, webhooks, field mappings), use dedicated transformer classes.

**[→ Complete guide: dto-transformers.md](references/dto-transformers.md)**

```php
$data = PaymentDataTransformer::fromStripePaymentIntent($webhook['data']);
```

## Test Factories

Create hydrated DTOs for tests using the `HasTestFactory` trait.

**[→ Complete guide: test-factories.md](references/test-factories.md)**

```php
$data = CreateOrderData::testFactory()->make();
$items = OrderItemData::testFactory()->collect(count: 5);
```

# DTO Transformers - Domain Data Transformation

**Transformer classes transform external system data into internal DTOs.** Use transformers when you need explicit, testable mapping logic between different data structures.

**Related guides:**
- [dtos.md](dtos.md) - Core DTO patterns and structure
- [actions.md](../../laravel-actions/references/actions.md) - Actions consume transformer-created DTOs
- [services.md](../../laravel-services/references/services.md) - Service layer integration points for transformers
- [testing.md](../../laravel-testing/references/testing.md) - Testing transformer logic and using test factories

## Transformers vs Test Factories

**Important distinction:**

| Aspect | Transformers | Test Factories |
|--------|--------------|----------------|
| **Purpose** | Transform domain data → DTO | Generate fake test data |
| **Location** | `app/Data/Transformers/` | `database/factories/Data/` |
| **Class naming** | `{Entity}DataTransformer` | `{Entity}DataFactory` |
| **Used in** | Domain logic, controllers, handlers | Tests only |
| **Method style** | `::fromStripe()`, `::fromRequest()` | `::testFactory()->make()` |

## Infrastructure Files

**[→ View all implementation files](./)**

This guide includes complete implementation files you can copy to your project:
- **[HasTestFactory.php](./HasTestFactory.php)** - Trait for test factory support
- **[Data.php](./Data.php)** - Base Data class with test factory trait
- **[DataTestFactory.php](./DataTestFactory.php)** - Base factory class for all test factories
- **[AppServiceProvider.php](./AppServiceProvider.php)** - Test factory resolver registration
- **[helpers.php](./helpers.php)** - collect_get() helper function

## Philosophy

DTO transformers serve a specific purpose:
- **Transform external data** (APIs, message queues, webhooks) into internal DTOs
- **Map heterogeneous sources** to normalized domain objects
- **Provide testable transformation logic** with well-named static methods
- **Preserve original data** alongside transformed data
- **Handle complex field mapping** that's too intricate for inline constructors

## When to Use Transformers

### Use Transformers When:

1. **Integrating external systems** with different data structures
   - Third-party APIs (Stripe, Twilio, etc.)
   - Message queue payloads (SQS, RabbitMQ)
   - Webhook data
   - Legacy system integration

2. **Multiple data sources** map to the same DTO
   - Different providers with different schemas
   - Multiple API versions
   - Various event types creating the same domain object

3. **Complex transformations** requiring business logic
   - Conditional field mapping
   - Enum conversions with match expressions
   - Date/time zone transformations
   - Nested DTO hierarchies

4. **Testing transformation logic** in isolation
   - Transformer methods are static and easily testable
   - Complex mapping warrants dedicated tests

### Don't Use Transformers When:

1. **Simple data** - Use Spatie's `::from()` directly
2. **Child DTOs** - Create inline in parent transformer
3. **Direct model to DTO** - Use DTO's `::from($model)` directly
4. **Internal application data** - Use `::from()` with arrays

## Decision Tree

```
Is data from external system?
├─ NO → Use DTO::from() or constructor
│
└─ YES → Does it need transformation?
    ├─ NO → Use DTO::from($data)
    │
    └─ YES → Does transformation have business logic?
        ├─ NO → Use inline transformation
        │
        └─ YES → Create Transformer
            └─ Multiple sources? → Multiple static methods
```

## Transformer Structure

### Basic Transformer

```php
<?php

declare(strict_types=1);

namespace App\Data\Transformers;

use App\Data\Match\MatchData;
use App\Enums\MatchSource;
use App\Enums\MatchStatus;
use Carbon\CarbonImmutable;

class MatchDataTransformer
{
    public static function fromFinderAutomatedMatch(array $match): MatchData
    {
        return MatchData::from([
            'id' => data_get($match, 'id'),
            'providerId' => data_get($match, 'provider.id'),
            'status' => match (data_get($match, 'status')) {
                'matched' => MatchStatus::Matched,
                'pending' => MatchStatus::Pending,
                default => MatchStatus::Unknown,
            },
            'source' => MatchSource::Finder,
            'matchedAt' => CarbonImmutable::parse(data_get($match, 'matched_at')),
            'rawData' => $match,  // Preserve original payload
        ]);
    }

    public static function fromNexusProvider(NexusProvider $provider): MatchData
    {
        return MatchData::from([
            'id' => $provider->id,
            'providerId' => $provider->providerId,
            'status' => $provider->active
                ? MatchStatus::Matched
                : MatchStatus::Inactive,
            'source' => MatchSource::Nexus,
            'matchedAt' => $provider->createdAt,
            'rawData' => $provider->toArray(),
        ]);
    }
}
```

## Transformer Patterns

### 1. Named Constructor Pattern

**Method names clearly indicate source system:**

```php
class ProviderResponseDataTransformer
{
    // Pattern: from{SourceSystem}{ContextualInfo}
    public static function fromFinderMessage(InboundMessage $message): ProviderResponseData
    public static function fromNexusMessage(InboundMessage $message): ProviderResponseData
    public static function fromStripeWebhook(array $payload): ProviderResponseData
}
```

**Naming conventions:**
- `from{SystemName}{DataType}` - e.g., `fromStripePaymentIntent`
- `from{SystemName}{EventType}` - e.g., `fromFinderAutomatedMatch`
- `from{MessageSource}` - e.g., `fromInboundMessage` (when single source)

### 2. Safe Data Access

**Always use safe accessors:**

**[→ See helpers.php for collect_get() implementation](./helpers.php)**

```php
// Array access - use data_get() with default
'town' => data_get($match, 'address.town', default: null)

// Collection access - use collect_get() helper
'items' => collect_get($payload, 'line_items')
    ->map(fn ($item) => OrderItemData::from([...]))

// Object property with null coalescing
'email' => $provider->email ?? null
```

### 3. Enum Transformation

**Use match expressions for enum mapping:**

```php
'status' => match (data_get($data, 'type')) {
    'fabric' => MatchSource::Fabric,
    'pts' => MatchSource::Pts,
    'nexus' => MatchSource::Nexus,
    default => MatchSource::Unknown,
},

'paymentStatus' => match ($stripeStatus) {
    'succeeded', 'paid' => PaymentStatus::Succeeded,
    'processing', 'pending' => PaymentStatus::Pending,
    'failed', 'canceled' => PaymentStatus::Failed,
    default => PaymentStatus::Unknown,
},
```

### 4. Nested DTO Creation

**Create child DTOs inline using map:**

```php
'addresses' => collect_get($match, 'addresses')
    ->map(fn ($address) => ProviderAddressData::from([
        'line1' => data_get($address, 'line_1'),
        'line2' => data_get($address, 'line_2'),
        'city' => data_get($address, 'town'),  // Field name mapping
        'postcode' => data_get($address, 'postcode'),
    ])),

'emails' => collect_get($match, 'emails')
    ->map(fn ($email) => ProviderEmailData::from([
        'address' => data_get($email, 'email'),
        'type' => data_get($email, 'type'),
        'verified' => data_get($email, 'verified', default: false),
    ])),
```

**First-class callable syntax for reusable transformers:**

```php
'matches' => collect_get($payload, 'loas')
    ->map(MatchDataTransformer::fromFinderAutomatedMatch(...))
```

### 5. Conditional Nested DTOs

**Handle optional nested objects:**

```php
'employer' => ($employer = $suggestion->employer)
    ? MatchSuggestionEmployerData::from([
        'id' => $employer->id,
        'name' => $employer->name,
        'address' => $employer->address,
    ])
    : null,

'preferences' => !empty($data['preferences'])
    ? ProviderPreferencesData::from($data['preferences'])
    : null,
```

### 6. Raw Data Preservation

**Always preserve the original payload:**

```php
public static function fromStripePaymentIntent(array $paymentIntent): PaymentData
{
    return PaymentData::from([
        'id' => data_get($paymentIntent, 'id'),
        'amount' => data_get($paymentIntent, 'amount'),
        'currency' => data_get($paymentIntent, 'currency'),
        // ... other mapped fields
        'rawData' => $paymentIntent,  // Complete original data
    ]);
}
```

**Benefits:**
- Debugging external system issues
- Auditing data transformations
- Adding new fields without re-fetching
- Compliance and data retention

### 7. Validation in Transformers

**Add guards for required data:**

```php
public static function fromInboundMessage(InboundMessage $message): ProviderResponseData
{
    $payload = $message->message->payload;

    throw_unless(
        data_get($payload, 'result'),
        InvalidProviderResponseException::missingResult($message)
    );

    return ProviderResponseData::from([
        'messageId' => $message->id,
        'result' => data_get($payload, 'result'),
        // ...
    ]);
}
```

## Organization

### Transformers (Domain Logic)

**[→ Implementation examples](./)**

Transformers live in `app/Data/Transformers/` and transform external system data to DTOs:

```
app/Data/
└── Transformers/
    ├── MatchDataTransformer.php
    ├── FinderProviderResponseDataTransformer.php
    ├── NexusProviderResponseDataTransformer.php
    ├── MatchSuggestionDataTransformer.php
    ├── Stripe/
    │   ├── PaymentDataTransformer.php
    │   └── CustomerDataTransformer.php
    ├── External/
    │   └── WebhookDataTransformer.php
    └── Web/
        ├── OrderDataTransformer.php
        └── UserDataTransformer.php
```

### Test Factories (Generate Test Data)

**[→ View complete implementation files](./)**

Test factories live in `database/factories/Data/` and generate fake data for testing:

```
database/factories/Data/
├── DataTestFactory.php           # Base factory class
├── AddressDataFactory.php        # Simple example
├── TraceDataFactory.php          # Advanced with states
├── OrderDataFactory.php
└── UserDataFactory.php
```

**Setup requirements:**

1. **Base Data class** - Apply `HasTestFactory` trait
   - **[→ View Data.php](./Data.php)**

2. **Factory resolver** - Register in AppServiceProvider
   - **[→ View AppServiceProvider.php](./AppServiceProvider.php)**

3. **Base factory class** - Extend `DataTestFactory`
   - **[→ View DataTestFactory.php](./DataTestFactory.php)**

**Organization principles:**
- Transformers: `app/Data/Transformers/` - Domain transformation logic
- Test factories: `database/factories/Data/` - Generate test data
- Subdirectories for external services with multiple transformers
- One transformer per DTO (can have multiple methods)
- Name transformers after the DTO they create: `{DTO}Transformer`

## Real-World Examples

### Example 1: Multiple Source Systems

**Scenario:** Match data comes from two different providers with different schemas.

```php
<?php

declare(strict_types=1);

namespace App\Data\Transformers;

use App\Data\Match\MatchData;
use App\Data\Match\ProviderAddressData;
use App\Data\Match\ProviderEmailData;
use App\Data\Match\ProviderPhoneData;
use App\Enums\MatchSource;
use App\Enums\MatchType;
use App\Models\Tenanted\Match\TraceMatch;
use App\Services\Nexus\DataObjects\Provider as NexusProvider;
use Carbon\CarbonImmutable;

class MatchDataTransformer
{
    /**
     * Create MatchData from Finder automated match array.
     */
    public static function fromFinderAutomatedMatch(array $match): MatchData
    {
        return MatchData::from([
            'id' => data_get($match, 'id'),
            'type' => match (data_get($match, 'type')) {
                'fabric' => MatchType::Fabric,
                'pts' => MatchType::Pts,
                default => MatchType::Unknown,
            },
            'source' => MatchSource::Finder,
            'providerId' => data_get($match, 'provider_id'),
            'forename' => data_get($match, 'forename'),
            'surname' => data_get($match, 'surname'),
            'dateOfBirth' => data_get($match, 'date_of_birth')
                ? CarbonImmutable::parse(data_get($match, 'date_of_birth'))
                : null,

            // Nested DTOs
            'addresses' => collect_get($match, 'addresses')
                ->map(fn ($address) => ProviderAddressData::from([
                    'line1' => data_get($address, 'line_1'),
                    'line2' => data_get($address, 'line_2'),
                    'city' => data_get($address, 'town'),
                    'county' => data_get($address, 'county'),
                    'postcode' => data_get($address, 'postcode'),
                    'fromDate' => CarbonImmutable::parse(data_get($address, 'from_date')),
                    'toDate' => data_get($address, 'to_date')
                        ? CarbonImmutable::parse(data_get($address, 'to_date'))
                        : null,
                ])),

            'emails' => collect_get($match, 'emails')
                ->map(fn ($email) => ProviderEmailData::from([
                    'address' => data_get($email, 'email'),
                    'type' => data_get($email, 'type'),
                ])),

            'phones' => collect_get($match, 'phones')
                ->map(fn ($phone) => ProviderPhoneData::from([
                    'number' => data_get($phone, 'phone'),
                    'type' => data_get($phone, 'type'),
                ])),

            'rawData' => $match,
        ]);
    }

    /**
     * Create MatchData from TraceMatch model.
     */
    public static function fromFinderTraceMatch(TraceMatch $match): MatchData
    {
        return MatchData::from([
            'id' => $match->id,
            'type' => MatchType::Trace,
            'source' => MatchSource::Finder,
            'providerId' => $match->provider_id,
            'forename' => $match->forename,
            'surname' => $match->surname,
            'dateOfBirth' => $match->date_of_birth,
            'addresses' => collect(),  // Trace doesn't include addresses
            'emails' => collect(),
            'phones' => collect(),
            'rawData' => $match->toArray(),
        ]);
    }

    /**
     * Create MatchData from Nexus provider DTO.
     */
    public static function fromNexusProvider(NexusProvider $provider): MatchData
    {
        return MatchData::from([
            'id' => $provider->id,
            'type' => MatchType::Nexus,
            'source' => MatchSource::Nexus,
            'providerId' => $provider->providerId,
            'forename' => $provider->firstName,  // Different field name
            'surname' => $provider->lastName,    // Different field name
            'dateOfBirth' => $provider->dob,     // Different field name

            'addresses' => collect([$provider->address])  // Single address, not array
                ->filter()
                ->map(fn ($address) => ProviderAddressData::from([
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'city' => $address->city,
                    'county' => $address->county,
                    'postcode' => $address->postcode,
                    'fromDate' => $provider->createdAt,  // Use provider created date
                    'toDate' => null,
                ])),

            'emails' => collect([$provider->email])
                ->filter()
                ->map(fn ($email) => ProviderEmailData::from([
                    'address' => $email,
                    'type' => 'primary',
                ])),

            'phones' => collect([$provider->phone])
                ->filter()
                ->map(fn ($phone) => ProviderPhoneData::from([
                    'number' => $phone,
                    'type' => 'mobile',
                ])),

            'rawData' => $provider->toArray(),
        ]);
    }
}
```

### Example 2: Message Handler Integration

**Scenario:** Different message types from queue need transformation.

```php
<?php

declare(strict_types=1);

namespace App\Data\Transformers;

use App\Data\Match\ProviderResponseData;
use App\Models\Central\InboundMessage;
use InvalidArgumentException;

class FinderProviderResponseDataTransformer
{
    /**
     * Create ProviderResponseData from Finder inbound message.
     */
    public static function fromInboundMessage(InboundMessage $message): ProviderResponseData
    {
        $payload = $message->message->payload;

        return ProviderResponseData::from([
            'messageId' => $message->id,
            'requestId' => data_get($payload, 'request_id'),
            'outcome' => data_get($payload, 'outcome'),

            'matches' => collect_get($payload, 'loas')
                ->map(MatchDataTransformer::fromFinderAutomatedMatch(...)),

            'errors' => collect_get($payload, 'errors'),
            'rawPayload' => $payload,
        ]);
    }
}
```

**Usage in message handler:**

```php
namespace App\Messages\Handlers;

use App\Actions\Tenanted\Match\ProcessMatchAttemptProviderResponseAction;
use App\Data\Transformers\FinderProviderResponseDataTransformer;
use App\Models\Central\InboundMessage;

class FinderIncomingMatchEventsHandler
{
    public function __invoke(InboundMessage $message): void
    {
        resolve(ProcessMatchAttemptProviderResponseAction::class)(
            FinderProviderResponseDataTransformer::fromInboundMessage($message)
        );
    }
}
```

### Example 3: Nested Transformer Composition

**Scenario:** Parent transformer delegates to child transformers.

```php
<?php

declare(strict_types=1);

namespace App\Data\Transformers;

use App\Data\Suggestion\MatchSuggestionData;
use App\Data\Suggestion\MatchSuggestionEmployerData;
use App\Data\Suggestion\MatchSuggestionProviderData;
use App\Models\Tenanted\Suggestion\Suggestion;

class MatchSuggestionDataTransformer
{
    public static function fromFinderSuggestion(Suggestion $suggestion): MatchSuggestionData
    {
        return MatchSuggestionData::from([
            'id' => $suggestion->id,
            'matchAttemptId' => $suggestion->match_attempt_id,
            'score' => $suggestion->score,
            'reason' => $suggestion->reason,

            // Conditional nested DTOs
            'employer' => ($employer = $suggestion->employer)
                ? MatchSuggestionEmployerData::from([
                    'id' => $employer->id,
                    'name' => $employer->name,
                    'reference' => $employer->reference,
                ])
                : null,

            'provider' => ($provider = $suggestion->provider)
                ? MatchSuggestionProviderData::from([
                    'id' => $provider->id,
                    'forename' => $provider->forename,
                    'surname' => $provider->surname,
                    'dateOfBirth' => $provider->date_of_birth,
                ])
                : null,

            'rawData' => $suggestion->toArray(),
        ]);
    }
}
```

## Testing Transformer Logic

**[→ See testing.md for comprehensive testing guide](../../laravel-testing/references/testing.md)**

### Snapshot Testing

**Test complete transformation with snapshots:**

```php
use App\Data\Transformers\FinderProviderResponseDataTransformer;
use App\Data\Match\ProviderResponseData;
use App\Models\Central\InboundMessage;

test('transforms finder matched outcome to provider response data', function (): void {
    $message = InboundMessage::factory()
        ->withFinderMessageOutcomeMatchedSingle()
        ->create(['id' => 1002]);

    $data = FinderProviderResponseDataTransformer::fromInboundMessage($message);

    expect($data)
        ->toBeInstanceOf(ProviderResponseData::class)
        ->toMatchSnapshot();
});

test('transforms finder no match outcome to provider response data', function (): void {
    $message = InboundMessage::factory()
        ->withFinderMessageOutcomeNoMatch()
        ->create(['id' => 1003]);

    $data = FinderProviderResponseDataTransformer::fromInboundMessage($message);

    expect($data)
        ->toBeInstanceOf(ProviderResponseData::class)
        ->outcome->toBe('no_match')
        ->matches->toBeEmpty();
});
```

### Field-Level Testing

**Test specific transformations:**

```php
test('maps finder field names to internal names', function (): void {
    $match = [
        'id' => 123,
        'forename' => 'John',
        'surname' => 'Doe',
        'addresses' => [
            [
                'line_1' => '123 Street',
                'town' => 'London',  // External: 'town'
                'postcode' => 'SW1A 1AA',
            ],
        ],
    ];

    $data = MatchDataTransformer::fromFinderAutomatedMatch($match);

    expect($data->addresses->first())
        ->city->toBe('London');  // Internal: 'city'
});

test('transforms stripe payment status to enum', function (): void {
    expect(PaymentDataTransformer::fromStripePaymentIntent(['status' => 'succeeded']))
        ->status->toBe(PaymentStatus::Succeeded);

    expect(PaymentDataTransformer::fromStripePaymentIntent(['status' => 'failed']))
        ->status->toBe(PaymentStatus::Failed);

    expect(PaymentDataTransformer::fromStripePaymentIntent(['status' => 'unknown_status']))
        ->status->toBe(PaymentStatus::Unknown);
});
```

### Validation Testing

**Test transformer guards:**

```php
use App\Exceptions\InvalidProviderResponseException;

test('throws exception when required data missing', function (): void {
    $message = InboundMessage::factory()
        ->create(['message' => ['payload' => []]]);  // Missing result

    FinderProviderResponseDataTransformer::fromInboundMessage($message);
})->throws(InvalidProviderResponseException::class);
```

## Anti-Patterns

### Inline Transformation in Actions

```php
// BAD - Transformation logic hidden in action
class ProcessPaymentAction
{
    public function __invoke(array $stripeData): Payment
    {
        $status = match ($stripeData['status']) {
            'succeeded' => PaymentStatus::Succeeded,
            // ... complex mapping logic
        };

        // More transformation logic...
    }
}
```

**Use transformer:**

```php
// GOOD - Explicit, testable transformation
class ProcessPaymentAction
{
    public function __invoke(PaymentData $data): Payment
    {
        // Clean domain logic
    }
}

// In message handler or controller
$paymentData = PaymentDataTransformer::fromStripePaymentIntent($stripeData);
$action($paymentData);
```

### Transformer That's Too Generic

```php
// BAD - Generic transformer loses type safety
class DataTransformer
{
    public static function make(string $type, array $data): Data
    {
        return match ($type) {
            'order' => OrderData::from($data),
            'user' => UserData::from($data),
            // ...
        };
    }
}
```

**Dedicated transformers:**

```php
// GOOD - Specific, type-safe transformers
OrderDataTransformer::fromStripeOrder($data)
UserDataTransformer::fromAuth0User($data)
```

### Transformer With Business Logic

```php
// BAD - Transformer doing more than transformation
class OrderDataTransformer
{
    public static function fromStripeOrder(array $order): OrderData
    {
        $data = OrderData::from([...]);

        // NO! Business logic doesn't belong here
        if ($data->total > 1000) {
            Mail::to($data->customer)->send(new HighValueOrder($data));
        }

        return $data;
    }
}
```

**Transform only:**

```php
// GOOD - Pure transformation
class OrderDataTransformer
{
    public static function fromStripeOrder(array $order): OrderData
    {
        return OrderData::from([
            'total' => data_get($order, 'amount'),
            'currency' => data_get($order, 'currency'),
            'rawData' => $order,
        ]);
    }
}

// Business logic in action
class ProcessOrderAction
{
    public function __invoke(OrderData $data): Order
    {
        $order = Order::create($data->toArray());

        if ($data->total > 1000) {
            Mail::to($data->customer)->send(new HighValueOrder($data));
        }

        return $order;
    }
}
```

## Common Helper Functions

**[→ View helpers.php implementation](./helpers.php)**

### data_get()

Safe array access with defaults (Laravel built-in):

```php
data_get($array, 'nested.key', default: null)
data_get($array, 'items.0.name')
```

### collect_get()

Safe collection access (custom helper - **[see implementation](./helpers.php)**):

```php
// Helper definition (add to app/helpers.php)
function collect_get(array $array, string $key): Collection
{
    return collect(data_get($array, $key, default: []));
}

// Usage in transformers
collect_get($payload, 'items')
    ->map(fn ($item) => ItemData::from($item))
```

## Summary

**Use DTO transformers when:**
- Integrating external systems with different schemas
- Multiple sources map to same internal DTO
- Complex transformation logic warrants testing
- Field mapping is non-trivial (enums, dates, nested objects)

**Transformer method naming:**
- `from{SystemName}{DataType}` - Clear source indication
- Static methods - No instantiation needed
- Well-named - Self-documenting transformation intent

**Key patterns:**
- Safe data access (`data_get`, `collect_get`)
- Enum transformation with `match`
- Nested DTO creation with `map`
- Raw data preservation
- Guard clauses for required fields

**Testing:**
- Snapshot testing for complete transformations
- Field-level tests for specific mappings
- Validation tests for guards

**Remember:** Transformers transform domain data, test factories generate test data, actions execute business logic. Keep concerns separate.

## Setup Checklist

**[→ View all implementation files](./)**

To implement DTO test factories in your project:

1. **Base Data class** - **[Copy Data.php](./Data.php)** to `app/Data/Data.php`
2. **Trait** - **[Copy HasTestFactory.php](./HasTestFactory.php)** to `app/Data/Concerns/HasTestFactory.php`
3. **Base factory** - **[Copy DataTestFactory.php](./DataTestFactory.php)** to `database/factories/Data/DataTestFactory.php`
4. **Factory resolver** - **[Copy AppServiceProvider.php method](./AppServiceProvider.php)** to your `AppServiceProvider::register()`
5. **Helper** - **[Copy helpers.php](./helpers.php)** to `app/helpers.php` (ensure it's autoloaded in `composer.json`)

**Autoload helpers in composer.json:**
```json
{
    "autoload": {
        "files": [
            "app/helpers.php"
        ]
    }
}
```

Run `composer dump-autoload` after adding the helpers file.

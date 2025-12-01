# Multi-Tenancy Patterns - Complete Guide

Multi-tenancy separates application logic into **central** (non-tenant) and **tenanted** (tenant-specific) contexts. This provides clear boundaries for what operates across all tenants vs. within a specific tenant.

**Related guides:**
- [actions.md](../../laravel-actions/references/actions.md) - Central vs Tenanted action organization
- [models.md](../../laravel-models/references/models.md) - Central vs Tenanted model organization
- [structure.md](../../laravel-architecture/references/structure.md) - Directory organization for multi-tenancy
- [route-binding.md](../../laravel-routing/references/route-binding.md) - Tenant-scoped route model binding

## Philosophy

Multi-tenancy provides:
- **Clear separation** between central and tenant contexts
- **Database isolation** with separate databases per tenant
- **Automatic scoping** of queries to current tenant
- **Context awareness** through helper classes
- **Queue integration** with tenant context preservation

## When to Use Multi-Tenancy

**Use multi-tenancy when:**
- Building SaaS applications with complete data isolation
- Each customer needs their own database
- Compliance requires strict data separation
- Different tenants have different schemas/customizations

**Don't use multi-tenancy when:**
- Simple user segmentation is sufficient (use user_id scoping)
- All customers share the same schema
- Data isolation isn't a regulatory requirement
- Application complexity doesn't justify the overhead

## Directory Structure

### Application Structure

```
app/
├── Actions/
│   ├── Central/          # Non-tenant actions
│   │   ├── Tenant/
│   │   │   ├── CreateTenantAction.php
│   │   │   ├── DeleteTenantAction.php
│   │   │   └── CreateTenantDatabaseAction.php
│   │   └── User/
│   │       ├── CreateCentralUserAction.php
│   │       └── AssignUserToTenantAction.php
│   └── Tenanted/         # Tenant-specific actions
│       ├── Order/
│       │   ├── CreateOrderAction.php
│       │   └── ProcessOrderAction.php
│       └── Customer/
│           ├── CreateCustomerAction.php
│           └── UpdateCustomerAction.php
├── Data/
│   ├── Central/          # Central DTOs
│   │   └── CreateTenantData.php
│   └── Tenanted/         # Tenant DTOs
│       └── CreateOrderData.php
├── Http/
│   ├── Central/          # Central routes (tenant management)
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Web/              # Tenant application routes
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Resources/
│   └── Api/              # Public API (tenant-scoped)
│       └── V1/
│           ├── Controllers/
│           ├── Requests/
│           └── Resources/
├── Models/
│   ├── Central/          # Central database models
│   │   ├── Tenant.php
│   │   └── User.php
│   └── Tenanted/         # Tenant database models
│       ├── Order.php
│       ├── Customer.php
│       └── Product.php
└── Support/
    └── TenantContext.php # Tenant context helper
```

### Key Principles

1. **Central vs Tenanted**: Explicit separation via directory structure
2. **Namespace Clarity**: `App\Actions\Central\` vs `App\Actions\Tenanted\`
3. **Model Distinction**: `App\Models\Central\` vs `App\Models\Tenanted\`
4. **Database Separation**: Each tenant has dedicated database
5. **Context Helpers**: Centralized tenant context access

## Central Actions

**Central actions** manage tenants and cross-tenant operations.

### Creating a Tenant

```php
<?php

declare(strict_types=1);

namespace App\Actions\Central\Tenant;

use App\Data\Central\CreateTenantData;
use App\Models\Central\Tenant;
use Illuminate\Support\Facades\DB;

class CreateTenantAction
{
    public function __construct(
        private readonly CreateTenantDatabaseAction $createDatabase,
    ) {}

    public function __invoke(CreateTenantData $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
            $this->guard($data);

            $tenant = $this->createTenant($data);

            ($this->createDatabase)($tenant);

            return $tenant;
        });
    }

    private function guard(CreateTenantData $data): void
    {
        throw_if(
            Tenant::where('domain', $data->domain)->exists(),
            TenantDomainAlreadyExistsException::forDomain($data->domain)
        );
    }

    private function createTenant(CreateTenantData $data): Tenant
    {
        return Tenant::create([
            'id' => $data->tenantId,
            'name' => $data->name,
            'domain' => $data->domain,
        ]);
    }
}
```

### Creating Tenant Database

```php
<?php

declare(strict_types=1);

namespace App\Actions\Central\Tenant;

use App\Models\Central\Tenant;
use Stancl\Tenancy\Database\TenantDatabaseManagers\PermissionControlledDatabaseManager;

class CreateTenantDatabaseAction
{
    public function __construct(
        private readonly PermissionControlledDatabaseManager $databaseManager,
    ) {}

    public function __invoke(Tenant $tenant): void
    {
        $this->databaseManager->createDatabase($tenant);
        $this->databaseManager->makeCurrentDatabase($tenant);

        // Run tenant-specific migrations
        artisan('tenants:migrate', ['--tenants' => [$tenant->getTenantKey()]]);
    }
}
```

### Deleting a Tenant

```php
<?php

declare(strict_types=1);

namespace App\Actions\Central\Tenant;

use App\Models\Central\Tenant;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Database\TenantDatabaseManagers\PermissionControlledDatabaseManager;

class DeleteTenantAction
{
    public function __construct(
        private readonly PermissionControlledDatabaseManager $databaseManager,
    ) {}

    public function __invoke(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant): void {
            $this->databaseManager->deleteDatabase($tenant);

            $tenant->delete();
        });
    }
}
```

## Tenanted Actions

**Tenanted actions** operate within a specific tenant's context. All database queries are automatically scoped to the current tenant.

### Creating Tenant-Scoped Records

```php
<?php

declare(strict_types=1);

namespace App\Actions\Tenanted\Order;

use App\Data\Tenanted\CreateOrderData;
use App\Models\Tenanted\Order;
use App\Models\Tenanted\User;
use Illuminate\Support\Facades\DB;

class CreateOrderAction
{
    public function __invoke(User $user, CreateOrderData $data): Order
    {
        return DB::transaction(function () use ($user, $data): Order {
            $this->guard($user, $data);

            // Automatically scoped to current tenant
            $order = $user->orders()->create([
                'status' => $data->status,
                'total' => $data->total,
                'notes' => $data->notes,
            ]);

            $this->createOrderItems($order, $data->items);

            return $order;
        });
    }

    private function guard(User $user, CreateOrderData $data): void
    {
        throw_unless(
            $user->canCreateOrders(),
            OrderCreationNotAllowedException::forUser($user)
        );
    }

    private function createOrderItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $order->items()->create([
                'product_id' => $item->productId,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ]);
        }
    }
}
```

### Key Characteristics

- **Automatic scoping**: All queries automatically scoped to current tenant
- **No tenant_id needed**: Scoping handled by tenancy package
- **Context awareness**: Current tenant available via helper
- **Isolation**: Cannot access other tenants' data

## Tenant Context Helper

**Centralized access** to current tenant context.

```php
<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Central\Tenant;
use Stancl\Tenancy\Facades\Tenancy;

class TenantContext
{
    /**
     * Get the current tenant instance.
     */
    public static function current(): ?Tenant
    {
        return Tenancy::tenant();
    }

    /**
     * Get the current tenant ID.
     */
    public static function id(): ?string
    {
        return Tenancy::tenant()?->getTenantKey();
    }

    /**
     * Check if a tenant is currently active.
     */
    public static function isActive(): bool
    {
        return Tenancy::tenant() !== null;
    }

    /**
     * Run a callback in a specific tenant's context.
     */
    public static function run(Tenant $tenant, callable $callback): mixed
    {
        return tenancy()->runForMultiple([$tenant], $callback);
    }

    /**
     * Run a callback in the central context.
     */
    public static function runCentral(callable $callback): mixed
    {
        return tenancy()->runForMultiple([], $callback);
    }
}
```

### Usage

```php
use App\Support\TenantContext;

// Get current tenant
$tenant = TenantContext::current();

// Get tenant ID
$tenantId = TenantContext::id();

// Check if tenant is active
if (TenantContext::isActive()) {
    // Tenant-specific logic
}

// Run in specific tenant context
TenantContext::run($tenant, function () {
    Order::create([...]);
});

// Run in central context
TenantContext::runCentral(function () {
    Tenant::create([...]);
});
```

## Tenant Identification Middleware

**Identify tenant** from request (domain, subdomain, header, etc.).

### Domain-Based Identification

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class IdentifyTenant extends InitializeTenancyByDomain
{
    public function handle(Request $request, Closure $next)
    {
        // Tenant identified by domain (e.g., tenant1.myapp.com)
        return parent::handle($request, $next);
    }
}
```

### Subdomain-Based Identification

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;

class IdentifyTenant extends InitializeTenancyBySubdomain
{
    public function handle(Request $request, Closure $next)
    {
        // Tenant identified by subdomain
        return parent::handle($request, $next);
    }
}
```

### Header-Based Identification

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

class IdentifyTenant extends InitializeTenancyByRequestData
{
    public static string $header = 'X-Tenant';

    public function handle(Request $request, Closure $next)
    {
        // Tenant identified by header
        return parent::handle($request, $next);
    }
}
```

## Route Configuration

### Tenant Routes

```php
// routes/tenant.php
Route::middleware(['tenant'])->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/customers', [CustomerController::class, 'index']);
});
```

### Central Routes

```php
// routes/central.php
Route::middleware(['central'])->prefix('central')->group(function () {
    Route::get('/tenants', [TenantController::class, 'index']);
    Route::post('/tenants', [TenantController::class, 'store']);
    Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy']);
});
```

### Bootstrap Configuration

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(function () {
        // Central routes (tenant management)
        Route::middleware('web')
            ->prefix('central')
            ->name('central.')
            ->group(base_path('routes/central.php'));

        // Tenant routes (scoped to tenant)
        Route::middleware(['web', 'tenant'])
            ->group(base_path('routes/tenant.php'));
    })
    ->create();
```

## Models

### Central Models

**Location:** `app/Models/Central/`

```php
<?php

declare(strict_types=1);

namespace App\Models\Central;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
```

### Tenanted Models

**Location:** `app/Models/Tenanted/`

```php
<?php

declare(strict_types=1);

namespace App\Models\Tenanted;

use App\Builders\OrderBuilder;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    // Automatically scoped to current tenant
    // No tenant_id needed in queries

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function newEloquentBuilder($query): OrderBuilder
    {
        return new OrderBuilder($query);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

## Queue Jobs with Tenant Context

**Jobs must preserve tenant context** when queued.

### Tenant-Aware Job

```php
<?php

declare(strict_types=1);

namespace App\Jobs\Tenanted;

use App\Data\Tenanted\OrderData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Jobs\TenantAwareJob;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, TenantAwareJob;

    public function __construct(
        public TenantWithDatabase $tenant,
        public OrderData $orderData,
    ) {
        $this->onQueue('orders');
    }

    public function handle(ProcessOrderAction $action): void
    {
        // Runs in tenant context automatically
        $action($this->orderData);
    }
}
```

### Dispatching Tenant-Aware Jobs

```php
use App\Jobs\Tenanted\ProcessOrderJob;
use App\Support\TenantContext;

// Dispatch with current tenant
ProcessOrderJob::dispatch(
    TenantContext::current(),
    $orderData
);
```

## Testing Multi-Tenancy

### Testing Central Actions

```php
use App\Actions\Central\Tenant\CreateTenantAction;
use App\Data\Central\CreateTenantData;

it('creates a tenant with database', function () {
    $data = CreateTenantData::from([
        'tenantId' => 'tenant-1',
        'name' => 'Acme Corp',
        'domain' => 'acme.myapp.com',
    ]);

    $tenant = resolve(CreateTenantAction::class)($data);

    expect($tenant)
        ->toBeInstanceOf(Tenant::class)
        ->id->toBe('tenant-1')
        ->name->toBe('Acme Corp');

    // Verify database exists
    expect($tenant->database()->exists())->toBeTrue();
});
```

### Testing Tenanted Actions

```php
use App\Actions\Tenanted\Order\CreateOrderAction;
use App\Support\TenantContext;

it('creates order in tenant context', function () {
    $tenant = Tenant::factory()->create();

    TenantContext::run($tenant, function () {
        $user = User::factory()->create();
        $data = CreateOrderData::factory()->make();

        $order = resolve(CreateOrderAction::class)($user, $data);

        expect($order)
            ->toBeInstanceOf(Order::class)
            ->user_id->toBe($user->id);

        assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
        ]);
    });
});
```

### Advanced Testing with Stancl Tenancy Package

**When using the Stancl Tenancy package**, use these testing traits and helpers for proper tenant database management in tests.

#### ManagesTenants Trait

**Location:** `tests/Concerns/ManagesTenants.php`

This trait provides tenant management functionality for tests, including:
- Creating and tracking test tenants
- Cleaning up tenant databases after tests
- Parallel testing support
- Tenant initialization helpers

**[View full implementation →](./testing/ManagesTenants.php)**

#### RefreshDatabaseWithTenant Trait

**Location:** `tests/Concerns/RefreshDatabaseWithTenant.php`

This trait extends Laravel's RefreshDatabase trait to handle both central and tenant databases in transactions.

**[View full implementation →](./testing/RefreshDatabaseWithTenant.php)**

#### TenantTestCase

**Location:** `tests/TenantTestCase.php`

Base test case for all tenant-scoped tests.

**[View full implementation →](./testing/TenantTestCase.php)**

#### Enhanced TestCase

**Location:** `tests/TestCase.php`

Base test case with multi-tenancy support.

**[View full implementation →](./testing/TestCase.php)**

#### Pest Configuration for Multi-Tenancy

**Location:** `tests/Pest.php`

Configure Pest to automatically use the correct test case based on directory structure.

**[View full implementation →](./testing/Pest.php)**

### Test Directory Structure

**Organize tests by central vs tenanted:**

```
tests/
├── Concerns/
│   ├── ManagesTenants.php
│   └── RefreshDatabaseWithTenant.php
├── Feature/
│   ├── Central/              # Tests extending TestCase
│   │   └── TenantManagementTest.php
│   └── Tenanted/             # Tests extending TenantTestCase
│       ├── OrderTest.php
│       └── CustomerTest.php
├── Unit/
│   ├── Central/
│   │   └── CreateTenantActionTest.php
│   └── Tenanted/
│       └── CreateOrderActionTest.php
├── Pest.php
├── TestCase.php
└── TenantTestCase.php
```

### Using the Test Helpers

**Central tests** (no tenant context needed):

```php
// tests/Feature/Central/TenantManagementTest.php

it('creates a tenant', function () {
    $tenant = create_tenant('tenant-1', ['name' => 'Acme Corp']);

    expect($tenant)
        ->toBeInstanceOf(Tenant::class)
        ->name->toBe('Acme Corp');
});
```

**Tenanted tests** (automatic tenant context):

```php
// tests/Feature/Tenanted/OrderTest.php

it('creates an order', function () {
    // Tenant is automatically initialized via TenantTestCase
    $user = User::factory()->create();

    $response = actingAs($user)
        ->postJson('/orders', [
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
        ]);

    $response->assertCreated();

    // Database assertions are scoped to tenant database
    assertDatabaseHas('orders', [
        'user_id' => $user->id,
    ]);
});
```

### Key Benefits

1. **Automatic tenant database management** - Databases created and cleaned up automatically
2. **Parallel testing support** - Each test suite gets unique tenant database suffix
3. **Directory-based test organization** - Central vs Tenanted tests auto-detected
4. **Transaction handling** - Both central and tenant databases wrapped in transactions
5. **HTTP header injection** - Tenant ID automatically added to API requests

## Common Patterns

### Running Code in Multiple Tenants

```php
use App\Models\Central\Tenant;
use App\Support\TenantContext;

$tenants = Tenant::all();

foreach ($tenants as $tenant) {
    TenantContext::run($tenant, function () use ($tenant) {
        // Runs for each tenant
        Order::where('status', 'pending')->update(['processed' => true]);
    });
}
```

### Accessing Central Data from Tenant Context

```php
use App\Support\TenantContext;

// In tenant context
TenantContext::runCentral(function () {
    // Access central database
    $allTenants = Tenant::all();

    // Do something with central data
});
```

### Conditional Logic Based on Tenant

```php
use App\Support\TenantContext;

if (TenantContext::isActive()) {
    // Tenant-specific logic
    $orders = Order::all(); // Scoped to tenant
} else {
    // Central logic
    $tenants = Tenant::all();
}
```

## Summary

**Multi-tenancy provides:**
1. **Clear separation** - Central vs Tenanted namespaces
2. **Database isolation** - Each tenant has dedicated database
3. **Automatic scoping** - Queries automatically tenant-scoped
4. **Context helpers** - Easy access to tenant context
5. **Queue integration** - Jobs preserve tenant context

**Best practices:**
- Use directory structure to separate central and tenanted code
- Always use TenantContext helper for tenant access
- Test both central and tenant contexts separately
- Preserve tenant context in queued jobs
- Use appropriate middleware for tenant identification

**See also:**
- [actions.md](../../laravel-actions/references/actions.md) - Action organization patterns
- [models.md](../../laravel-models/references/models.md) - Model organization
- [structure.md](../../laravel-architecture/references/structure.md) - Project structure
- [route-binding.md](../../laravel-routing/references/route-binding.md) - Route model binding with tenant scoping

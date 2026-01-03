# Controllers - Complete Guide

Controllers are **extremely thin**. They handle **HTTP concerns only** and contain **zero domain logic**.

**Related guides:**
- [actions.md](../../laravel-actions/references/actions.md) - Actions contain the domain logic
- [form-requests.md](../../laravel-validation/references/form-requests.md) - Validation layer
- [DTOs](../../laravel-dtos/SKILL.md) - DTOs for data transfer
- [structure.md](../../laravel-architecture/references/structure.md) - Web vs API organization
- [testing.md](../../laravel-testing/references/testing.md) - Testing controllers with feature tests (HTTP layer testing)
- [testing-conventions.md](../../laravel-testing/references/testing-conventions.md) - Test file structure matching RESTful order

## Philosophy

**Controllers should ONLY:**
1. Type-hint dependencies
2. Validate (via Form Requests)
3. Transform request to DTO (via Transformers)
4. Call actions
5. Return responses (resources, redirects, views)

**Controllers should NEVER:**
- Contain domain logic
- Make database queries directly
- Perform calculations
- Handle complex business rules

## Controller Naming Conventions

**Controllers should be named using the PLURAL form of the main resource:**

### Standard Resource Controllers

```php
// ✅ CORRECT - Plural resource names
CalendarsController      // manages calendar resources
EventsController         // manages event resources
RecipientsController     // manages recipient resources
OrdersController         // manages order resources
UsersController          // manages user resources
```

```php
// ❌ INCORRECT - Singular form
CalendarController
EventController
RecipientController
```

### Nested Resource Controllers

**For nested resources, combine both resource names (parent + child):**

```php
// Route: /calendars/{calendar}/events
CalendarEventsController  // manages events within a calendar

// Route: /orders/{order}/items
OrderItemsController      // manages items within an order

// Route: /users/{user}/notifications
UserNotificationsController  // manages notifications for a user
```

**Pattern:** `{ParentSingular}{ChildPlural}Controller`

### Real-World Examples

```php
// Standard resources (plural)
app/Http/Web/Controllers/CalendarsController.php
app/Http/Web/Controllers/EventsController.php
app/Http/Web/Controllers/RecipientsController.php

// Nested resources (parent singular + child plural)
app/Http/Web/Controllers/CalendarEventsController.php
app/Http/Web/Controllers/CalendarRecipientsController.php
app/Http/Web/Controllers/EventAttendeesController.php
```

**Routes:**

```php
// Standard resource routes
Route::resource('calendars', CalendarsController::class);
Route::resource('events', EventsController::class);

// Nested resource routes
Route::resource('calendars.events', CalendarEventsController::class);
Route::resource('calendars.recipients', CalendarRecipientsController::class);
```

**Why this matters:**
- **Consistency** - Same naming pattern across your codebase
- **Clarity** - Controller name immediately indicates what it manages
- **REST conventions** - Aligns with RESTful resource naming
- **Laravel conventions** - Matches Laravel's expectations for route model binding

## RESTful Methods Only

Controllers **must only** use Laravel's standard RESTful method names. Custom action methods should be extracted to invokable controllers.

### Standard RESTful Methods

**For web applications (with forms):**
- `index` - Display a listing of the resource
- `create` - Show the form for creating a new resource
- `store` - Store a newly created resource
- `show` - Display the specified resource
- `edit` - Show the form for editing the resource
- `update` - Update the specified resource
- `destroy` - Remove the specified resource

**For APIs (no form views):**
- `index` - List resources
- `show` - Get a specific resource
- `store` - Create a new resource
- `update` - Update a resource
- `destroy` - Delete a resource

**Critical: APIs must NOT include `create` or `edit` methods**

The `create` and `edit` methods are exclusively for displaying HTML forms in traditional web applications. APIs return JSON data and have no need to "show a form", so these methods should never exist in API controllers.

✅ **Correct API Controller:**
```php
namespace App\Http\Api\V1\Controllers;

class OrdersController extends Controller
{
    public function index(OrderIndexQuery $query): AnonymousResourceCollection { }
    public function show(Order $order): OrderResource { }
    public function store(CreateOrderRequest $request): OrderResource { }
    public function update(UpdateOrderRequest $request, Order $order): OrderResource { }
    public function destroy(Order $order): Response { }
}
```

❌ **Incorrect - has create/edit in API:**
```php
namespace App\Http\Api\V1\Controllers;

class OrdersController extends Controller
{
    public function index() { }
    public function create() { }  // ❌ Don't do this in APIs
    public function store() { }
    public function show() { }
    public function edit() { }    // ❌ Don't do this in APIs
    public function update() { }
    public function destroy() { }
}
```

✅ **Correct Web Controller (with forms):**
```php
namespace App\Http\Web\Controllers;

class OrdersController extends Controller
{
    public function index() { }
    public function create() { }  // ✅ Shows create form
    public function store() { }
    public function show() { }
    public function edit() { }    // ✅ Shows edit form
    public function update() { }
    public function destroy() { }
}
```

### Forbidden Method Names

Never use custom method names in resource controllers:

❌ **Incorrect:**
```php
class OrdersController extends Controller
{
    public function all() { }              // Use index
    public function list() { }             // Use index
    public function get(Order $order) { }  // Use show
    public function add() { }              // Use store
    public function save() { }             // Use store or update
    public function remove() { }           // Use destroy
    public function delete() { }           // Use destroy

    // Non-RESTful actions - extract these!
    public function cancel() { }           // Extract to CancelOrderController
    public function approve() { }          // Extract to ApproveOrderController
    public function duplicate() { }        // Extract to DuplicateOrderController
    public function export() { }           // Extract to ExportOrdersController
    public function import() { }           // Extract to ImportOrdersController
}
```

### Non-RESTful Actions: Extract to Invokable Controllers

If you need an endpoint that doesn't fit the standard RESTful methods, **extract it to its own invokable controller**. Name the controller as a verb describing the action.

✅ **Correct - Separate invokable controllers:**
```php
// app/Http/Api/V1/Controllers/CancelOrderController.php
namespace App\Http\Api\V1\Controllers;

class CancelOrderController extends Controller
{
    public function __invoke(
        Order $order,
        CancelOrderAction $action
    ): OrderResource {
        $order = $action($order);
        return OrderResource::make($order);
    }
}

// app/Http/Api/V1/Controllers/ApproveOrderController.php
class ApproveOrderController extends Controller
{
    public function __invoke(
        Order $order,
        ApproveOrderAction $action
    ): OrderResource {
        $order = $action($order);
        return OrderResource::make($order);
    }
}

// app/Http/Api/V1/Controllers/DuplicateOrderController.php
class DuplicateOrderController extends Controller
{
    public function __invoke(
        Order $order,
        DuplicateOrderAction $action
    ): OrderResource {
        $order = $action($order);
        return OrderResource::make($order);
    }
}

// app/Http/Api/V1/Controllers/ExportOrdersController.php
class ExportOrdersController extends Controller
{
    public function __invoke(
        OrderIndexQuery $query,
        ExportOrdersAction $action
    ): BinaryFileResponse {
        return $action($query->get());
    }
}
```

**Routes:**
```php
// Resourceful routes
Route::apiResource('orders', OrdersController::class);

// Custom action routes
Route::post('/orders/{order:uuid}/cancel', CancelOrderController::class);
Route::post('/orders/{order:uuid}/approve', ApproveOrderController::class);
Route::post('/orders/{order:uuid}/duplicate', DuplicateOrderController::class);
Route::post('/orders/export', ExportOrdersController::class);
```

**Why invokable controllers for non-RESTful actions?**

1. **Single Responsibility Principle** - Each controller does exactly one thing
2. **Clear Intent** - The controller name explicitly describes what it does (`CancelOrderController` vs `OrdersController::cancel()`)
3. **Better Organization** - Easier to find specific functionality
4. **Consistent Patterns** - Follows Laravel conventions for single-action controllers
5. **Testability** - Each action is independently testable
6. **Maintainability** - Prevents resource controllers from becoming bloated with custom methods

## Web Layer vs Public API

### Web Layer Controllers

**Purpose:** Serve your application's web layer (API for separate frontend, Blade views, or Inertia)

**Location:** `app/Http/Web/Controllers/`

**Routes:** `routes/web.php`

**Characteristics:**
- Not versioned
- Can change freely
- Private (only your app consumes)

**Example: JSON API for separate frontend (Nuxt, React, Vue)**

```php
<?php

declare(strict_types=1);

namespace App\Http\Web\Controllers;

use App\Actions\Order\CreateOrderAction;
use App\Actions\Order\DeleteOrderAction;
use App\Actions\Order\UpdateOrderAction;
use App\Data\Transformers\Web\OrderDataTransformer;
use App\Http\Controllers\Controller;
use App\Http\Web\Queries\OrderIndexQuery;
use App\Http\Web\Requests\CreateOrderRequest;
use App\Http\Web\Requests\UpdateOrderRequest;
use App\Http\Web\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    public function index(OrderIndexQuery $query): AnonymousResourceCollection
    {
        return OrderResource::collection($query->jsonPaginate());
    }

    public function show(Order $order): OrderResource
    {
        return OrderResource::make($order->load('items', 'customer'));
    }

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

    public function update(
        UpdateOrderRequest $request,
        Order $order,
        UpdateOrderAction $action
    ): OrderResource {
        $order = $action(
            $order,
            OrderDataTransformer::fromRequest($request)
        );

        return OrderResource::make($order);
    }

    public function destroy(
        Order $order,
        DeleteOrderAction $action
    ): Response {
        $action($order);

        return response()->noContent();
    }
}
```

### Public API Controllers (Versioned)

**Purpose:** For external/third-party consumption

**Location:** `app/Http/Api/V1/Controllers/`

**Routes:** `routes/api/v1.php`, `routes/api/v2.php`

**Characteristics:**
- Versioned (`/api/v1`, `/api/v2`)
- Stable contract
- Breaking changes require new version
- Fully documented

```php
<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Actions\Order\CreateOrderAction;
use App\Actions\Order\DeleteOrderAction;
use App\Actions\Order\UpdateOrderAction;
use App\Data\Transformers\Api\V1\OrderDataTransformer;
use App\Http\Api\V1\Queries\OrderIndexQuery;
use App\Http\Api\V1\Requests\CreateOrderRequest;
use App\Http\Api\V1\Requests\UpdateOrderRequest;
use App\Http\Api\V1\Resources\OrderResource;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    public function index(OrderIndexQuery $query): AnonymousResourceCollection
    {
        return OrderResource::collection($query->jsonPaginate());
    }

    public function show(Order $order): OrderResource
    {
        return OrderResource::make($order->load('items', 'customer'));
    }

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

    public function update(
        UpdateOrderRequest $request,
        Order $order,
        UpdateOrderAction $action
    ): OrderResource {
        $order = $action(
            $order,
            OrderDataTransformer::fromRequest($request)
        );

        return OrderResource::make($order);
    }

    public function destroy(
        Order $order,
        DeleteOrderAction $action
    ): Response {
        $action($order);

        return response()->noContent();
    }
}
```

**Key differences:**
- **Namespace**: `Http\Web` vs `Http\Api\V1`
- **Stability**: Web can evolve freely, API is versioned and stable
- **Purpose**: Web serves your app, API serves external consumers

## Invokable Controllers

**For single-action controllers:**

```php
<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Actions\Order\CancelOrderAction;
use App\Http\Api\V1\Resources\OrderResource;
use App\Http\Controllers\Controller;
use App\Models\Order;

class CancelOrderController extends Controller
{
    public function __invoke(
        Order $order,
        CancelOrderAction $action
    ): OrderResource {
        $order = $action($order);

        return OrderResource::make($order);
    }
}
```

**When to use:**
- Single responsibility endpoint
- Non-standard REST action
- Cleaner than adding to resource controller

**Examples:**
- `CancelOrderController`
- `ApproveApplicationController`
- `ExportUsersController`

## Query Objects

**Extract complex queries** to dedicated classes:

```php
<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Queries;

use App\Builders\OrderBuilder;
use App\Models\Order;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class OrderIndexQuery extends QueryBuilder
{
    public function __construct()
    {
        $query = Order::query()
            ->with(['customer', 'items']);

        parent::__construct($query);

        $this
            ->defaultSort('-created_at')
            ->allowedSorts([
                AllowedSort::field('id'),
                AllowedSort::field('total'),
                AllowedSort::field('created_at'),
            ])
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::callback('search', function (OrderBuilder $query, $value): void {
                    $query->where(function (OrderBuilder $query) use ($value): void {
                        $query
                            ->where('id', $value)
                            ->orWhere('order_number', $value)
                            ->orWhereHas('customer', fn ($q) => $q->whereLike('email', "%{$value}%"));
                    });
                }),
            ])
            ->allowedIncludes(['customer', 'items', 'shipments']);
    }
}
```

**Usage in controller:**

```php
public function index(OrderIndexQuery $query): AnonymousResourceCollection
{
    return OrderResource::collection($query->jsonPaginate());
}
```

**Benefits:**
- Query logic separated from controller
- Reusable across endpoints
- Easy to test
- Type-safe with custom builders

## Controller Patterns

### Standard CRUD

```php
class OrderController extends Controller
{
    public function index(OrderIndexQuery $query)
    {
        return OrderResource::collection($query->jsonPaginate());
    }

    public function show(Order $order)
    {
        return OrderResource::make($order);
    }

    public function store(CreateOrderRequest $request, CreateOrderAction $action)
    {
        $order = $action(user(), OrderDataTransformer::fromRequest($request));
        return OrderResource::make($order);
    }

    public function update(UpdateOrderRequest $request, Order $order, UpdateOrderAction $action)
    {
        $order = $action($order, OrderDataTransformer::fromRequest($request));
        return OrderResource::make($order);
    }

    public function destroy(Order $order, DeleteOrderAction $action)
    {
        $action($order);
        return response()->noContent();
    }
}
```

### With Authorization

```php
class OrderController extends Controller
{
    public function store(
        CreateOrderRequest $request,
        CreateOrderAction $action
    ): OrderResource {
        $this->authorize('create', Order::class);

        $order = $action(user(), OrderDataTransformer::fromRequest($request));

        return OrderResource::make($order);
    }
}
```

**Or use route middleware:**

```php
Route::post('/orders', [OrderController::class, 'store'])
    ->can('create', Order::class);
```

### With Manual Validation

**Avoid - use Form Requests instead:**

```php
// ❌ Bad
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required',
        'email' => 'required|email',
    ]);
}

// ✅ Good
public function store(CreateOrderRequest $request)
{
    // Validation already done
}
```

## Response Types

### JSON Resource

```php
public function show(Order $order): OrderResource
{
    return OrderResource::make($order);
}
```

### Collection Resource

```php
public function index(OrderIndexQuery $query): AnonymousResourceCollection
{
    return OrderResource::collection($query->jsonPaginate());
}
```

### 201 Created

```php
public function store(CreateOrderRequest $request, CreateOrderAction $action): OrderResource
{
    $order = $action(user(), OrderDataTransformer::fromRequest($request));

    return OrderResource::make($order)->response()->setStatusCode(201);
}
```

### 204 No Content

```php
public function destroy(Order $order, DeleteOrderAction $action): Response
{
    $action($order);

    return response()->noContent();
}
```

### Redirect

```php
public function store(CreateOrderRequest $request, CreateOrderAction $action): RedirectResponse
{
    $order = $action(user(), OrderDataTransformer::fromRequest($request));

    return redirect()->route('orders.show', $order);
}
```

## Route Model Binding

**Use route model binding** for cleaner controllers:

```php
Route::get('/orders/{order}', [OrderController::class, 'show']);
```

**Controller automatically receives model:**

```php
public function show(Order $order): OrderResource
{
    return OrderResource::make($order);
}
```

**Custom binding key:**

```php
Route::get('/orders/{order:uuid}', [OrderController::class, 'show']);
```

**With relationships:**

```php
public function show(Order $order): OrderResource
{
    return OrderResource::make($order->load('items', 'customer'));
}
```

## Controller Testing

**Feature tests for controllers:**

```php
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

it('creates an order', function () {
    $user = User::factory()->create();
    $data = CreateOrderData::testFactory()->make();

    actingAs($user)
        ->postJson('/orders', $data->toArray())
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'status']]);
});

it('requires authentication', function () {
    postJson('/orders', [])->assertUnauthorized();
});

it('validates required fields', function () {
    actingAs(User::factory()->create())
        ->postJson('/orders', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['customer_email', 'items']);
});
```

## Common Mistakes to Avoid

### ❌ Domain Logic in Controller

```php
// BAD
public function store(Request $request)
{
    $order = Order::create($request->validated());
    $order->items()->createMany($request->items);
    $total = $order->items->sum('total');
    $order->update(['total' => $total]);

    // More logic...
}
```

### ✅ Delegate to Action

```php
// GOOD
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
```

### ❌ Database Queries in Controller

```php
// BAD
public function index()
{
    $orders = Order::with('items')
        ->where('status', 'pending')
        ->latest()
        ->paginate();
}
```

### ✅ Use Query Object

```php
// GOOD
public function index(OrderIndexQuery $query): AnonymousResourceCollection
{
    return OrderResource::collection($query->jsonPaginate());
}
```

## Summary

**Controllers are HTTP adapters:**
1. Receive HTTP request
2. Validate via Form Request
3. Transform to DTO via Transformer
4. Call Action with DTO
5. Return HTTP response via Resource

**Every line of domain logic belongs in an Action, not a Controller.**

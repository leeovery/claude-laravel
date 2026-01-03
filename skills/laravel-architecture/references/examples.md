# Request Flow Example

A complete vertical slice showing how a request flows through the system.

**See [patterns.md](patterns.md) for the conceptual data flow diagram.**

## 1. Form Request (Validation)

`app/Http/Web/Requests/CreateOrderRequest.php`
```php
<?php

declare(strict_types=1);

namespace App\Http\Web\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_email' => [
                'required',
                'string',
                'email',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id'),
            ],
        ];
    }
}
```

## 2. Transformer (Request → DTO)

`app/Data/Transformers/Web/OrderDataTransformer.php`
```php
<?php

declare(strict_types=1);

namespace App\Data\Transformers\Web;

use App\Data\CreateOrderData;
use App\Data\OrderItemData;
use App\Enums\OrderStatus;
use App\Http\Web\Requests\CreateOrderRequest;

class OrderDataTransformer
{
    public static function fromRequest(CreateOrderRequest $request): CreateOrderData
    {
        return CreateOrderData::from([
            'customerEmail' => $request->input('customer_email'),
            'notes' => $request->input('notes'),
            'status' => OrderStatus::from($request->input('status')),
            'items' => OrderItemData::collect($request->input('items')),
        ]);
    }
}
```

## 3. DTO (Typed Data)

`app/Data/CreateOrderData.php`
```php
<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\OrderStatus;
use Illuminate\Support\Collection;

class CreateOrderData extends Data
{
    public function __construct(
        public string $customerEmail,
        public ?string $notes,
        public OrderStatus $status,
        /** @var Collection<int, OrderItemData> */
        public Collection $items,
    ) {}
}
```

## 4. Controller (Thin Orchestration)

`app/Http/Web/Controllers/OrderController.php`
```php
<?php

declare(strict_types=1);

namespace App\Http\Web\Controllers;

use App\Actions\Order\CreateOrderAction;
use App\Data\Transformers\Web\OrderDataTransformer;
use App\Http\Controllers\Controller;
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

## 5. Action (Domain Logic)

`app/Actions/Order/CreateOrderAction.php`
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
            $order = $user->orders()->create([
                'status' => $data->status,
                'notes' => $data->notes,
            ]);

            $order->items()->createMany(
                $data->items->map(fn ($item) => [
                    'product_id' => $item->productId,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ])->all()
            );

            return $order->fresh(['items']);
        });
    }
}
```

## 6. Test (Feature Test)

`tests/Feature/Web/OrderControllerTest.php`
```php
<?php

declare(strict_types=1);

use App\Data\CreateOrderData;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('creates an order', function () {
    $user = User::factory()->create();
    $data = CreateOrderData::testFactory()->make();

    actingAs($user)
        ->postJson('/orders', $data->toArray())
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'status']]);
});
```

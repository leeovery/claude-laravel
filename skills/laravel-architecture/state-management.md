# State Management

Use **Spatie Model States** for complex state management with type-safe transitions.

**Related guides:**
- [models.md](models.md) - Model integration
- [enums.md](enums.md) - Simple state without transitions use enums
- [packages.md](packages.md) - Installing Spatie Model States

## When to Use State Machines

**Use state machines when:**
- Complex state transitions with rules
- State-specific behavior needed
- Transition history required
- Guards/validations per transition
- Side effects during transitions

**Use simple enums when:**
- Simple status fields
- No transition logic
- No side effects

See [enums.md](enums.md) for simple state management.

## State Class Hierarchy

```php
<?php

declare(strict_types=1);

namespace App\States\Order;

use App\States\Order\Transitions\ToCancelled;
use App\States\Order\Transitions\ToCompleted;
use App\States\Order\Transitions\ToProcessing;
use Spatie\ModelStates\Attributes\AllowTransition;
use Spatie\ModelStates\Attributes\DefaultState;
use Spatie\ModelStates\State;

#[
    AllowTransition(OrderPending::class, OrderProcessing::class, ToProcessing::class),
    AllowTransition(OrderProcessing::class, OrderCompleted::class, ToCompleted::class),
    AllowTransition(OrderProcessing::class, OrderCancelled::class, ToCancelled::class),
    AllowTransition([OrderPending::class, OrderProcessing::class], OrderCancelled::class, ToCancelled::class),
]
#[DefaultState(OrderPending::class)]
abstract class OrderState extends State
{
    abstract public function color(): string;
    abstract public function canBeModified(): bool;
    abstract public function canBeCancelled(): bool;
}
```

## Individual State Classes

```php
<?php

declare(strict_types=1);

namespace App\States\Order;

final class OrderPending extends OrderState
{
    public static string $name = 'pending';

    public function color(): string
    {
        return 'gray';
    }

    public function canBeModified(): bool
    {
        return true;
    }

    public function canBeCancelled(): bool
    {
        return true;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\States\Order;

final class OrderCompleted extends OrderState
{
    public static string $name = 'completed';

    public function color(): string
    {
        return 'green';
    }

    public function canBeModified(): bool
    {
        return false;
    }

    public function canBeCancelled(): bool
    {
        return false;
    }
}
```

## Transition Classes

Transitions handle side effects during state changes:

```php
<?php

declare(strict_types=1);

namespace App\States\Order\Transitions;

use App\Events\OrderCompleted as OrderCompletedEvent;
use App\Models\Order;
use App\States\Order\OrderCompleted;
use Spatie\ModelStates\Transition;

class ToCompleted extends Transition
{
    public function __construct(public Order $order) {}

    public function handle(): Order
    {
        $this->order->state = new OrderCompleted($this->order);
        $this->order->completed_at = now();
        $this->order->save();

        OrderCompletedEvent::dispatch($this->order);

        return $this->order;
    }
}
```

## Model Integration

```php
use Spatie\ModelStates\HasStates;

class Order extends Model
{
    use HasStates;

    // Wrap transitions in methods
    public function markAsCompleted(): self
    {
        return $this->state->transitionTo(OrderCompleted::class);
    }

    public function markAsCancelled(): self
    {
        return $this->state->transitionTo(OrderCancelled::class);
    }

    // Helper methods for checking state
    public function isPending(): bool
    {
        return $this->state->equals(OrderPending::class);
    }

    public function canBeCancelled(): bool
    {
        return $this->state->canTransitionTo(OrderCancelled::class);
    }

    protected function casts(): array
    {
        return [
            'state' => OrderState::class,
            // ...
        ];
    }
}
```

## Query Builders with States

```php
use App\States\Order\OrderPending;

/**
 * @method static OrderBuilder whereState(string $column, string|array $state)
 * @method static OrderBuilder whereNotState(string $column, string|array $state)
 */
class OrderBuilder extends Builder
{
    public function wherePending(): self
    {
        return $this->whereState('state', OrderPending::class);
    }

    public function whereCompleted(): self
    {
        return $this->whereState('state', OrderCompleted::class);
    }
}
```

## Usage Examples

```php
// Transition with side effects
$order->markAsCompleted();

// Check if transition is allowed
if ($order->canBeCancelled()) {
    $order->markAsCancelled();
}

// Check current state
if ($order->isPending()) {
    // ...
}

// State-specific behavior
$color = $order->state->color();
$canModify = $order->state->canBeModified();
```

## Key Principles

1. **Define allowed transitions** using attributes on base state class
2. **Create transition classes** for each state change that needs side effects
3. **Dispatch events** in transition handlers
4. **Wrap transitions** in model methods for clean API
5. **Update related timestamps** in transition handlers
6. **Abstract methods** for state-specific behavior

## Directory Structure

```
app/States/
└── Order/
    ├── OrderState.php           # Base state class
    ├── OrderPending.php         # Concrete states
    ├── OrderProcessing.php
    ├── OrderCompleted.php
    ├── OrderCancelled.php
    └── Transitions/
        ├── ToProcessing.php
        ├── ToCompleted.php
        └── ToCancelled.php
```

## Summary

**State machines provide:**
- Type-safe state transitions
- Transition validation
- Side effects during transitions
- State-specific behavior
- Clear state transition rules

**Use for complex states.** For simple statuses, use [enums.md](enums.md).

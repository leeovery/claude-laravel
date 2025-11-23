---
name: laravel-dtos
description: Data Transfer Objects using Spatie Laravel Data. Use when handling data transfer, API requests/responses, or when user mentions DTOs, data objects, Spatie Data, formatters, transformers, or structured data handling.
---

# Laravel DTOs

Data Transfer Objects: never pass primitives, always use structured DTOs with Spatie Laravel Data.

## Core Concepts

**[dtos.md](references/dtos.md)** - Complete DTO guide:
- Spatie Data basics
- Request vs response DTOs (dual DTO pattern)
- Formatters and transformers
- Test factories
- Validation integration

**[dto-factories.md](references/dto-factories.md)** - DTO Factory pattern:
- Transform external data into internal DTOs
- Named constructors
- Field mapping and enum transformations
- Nested DTOs
- Safe data access

## Pattern

```php
use Spatie\LaravelData\Data;

final class CreateOrderDto extends Data
{
    public function __construct(
        public int $userId,
        public array $items,
        public ?string $notes = null,
    ) {}
}
```

Use DTOs for:
- Action parameters
- API requests/responses
- Service communication
- Form request transformation

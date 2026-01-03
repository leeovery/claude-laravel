# Directory Structure

Complete project organization following action-oriented architecture.

**Related guides:**
- [actions.md](../../laravel-actions/references/actions.md) - Action pattern and organization
- [DTOs](../../laravel-dtos/SKILL.md) - DTO structure and transformers
- [controllers.md](../../laravel-controllers/references/controllers.md) - HTTP layer organization
- [models.md](../../laravel-models/references/models.md) - Model structure and custom builders
- [multi-tenancy.md](../../laravel-multi-tenancy/references/multi-tenancy.md) - Multi-tenant directory organization
- [validation-testing.md](../../laravel-validation/references/validation-testing.md) - Test datasets for validation testing

## Full Structure Created

```
app/
├── Actions/              # Business logic (invokable classes) - See actions.md
│   ├── Auth/
│   ├── User/
│   └── Order/
├── Booters/              # Bootstrap configuration classes
├── Builders/             # Custom Eloquent query builders - See models.md
│   └── Concerns/         # Reusable builder traits
├── Data/                 # Data Transfer Objects (Spatie Data) - See laravel-dtos SKILL.md
│   ├── Concerns/         # DTO traits (e.g., HasTestFactory)
│   ├── Factories/        # DTO test factories
│   ├── Formatters/       # Value formatting utilities
│   └── Transformers/     # Request-to-DTO converters
│       ├── Web/
│       └── Api/V1/
├── Enums/                # Backed enums with attributes
│   ├── Attributes/
│   └── Concerns/
├── Exceptions/
│   └── Concerns/
├── Http/
│   ├── Web/              # Private API/Blade layer (not versioned) - See controllers.md
│   │   ├── Controllers/
│   │   ├── Queries/
│   │   ├── Requests/    # Form Requests - See form-requests.md
│   │   └── Resources/
│   ├── Api/V1/           # Public API v1 (optional) - See controllers.md
│   │   ├── Controllers/
│   │   ├── Queries/
│   │   ├── Requests/    # Form Requests - See form-requests.md
│   │   └── Resources/
│   ├── Middleware/
│   └── Controllers/      # Base controller classes
├── Jobs/
├── Listeners/
├── Models/
│   ├── Concerns/
│   └── Contracts/
├── Policies/
├── Rules/
├── Services/             # External service integrations
│   └── [ServiceName]/
│       ├── [ServiceName]Manager.php
│       ├── Connectors/   # Saloon connectors
│       ├── Contracts/
│       ├── Drivers/
│       ├── Exceptions/
│       └── Requests/
├── States/               # State machines (Spatie Model States)
│   └── [Model]/
│       └── Transitions/
├── Support/
├── Values/               # Value objects
└── helpers.php

tests/
├── Architecture/         # Pest architecture tests
├── Concerns/             # Reusable test traits (Makeable, etc.)
├── Datasets/             # Pest datasets for validation testing - See validation-testing.md
├── Feature/
│   ├── Api/
│   └── Web/
└── Unit/
    ├── Actions/
    ├── Data/
    └── Models/
```

## Minimal Structure

For minimal setup, only create:
- `Actions/`
- `Data/` (with Concerns, Formatters, Transformers/Web)
- `Http/Web/` (Controllers, Requests, Resources)
- Base `Data` class
- Helper functions

## Bootstrap Structure

```
bootstrap/
└── app.php              # Updated with Booters

app/Booters/
├── ExceptionBooter.php
├── MiddlewareBooter.php
└── ScheduleBooter.php
```

## Routes Structure

```
routes/
├── web.php              # Always created (Web layer)
├── console.php
└── api/                 # Optional (Public API)
    ├── v1.php
    └── v2.php
```

---
name: laravel-multi-tenancy
description: Multi-tenant application architecture patterns. Use when working with multi-tenant systems, tenant isolation, or when user mentions multi-tenancy, tenants, tenant scoping, tenant isolation, multi-tenant.
---

# Laravel Multi-Tenancy

Multi-tenant architecture with Central and Tenanted organization.

## Core Concept

**[multi-tenancy.md](references/multi-tenancy.md)** - Multi-tenancy patterns:
- Central vs Tenanted directory structure
- Tenant context helpers
- Route configuration for tenants
- Database isolation strategies
- Tenant scoping in models
- Queue integration
- Testing multi-tenant features

## Pattern

```php
// Directory structure
Domain/
├── Central/
│   ├── Models/User.php
│   ├── Actions/CreateUserAction.php
│   └── Controllers/UserController.php
└── Tenanted/
    ├── Models/Order.php
    ├── Actions/CreateOrderAction.php
    └── Controllers/OrderController.php

// Tenant scoping
final class Order extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $builder->where('tenant_id', tenant()->id);
        });
    }
}

// Tenant helper
function tenant(): Tenant
{
    return app(TenantContext::class)->current();
}
```

Separate Central (user management, billing) from Tenanted (tenant-specific data) concerns.

---
name: laravel-services
description: Service layer for external API integration using manager pattern and Saloon. Use when working with external APIs, third-party services, or when user mentions services, external API, Saloon, API integration, manager pattern.
---

# Laravel Services

Service layer architecture for external API integration.

## Core Concept

**[services.md](references/services.md)** - Service patterns:
- Manager pattern for multi-driver services
- Saloon for HTTP API integration
- Driver contracts and implementations
- Null driver pattern for testing
- Facade creation
- Service provider registration

## Pattern

```php
// Manager
final class PaymentManager extends Manager
{
    public function createStripeDriver(): StripePaymentDriver
    {
        return new StripePaymentDriver(
            config('services.stripe.key')
        );
    }

    public function getDefaultDriver(): string
    {
        return config('services.payment.default');
    }
}

// Driver contract
interface PaymentDriver
{
    public function charge(Money $amount, string $token): PaymentResult;
}

// Null driver for testing
final class NullPaymentDriver implements PaymentDriver
{
    public function charge(Money $amount, string $token): PaymentResult
    {
        return PaymentResult::success('null-'.Str::random());
    }
}
```

Use Saloon for HTTP API clients. Use null drivers in tests to avoid external calls.

---
name: laravel-exceptions
description: Custom exceptions with static factories and HTTP responses. Use when working with error handling, custom exceptions, or when user mentions exceptions, custom exception, error handling, HTTP exceptions.
---

# Laravel Exceptions

Custom exceptions with static factory methods and HTTP integration.

## Core Concept

**[exceptions.md](references/exceptions.md)** - Exception patterns:
- Static factory methods for creation
- HTTP status code integration
- Descriptive error messages
- HttpExceptionInterface for HTTP responses
- When to throw vs return results

## Pattern

```php
final class InsufficientInventoryException extends Exception implements HttpExceptionInterface
{
    public static function forProduct(Product $product, int $requested): self
    {
        return new self(
            "Insufficient inventory for product {$product->name}. ".
            "Requested: {$requested}, Available: {$product->stock}"
        );
    }

    public function getStatusCode(): int
    {
        return 422;
    }

    public function getHeaders(): array
    {
        return [];
    }
}

// Usage
throw InsufficientInventoryException::forProduct($product, $quantity);
```

Use static factories for context-rich exception creation. Implement HttpExceptionInterface for automatic HTTP responses.

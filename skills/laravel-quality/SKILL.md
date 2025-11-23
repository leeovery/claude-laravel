---
name: laravel-quality
description: Code quality tooling with PHPStan, Pint, and strict types. Use when working with code quality, static analysis, formatting, or when user mentions PHPStan, Pint, quality, static analysis, type safety, code style, linting.
---

# Laravel Quality

Code quality assurance: PHPStan for static analysis, Pint for formatting, strict types everywhere.

## Core Concepts

**[quality.md](references/quality.md)** - Quality tools:
- PHPStan configuration and levels
- Architecture tests with Pest
- CI/CD integration
- Enforcing standards

**[code-style.md](references/code-style.md)** - Code style:
- Laravel Pint configuration
- Declarative coding style
- File structure conventions
- Composer scripts for quality checks

**[type-safety.md](references/type-safety.md)** - Type safety:
- Mandatory `declare(strict_types=1)`
- Parameter and return type hints
- Nullable types and union types
- PHPDoc for complex types

## Quality Stack

```bash
# composer.json scripts
{
    "test": "pest",
    "analyse": "phpstan analyse",
    "format": "pint",
    "quality": [
        "@analyse",
        "@test"
    ]
}
```

All files must have `declare(strict_types=1)` at top. Run quality checks before every commit.

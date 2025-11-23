---
name: laravel-packages
description: Package development and extraction of reusable code. Use when working with package development, code reusability, or when user mentions packages, composer packages, extract package, reusable code, package development.
---

# Laravel Packages

Package development: extracting reusable patterns for use across projects.

## Core Concepts

**[package-extraction.md](references/package-extraction.md)** - Package extraction:
- When to extract (three project rule)
- Package structure and organization
- Service provider patterns
- Versioning with semantic versioning
- Distribution (private vs public)
- Testing packages

**[packages.md](references/packages.md)** - Package ecosystem:
- Core packages (Spatie Data, Model States, Query Builder, Saloon, Pest)
- When to use each package
- Integration patterns

## When to Extract

Extract to package when:
1. Pattern used in 3+ projects
2. Code is stable and well-tested
3. Pattern has clear boundaries
4. Maintenance cost justified

## Pattern

```
my-package/
├── src/
│   ├── PackageServiceProvider.php
│   ├── Actions/
│   ├── DTOs/
│   └── ...
├── tests/
├── composer.json
└── README.md
```

Use semantic versioning. Test packages independently. Document clearly.

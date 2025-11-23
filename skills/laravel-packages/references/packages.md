# Packages Installed

Essential packages used in the Laravel architecture.

**Related guides:**
- [dtos.md](../../laravel-dtos/references/dtos.md) - Using Spatie Laravel Data
- [models.md](../../laravel-models/references/models.md) - Using Spatie Model States and Query Builder
- [quality.md](../../laravel-quality/references/quality.md) - Using Pest for testing

## Core Packages (Always)

### Spatie Laravel Data
```bash
composer require spatie/laravel-data
```
- DTOs with casting, validation, transformers
- Test factory support

### Spatie Model States
```bash
composer require spatie/laravel-model-states
```
- State machine pattern
- State transitions with dedicated classes

### Spatie Query Builder
```bash
composer require spatie/laravel-query-builder
```
- Filter, sort, include relations via query strings
- API-friendly querying

### Saloon
```bash
composer require saloonphp/saloon saloonphp/laravel-plugin
```
- Elegant API client builder
- Testable external service integrations

### Pest
```bash
composer require pestphp/pest pestphp/pest-plugin-laravel --dev
```
- Expressive testing framework
- Architecture tests

## Optional Packages

### Laravel Sanctum
```bash
composer require laravel/sanctum
```
**When:** API authentication needed

### Stancl Tenancy
```bash
composer require stancl/tenancy
```
**When:** Multi-tenant application

### Spatie Settings
```bash
composer require spatie/laravel-settings
```
**When:** Application-level settings needed

## Installation Commands

### Full Install
```bash
composer require \
  spatie/laravel-data \
  spatie/laravel-model-states \
  spatie/laravel-query-builder \
  saloonphp/saloon \
  saloonphp/laravel-plugin

composer require \
  pestphp/pest \
  pestphp/pest-plugin-laravel \
  --dev

./vendor/bin/pest --init
```

### Minimal Install
```bash
composer require spatie/laravel-data
composer require pestphp/pest pestphp/pest-plugin-laravel --dev
./vendor/bin/pest --init
```

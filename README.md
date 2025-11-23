# Claude Laravel Plugin

Comprehensive Laravel skills for Claude Code - expert guidance for building modern, well-architected Laravel applications using action-oriented patterns, strict typing, and enterprise best practices.

## Installation

```bash
composer require --dev leeovery/claude-laravel
```

The plugin is automatically installed via the Claude Manager. Skills are symlinked to `.claude/skills/`.

## What's Included

A modular collection of specialized Laravel skills covering architecture, patterns, and best practices:

### Foundation & Architecture

**laravel-architecture** - High-level architecture decisions, patterns, and project structure
- Foundational principles and philosophy
- Pattern selection guidance
- Decision flowcharts
- Project structure conventions

**laravel-quality** - Code quality tooling with PHPStan, Pint, and strict types
- PHPStan configuration and static analysis
- Laravel Pint for code formatting
- Architecture testing with Pest
- Type safety enforcement

### Core Patterns

**laravel-actions** - Action-oriented architecture for domain logic
- Invokable classes with `__invoke()` method
- Guard methods for validation
- Context storage patterns
- When to create actions

**laravel-dtos** - Data Transfer Objects using Spatie Laravel Data
- Request vs response DTOs (dual DTO pattern)
- Formatters and transformers
- DTO factories for external data
- Test factories

**laravel-enums** - Native PHP enums for type-safe values
- Basic and backed enums
- Enum methods and traits
- Integration with DTOs and models

**laravel-value-objects** - Domain value objects for complex values
- Immutable value objects
- Validation and equality
- Integration patterns

**laravel-state-machines** - Complex state management with Spatie Model States
- State classes and transitions
- Transition guards and side effects
- State-specific behavior

### HTTP & API Layer

**laravel-controllers** - Thin HTTP layer patterns
- Single-action controllers
- Controller responsibility
- Proper HTTP concerns

**laravel-validation** - Form requests and validation testing
- Form request patterns
- Validation rules
- Testing validation logic
- Custom validation macros

**laravel-routing** - Route model binding and permissions
- Custom route binding
- Route-level permissions
- Resource routing patterns

### Data Layer

**laravel-models** - Eloquent models and relationships
- Model organization
- Relationship patterns
- Casts and accessors

**laravel-query-builders** - Type-safe custom query builders
- Custom builder classes
- Chainable query methods
- Query scoping patterns

### Infrastructure

**laravel-providers** - Service providers and application bootstrapping
- Service provider patterns
- Bootstrap/booters
- Environment configuration
- Helper registration

**laravel-jobs** - Background processing with jobs and listeners
- Queue job patterns
- Event listeners
- Job chaining
- Error handling

**laravel-policies** - Authorization and policies
- Policy organization
- Gate definitions
- Resource authorization

**laravel-services** - External API integration
- Service classes with Saloon
- API client patterns
- Error handling

**laravel-exceptions** - Exception handling patterns
- Custom exceptions
- Exception rendering
- Error responses

### Enterprise Features

**laravel-multi-tenancy** - Multi-tenancy patterns
- Tenant identification
- Database separation
- Tenant-scoped queries

**laravel-packages** - Package extraction and organization
- When to extract packages
- Package structure
- Package development workflow

### Testing

**laravel-testing** - Comprehensive testing with Pest
- Arrange-Act-Assert pattern
- Testing actions in isolation
- Feature vs unit tests
- Null driver pattern
- Factory usage

## Usage

Once installed, all skills are automatically available in Claude Code. Claude will intelligently select and reference the appropriate skills when working on Laravel projects based on the context of your code and questions.

## Requirements

- PHP ^8.2
- leeovery/claude-manager ^1.0

## License

MIT

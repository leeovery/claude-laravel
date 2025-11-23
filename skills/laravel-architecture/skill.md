---
name: Laravel Architecture Guide
description: Expert guidance for action-oriented Laravel architecture with strict typing and enterprise patterns
triggers:
  - laravel
  - architecture
  - action
  - actions
  - dto
  - dtos
  - data transfer object
  - form request
  - form requests
  - validation
  - validate
  - query builder
  - custom query builder
  - query builders
  - controller
  - controllers
  - model
  - models
  - eloquent
  - multi-tenancy
  - multi-tenant
  - tenant
  - package
  - packages
  - service provider
  - service providers
  - spatie
  - spatie query builder
  - spatie model states
  - laravel data
  - model states
  - pest plugin
  - saloonphp
  - sanctum
  - composer
  - composer require
  - package development
  - private package
  - packagist
  - state machine
  - state machines
  - enum
  - enums
  - backed enum
  - value object
  - value objects
  - policy
  - policies
  - authorization
  - job
  - jobs
  - listener
  - listeners
  - event
  - events
  - spatie data
  - saloon
  - api integration
  - external api
  - service layer
  - manager pattern
  - invokable
  - strict types
  - type safety
  - type hints
  - guard method
  - guard methods
  - route binding
  - route model binding
  - routing
  - routes
  - middleware
  - exception
  - exceptions
  - custom exception
  - http exception
  - booter
  - booters
  - bootstrap
  - refactor
  - refactoring
  - code review
  - clean code
  - best practices
  - enterprise patterns
  - domain logic
  - business logic
  - separation of concerns
  - single responsibility
  - thin controller
  - fat action
  - composability
  - reusability
  - testability
  - phpstan
  - pest
  - pint
  - laravel pint
  - code style
  - quality
  - quality assurance
  - architecture test
  - arch test
  - testing
  - test
  - unit test
  - feature test
  - integration test
  - test factory
  - test factories
  - dataset
  - datasets
  - pest dataset
  - request testing
  - validation testing
  - model testing
  - action testing
  - test case
  - test coverage
  - phpunit
  - static analysis
  - code analysis
  - linting
  - linter
  - formatting
  - code formatting
  - ci/cd
  - continuous integration
  - github actions
  - gitlab ci
  - conditional validation
  - nested validation
  - request data
  - response data
  - formatters
  - transformers
  - factories
  - factory
  - dto factory
  - data factory
  - casts
  - query object
  - query objects
  - scope
  - scopes
  - relationship
  - relationships
  - observer
  - observers
  - tenant scope
  - tenant scoping
  - database isolation
  - queue
  - queues
  - unique job
  - job middleware
  - retry logic
  - workflow
  - workflows
  - morph map
  - factory resolver
  - helpers
  - global helpers
  - conditional route binder
  - web layer
  - api layer
  - resource
  - api resource
  - permission
  - permissions
  - ownership check
  - null driver
  - facade
  - contract
  - interface
  - directory structure
  - namespace
  - namespacing
  - file placement
  - where should
  - where do i put
  - how should i structure
  - how do i organize
  - should i use
  - what's the best way
  - is this correct
  - am i doing this right
  - review this
  - improve
  - make this better
  - extract to package
  - reuse across projects
  - create package
  - package extraction
---

# Laravel Architecture Guide

Expert guidance for building well-structured Laravel applications following action-oriented architecture, strict typing, and enterprise patterns.

## What This Skill Knows

**[philosophy.md](philosophy.md)** - Core Philosophy
The foundational principles: Actions contain domain logic, DTOs for data transfer, strict typing, thin HTTP layer, custom query builders, and composability.

### Core Architecture

**[actions.md](actions.md)** - The Action Pattern
Complete guide to invokable classes, composition, guard methods, context storage, and when to create actions. Actions are the heart of your domain logic.

**[dtos.md](dtos.md)** - Data Transfer Objects
Never pass primitives. Complete guide to Spatie Data, formatters, transformers, test factories, and the dual DTO pattern (request vs response).

**[dto-factories.md](dto-factories.md)** - DTO Factory Pattern
Transform external system data into internal DTOs. Named constructors, field mapping, enum transformations, nested DTOs, safe data access, and testable transformation logic.

**[controllers.md](controllers.md)** - Thin HTTP Layer
Controllers contain zero domain logic. Web layer vs public API, query objects, invokable controllers, and proper delegation patterns.

**[form-requests.md](form-requests.md)** - Validation Rules
Single source of truth for validation. Array-based rules, custom rules, conditional validation, custom messages, and basic testing patterns.

**[testing.md](testing.md)** - Testing Guide
Comprehensive guide to testing Laravel applications following the triple-A pattern (Arrange, Act, Assert). Testing actions in isolation, proper mocking (only mock what you own), using factories, null driver pattern, and avoiding brittle tests.

**[validation-testing.md](validation-testing.md)** - Comprehensive Validation Testing
Systematic validation testing using RequestDataProviderItem helper with Pest datasets. Built-in helper methods (string, email, number, date, array, boolean), nested array testing, conditional validation, and real-world examples.

**[models.md](models.md)** - Database & Eloquent
Model structure, relationships, casts, observers, and integration with custom query builders.

**[query-builders.md](query-builders.md)** - Custom Query Builders
Why custom builders over scopes. Type-safe nested queries, builder traits, and composable query methods.

**[type-safety.md](type-safety.md)** - Type Safety & Strict Typing
Mandatory strict types declaration, parameter/return typing, nullable types, union types, and PHPDoc for complex types.

**[service-providers.md](service-providers.md)** - Service Providers
AppServiceProvider structure with named methods, Model::unguard(), factory resolver for Data classes, morph maps, and configuration patterns.

**[route-binding.md](route-binding.md)** - Route Model Binding
Simple and conditional route model binding strategies. ConditionalRouteBinder pattern for route-specific resolution logic, query objects, and multi-tenant scoping.

### Structure & Organization

**[structure.md](structure.md)** - Directory Organization
Complete project structure, file placement, namespace organization, and the Web vs API distinction.

**[patterns.md](patterns.md)** - Architectural Patterns
Overview of all patterns: Actions, DTOs, State machines, Services, Workflows, Value Objects.

**[decisions.md](decisions.md)** - Pattern Selection
When to use which pattern, decision flowcharts, and architectural trade-offs.

**[multi-tenancy.md](multi-tenancy.md)** - Multi-Tenancy Patterns
Central vs Tenanted organization, directory structure, tenant context helpers, route configuration, database isolation, and queue integration.

### State & Enums

**[enums.md](enums.md)** - Backed Enums
Backed enums with labels, attributes, business logic, and when to use enums vs state machines.

**[state-management.md](state-management.md)** - State Machines
Spatie Model States for complex transitions, state classes, transition classes, and state-specific behavior.

### HTTP & Routing

**[routing-permissions.md](routing-permissions.md)** - Routing & Authorization
Route-level authorization with `->can()`, web vs API routing, route naming, and model binding.

**[policies.md](policies.md)** - Authorization Policies
Policy structure, permission enums, standard methods, ownership checks, and state-based authorization.

### Background Processing

**[jobs-listeners.md](jobs-listeners.md)** - Jobs & Listeners
Thin delegation layers, queue configuration, retry logic, unique jobs, job middleware, and event listeners.

### External Services

**[services.md](services.md)** - Service Layer Architecture
Manager pattern, Saloon integration, driver contracts, null drivers for testing, and facade creation.

**[exceptions.md](exceptions.md)** - Custom Exceptions
Static factory methods, HTTP status codes, descriptive messages, and HttpExceptionInterface.

**[value-objects.md](value-objects.md)** - Value Objects
Immutable domain values, static factories, domain logic encapsulation, and when to use vs DTOs.

### Configuration & Setup

**[bootstrap-booters.md](bootstrap-booters.md)** - Bootstrap & Booters
Invokable booter classes for middleware, scheduling, exceptions, and clean bootstrap configuration.

**[environment.md](environment.md)** - Environment Configuration
Template and instance pattern, `.env-local` templates, git-ignored instances, and optional git-crypt.

**[helpers.md](helpers.md)** - Helper Functions
Global helpers, autoloading, when to use sparingly, and alternatives with static methods.

### Development Practices

**[packages.md](packages.md)** - Package Ecosystem
Core packages (Spatie Data, Model States, Query Builder, Saloon, Pest) and when to use each.

**[package-extraction.md](package-extraction.md)** - Package Extraction
When and how to extract reusable patterns into packages. Three project rule, package structure, service providers, versioning, and distribution best practices.

**[code-style.md](code-style.md)** - Code Style Guidelines
Declarative coding, file structure, Laravel Pint configuration, Composer scripts, and common packages.

**[examples.md](examples.md)** - Reference Code
Working examples of Actions, DTOs, Controllers, Form Requests, and complete workflows.

**[quality.md](quality.md)** - Quality Assurance
Architecture tests, PHPStan, Pint, CI/CD, and enforcing standards.

**[checklist.md](checklist.md)** - Implementation Checklist
Step-by-step tasks for setup, feature development, and production deployment.

## Quick Reference

Domain logic → **[actions.md](actions.md)**
Data transfer → **[dtos.md](dtos.md)**
Validation → **[form-requests.md](form-requests.md)**
Testing → **[testing.md](testing.md)**, **[validation-testing.md](validation-testing.md)**
Patterns & decisions → **[decisions.md](decisions.md)**, **[patterns.md](patterns.md)**
Examples → **[examples.md](examples.md)**

# Claude Laravel Plugin

Laravel architecture skills for Claude Code - expert guidance for building well-structured Laravel applications.

## Installation

```bash
composer require --dev leeovery/claude-laravel
```

The plugin is automatically installed via the Claude Manager. Skills are symlinked to `.claude/skills/laravel-architecture/`.

## What's Included

### Laravel Architecture Skill

Comprehensive guidance for action-oriented Laravel architecture with strict typing and enterprise patterns.

**Core Concepts:**
- Action-oriented architecture (domain logic in actions, not controllers)
- Strict typing and type safety
- DTOs for data transfer (Spatie Data)
- Custom query builders
- Thin HTTP layer
- Multi-tenancy patterns
- State machines (Spatie Model States)

**Topics Covered:**
- Actions, DTOs, and DTO Factories
- Controllers and Form Requests
- Models and Custom Query Builders
- Testing (Pest) and Validation Testing
- Service Providers and Route Binding
- Jobs, Listeners, and Background Processing
- Policies and Authorization
- Package Extraction
- Code Quality and Architecture Tests

## Usage

Once installed, the skill is automatically available in Claude Code. Claude will reference this architecture guide when working on Laravel projects.

## Skill Contents

The `laravel-architecture` skill includes:

- **Philosophy** - Core architectural principles
- **Actions** - The action pattern and domain logic
- **DTOs** - Data transfer objects with Spatie Data
- **Controllers** - Thin HTTP layer patterns
- **Testing** - Comprehensive testing strategies
- **Models & Query Builders** - Database layer patterns
- **And much more** - See skill.md for full index

## Requirements

- PHP ^8.2
- leeovery/claude-manager ^1.0

## License

MIT

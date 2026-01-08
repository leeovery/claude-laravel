<h1 align="center">Claude Laravel</h1>

<p align="center">
  <strong>Opinionated Laravel Skills & Commands for Claude Code</strong>
</p>

<p align="center">
  <a href="#installation">Installation</a> •
  <a href="#skills">Skills</a> •
  <a href="#commands">Commands</a> •
  <a href="#how-it-works">How It Works</a> •
  <a href="#contributing">Contributing</a>
</p>

---

> **Version 1.x is deprecated.** This version uses Composer/PHP.
>
> **Version 2 uses native Claude Code plugins.** Update with: `/plugin install leeovery/claude-laravel`

## About

This is a curated collection of Laravel development patterns and practices I've refined over **20 years in the industry** and **10+ years working with Laravel**.

**These are opinionated.** They represent how I build Laravel applications—action-oriented architecture, strict typing, DTOs everywhere, and a relentless focus on maintainability. They won't be for everyone, and that's okay.

**This is a work in progress.** As I use these skills in real projects, I'm continuously refining them to better represent how I actually work. Expect updates as patterns evolve and edge cases reveal themselves.

## Installation

```bash
composer require --dev leeovery/claude-laravel
```

That's it. The [Claude Manager](https://github.com/leeovery/claude-manager) handles everything else automatically.

## How It Works

This package depends on [`leeovery/claude-manager`](https://github.com/leeovery/claude-manager), which automatically installs skills, commands, hooks, and agents into your project's `.claude/` directory.

### Installation Modes

On first install, you'll be prompted to choose an installation mode. This choice applies to all plugins managed by Claude Manager:

| Mode | Description |
|------|-------------|
| **Symlink** (default) | Assets stay in vendor/ with symlinks to `.claude/`. Gitignore is managed automatically. |
| **Copy** | Assets are copied to `.claude/` and become part of your repository. Ideal for Claude Code on the web where skills need to be available before `composer install` runs. |

See the [Claude Manager README](https://github.com/leeovery/claude-manager#readme) for full details on switching modes and CLI commands.

## Skills

Each skill provides focused guidance on a specific aspect of Laravel development.

### Foundation

| Skill | Description |
|-------|-------------|
| [**laravel-architecture**](skills/laravel-architecture/) | High-level architecture decisions, patterns, and project structure |
| [**laravel-quality**](skills/laravel-quality/) | Code quality with PHPStan, Pint, and strict types |

### Core Patterns

| Skill | Description |
|-------|-------------|
| [**laravel-actions**](skills/laravel-actions/) | Action-oriented architecture—domain logic in invokable classes |
| [**laravel-dtos**](skills/laravel-dtos/) | Data Transfer Objects with Spatie Laravel Data |
| [**laravel-enums**](skills/laravel-enums/) | Backed enums with labels and business logic |
| [**laravel-value-objects**](skills/laravel-value-objects/) | Immutable value objects for domain values |
| [**laravel-state-machines**](skills/laravel-state-machines/) | Complex state management with Spatie Model States |

### HTTP Layer

| Skill | Description |
|-------|-------------|
| [**laravel-controllers**](skills/laravel-controllers/) | Thin HTTP layer—zero domain logic in controllers |
| [**laravel-validation**](skills/laravel-validation/) | Form requests and validation testing |
| [**laravel-routing**](skills/laravel-routing/) | Route model binding and authorization |

### Data Layer

| Skill | Description |
|-------|-------------|
| [**laravel-models**](skills/laravel-models/) | Eloquent models and relationships |
| [**laravel-query-builders**](skills/laravel-query-builders/) | Type-safe custom query builders |

### Infrastructure

| Skill | Description |
|-------|-------------|
| [**laravel-providers**](skills/laravel-providers/) | Service providers and bootstrapping |
| [**laravel-jobs**](skills/laravel-jobs/) | Background processing with jobs and listeners |
| [**laravel-policies**](skills/laravel-policies/) | Authorization policies |
| [**laravel-services**](skills/laravel-services/) | External API integration with Saloon |
| [**laravel-exceptions**](skills/laravel-exceptions/) | Custom exceptions and error handling |

### Enterprise

| Skill | Description |
|-------|-------------|
| [**laravel-multi-tenancy**](skills/laravel-multi-tenancy/) | Multi-tenant application patterns |
| [**laravel-packages**](skills/laravel-packages/) | Package extraction and development |

### Testing

| Skill | Description |
|-------|-------------|
| [**laravel-testing**](skills/laravel-testing/) | Comprehensive testing patterns with Pest |

## Commands

Slash commands for common Laravel development tasks.

*Coming soon.*

## Requirements

- PHP ^8.2
- [leeovery/claude-manager](https://github.com/leeovery/claude-manager) ^1.0 (installed automatically)

## Contributing

Contributions are welcome! Whether it's:

- **Bug fixes** in the documentation
- **Improvements** to existing patterns
- **Discussion** about approaches and trade-offs
- **New skills** for patterns not yet covered

Please open an issue first to discuss significant changes. These are opinionated patterns, so let's talk through the approach before diving into code.

## Related Packages

- [**claude-manager**](https://github.com/leeovery/claude-manager) — The plugin manager that powers skill installation
- [**claude-nuxt**](https://github.com/leeovery/claude-nuxt) — Nuxt.js development skills for Claude Code
- [**claude-technical-workflows**](https://github.com/leeovery/claude-technical-workflows) — Technical workflow skills for Claude Code

## License

MIT License. See [LICENSE](LICENSE) for details.

---

<p align="center">
  <sub>Built with care by <a href="https://github.com/leeovery">Lee Overy</a></sub>
</p>

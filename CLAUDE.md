# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Install dependencies
composer install

# Run all checks (PHPCS + PHPStan)
composer check

# Static analysis only (PHPStan level max)
composer phpstan

# Code style check (WordPress Coding Standards)
composer phpcs

# Auto-fix code style issues
composer fix

# Normalize composer.json
composer normalize

# Generate PHPStan baseline
composer phpstan:baseline
```

## Architecture

This is a PHP library providing base classes for WordPress plugin development using a service/manager pattern.

**Namespace:** `Arts\Base\` → `src/php/`

### Core Classes

- **`Plugins\BasePlugin`** - Abstract singleton base for WordPress plugins. Manages lifecycle: initializes properties → applies filters → adds managers → runs on WordPress hook. Child classes implement `get_managers_classes()`, `get_default_config()`, `get_default_strings()`, `get_default_run_action()`.

- **`Managers\BaseManager`** - Abstract base for managers. Receives `$args`, `$config`, `$strings` from plugin. Has access to other managers via `$this->managers`. Use `init_property()` / `init_array_property()` helpers to pull from config.

- **`Containers\ManagersContainer`** - ArrayObject-based container enabling both iteration and property access (`$managers->some_manager`).

### Plugin Lifecycle

1. `Plugin::instance()` creates singleton
2. `init()` runs: init properties → apply filters → add managers → init managers → add options → add run action
3. On WordPress hook (from `get_default_run_action()`): `run()` executes → adds filters → adds actions → `do_run()`

### Generic Type Support

`BasePlugin` uses `@template TManagers of ManagersContainer` - child plugins can extend `ManagersContainer` for type-safe manager access by overriding `init_managers_container()`.

## Code Standards

- WordPress Coding Standards with exceptions: short array syntax allowed, PSR-4 filenames, no Yoda conditions
- PHPStan level max with WordPress stubs
- PHP 7.4+ required
- Pre-commit/pre-push hooks run `composer check` automatically (CaptainHook)

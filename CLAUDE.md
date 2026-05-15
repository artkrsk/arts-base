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

- **`Plugins\BasePlugin`** - Abstract per-class singleton. Bootstraps the plugin in its constructor, then schedules `run()` against a WordPress hook. Subclasses implement `get_managers_classes()`, `get_default_config()`, `get_default_strings()`, `get_default_run_action()`.

- **`Managers\BaseManager`** - Abstract manager base. Constructed with the plugin's `$args`, `$config`, `$strings`; peer managers are wired in later via `init($managers)` (self is excluded from peers). Use `init_property()` / `init_array_property()` to copy config keys onto typed properties.

- **`Containers\ManagersContainer`** - `ArrayObject` subclass supporting both iteration and property access (`$managers->some_manager`); unknown keys return `null` rather than raising a notice.

### Plugin Lifecycle

1. `Plugin::instance()` returns the per-class singleton, creating it on first call. The constructor calls `init()`.
2. `init()` runs in order:
   1. `init_properties()` (also creates the managers container)
   2. `apply_filters()` — runs `{filter_portion}/{args,config,strings,run_action}` filters; values failing validation are discarded
   3. `add_managers()` — instantiate each manager from `get_managers_classes()`
   4. `init_managers()` — call `init($managers)` on every manager that defines it
   5. `do_after_init_managers()` (extension point)
   6. `add_options()` — default resets `$options` to `[]`; override to populate
   7. `add_run_action()` — register `run()` on `$run_action`, or invoke it immediately if that action has already fired
   8. `do_after_run_action()` (extension point)
3. When the run action fires (or immediately in step 2.7 if already fired): `run()` calls `add_filters()` → `add_actions()` → `do_run()`.

The filter portion is derived from the fully-qualified subclass name: e.g. `Arts\Base\Plugins\BasePlugin` → `arts/base/plugins/baseplugin`.

### Generic Type Support

`BasePlugin` uses `@template TManagers of ManagersContainer` - child plugins can extend `ManagersContainer` for type-safe manager access by overriding `init_managers_container()`.

## Code Standards

- WordPress Coding Standards with exceptions: short array syntax allowed, PSR-4 filenames, no Yoda conditions
- PHPStan level max with WordPress stubs
- PHP 7.4+ required
- Pre-commit/pre-push hooks run `composer check` automatically (CaptainHook)

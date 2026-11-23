# Arts Base

Base classes for WordPress plugins using a manager pattern architecture.

## Installation

```bash
composer require arts/base
```

## Quick Start

```php
use Arts\Base\Plugins\BasePlugin;
use Arts\Base\Managers\BaseManager;

class MyPlugin extends BasePlugin {
    protected function get_default_config() {
        return ['version' => '1.0.0'];
    }

    protected function get_default_strings() {
        return ['name' => 'My Plugin'];
    }

    protected function get_managers_classes() {
        return ['assets' => AssetsManager::class];
    }

    protected function get_default_run_action() {
        return 'init';
    }
}

class AssetsManager extends BaseManager {
    // Access config via $this->config, strings via $this->strings
    // Access other managers via $this->managers->other_manager
}

// Initialize
MyPlugin::instance();
```

## Architecture

- **BasePlugin** - Per-class singleton. Constructor bootstraps the plugin (init properties, apply filters, instantiate and init managers, register `run()`), then `run()` fires on the configured WordPress action.
- **BaseManager** - Receives `$args`, `$config`, `$strings` from the plugin at construction; peer managers are wired in later via `init($managers)` (self excluded).
- **ManagersContainer** - `ArrayObject` subclass supporting both iteration and property access (`$managers->some_manager`); missing keys return `null`.

## Manager Communication

Peers are wired into `$this->managers` at the end of `BaseManager::init()`, *after* `init_properties()` runs. So `$this->managers` is `null` inside `init_properties()`, and only reachable from methods called after `init()` completes:

```php
$this->managers->assets->enqueue_scripts();
```

## Type Safety

For IDE autocompletion, extend `ManagersContainer` and override `init_managers_container()` in your plugin. See `@template TManagers` in BasePlugin.

## Elementor Cache Clear

A product whose output Elementor caches (post CSS, element cache) clears those caches once after it changes, from its bootstrap or a theme's `functions.php`:

```php
use Arts\Base\Elementor\CacheClear;

CacheClear::on_version( 'horizontal-scroll', MY_PLUGIN_VERSION ); // clears when the running version is higher
CacheClear::on_change( 'trigger-cursor-follower', $state );        // clears when the value differs
```

Both only register the comparison; it runs on `elementor/init`, stores the value in the `arts_elementor_cache_<key>` option, and clears at most once per request however many products changed. The contract is in [issue #1](https://github.com/artkrsk/arts-base/issues/1).

## Used By

- [Release Deploy for EDD](https://github.com/artkrsk/release-deploy-edd) - GitHub release → Easy Digital Downloads automation

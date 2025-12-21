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

- **BasePlugin** - Abstract singleton handling plugin lifecycle: init → apply filters → add managers → run on WordPress hook
- **BaseManager** - Abstract base receiving `$args`, `$config`, `$strings` from plugin with access to peer managers
- **ManagersContainer** - ArrayObject-based container enabling `$this->managers->manager_name` access

## Manager Communication

Managers can access each other after initialization:

```php
$this->managers->assets->enqueue_scripts();
```

## Type Safety

For IDE autocompletion, extend `ManagersContainer` and override `init_managers_container()` in your plugin. See `@template TManagers` in BasePlugin.

## Used By

- [Release Deploy for EDD](https://github.com/artkrsk/release-deploy-edd) - GitHub release → Easy Digital Downloads automation

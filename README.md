# Arts Base

**WordPress plugin foundation classes providing consistent architecture patterns.**

Base classes for building WordPress plugins with a standardized service/manager pattern architecture.

## Features

- **Plugin Base Class** - Unified plugin initialization and lifecycle management
- **Service Pattern** - Modular functionality with dependency injection
- **Manager Pattern** - WordPress hook integration layer
- **Consistent Architecture** - Standardized patterns across all Arts plugins

## Used By

- [Release Deploy for EDD](https://github.com/artkrsk/release-deploy-edd) - GitHub release management for Easy Digital Downloads

## Installation

```bash
composer require arts/base
```

## Basic Usage

```php
use Arts\Base\Plugin as BasePlugin;

class MyPlugin extends BasePlugin {
    protected function get_core_services_classes() {
        return [
            'my_service' => MyService::class,
        ];
    }
    
    protected function get_managers_classes() {
        return [
            'my_manager' => MyManager::class,
        ];
    }
}

// Initialize plugin
MyPlugin::instance();
```

## Architecture

- **Plugin** - Main plugin class with service container
- **Service** - Business logic and functionality
- **Manager** - WordPress integration (hooks, filters, admin)

## License

GPL-3.0-or-later - Compatible with WordPress

This library is licensed under GPL-3.0-or-later to ensure compatibility with WordPress and GPL-licensed plugins.


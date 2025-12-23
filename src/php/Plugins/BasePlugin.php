<?php

namespace Arts\Base\Plugins;

use Arts\Base\Containers\ManagersContainer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * @template TManagers of ManagersContainer
 * @phpstan-consistent-constructor
 */
abstract class BasePlugin {
	/**
	 * Instances of the class.
	 *
	 * @var array<class-string, static>
	 */
	private static $instances = array();

	/**
	 * The URL to the AJAX handler for the plugin.
	 * Typically this is the admin-ajax.php file which can be accessed via the admin_url() function.
	 *
	 * @var string|null
	 */
	private static $ajax_url;

	/**
	 * Common arguments for the plugin.
	 *
	 * @var array<string, mixed>
	 */
	protected $args;

	/**
	 * Options from panel for the plugin.
	 *
	 * @var array<string, mixed>
	 */
	protected $options;

	/**
	 * Configuration for the plugin.
	 *
	 * @var array<string, mixed>
	 */
	protected $config;

	/**
	 * Strings for the plugin.
	 *
	 * @var array<string, string>
	 */
	protected $strings;

	/**
	 * The action to run the plugin.
	 *
	 * @var string
	 */
	protected $run_action;

	/**
	 * Managers for the plugin.
	 *
	 * Can be overridden by child classes to use custom container types.
	 *
	 * @var TManagers
	 */
	protected $managers;

	/**
	 * Get the instance of this class.
	 *
	 * @return static The instance of this class.
	 */
	public static function instance(): static {
		$cls = static::class;

		if ( ! isset( self::$instances[ $cls ] ) ) {
			self::$instances[ $cls ] = new static();
		}

		if ( self::$ajax_url === null ) {
			self::$ajax_url = admin_url( 'admin-ajax.php' );
		}

		return self::$instances[ $cls ];
	}

	/**
	 * Constructor for the class.
	 *
	 * @return void
	 */
	final protected function __construct() {
		$this->init();
	}

	/**
	 * Singleton should not be cloneable.
	 */
	private function __clone(): void { }

	/**
	 * Singleton should not be restorable from strings.
	 */
	public function __wakeup(): void {
		throw new \Exception( 'Cannot unserialize a singleton.' );
	}

	/**
	 * Initializes the plugin by adding managers, filters, and actions.
	 *
	 * @return void
	 */
	protected function init(): void {
		$this->init_properties();
		$this->apply_filters();
		$this->add_managers();
		$this->init_managers();
		$this->do_after_init_managers();
		$this->add_options();
		$this->add_run_action();
		$this->do_after_run_action();
	}

	/**
	 * Initialize properties of the plugin.
	 *
	 * @return void
	 */
	private function init_properties(): void {
		$this->init_managers_container();
		$this->args       = array(
			'dir_path' => $this->get_plugin_dir_path(),
			'dir_url'  => $this->get_plugin_dir_url(),
			'ajax_url' => self::$ajax_url,
		);
		$this->config     = $this->get_default_config();
		$this->strings    = $this->get_default_strings();
		$this->run_action = $this->get_default_run_action();
	}

	/**
	 * Initialize the managers container.
	 *
	 * Can be overridden by child classes to use custom container types.
	 *
	 * @return void
	 */
	protected function init_managers_container(): void {
		/** @var TManagers $managers */
		$managers       = new ManagersContainer();
		$this->managers = $managers;
	}

	/**
	 * Get the default configuration.
	 *
	 * @return array<string, mixed>
	 */
	abstract protected function get_default_config(): array;

	/**
	 * Get the default strings.
	 *
	 * @return array<string, string>
	 */
	abstract protected function get_default_strings(): array;

	/**
	 * Get the manager classes.
	 *
	 * @return array<string, class-string>
	 */
	abstract protected function get_managers_classes(): array;

	/**
	 * Get the default run action hook name.
	 *
	 * @return string
	 */
	abstract protected function get_default_run_action(): string;

	/**
	 * Get the plugin directory path.
	 *
	 * @return string
	 */
	protected function get_plugin_dir_path(): string {
		$reflection = new \ReflectionClass( static::class );
		$file_name  = $reflection->getFileName();

		if ( $file_name === false ) {
			return '';
		}

		return plugin_dir_path( $file_name );
	}

	/**
	 * Get the URL of the plugin directory.
	 *
	 * Constructs the URL based on whether the file is located within the `/wp-content/plugins/`
	 * directory or the `/wp-content/themes/` directory.
	 *
	 * @return string URL of the plugin directory. Returns an empty string if the file is outside known directories.
	 */
	protected function get_plugin_dir_url(): string {
		$reflection = new \ReflectionClass( static::class );
		$file_name  = $reflection->getFileName();

		if ( $file_name === false ) {
			return '';
		}

		$dir_path = plugin_dir_path( $file_name );

		if ( strpos( $dir_path, WP_PLUGIN_DIR ) === 0 ) {
			// The file is inside the plugins directory
			$relative_path = str_replace( WP_PLUGIN_DIR, '', $dir_path );
			return plugins_url( $relative_path );
		} elseif ( strpos( $dir_path, get_theme_root() ) === 0 ) {
			// The file is inside the themes directory
			$relative_path = str_replace( get_theme_root(), '', $dir_path );
			return get_theme_root_uri() . $relative_path;
		}

		return '';
	}

	/**
	 * Apply filters to plugin configuration, strings, and run action.
	 *
	 * @return void
	 */
	protected function apply_filters(): void {
		$name = $this->get_plugin_filters_portion_name();

		$args = apply_filters( "{$name}/args", $this->args );
		if ( $this->is_string_keyed_array( $args ) ) {
			$this->args = $args;
		}

		$config = apply_filters( "{$name}/config", $this->config );
		if ( $this->is_string_keyed_array( $config ) ) {
			$this->config = $config;
		}

		$strings = apply_filters( "{$name}/strings", $this->strings );
		if ( $this->is_string_array( $strings ) ) {
			$this->strings = $strings;
		}

		$run_action = apply_filters( "{$name}/run_action", $this->run_action );
		if ( is_string( $run_action ) ) {
			$this->run_action = $run_action;
		}
	}

	/**
	 * Check if a value is an array with string keys.
	 *
	 * @param mixed $value The value to check.
	 * @return bool
	 * @phpstan-assert-if-true array<string, mixed> $value
	 */
	private function is_string_keyed_array( $value ): bool {
		return is_array( $value );
	}

	/**
	 * Check if a value is an array with string keys and string values.
	 *
	 * @param mixed $value The value to check.
	 * @return bool
	 * @phpstan-assert-if-true array<string, string> $value
	 */
	private function is_string_array( $value ): bool {
		if ( ! is_array( $value ) ) {
			return false;
		}

		foreach ( $value as $item ) {
			if ( ! is_string( $item ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the run action priority.
	 *
	 * @return int
	 */
	protected function get_run_action_priority(): int {
		return 10;
	}

	/**
	 * Get the number of accepted args for run action.
	 *
	 * @return int
	 */
	protected function get_run_action_accepted_args(): int {
		return 1;
	}

	/**
	 * Add a WordPress action hook for the run method.
	 *
	 * @return void
	 */
	protected function add_run_action(): void {
		if ( is_string( $this->run_action ) && ! empty( $this->run_action ) ) {
			// If the action already fired, run immediately instead of hooking
			if ( did_action( $this->run_action ) ) {
				$this->run();
			} else {
				$priority      = $this->get_run_action_priority();
				$accepted_args = $this->get_run_action_accepted_args();
				add_action( $this->run_action, array( $this, 'run' ), $priority, $accepted_args );
			}
		}
	}

	/**
	 * Execute the plugin with the provided arguments.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->add_filters();
		$this->add_actions();
		$this->do_run();
	}

	/**
	 * Extension point for framework-specific initialization.
	 *
	 * @return void
	 */
	protected function do_run(): void {
	}

	/**
	 * Add options for the plugin.
	 *
	 * @return void
	 */
	protected function add_options(): void {
		$this->options = array();
	}

	/**
	 * Get the Plugin options.
	 *
	 * @return array<string, mixed>
	 */
	public function get_options(): array {
		return $this->options;
	}

	/**
	 * Add WordPress actions for the plugin.
	 *
	 * @return void
	 */
	protected function add_actions(): void {
	}

	/**
	 * Add WordPress filters for the plugin.
	 *
	 * @return void
	 */
	protected function add_filters(): void {
	}

	/**
	 * Called after managers are initialized.
	 *
	 * @return void
	 */
	protected function do_after_init_managers(): void {
	}

	/**
	 * Called after run action is added.
	 *
	 * @return void
	 */
	protected function do_after_run_action(): void {
	}

	/**
	 * Add manager instances to the managers property.
	 *
	 * @return void
	 */
	private function add_managers(): void {
		$manager_classes = $this->get_managers_classes();

		if ( empty( $manager_classes ) ) {
			return;
		}

		foreach ( $manager_classes as $key => $class ) {
			$this->managers->$key = $this->get_manager_instance( $class );
		}
	}

	/**
	 * Initialize all manager classes by calling their init method if it exists.
	 *
	 * @return void
	 */
	private function init_managers(): void {
		foreach ( $this->managers as $manager ) {
			if ( method_exists( $manager, 'init' ) ) {
				$manager->init( $this->managers );
			}
		}
	}

	/**
	 * Helper method to instantiate a manager class.
	 *
	 * @param class-string $class The manager class to instantiate.
	 *
	 * @return object The instantiated manager class.
	 */
	private function get_manager_instance( $class ): object {
		try {
			$reflection = new \ReflectionClass( $class );
			return $reflection->newInstanceArgs( array( $this->args, $this->config, $this->strings ) );
		} catch ( \ReflectionException $e ) {
			return new $class();
		}
	}

	/**
	 * Get the plugin filters portion name.
	 *
	 * Constructs a string based on the namespace and class name.
	 *
	 * @return string The plugin filters portion name.
	 */
	protected function get_plugin_filters_portion_name(): string {
		$fully_qualified_class_name = static::class;

		$last_separator = strrpos( $fully_qualified_class_name, '\\' );

		if ( $last_separator === false ) {
			$namespace = '';
		} else {
			$namespace = substr( $fully_qualified_class_name, 0, $last_separator );
		}

		// Get the class short name using reflection
		$class_name = ( new \ReflectionClass( static::class ) )->getShortName();

		return strtolower( str_replace( '\\', '/', $namespace ) ) . '/' . strtolower( $class_name );
	}
}

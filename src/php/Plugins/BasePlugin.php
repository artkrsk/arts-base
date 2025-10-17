<?php

namespace Arts\Base\Plugins;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class BasePlugin {
	/**
	 * Instances of the class.
	 *
	 * @var array
	 */
	private static $instances = array();

	/**
	 * The URL to the AJAX handler for the plugin.
	 * Typically this is the admin-ajax.php file which can be accessed via the admin_url() function.
	 *
	 * @var string
	 */
	private static $ajax_url;

	/**
	 * Common arguments for the plugin.
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * Options from panel for the plugin.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Configuration for the plugin.
	 *
	 * @var array
	 */
	protected $config;

	/**
	 * Strings for the plugin.
	 *
	 * @var array
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
	 * @var Object
	 */
	protected $managers;

	/**
	 * Get the instance of this class.
	 *
	 * @return object The instance of this class.
	 */
	public static function instance() {
		$cls = static::class;

		if ( ! isset( self::$instances[ $cls ] ) ) {
			self::$instances[ $cls ] = new static();
		}

		if ( ! isset( self::$ajax_url ) ) {
			self::$ajax_url = admin_url( 'admin-ajax.php' );
		}

		return self::$instances[ $cls ];
	}

	/**
	 * Constructor for the class.
	 *
	 * @return void
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Singleton should not be cloneable.
	 */
	private function __clone() { }

	/**
	 * Singleton should not be restorable from strings.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize a singleton.' );
	}

	/**
	 * Initializes the plugin by adding managers, filters, and actions.
	 *
	 * @return BasePlugin Returns the current instance for method chaining.
	 */
	protected function init() {
		$this->init_properties();
		$this->apply_filters();
		$this->add_managers();
		$this->init_managers();
		$this->do_after_init_managers();
		$this->add_options();
		$this->add_run_action();
		$this->do_after_run_action();

		return $this;
	}

	/**
	 * Initialize properties of the plugin.
	 *
	 * @return $this
	 */
	private function init_properties() {
		$this->managers   = new \stdClass();
		$this->args       = array(
			'dir_path' => $this->get_plugin_dir_path(),
			'dir_url'  => $this->get_plugin_dir_url(),
			'ajax_url' => self::$ajax_url,
		);
		$this->config     = $this->get_default_config();
		$this->strings    = $this->get_default_strings();
		$this->run_action = $this->get_default_run_action();

		return $this;
	}

	abstract protected function get_default_config();
	abstract protected function get_default_strings();
	abstract protected function get_managers_classes();
	abstract protected function get_default_run_action();

	protected function get_plugin_dir_path() {
		$reflection = new \ReflectionClass( static::class );
		return plugin_dir_path( $reflection->getFileName() ); // Get full directory path
	}

	/**
	 * Get the URL of the plugin directory.
	 *
	 * Constructs the URL based on whether the file is located within the `/wp-content/plugins/`
	 * directory or the `/wp-content/themes/` directory.
	 *
	 * @return string URL of the plugin directory. Returns an empty string if the file is outside known directories.
	 */
	protected function get_plugin_dir_url() {
		$reflection = new \ReflectionClass( static::class );
		$dir_path   = plugin_dir_path( $reflection->getFileName() ); // Get full directory path

		if ( strpos( $dir_path, WP_PLUGIN_DIR ) === 0 ) {
			// The file is inside the plugins directory
			$relative_path = str_replace( WP_PLUGIN_DIR, '', $dir_path );
			return plugins_url( $relative_path );
		} elseif ( strpos( $dir_path, get_theme_root() ) === 0 ) {
			// The file is inside the themes directory
			$relative_path = str_replace( get_theme_root(), '', $dir_path );
			return get_theme_root_uri() . $relative_path;
		}

		return ''; // Fallback in case it's outside known directories
	}

	/**
	 * Apply filters to plugin configuration, strings, and run action.
	 *
	 * @return $this
	 */
	protected function apply_filters() {
		$plugin_filters_portion_name = $this->get_plugin_filters_portion_name();

		$this->args       = apply_filters( "{$plugin_filters_portion_name}/args", $this->args );
		$this->config     = apply_filters( "{$plugin_filters_portion_name}/config", $this->config );
		$this->strings    = apply_filters( "{$plugin_filters_portion_name}/strings", $this->strings );
		$this->run_action = apply_filters( "{$plugin_filters_portion_name}/run_action", $this->run_action );

		return $this;
	}

	protected function get_run_action_priority() {
		return 10;
	}

	protected function get_run_action_accepted_args() {
		return 1;
	}

	/**
	 * Add a WordPress action hook for the run method.
	 *
	 * @return $this
	 */
	protected function add_run_action() {
		if ( is_string( $this->run_action ) && ! empty( $this->run_action ) ) {
			$priority      = $this->get_run_action_priority();
			$accepted_args = $this->get_run_action_accepted_args();

			add_action( $this->run_action, array( $this, 'run' ), $priority, $accepted_args );
		}

		return $this;
	}

	/**
	 * Execute the plugin with the provided arguments.
	 *
	 * @return Plugin Returns the current instance for method chaining.
	 */
	public function run() {
		$this->add_filters();
		$this->add_actions();
		$this->do_run();

		return $this;
	}

	/**
	 * Extension point for framework-specific initialization.
	 *
	 * @return $this
	 */
	protected function do_run() {
		return $this;
	}

	/**
	 * Add options for the plugin.
	 *
	 * @return Plugin Returns the current instance for method chaining.
	 */
	protected function add_options() {
		$this->options = array();

		return $this;
	}

	/**
	 * Get the Plugin options.
	 *
	 * @return array $options Array of options for the Plugin
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Add WordPress actions for the plugin.
	 *
	 * @return Plugin Returns the current instance for method chaining.
	 */
	protected function add_actions() {
		return $this;
	}

	/**
	 * Add WordPress filters for the plugin.
	 *
	 * @return Plugin Returns the current instance for method chaining.
	 */
	protected function add_filters() {
		return $this;
	}

	protected function do_after_init_managers() {
		return $this;
	}

	protected function do_after_run_action() {
		return $this;
	}

	/**
	 * Add manager instances to the managers property.
	 *
	 * @return Plugin Returns the current instance for method chaining.
	 */
	private function add_managers() {
		$manager_classes = $this->get_managers_classes();

		if ( ! is_array( $manager_classes ) || empty( $manager_classes ) ) {
			return $this;
		}

		foreach ( $manager_classes as $key => $class ) {
			$this->managers->$key = $this->get_manager_instance( $class );
		}

		return $this;
	}

	/**
	 * Initialize all manager classes by calling their init method if it exists.
	 *
	 * @return Plugin Returns the current instance for method chaining.
	 */
	private function init_managers() {
		$managers = $this->managers;

		if ( ! is_object( $managers ) || empty( $managers ) ) {
			return $this;
		}

		foreach ( $managers as $manager ) {
			if ( method_exists( $manager, 'init' ) ) {
				$manager->init( $managers );
			}
		}

		return $this;
	}

	/**
	 * Helper method to instantiate a manager class.
	 *
	 * @param string $class The manager class to instantiate.
	 *
	 * @return object The instantiated manager class.
	 */
	private function get_manager_instance( $class ) {
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
	protected function get_plugin_filters_portion_name() {
		// Get the fully qualified class name of the extending class
		$fully_qualified_class_name = static::class;

		// Extract namespace from the fully qualified class name
		$namespace = substr( $fully_qualified_class_name, 0, strrpos( $fully_qualified_class_name, '\\' ) );

		// Get the class short name using reflection
		$class_name = ( new \ReflectionClass( static::class ) )->getShortName();

		return strtolower( str_replace( '\\', '/', $namespace ) ) . '/' . strtolower( $class_name );
	}
}

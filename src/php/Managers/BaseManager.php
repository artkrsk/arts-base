<?php

namespace Arts\Base\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abstract class BaseManager
 *
 * Serves as a base for managers of the plugin.
 *
 * @package Arts\Base\Managers
 */
abstract class BaseManager {
	/**
	 * Common Plugin arguments for the manager.
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * Configuration for the manager.
	 *
	 * @var array
	 */
	protected $config;

	/**
	 * Array of text strings used by the manager.
	 *
	 * @var array
	 */
	protected $strings;

	/**
	 * Other managers used by the current manager.
	 *
	 * @var \stdClass
	 */
	protected $managers;

	protected $plugin_dir_path;
	protected $plugin_dir_url;
	protected $plugin_ajax_url;

	/**
	 * Constructor for the BaseManager class.
	 *
	 * @param array $args    Common plugin arguments for the manager.
	 * @param array $config  Configuration for the manager.
	 * @param array $strings Array of text strings used by the manager.
	 */
	public function __construct( $args = array(), $config = array(), $strings = array() ) {
		$this->args = $args;

		if ( isset( $args['dir_path'] ) ) {
			$this->plugin_dir_path = $args['dir_path'];
		}

		if ( isset( $args['dir_url'] ) ) {
			$this->plugin_dir_url = $args['dir_url'];
		}

		if ( isset( $args['ajax_url'] ) ) {
			$this->plugin_ajax_url = $args['ajax_url'];
		}

		$this->config  = $config;
		$this->strings = $strings;
	}

	/**
	 * Initialize the manager with other managers.
	 *
	 * @param \stdClass $managers Other managers used by the current manager.
	 */
	public function init( $managers ) {
		$this->init_properties();
		$this->add_managers( $managers );
	}

	/**
	 * Add other managers to the current manager.
	 *
	 * @param \stdClass $managers Other managers used by the current manager.
	 */
	protected function add_managers( $managers ) {
		if ( ! isset( $this->managers ) ) {
			$this->managers = new \stdClass();
		}

		foreach ( $managers as $key => $manager ) {
			// Prevent adding self to the managers property to avoid infinite loop.
			if ( $manager !== $this ) {
				$this->managers->$key = $manager;
			}
		}

		return $this;
	}

	/**
	 * Initializes properties for the manager.
	 *
	 * @return $this
	 */
	protected function init_properties() {
		return $this;
	}

	/**
	 * Initialize a property from the config array.
	 *
	 * This method checks if the specified property exists in the config array and initializes
	 * the property with the corresponding value from the config array if it exists.
	 *
	 * @param string $property The name of the property to initialize.
	 *
	 * @return BaseManager Returns the current instance for method chaining.
	 */
	protected function init_property( $property ) {
		if ( isset( $this->config[ $property ] ) ) {
			$this->$property = $this->config[ $property ];
		}

		return $this;
	}

	/**
	 * Initialize an array property from the config array.
	 *
	 * This method checks if the specified property exists in the config array, is an array,
	 * and is not empty. If all conditions are met, it initializes the property with the
	 * corresponding value from the config array.
	 *
	 * @param string $property The name of the property to initialize.
	 *
	 * @return BaseManager Returns the current instance for method chaining.
	 */
	protected function init_array_property( $property ) {
		if ( isset( $this->config[ $property ] ) && is_array( $this->config[ $property ] ) && ! empty( $this->config[ $property ] ) ) {
			$this->$property = $this->config[ $property ];
		}

		return $this;
	}

	/**
	 * Gets a configuration value with caching.
	 *
	 * @param string $key The configuration key.
	 * @param mixed  $default The default value.
	 * @return mixed The configuration value.
	 */
	protected function get_config( $key, $default = null ) {
		static $config_cache = array();

		if ( ! isset( $config_cache[ $key ] ) ) {
			$config_cache[ $key ] = apply_filters( $key, $default );
		}

		return $config_cache[ $key ];
	}
}

<?php

namespace Arts\Base\Managers;

use Arts\Base\Containers\ManagersContainer;

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
	 * @var array<string, mixed>
	 */
	protected $args;

	/**
	 * Configuration for the manager.
	 *
	 * @var array<string, mixed>
	 */
	protected $config;

	/**
	 * Array of text strings used by the manager.
	 *
	 * @var array<string, string>
	 */
	protected $strings;

	/**
	 * Other managers used by the current manager.
	 *
	 * @var ManagersContainer|null
	 */
	protected $managers;

	/** @var string */
	protected $plugin_dir_path;

	/** @var string */
	protected $plugin_dir_url;

	/** @var string */
	protected $plugin_ajax_url;

	/**
	 * Constructor for the BaseManager class.
	 *
	 * @param array<string, mixed>  $args    Common plugin arguments for the manager.
	 * @param array<string, mixed>  $config  Configuration for the manager.
	 * @param array<string, string> $strings Array of text strings used by the manager.
	 */
	public function __construct( $args = array(), $config = array(), $strings = array() ) {
		$this->args = $args;

		if ( isset( $args['dir_path'] ) && is_string( $args['dir_path'] ) ) {
			$this->plugin_dir_path = $args['dir_path'];
		}

		if ( isset( $args['dir_url'] ) && is_string( $args['dir_url'] ) ) {
			$this->plugin_dir_url = $args['dir_url'];
		}

		if ( isset( $args['ajax_url'] ) && is_string( $args['ajax_url'] ) ) {
			$this->plugin_ajax_url = $args['ajax_url'];
		}

		$this->config  = $config;
		$this->strings = $strings;
	}

	/**
	 * Initialize the manager with other managers.
	 *
	 * @param ManagersContainer $managers Other managers used by the current manager.
	 * @return void
	 */
	public function init( $managers ): void {
		$this->init_properties();
		$this->add_managers( $managers );
	}

	/**
	 * Add other managers to the current manager.
	 *
	 * @param ManagersContainer $managers Other managers used by the current manager.
	 * @return void
	 */
	protected function add_managers( $managers ): void {
		if ( $this->managers === null ) {
			$this->managers = new ManagersContainer();
		}

		foreach ( $managers as $key => $manager ) {
			// Prevent adding self to the managers property to avoid infinite loop.
			if ( $manager !== $this ) {
				$this->managers->$key = $manager;
			}
		}
	}

	/**
	 * Initializes properties for the manager.
	 *
	 * @return void
	 */
	protected function init_properties(): void {
	}

	/**
	 * Initialize a property from the config array.
	 *
	 * @param string $property The name of the property to initialize.
	 * @return void
	 */
	protected function init_property( $property ): void {
		if ( isset( $this->config[ $property ] ) ) {
			$this->$property = $this->config[ $property ];
		}
	}

	/**
	 * Initialize an array property from the config array.
	 *
	 * @param string $property The name of the property to initialize.
	 * @return void
	 */
	protected function init_array_property( $property ): void {
		if ( isset( $this->config[ $property ] ) && is_array( $this->config[ $property ] ) && ! empty( $this->config[ $property ] ) ) {
			$this->$property = $this->config[ $property ];
		}
	}

	/**
	 * Gets a configuration value with caching.
	 *
	 * @param non-empty-string $key The configuration key.
	 * @param mixed            $default The default value.
	 * @return mixed The configuration value.
	 */
	protected function get_config( $key, $default = null ): mixed {
		/** @var array<non-empty-string, mixed> $config_cache */
		static $config_cache = array();

		if ( ! isset( $config_cache[ $key ] ) ) {
			$config_cache[ $key ] = apply_filters( $key, $default );
		}

		return $config_cache[ $key ];
	}
}

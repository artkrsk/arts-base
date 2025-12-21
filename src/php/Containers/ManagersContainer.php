<?php

namespace Arts\Base\Containers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Container for manager instances.
 *
 * Extends ArrayObject to provide both array-like iteration and object property access.
 *
 * @extends \ArrayObject<string, object>
 */
class ManagersContainer extends \ArrayObject {

	/**
	 * Get a manager by name.
	 *
	 * @param string $name Manager name.
	 * @return object|null
	 */
	public function __get( $name ) {
		return $this->offsetExists( $name ) ? $this->offsetGet( $name ) : null;
	}

	/**
	 * Set a manager by name.
	 *
	 * @param string $name Manager name.
	 * @param object $value Manager instance.
	 * @return void
	 */
	public function __set( $name, $value ) {
		$this->offsetSet( $name, $value );
	}

	/**
	 * Check if a manager exists.
	 *
	 * @param string $name Manager name.
	 * @return bool
	 */
	public function __isset( $name ) {
		return $this->offsetExists( $name );
	}
}

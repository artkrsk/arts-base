<?php

namespace Arts\Base\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Clears Elementor's caches once after a product's output changed, under the contract in
 * https://github.com/artkrsk/arts-base/issues/1.
 *
 * Products ship this class prefixed (Strauss) or unprefixed, so copies on one site share no class
 * and no static: they agree only through WordPress. Each key keeps its own autoloaded
 * `arts_elementor_cache_<key>` option, and the `elementor/core/files/clear_cache` action count tells
 * every copy whether this request already cleared. Both names are part of the contract and must
 * never change.
 *
 * The comparison runs on `elementor/init` at priority 20, after Elementor Pro's listeners, or at
 * once when that action has finished. A call made inside `elementor/init` at priority 20 or later
 * is registered too late for that request.
 */
final class CacheClear {
	private const OPTION_PREFIX = 'arts_elementor_cache_';

	/**
	 * Clear when the running version is higher than the stored one or nothing is stored. A lower
	 * running version (rolling deploy, stale OPcache) does nothing and leaves the stored one alone.
	 *
	 * @param string $key     Product key, e.g. `horizontal-scroll`.
	 * @param string $version Version of the running code.
	 * @return void
	 */
	public static function on_version( string $key, string $version ): void {
		self::when_elementor_ready(
			static function () use ( $key, $version ): void {
				$stored = get_option( self::OPTION_PREFIX . $key );

				if ( is_string( $stored ) && ! version_compare( $version, $stored, '>' ) ) {
					return;
				}

				self::claim_and_clear( $key, $version );
			}
		);
	}

	/**
	 * Clear when the value differs from the stored one or nothing is stored.
	 *
	 * @param string $key   Product key, e.g. `trigger-cursor-follower`.
	 * @param string $value State of the running code.
	 * @return void
	 */
	public static function on_change( string $key, string $value ): void {
		self::when_elementor_ready(
			static function () use ( $key, $value ): void {
				if ( get_option( self::OPTION_PREFIX . $key ) === $value ) {
					return;
				}

				self::claim_and_clear( $key, $value );
			}
		);
	}

	/** Run now if `elementor/init` has finished, otherwise on it at priority 20. */
	private static function when_elementor_ready( callable $compare ): void {
		if ( did_action( 'elementor/init' ) && ! doing_action( 'elementor/init' ) ) {
			$compare();
			return;
		}

		add_action( 'elementor/init', $compare, 20 );
	}

	/**
	 * Store the new value first and clear only if this request changed the row, so concurrent
	 * requests clear once. Skip the clear when this request already cleared, by Elementor or by
	 * another product.
	 */
	private static function claim_and_clear( string $key, string $value ): void {
		if ( ! update_option( self::OPTION_PREFIX . $key, $value, true ) ) {
			return;
		}

		if ( did_action( 'elementor/core/files/clear_cache' ) ) {
			return;
		}

		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
}

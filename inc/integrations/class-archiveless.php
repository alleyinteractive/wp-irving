<?php
/**
 * WP Irving integration for Archiveless.
 *
 * @package WP_Irving;
 */

namespace WP_Irving\Integrations;

/**
 * Class to replicate Archiveless's query modifications.
 */
class Archiveless {

	/**
	 * Class instance.
	 *
	 * @var null|self
	 */
	protected static $instance;

	/**
	 * Get class instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Constructor for class.
	 */
	public function setup() {
		// Ensure Archiveless exists and is enabled.
		if ( ! class_exists( '\Archiveless' ) ) {
			return;
		}

		if ( ! is_admin() ) {
			add_filter( 'posts_where', [ $this, 'posts_where' ], 10, 2 );
		}
	}

	/**
	 * Hide archiveless posts on non-singular pages.
	 *
	 * @param string    $where MySQL WHERE clause.
	 * @param \WP_Query $query Current WP_Query object.
	 *
	 * @return string WHERE clause, potentially with 'archiveless' post status
	 *                      removed.
	 */
	public function posts_where( $where, $query ) {
		global $wpdb;

		if ( ! isset( \Archiveless::$status ) ) {
			return $where;
		}

		$archiveless_status = \Archiveless::$status;

		if (
			! is_admin() &&
			! $query->is_singular() &&
			false !== strpos( $where, " OR {$wpdb->posts}.post_status = '{$archiveless_status}'" )
		) {
			$where = str_replace(
				" OR {$wpdb->posts}.post_status = '{$archiveless_status}'",
				'',
				$where
			);
		}

		return $where;
	}
}

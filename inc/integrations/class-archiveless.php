<?php
/**
 * WP Irving integration for Archiveless.
 *
 * @package WP_Irving;
 */

namespace WP_Irving;

/**
 * Class to replicate Archiveless's query modifications.
 */
class Archiveless {

	/**
	 * Constructor for class.
	 */
	public function __construct() {
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
	 * @param  string $where MySQL WHERE clause.
	 * @param  WP_Query $query Current WP_Query object.
	 * @return string WHERE clause, potentially with 'archiveless' post status
	 *                      removed.
	 */
	public function posts_where( $where, $query ) {
		global $wpdb;

		$archiveless_status = \Archiveless::$status;

		if (
			empty( $query->get( 'irving-path' ) ) ||
			$query->is_singular() ||
			false === strpos( $where, " OR {$wpdb->posts}.post_status = '{$archiveless_status}'" )
		) {
			return $where;
		}

		$where = str_replace(
			" OR {$wpdb->posts}.post_status = '{$archiveless_status}'",
			'',
			$where
		);

		return $where;
	}
}

add_action( 'init', function() {
	new \WP_Irving\Archiveless();
} );


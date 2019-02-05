<?php
/**
 * Class file for Irving's Paginator component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Paginator component.
 */
class Paginator extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'paginator';


	/**
	 * Determine which config keys should be passed into result.
	 *
	 * @var array
	 */
	public $whitelist = [];

	/**
	 * Default Irving query args to strip from pagination requests.
	 *
	 * @var array
	 */
	public $query_arg_blacklist = [
		'context',
		'path',
	];

	/**
	 * Define the default config for this component.
	 *
	 * @return array
	 */
	public function default_config() {
		return [
			'base'               => '',
			'irving_path_params' => [],
		];
	}

	/**
	 * Generate the pagination from a \WP_Query object.
	 *
	 * @param \WP_Query $wp_query \WP_Query object.
	 * @return Paginator
	 */
	public function set_from_wp_query( \WP_Query $wp_query ) {

		// Get and validate root path.
		$irving_path = $wp_query->get( 'irving-path' );
		if ( empty( $irving_path ) ) {
			wp_die( esc_html__( 'WP_Query must have an `irving-path` to use pagination', 'wp-irving' ) );
		}

		// Determine the base.
		switch ( true ) {

			// We're already on a paged url, so the base is everything up to
			// `/path/`.
			case false !== strpos( $irving_path, '/page/' ):
				$base = trailingslashit( substr( $irving_path, 0, strpos( $irving_path, '/page/' ) ) );
				break;

			case ! $wp_query->is_paged():
			default:
				$base = $irving_path;
				break;

		}

		$this->set_config( 'base', $base );
		$this->set_config( 'irving_path_params', $wp_query->get( 'irving-path-params' ) );

		$loop_query = $GLOBALS['wp_query'];
		// We need to carefully insert the Irving query as the global query so
		// the various core functions reference the correct query.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$GLOBALS['wp_query'] = $wp_query;
		$links = paginate_links(
			[
				'base' => $base . '%_%',
				'type' => 'array',
			]
		);
		// Then we just kind of slide this guy back in like nothing happened...
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$GLOBALS['wp_query'] = $loop_query;

		$this->set_children( array_map( [ $this, 'map_link' ], (array) $links ) );

		// Allow for further customizations.
		do_action( 'wp_irving_paginator', $this, $wp_query );

		// Clean all children components.
		$this->children = array_map( [ $this, 'cleanup_children' ], $this->children );

		return $this;
	}

	/**
	 * Map the raw html of a paginated_links() item to a pagination-link component.
	 *
	 * @param string $link - A paginated_links() generated html link item.
	 * @return Component
	 */
	protected function map_link( $link ) {
		$doc = new \DOMDocument();
		$doc->loadHTML( $link );

		$pagination_link = ( new Pagination_Link() );

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
		$pagination_link->set_config( 'text', $doc->textContent );

		// Handle classes.
		$span = $doc->getElementsByTagName( 'span' )[0];
		if ( $span ) {
			$class_name = $span->getAttribute( 'class' );
			if ( strstr( $class_name, 'current' ) ) {
				$pagination_link->set_config( 'current', true );
			}
		}

		// Handle URL.
		$anchor = $doc->getElementsByTagName( 'a' )[0];
		if ( $anchor ) {
			$pagination_link->set_config( 'url', $anchor->getAttribute( 'href' ) );
		}

		return $pagination_link;
	}

	/**
	 * Clean up all children `pagination-link` components.
	 *
	 * @param \Pagination_Link $pagination_link \Pagination_Link component to clean.
	 * @return \Pagination_Link
	 */
	public function cleanup_children( $pagination_link ) {

		$url = $pagination_link->get_config( 'url' );
		if ( empty( $url ) ) {
			return $pagination_link;
		}

		// Apply custom params.
		$url = add_query_arg(
			array_filter( (array) $this->get_config( 'irving_path_params' ) ),
			$url
		);

		// Strip the api query args from the url.
		$url = remove_query_arg(
			apply_filters( 'wp_irving_pagination_query_arg_blacklist', $this->query_arg_blacklist ),
			$url
		);

		$pagination_link->set_config( 'url', $url );

		return $pagination_link;
	}
}

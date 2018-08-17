<?php
/**
 * Class file for Irving's Paginator component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

use DOMDocument;

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
	 * Define the default config of a byline.
	 *
	 * @return array Default config values for this component.
	 */
	public function default_config() {
		return [];
	}

	/**
	 * Mutate the Paginator class with the current query object.
	 *
	 * @param \WP_Query $wp_query - The Irving context WP_Query object.
	 * @return Paginator
	 */
	public function set_from_wp_query( \WP_Query $wp_query ) {
		$loop_query = $GLOBALS['wp_query'];
		// We need to carefully insert the Irving query as the global query so
		// the various core functions reference the correct query.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$GLOBALS['wp_query'] = $wp_query;
		$links = paginate_links([
			'base' => '%_%',
			'type' => 'array',
		]);
		// Then we just kind of slide this guy back in like nothing happened...
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$GLOBALS['wp_query'] = $loop_query;

		$this->set_children( array_map( [ $this, 'map_link' ], $links ) );

		return $this;
	}

	/**
	 * Map the raw html of a paginated_links() item to a pagination-link component.
	 *
	 * @param string $link - A paginated_links() generated html link item.
	 * @return Component
	 */
	protected function map_link( $link ) {
		$doc = new DOMDocument();
		$doc->loadHTML( $link );
		$span = $doc->getElementsByTagName( 'span' )[0];
		$anchor = $doc->getElementsByTagName( 'a' )[0];

		$data = [
			'current' => false,
			'url'     => '',
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
			'text'    => $doc->textContent,
		];

		if ( $span ) {
			$class_name = $span->getAttribute( 'class' );
			if ( strstr( $class_name, 'current' ) ) {
				$data['current'] = true;
			}
		}

		if ( $anchor ) {
			$data['url'] = $anchor->getAttribute( 'href' );
		}

		return new Component( 'pagination-link', $data );
	}
}

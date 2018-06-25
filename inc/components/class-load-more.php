<?php
/**
 * Class file for Irving's LoadMore component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the LoadMore component.
 */
class LoadMore extends Component {

	/**
	 * The component name.
	 *
	 * @var string
	 */
	public $name = 'load-more';

	/**
	 * Define the default config of a load more component.
	 *
	 * @return array
	 */
	public function default_config() {
		return [
			'label' => 'Load More',
			'url'   => $this->get_next_posts_page_link(),
		];
	}

	/**
	 * Get a API friendly url of the next page of posts.
	 *
	 * @return string|null
	 */
	public function get_next_posts_page_link() {
		global $paged, $wp_query;

		$current_page = $paged;
		if ( ! $current_page ) {
			$current_page = 1;
		}

		// We have to duplicate the majority of \get_next_posts_page_link(),
		// because we need to call \get_pagenum_link() with escape = false.
		$next_page = intval( $current_page ) + 1;
		$max_page  = $wp_query->max_num_pages;
		if ( ! $max_page || $max_page >= $next_page ) {
			return get_pagenum_link( $next_page, false );
		}

		return null;
	}
}

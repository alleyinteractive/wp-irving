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
class Load_More extends Component {

	/**
	 * The component name.
	 *
	 * @var string
	 */
	public $name = 'load-more';

	/**
	 * The current archive page.
	 *
	 * @var int
	 */
	public $page = 0;

	/**
	 * The total number of archive pages.
	 *
	 * @var int
	 */
	public $num_pages = 0;

	/**
	 * Define the default config of a load more component.
	 *
	 * @return array
	 */
	public function default_config() {
		add_filter( 'get_pagenum_link', [ $this, 'map_next_page_url' ], 10, 2 );

		return [
			'label' => 'Load More',
			'url'   => $this->get_next_posts_page_link(),
		];
	}

	/**
	 * Set the necessary pagination data to render the component.
	 *
	 * @param int $page The current archive page.
	 * @param int $num_pages The total number of archive pages.
	 * @return Load_More
	 */
	public function set_pagination_vars( $page, $num_pages ) {
		$this->page      = $page;
		$this->num_pages = $num_pages;
		// Reapply generated link if pagination vars are updated.
		$this->set_config( 'url', $this->get_next_posts_page_link() );
		return $this;
	}

	/**
	 * Get a API friendly url of the next page of posts.
	 *
	 * @return string|null
	 */
	public function get_next_posts_page_link() {
		if ( ! $this->page ) {
			$this->page = 1;
		}

		// We have to duplicate the majority of \get_next_posts_page_link(),
		// because we need to call \get_pagenum_link() with escape = false.
		$next_page = intval( $this->page ) + 1;
		if ( $this->num_pages >= $next_page ) {
			return get_pagenum_link( $next_page, false );
		}

		return null;
	}

	/**
	 * Map the generated next page url to a components endpoint compatible url.
	 * Example:
	 * /components/page/2/?path=/2018/page/2/&context=page
	 * ->
	 * /components/?path=/2018/page/2/&context=page
	 *
	 * @param string $url The next page url.
	 * @return string
	 */
	public function map_next_page_url( $url ) {
		$page_regex = '/page\/\d+\/?/';
		$page_match = [];
		preg_match( $page_regex, $url, $page_match );

		// Remove pagination from api url.
		$url = preg_replace( $page_regex, '', $url );

		// Move pagination path items to path query param.
		$parsed_url     = wp_parse_url( $url );
		$query          = wp_parse_args( $parsed_url['query'] );
		$query['path']  = preg_replace( $page_regex, '', $query['path'] );
		$query['path']  = rtrim( $query['path'], '/' );
		$query['path'] .= '/' . $page_match[0];

		// Add new query back to url.
		$url = preg_replace( '/\?.*?$/', '', $url );
		return add_query_arg( $query, $url );
	}
}

/**
 * Helper to get the content grid component.
 *
 * @param  string $name     Component name or array of properties.
 * @param  array  $config   Component config.
 * @param  array  $children Component children.
 * @return Load_More An instance of the Load_More class.
 */
function load_more( $name = '', array $config = [], array $children = [] ) {
	return new Load_More( $name, $config, $children );
}

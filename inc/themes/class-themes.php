<?php
/**
 * This class manages theme logic.
 *
 * @package WP_Irving.
 */

namespace WP_Irving\Themes;

class Themes {

	public static $path_to_theme = __DIR__ . '/example-theme/';

	/**
	 * Class instance.
	 *
	 * @var null|self
	 */
	protected static $instance;

	/**
	 * Get class instance
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
			static::$instance->setup();
		}
		return static::$instance;
	}

	/**
	 * Setup the singleton. Validate JWT is installed, and setup hooks.
	 */
	public function setup() {

		add_action( 'init', [ $this, 'register_blocks' ] );

		add_filter( 'wp_irving_components_route', [ $this, 'load_theme'], 999, 5 );
		// add_filter( 'wp_irving_theme_parsing', [ $this, 'conform_to_data_schema' ], 10, 2 );

		// add_filter( 'wp_irving_theme_template_part_post-title', [ $this, 'parse_post_title' ] );
	}

	public function register_blocks() {
		register_block_type(
			'irving/container',
			[
				'render_callback' => function( $attributes ) { return '<div>testing</div>'; },
				'attributes'      => [
					'some_string' => [
						'default' => 'default string',
						'type'    => 'string'
					],
					'some_array'  => [
						'type'  => 'array',
						'items' => [
							'type' => 'string',
						],
					]
				]
			]
		);

		// print_r(\WP_Block_Type_Registry::get_instance()->get_all_registered());
		// die();
	}

	public function conform_to_data_schema( $name, $data ) {


		// Ensure values are all correct.
		$data = [
			'defaults'       => (array) ( $data['defaults'] ?? [] ),
			'page'           => (array) ( $data['page'] ?? [] ),
			'providers'      => (array) ( $data['providers'] ?? [] ),
			'redirectTo'     => (string) ( $data['redirectTo'] ?? '' ),
			'redirectStatus' => absint( $data['redirectStatus'] ?? 0 ),
		];


		$data['defaults'] = $this->traverse_components( $data['defaults'], [ $this, 'conform_to_component_schema' ] );
		$data['defaults'] = $this->traverse_components( $data['defaults'], [ $this, 'swap_template_part' ] );
		$data['page']     = $this->traverse_components( $data['page'], [ $this, 'conform_to_component_schema' ] );
		$data['page']     = $this->traverse_components( $data['page'], [ $this, 'swap_template_part' ] );

		return $data;
	}

	public function traverse_components( $components, $callable ) {
		return array_map(
			function( $component ) use ( $callable ) {
				$component = call_user_func( $callable, (array) $component );
				if ( ! empty( $component['children'] ?? [] ) ) {
					$component['children'] = $this->traverse_components( $component['children'], $callable );
				}
				return $component;
			},
			$components
		);
	}

	public function conform_to_component_schema( array $component ) {
		$component = (array) $component;

		return [
			'name'            => (string) $component['name'] ?? '',
			'config'          => (array) $component['config'] ?? [], // todo JS-case keys
			'children'        => array_values( array_filter( (array) $component['children'] ?? [] ) ),
			'componentGroups' => [],
			'theme'           => (string) $component['theme'] ?? 'default',
		];
	}

	/**
	 * [parse_template description]
	 * @param  [type] $template_part [description]
	 * @param  [type] $wp_query      [description]
	 * @return [type]                [description]
	 */
	public function swap_template_part( $component ) {

		if ( 0 !== strpos( $component['name'], 'template-parts/' ) ) {
			return $component;
		}

		$partial_name = str_replace( 'template-parts/', '', $component['name'] );

		$partial = $this->get_template_part_by_slug( $partial_name );

		if ( ! $partial ) {
			return $component;
		}

		$partial = apply_filters( 'wp_irving_theme_template_part_' . $partial_name, $partial, $component );

		return array_replace_recursive( $component, $partial );
	}

	public function parse_post_title( $partial ) {
		$partial['config']['content'] = get_the_title();
		return $partial;
	}

	/**
	 * Add a welcome message if nothing has been modified in previous hooks.
	 *
	 * @param array            $data     Data for response.
	 * @param \WP_Query        $wp_query WP_Query object corresponding to this
	 *                                   request.
	 * @param string           $context  The context for this request.
	 * @param string           $path     The path for this request.
	 * @param \WP_REST_Request $request  WP_REST_Request object.
	 * @return  array Data for response.
	 */
	function load_theme(
		array $data,
		\WP_Query $wp_query,
		string $context,
		string $path,
		\WP_REST_Request $request
	): array {

		$markup = file_get_contents( __DIR__ . '/example-theme/templates/single.html' );

		if ( isset( $wp_query->post->post_content ) ) {
			$markup = $wp_query->post->post_content;
		}

		$blocks = parse_blocks( $markup );

		$components = self::blocks_to_components( $blocks );

		$data['page'] = $blocks;
		// $data['page'] = $components;

		// $data['page'] = self::recursively_render_blocks( parse_blocks( $markup ) );


		// echo serialize_blocks( $data['page'] ); die();

		// echo render_block( $post_title );

		// print_r($post_title);
		// die();

		return $data;

		// Build defaults.
		if ( 'site' === $context ) {
			$defaults = $this->get_template_by_slug( 'defaults' );
			$data     = array_merge( $data, $defaults );
		}

		$template_slug = $this->get_slug_template_by_query( $wp_query );
		$template      = $this->get_template_by_slug( $template_slug );
		$data          = array_merge( $data, $template );

		$data = apply_filters( 'wp_irving_theme_parsing', self::$path_to_theme, $data );

		return $data;
	}


	public static function blocks_to_components( $blocks ) {

		$components = [];

		foreach ( $blocks as &$block ) {

			if ( ! isset( $block['blockName'] ) ) {
				continue;
			}

			$components[] = [
				'name'        => $block['blockName'],
				// 'originalAttributes' => $block['attrs'],
				'attributes' => array_merge(
					$block['attrs'],
					[
						'innerHTML'    => $block['innerHTML'],
						'innerContent' => $block['innerContent'],
						'renderedBlock' => render_block( $block ),
					]
				),
				'children'    => self::blocks_to_components( $block['innerBlocks'] ),
			];
		}

		return $components;
	}


	/**
	 * Placeholder for WP Core's template hierarchy parsing logic.
	 *
	 * @param  \WP_Query $wp_query \WP_Query object.
	 * @return string
	 */
	public function get_slug_template_by_query( \WP_Query $wp_query ): string {
		switch ( true ) {
			case $wp_query->is_single():
				return 'single';

			default:
				return 'index';
		}
	}

	public function get_template_part_by_slug( $slug ) {
		return $this->load_json_file( sprintf( 'template-parts/%1$s.json', $slug ) );
	}

	public function get_template_by_slug( $slug ) {
		return $this->load_json_file( sprintf( 'templates/%1$s.json', $slug ) );
	}

	/**
	 * Helper to load a JSON file.
	 *
	 * @param  string $relative_path Relative path to the file from the self::$path_to_theme value.
	 * @return array
	 */
	public function load_json_file( $relative_path ) {

		$file_path = self::$path_to_theme . $relative_path;

		// Check if the file exists.
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		$contents = file_get_contents( $file_path );

		$json = json_decode( $contents, true );

		if ( ! is_null( $json ) ) {
			return $json;
		}

		echo "Something died at " . $file_path;
		echo json_last_error(); die();
	}
}

( new \WP_Irving\Themes\Themes() )->instance();

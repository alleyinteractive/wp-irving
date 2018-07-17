<?php
/**
 * Class file for Irving's Content component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Content component.
 */
class Content extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'content';

	/**
	 * Define the default config of a content component.
	 *
	 * @return array
	 */
	public function default_config() {
		return [];
	}

	/**
	 * Update the Component's state with a post's content.
	 *
	 * @param \WP_Post $post A post instance.
	 * @return Content
	 */
	public function set_post( \WP_Post $post ) {
		// If gutenberg is not enabled return the post's content as raw HTML.
		if ( ! function_exists( 'gutenberg_parse_blocks' ) ) {
			$this->children = [
				new Component(
					'rawHTML',
					[ 'content' => apply_filters( 'the_content', $post->post_content ) ]
				),
			];
		} else {
			$blocks = gutenberg_parse_blocks( $post->post_content );
			$blocks = array_values( array_filter( $blocks, function ( $block ) {
				return ! preg_match( '/^\s+$/', $block['innerHTML'] );
			}));
			$this->children = array_map( [ $this, 'map_block' ], $blocks );
		}

		return $this;
	}

	/**
	 * Map a block array to a Component instance.
	 *
	 * @todo: Create "columns" and "column" components that can handle placing child blocks within the wrapping markup.
	 *
	 * @param array $block A parsed block associative array.
	 * @return Component
	 */
	private function map_block( array $block ) {
		// The presence of html means this is a non dynamic block.
		if ( ! empty( $block['innerHTML'] ) ) {
			// Clean up extraneous whitespace characters.
			$content = preg_replace( '/[\r\n\t\f\v]/', '', $block['innerHTML'] );
			return new Component(
				'rawHTML',
				array_merge( $block['attrs'] ?? [], [ 'content' => $content ] ),
				array_map( [ $this, 'map_block' ], $block['innerBlocks'] ?? [] )
			);
		}

		// A dynamic block. All attributes will be available.
		return new Component(
			$block['blockName'],
			$block['attrs'],
			array_map( [ $this, 'map_block' ], $block['innerBlocks'] ?? [] )
		);
	}
}

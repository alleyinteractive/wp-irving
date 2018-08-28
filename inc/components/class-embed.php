<?php
/**
 * Class file for Irving's Embed component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Embed component.
 */
class Embed extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'embed';

	/**
	 * Define the default config of a header.
	 *
	 * @return array
	 */
	public function default_config() {
		return [
			'content' => ''
			// caption
		];
	}

	/**
	 * @param array $block
	 */
	public function set_from_block( array $block ) {
		$html = wp_oembed_get( $block['attrs']['url'] );
		$doc = new \DOMDocument();
		libxml_use_internal_errors( true );
		// skip html wrapper.
		$doc->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		$script_elements = $doc->getElementsByTagName( 'script' );
		$script_tags = [];
		foreach ( $script_elements as $script ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
			$script_tags[] = $script->ownerDocument->saveHTML( $script );
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
			$script->parentNode->removeChild( $script );
		}

		$content = str_replace( $block['attrs']['url'], $doc->saveHTML(), $block['innerHTML'] );

		return $this->set_config( 'rich', true )
			->set_config( 'provider', $block['attrs']['providerNameSlug'] ?? '' )
			->set_config( 'content', $content )
			->set_config( 'scripts', $script_tags );
	}
}

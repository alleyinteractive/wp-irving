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
	 * Define the default config of an embed.
	 *
	 * @return array
	 */
	public function default_config() {
		return [
			'content' => '',
			'provider' => '',
			'rich' => true,
		];
	}

	/**
	 * Container for filtered scripts from embed html.
	 *
	 * @var array
	 */
	private static $scripts = [];

	/**
	 * Add a script to the container.
	 *
	 * @param array $script_attrs - The attributes of a script tag.
	 */
	public static function add_script( array $script_attrs ) {
		$scripts[] = $script_attrs;
	}

	/**
	 * Get all gathered scripts, and clear the container.
	 *
	 * @return array
	 */
	public static function get_scripts() {
		$scripts = static::$scripts;
		static::$scripts = [];
		return $scripts;
	}

	/**
	 * Set embed configuration from a parsed Gutenberg embed block data.
	 *
	 * @param array $block - The saved markup of an embed block.
	 * @return Embed
	 */
	public function set_from_block( array $block ) {
		$html = wp_oembed_get( $block['attrs']['url'] );
		$doc = new \DOMDocument();
		// Ignore errors related to HTML5 tags being parsed.
		libxml_use_internal_errors( true );

		$skip_html_and_body_wrapper = LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD;
		$doc->loadHTML( $html, $skip_html_and_body_wrapper );

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
		$script_elements = $doc->getElementsByTagName( 'script' );
		// Extract scripts from raw HTML and save them to be rendered separately.
		foreach ( $script_elements as $script ) {
			static::add_script([
				'src' => $script->getAttribute( 'src' ),
				'defer' => $script->hasAttribute( 'defer' ),
				'async' => $script->hasAttribute( 'async' ),
			]);

			$script->parentNode->removeChild( $script );
		}
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
		$content = str_replace( $block['attrs']['url'], $doc->saveHTML(), $block['innerHTML'] );

		// Restore original error optional value.
		libxml_use_internal_errors( false );

		$this->set_config( 'provider', $block['attrs']['providerNameSlug'] ?? '' );
		$this->set_config( 'content', $content );

		return $this;
	}
}

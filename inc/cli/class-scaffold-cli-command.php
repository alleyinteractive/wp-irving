<?php
/**
 * WP-Irving commands for scaffolding code.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_CLI;
use WP_CLI\Utils;
use WP_CLI\Process;
use WP_CLI\Inflector;

/**
 * Class to add additional scaffolding options to the `wp scaffold` command.
 */
class Scaffold_CLI_Command extends \Scaffold_Command {

	/**
	 * Scaffold out a new component.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : Component slug.
	 *
	 * [--force]
	 * : Override existing code.
	 *
	 * ## EXAMPLES
	 *
	 *     # Scaffold a new WP-Irving component.
	 *     $ wp irving_component jumbotron --force=yes
	 *     Success: Created '/var/www/thing.com/wp-content/themes/theme/'.
	 *
	 * @param array $args       CLI args.
	 * @param array $assoc_args CLI associate args.
	 */
	public function irving_component( $args, $assoc_args ) {

		// Validate component slug.
		$component_slug = $args[0] ?? '';
		if ( empty( $component_slug ) ) {
			WP_CLI::error( __( 'Component slug missing or invalid', 'wp-irving' ) );
		}

		// Construct data needed for the template.
		$theme_name = wp_get_theme()->get( 'Name' );
		$data       = [
			'class'     => $this->slug_to_label( $component_slug, '_' ),
			'domain'    => strtolower( str_replace( ' ', '-', $theme_name ) ),
			'fields'    => $this->build_fields(),
			'label'     => $this->slug_to_label( $component_slug ),
			'namespace' => $theme_name,
			'package'   => $theme_name,
			'slug'      => $component_slug,
		];

		// Determine where to put these files.
		$components_dir = get_template_directory() . '/inc/components';
		$this->create_files(
			[

				"$components_dir/class-{$component_slug}.php" => $this->mustache_render( 'wp-irving-component.mustache', $data ),
			],
			(bool) ( $assoc_args['force'] ?? false )
		);
	}

	/**
	 * Override mustache render so we can access it here.
	 *
	 * @param  string $template Template.
	 * @param  array  $data     Data to use in the template.
	 * @return string
	 */
	private static function mustache_render( $template, $data ) {
		$template_path = dirname( dirname( __FILE__ ) ) . "/cli/templates/{$template}";
		return Utils\mustache_render( $template_path, $data );
	}

	/**
	 * Build an array to be used to scaffold the config and Fieldmanger fields
	 * in the component.
	 *
	 * @return array
	 */
	private function build_fields() {

		$fields = [];

		$adding_fields = true;
		do {

			// Build field properties.
			$properties = [
				'default_value' => '',
				'fm_field'      => '',
				'label'         => '',
				'slug'          => '',
			];

			WP_CLI::success( __( 'Creating a new field (leave blank to stop)', 'wp-irving' ) );

			// Label.
			WP_CLI::success( 'Field Label:' );
			$properties['label'] = trim( fgets( STDIN ) );

			if ( empty( $properties['label'] ) ) {
				$adding_fields = false;
				continue;
			}

			// Set slug from label.
			$properties['slug'] = implode( '_', explode( ' ', $properties['label'] ) );
			$properties['slug'] = strtolower( $properties['slug'] );
			$properties['slug'] = sanitize_title_with_dashes( $properties['slug'] );

			// Default value.
			WP_CLI::success( 'Default Value:' );
			$properties['default_value'] = trim( fgets( STDIN ) );

			// Setup default value.
			if ( empty( $properties['default_value'] ) ) {
				$properties['default_value'] = '\'\'';
			}

			// Parse default value to check if we should attempt translating it.
			$value_parts = explode( '__', $properties['default_value'] );
			if ( isset( $value_parts[1] ) ) {
				$properties['default_value'] = "__( '{$value_parts[1]}', 'theme' )";
			}

			// Fm Field.
			WP_CLI::success( 'FM Field (default is Fieldmanager_Textfield):' );
			$input                  = (string) trim( fgets( STDIN ) );
			$properties['fm_field'] = ! empty( $input ) ? $input : 'Fieldmanager_Textfield';

			$fields[] = $properties;

		} while ( $adding_fields );

		return $fields;
	}

	/**
	 * Convert a slug into a capitalized and delimited version of itself.
	 *
	 * @param  string $slug      Component slug.
	 * @param  string $delimiter Delimiter to use for imploding.
	 * @return string
	 */
	public function slug_to_label( string $slug, $delimiter = ' ' ) : string {
		$parts = explode( '-', str_replace( '_', '-', $slug ) );
		$parts = array_map( 'ucfirst', $parts );
		return implode( $delimiter, $parts );
	}

}
WP_CLI::add_command( 'scaffold', __NAMESPACE__ . '\Scaffold_CLI_Command' );

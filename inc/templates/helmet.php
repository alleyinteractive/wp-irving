<?php
/**
 * Helmet helpers to manage the <head> in templates.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Templates;

use WP_Irving\Components\Component;

/**
 * Manage the template head by automatically inserting Helmet tags.
 *
 * @param array            $data    Data object to be hydrated by templates.
 * @param \WP_Query        $query   The current WP_Query object.
 * @param string           $context The context for this request.
 * @param string           $path    The path for this request.
 * @param \WP_REST_Request $request WP_REST_Request object.
 * @return array The updated endpoint data.
 */
function setup_helmet(
	array $data,
	\WP_Query $query,
	string $context,
	string $path,
	\WP_REST_Request $request
): array {

	// Disable Helmet management via filter.
	if ( ! apply_filters( 'wp_irving_setup_helmet', true ) ) {
		return $data;
	}

	// Unshift a helmet component to the top of the `defaults` array.
	if ( 'site' === $context ) {
		array_unshift(
			$data['defaults'],
			/**
			 * Filter for the helmet component in the `defaults` array.
			 *
			 * @param Component        $helmet  Helmet tag used in the
			 *                                  `defaults` array.
			 * @param array            $data    Data object to be hydrated by
			 *                                  templates.
			 * @param \WP_Query        $query   The current WP_Query object.
			 * @param string           $context The context for this request.
			 * @param string           $path    The path for this request.
			 * @param \WP_REST_Request $request WP_REST_Request object.
			 */
			apply_filters(
				'wp_irving_default_helmet_component',
				create_or_update_title( new Component( 'irving/helmet' ), get_bloginfo( 'name' ) ),
				$data,
				$query,
				$context,
				$path,
				$request
			)
		);
	}

	// Unshift a helmet component to the top of the `page` array.
	array_unshift(
		$data['page'],
		/**
		 * Filter for the helmet component in the `page` array.
		 *
		 * @param Component        $helmet  Helmet tag used in the `page` array.
		 * @param array            $data    Data object to be hydrated by
		 *                                  templates.
		 * @param \WP_Query        $query   The current WP_Query object.
		 * @param string           $context The context for this request.
		 * @param string           $path    The path for this request.
		 * @param \WP_REST_Request $request WP_REST_Request object.
		 */
		apply_filters(
			'wp_irving_page_helmet_component',
			create_or_update_title( new Component( 'irving/helmet' ), wp_title( '&raquo;', false ) ),
			$data,
			$query,
			$context,
			$path,
			$request
		)
	);

	return $data;
}

/**
 * Manage the template head by automatically inserting Helmet tags.
 *
 * @param Component $helmet    Helmet component in the head tag.
 * @param array     $head_tags Array of tags that should be injected into a
 *                             Helmet component.
 * @return Component
 */
function inject_head_tags( Component $helmet, array $head_tags = [] ): Component {

	// Handle titles.
	if ( isset( $head_tags['title'] ) ) {
		$helmet = create_or_update_title( $helmet, $head_tags['title'][0]['content'] ?? '' );
		unset( $head_tags['title'] );
	}

	// Loop through all tags.
	foreach ( $head_tags as $tag => $elements ) {

		// Loop through each instance of a tag.
		foreach ( $elements as $element ) {

			// Create a new component for each element.
			$helmet->append_child(
				( new Component( $tag ) )
					->set_config( $element['attributes'] ?? [] )
					->set_config( 'content', $element['content'] ?? '' )
					->set_child( $element['content'] ?? '' )
			);
		}
	}

	return $helmet;
}

/**
 * Helper that gets the title value.
 *
 * @param Component $helmet Helmet component.
 * @return null|string
 */
function get_title_from_helmet( Component $helmet ): ?string {

	if ( 'irving/helmet' !== $helmet->get_name() ) {
		return null;
	}

	// Loop through all Helmet children.
	foreach ( $helmet->get_children() as $child ) {

		// Skip non-titles.
		if ( ! 'title' === $child->get_name() ) {
			continue;
		}

		// If we have a `title` component and the first child is a string,
		// that's the title.
		if ( is_string( $child->get_children()[0] ?? null ) ) {
			return $child->get_children()[0];
		}
	}

	return null;
}

/**
 * Update the value of <title> within a <Helmet> component. If a child <title>
 * component doesn't exist, we'll create one.
 *
 * @param Component $helmet Helmet component.
 * @param string    $title  New title value.
 * @return Component
 */
function create_or_update_title( Component $helmet, string $title = '' ): Component {

	// Ensure characters are decoded before the response.
	$title = html_entity_decode( $title );

	// Loop through the children, modifying the child <title> by reference if
	// available.
	foreach ( $helmet->get_children() as &$child ) {
		if ( 'title' === $child->get_name() ) {
			$child->set_child( $title );
			return $helmet;
		}
	}

	// Fallback to prepending a new title component.
	return $helmet->prepend_child( new Component( 'title', [ 'children' => [ $title ] ] ) );
}

/**
 * Capture wpseo_head output and convert the tags into an array.
 *
 * @param string $markup String of HTML.
 * @param array  $tags   Which HTML tags should be parsed.
 * @return array Array of parsed html tags.
 */
function parse_html( string $markup, array $tags ): array {

	// phpcs:disable WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
	// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

	// Nothing found.
	if ( empty( $markup ) ) {
		return [];
	}

	// Create a DOMDocument and parse it.
	$dom = new \DOMDocument();
	@$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $markup ); // phpcs:ignore

	$parsed_markup = [];

	// Loop though various tags and breakdown the markup into an array for
	// easy use elsewhere.
	foreach ( $tags as $tag ) {

		// Get all nodes for a given tag.
		$nodes = $dom->getElementsByTagName( $tag );
		foreach ( $nodes as $node ) {

			// Build attributes array.
			$attributes = [];
			foreach ( $node->attributes as $attribute ) {
				$attributes[ $attribute->localName ] = $attribute->nodeValue;
			}

			// Add this tag's parsed values to our array.
			$parsed_markup[ $tag ][] = [
				'attributes' => $attributes,
				'content'    => $node->nodeValue,
				'tag'        => $node->tagName,
			];
		}
	}

	// phpcs:enable WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
	// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	return $parsed_markup;
}

/**
 * Capture the markup output by WordPress for the favicon.
 *
 * @return string
 */
function get_favicon_markup(): string {
	ob_start();
	wp_site_icon();
	return ob_get_clean();
}

/**
 * Parse WP's favicon markup and inject it into the Helmet component.
 *
 * @param Component $helmet Helmet component to modify.
 * @return Component
 */
function inject_favicon( Component $helmet ): Component {
	return inject_head_tags(
		$helmet,
		parse_html( get_favicon_markup(), [ 'link', 'meta' ] )
	);
}
add_action( 'wp_irving_default_helmet_component', __NAMESPACE__ . '\inject_favicon' );

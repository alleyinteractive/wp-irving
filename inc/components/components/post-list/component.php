<?php
/**
 * Post lists.
 *
 * Iterate through a WP_Query's posts.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use WP_Query;

/**
 * Register the component and callback.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'config_callback'   => function ( array $config ): array {
			global $wp_query;
			$query = $wp_query;

			$query_args = $config['query_args'] ?? [];

			if ( ! empty( $query_args ) ) {

				if ( wp_validate_boolean( $query_args['exclude'] ?? false ) ) {
					$query_args['post__not_in'] = post_list_get_and_add_used_post_ids();
				}

				// Create a new `WP_Query` object for the data provider/consumers.
				$query = new WP_Query( $query_args );
			}

			$config['wp_query'] = $query;

			return $config;
		},
		'children_callback' => function ( array $children, array $config ): array {
			$templates = wp_parse_args(
				$config['templates'],
				[
					'after'          => [],
					'before'         => [],
					'interstitials'  => [],
					'item'           => [],
					'item_overrides' => [],
					'no_results'     => [ __( 'No results found.', 'wp-irving' ) ],
					'wrapper'        => [],
				]
			);

			$query = $config['wp_query'];

			// Bail early if no posts found.
			if ( ! $query->have_posts() ) {
				return $templates['no_results'];
			}

			$post_ids = wp_list_pluck( $query->posts, 'ID' );

			// Add the current $post_ids to the list of used ids.
			post_list_get_and_add_used_post_ids( $post_ids );

			$post_ids_to_skip = (array) ( $config['post_ids_to_skip'] ?? [] );
			$post_ids         = array_values( array_diff( $post_ids, $post_ids_to_skip ) );

			$children = array_map(
				function ( $post_id, $index ) use ( $templates ) {

					// Decide which item to use.
					$item = $templates['item_overrides'][ $index ] ?? $templates['item'];

					// Ensure single items are wrapped in an array.
					$item = ( isset( $item[0] ) ) ? $item : [ $item ];

					return [
						'name'     => 'irving/post-provider',
						'config'   => [
							'post_id' => $post_id,
							'index'   => $index++,
						],
						'children' => $item,
					];
				},
				$post_ids,
				array_keys( $post_ids )
			);

			// Inject interstitals.
			if ( ! empty( $templates['interstitials'] ) ) {
				// Track each interstitial that successfully injects, to
				// account for subsequent injections.
				$additional_offset = 0;

				foreach ( $templates['interstitials'] as $position => $interstitial ) {
					if (
						empty( $interstitial ) // $interstitial can't be empty.
						|| ! is_array( $interstitial ) // Or an array.
						|| absint( $position ) !== $position // $position must be an integer.
						|| $position > count( $children ) // And we must have more children than the position.
						|| array_keys( $interstitial ) !== range( 0, count( $interstitial ) - 1 ) // And ensure $interstitial is a sequential array.
					) {
						continue;
					}

					// Inject the interstitial.
					array_splice( $children, $position + $additional_offset, 0, $interstitial );

					$additional_offset++;
				}
			}

			// If a list of components are set as a wrapper, only use the first.
			$wrapper = $templates['wrapper'][0] ?? $templates['wrapper'];

			// Wrap the children.
			if ( ! empty( $wrapper ) ) {
				$children = [
					array_merge(
						$wrapper,
						[ 'children' => $children ]
					),
				];
			}

			// Prepend before components.
			if ( ! empty( $templates['before'] ) ) {
				array_unshift( $children, ...$templates['before'] );
			}

			// Append after components.
			if ( ! empty( $templates['after'] ) ) {
				array_push( $children, ...$templates['after'] );
			}

			return $children;
		},
	]
);

/**
 * Keep track of used post IDs to de-duplicate with `post__not_in`.
 *
 * @param array $post_ids_to_add Array of post ids to flag as used.
 * @return array
 */
function post_list_get_and_add_used_post_ids( array $post_ids_to_add = [] ): array {
	static $used_post_ids;

	// Initialize values.
	if ( is_null( $used_post_ids ) ) {
		$used_post_ids = [];
	}

	// Merge additional values.
	if ( ! empty( $post_ids_to_add ) ) {
		$used_post_ids = array_unique( array_merge( $used_post_ids, $post_ids_to_add ) );
	}

	return $used_post_ids;
}

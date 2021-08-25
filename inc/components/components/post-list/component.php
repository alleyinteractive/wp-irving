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

			// Grab the global in case we need it.
			global $wp_query;

			/**
			 * Fallback to the global WP_Query object if we don't pass any
			 * arguments directly into the Post List component.
			 */
			$config['wp_query'] = ! empty( $config['query_args'] )
				? get_wp_query_for_post_list( $config['query_args'] )
				: $wp_query;

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
 * Wrapper for getting a new WP_Query object.
 *
 * This allows us to deduplicate posts returned by Post List components.
 *
 * @see https://docs.wpvip.com/technical-references/code-quality-and-best-practices/using-post__not_in/
 *
 * @param array $query_args Query args for a new WP_Query.
 * @return WP_Query
 */
function get_wp_query_for_post_list( array $query_args ): WP_Query {

	// Do not deduplicate the WP_Query.
	if ( ! wp_validate_boolean( $query_args['exclude'] ?? false ) ) {
		return new WP_Query( $query_args );
	}

	// Ensure we have a value for `posts_per_page`.
	if ( ! isset( $query_args['posts_per_page'] ) ) {
		$query_args['posts_per_page'] = get_option( 'posts_per_page', 10 );
	}

	$number_of_posts_to_return = -1;

	/**
	 * If `posts_per_page` is a valid integer above 0, increase the requested
	 * total by the number of post IDs already used.
	 */
	if (
		is_int( $query_args['posts_per_page'] )
		&& $query_args['posts_per_page'] > 0
	) {

		// Remember the original number of posts to return.
		$number_of_posts_to_return = $query_args['posts_per_page'];

		// Increase our query by the number of posts already used, ensuring we
		// can deduplicate and still meet our original `posts_per_page` value.
		$query_args['posts_per_page'] = $number_of_posts_to_return + count( post_list_get_and_add_used_post_ids() );
	}

	// Get the new query.
	$query = new WP_Query( $query_args );

	// No valid results found anyway.
	if ( ! $query->have_posts() ) {
		return $query;
	}

	return deduplicate_query( $query, $number_of_posts_to_return );
}

/**
 * Remove any posts from a WP_Query object that have already been used
 *
 * @param WP_Query $query           Query object to deduplicate against.
 * @param int      $posts_to_return Max number of posts to return.
 * @return WP_Query
 */
function deduplicate_query( WP_Query $query, int $posts_to_return ): WP_Query {

	// Keep track of new posts.
	$deduplicated_posts = [];

	// Grab already used posts.
	$used_posts = post_list_get_and_add_used_post_ids();

	// Loop through each post and remove if we've already used it.
	foreach ( $query->posts as $post ) {

		// Handle both WP_Post objects, and post IDs when only fetching IDs.
		$post_id = $post->ID ?? $post;

		// If this post hasn't been used already.
		if ( ! in_array( $post_id, $used_posts, true ) ) {
			$deduplicated_posts[] = $post;
		}

		// Once we hit our number of posts to return, exit the loop.
		if (
			$posts_to_return >= 0
			&& count( $deduplicated_posts ) >= $posts_to_return
		) {
			break;
		}
	}

	// Update WP_Query to only include deduplicated posts, and reset the
	// indexes.
	$query->posts = array_values( $deduplicated_posts );

	// Update our count to reflect the change.
	$query->post_count = count( $query->posts );

	return $query;
}

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

<?php
/**
 * Term list.
 *
 * List of terms.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use WP_Term;
use WP_Term_Query;

/**
 * Register the component and callback.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'config_callback'   => function ( array $config ): array {

			// Support provider context.
			if ( isset( $config['object_ids'] ) ) {
				$config['query_args']['object_ids'] = $config['object_ids'];
			}

			$config['wp_term_query'] = new WP_Term_Query( $config['query_args'] );
			return $config;
		},
		'children_callback' => function ( array $children, array $config ): array {
			$templates = wp_parse_args(
				$config['templates'],
				[
					'after'         => [],
					'before'        => [],
					'interstitials' => [],
					'item'          => [],
					'no_results'    => [ __( 'No results found.', 'wp-irving' ) ],
					'wrapper'       => [],
				]
			);

			$wp_term_query = $config['wp_term_query'];

			// Bail early if no terms found.
			if ( empty( $wp_term_query->terms ?? [] ) ) {
				return $templates['no_results'];
			}

			// Ensure single items are wrapped in an array.
			$item = ( isset( $templates['item'][0] ) ) ? $templates['item'] : [ $templates['item'] ];

			$children = array_map(
				function ( $term_id ) use ( $item ) {
					return [
						'name'     => 'irving/term-provider',
						'config'   => [
							'term_id' => $term_id,
						],
						'children' => array_filter( $item ),
					];
				},
				wp_list_pluck( $wp_term_query->terms, 'term_id' )
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

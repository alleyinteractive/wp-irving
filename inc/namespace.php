<?php
/**
 * Irving functionality.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_Irving\REST_API;

/**
 * Irving equivelent to is_main_query(). True for the Components Endpoint
 * query.
 *
 * @return boolean
 */
function is_main_irving_query() {
	return REST_API\Components_Endpoint::$is_main_irving_query;
}

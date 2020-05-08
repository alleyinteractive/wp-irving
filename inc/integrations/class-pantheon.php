<?php
/**
 * WP Irving integration for VIP Go.
 *
 * @package WP_Irving;
 */

namespace WP_Irving;

/**
 * Class to handle modifications specific to VIP Go.
 */
class Pantheon {

	/**
	 * Constructor for class.
	 */
	public function __construct() {

	}
}

add_action(
	'init',
	function() {
		new \WP_Irving\Pantheon();
	}
);

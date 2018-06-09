<?php
/**
 *
 */

namespace WP_Irving\Component;

class Component {

	public $name = '';
	public $config = [];
	public $children = [];

	public function __construct( $name = '', array $config = [], array $children = [] ) {

		// Allow $name to be passed as a config array.
		if ( is_array( $name ) ) {
			$data     = $name;
			$name     = $data['name'] ?? '';
			$config   = $data['config'] ?? [];
			$children = $data['children'] ?? [];
		}

		// Store in class.
		$this->name     = $name;
		$this->config   = $config;
		$this->children = $children;

		return $this;
	}

	public function json() {
		return [
			'name'     => $this->name,
			'config'   => $this->config,
			'children' => $this->children,
		];
	}
}

function component( $name = '', array $config = [], array $children = [] ) {
	return new Component( $name, $config, $children );
}

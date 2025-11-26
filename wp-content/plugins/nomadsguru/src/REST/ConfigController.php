<?php

namespace NomadsGuru\REST;

use NomadsGuru\Core\Config;

class ConfigController {

	/**
	 * Register routes
	 */
	public function register_routes() {
		register_rest_route( 'nomadsguru/v1', '/config', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_config' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( 'nomadsguru/v1', '/config', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'update_config' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );
	}

	/**
	 * Check permission
	 */
	public function check_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get config
	 */
	public function get_config() {
		$config = Config::get_publishing_config();
		return new \WP_REST_Response( $config, 200 );
	}

	/**
	 * Update config
	 */
	public function update_config( $request ) {
		$data = array(
			'publishing_mode'        => sanitize_text_field( $request['publishing_mode'] ),
			'min_articles_per_batch' => intval( $request['min_articles_per_batch'] ),
			'max_articles_per_batch' => intval( $request['max_articles_per_batch'] ),
		);

		$result = Config::update_publishing_config( $data );

		if ( $result !== false ) {
			return new \WP_REST_Response( array( 'success' => true ), 200 );
		}

		return new \WP_REST_Response( array( 'error' => 'Failed to update config' ), 500 );
	}
}

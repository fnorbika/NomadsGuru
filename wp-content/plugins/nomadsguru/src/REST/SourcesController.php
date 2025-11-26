<?php

namespace NomadsGuru\REST;

class SourcesController {

	/**
	 * Register routes
	 */
	public function register_routes() {
		register_rest_route( 'nomadsguru/v1', '/sources', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_sources' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( 'nomadsguru/v1', '/sources', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'create_source' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( 'nomadsguru/v1', '/sources/(?P<id>\d+)', array(
			'methods'             => 'DELETE',
			'callback'            => array( $this, 'delete_source' ),
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
	 * Get sources
	 */
	public function get_sources() {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_deal_sources';
		$sources = $wpdb->get_results( "SELECT * FROM $table" );
		return new \WP_REST_Response( $sources, 200 );
	}

	/**
	 * Create source
	 */
	public function create_source( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_deal_sources';
		
		$data = array(
			'source_type'           => sanitize_text_field( $request['source_type'] ),
			'source_name'           => sanitize_text_field( $request['source_name'] ),
			'api_endpoint'          => esc_url_raw( $request['api_endpoint'] ),
			'sync_interval_minutes' => intval( $request['sync_interval_minutes'] ),
			'is_active'             => 1,
		);

		$result = $wpdb->insert( $table, $data );

		if ( $result ) {
			return new \WP_REST_Response( array( 'success' => true, 'id' => $wpdb->insert_id ), 201 );
		}

		return new \WP_REST_Response( array( 'error' => 'Failed to create source' ), 500 );
	}

	/**
	 * Delete source
	 */
	public function delete_source( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_deal_sources';
		
		$result = $wpdb->delete( $table, array( 'id' => $request['id'] ) );

		if ( $result ) {
			return new \WP_REST_Response( array( 'success' => true ), 200 );
		}

		return new \WP_REST_Response( array( 'error' => 'Failed to delete source' ), 500 );
	}
}

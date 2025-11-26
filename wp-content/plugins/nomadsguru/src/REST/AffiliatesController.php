<?php

namespace NomadsGuru\REST;

class AffiliatesController {

	/**
	 * Register routes
	 */
	public function register_routes() {
		register_rest_route( 'nomadsguru/v1', '/affiliates', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_affiliates' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( 'nomadsguru/v1', '/affiliates', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'create_affiliate' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( 'nomadsguru/v1', '/affiliates/(?P<id>\d+)', array(
			'methods'             => 'DELETE',
			'callback'            => array( $this, 'delete_affiliate' ),
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
	 * Get affiliates
	 */
	public function get_affiliates() {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_affiliate_programs';
		$affiliates = $wpdb->get_results( "SELECT * FROM $table" );
		return new \WP_REST_Response( $affiliates, 200 );
	}

	/**
	 * Create affiliate
	 */
	public function create_affiliate( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_affiliate_programs';
		
		$data = array(
			'program_name'    => sanitize_text_field( $request['program_name'] ),
			'program_type'    => sanitize_text_field( $request['program_type'] ),
			'url_pattern'     => sanitize_text_field( $request['url_pattern'] ),
			'commission_rate' => floatval( $request['commission_rate'] ),
			'is_active'       => 1,
		);

		$result = $wpdb->insert( $table, $data );

		if ( $result ) {
			return new \WP_REST_Response( array( 'success' => true, 'id' => $wpdb->insert_id ), 201 );
		}

		return new \WP_REST_Response( array( 'error' => 'Failed to create affiliate' ), 500 );
	}

	/**
	 * Delete affiliate
	 */
	public function delete_affiliate( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_affiliate_programs';
		
		$result = $wpdb->delete( $table, array( 'id' => $request['id'] ) );

		if ( $result ) {
			return new \WP_REST_Response( array( 'success' => true ), 200 );
		}

		return new \WP_REST_Response( array( 'error' => 'Failed to delete affiliate' ), 500 );
	}
}

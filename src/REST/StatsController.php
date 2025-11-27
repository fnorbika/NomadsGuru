<?php

namespace NomadsGuru\REST;

class StatsController {

	/**
	 * Register routes
	 */
	public function register_routes() {
		register_rest_route( 'nomadsguru/v1', '/stats', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_stats' ),
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
	 * Get stats
	 */
	public function get_stats() {
		global $wpdb;

		$stats = array(
			'total_deals'      => $this->get_total_deals(),
			'published_deals'  => $this->get_published_deals(),
			'pending_queue'    => $this->get_pending_queue(),
			'active_sources'   => $this->get_active_sources(),
			'total_revenue'    => 0, // Placeholder
		);

		return new \WP_REST_Response( $stats, 200 );
	}

	/**
	 * Get total deals
	 */
	private function get_total_deals() {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_raw_deals';
		return $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
	}

	/**
	 * Get published deals
	 */
	private function get_published_deals() {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_raw_deals';
		return $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE post_id IS NOT NULL" );
	}

	/**
	 * Get pending queue
	 */
	private function get_pending_queue() {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_processing_queue';
		return $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'pending'" );
	}

	/**
	 * Get active sources
	 */
	private function get_active_sources() {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_deal_sources';
		return $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE is_active = 1" );
	}
}

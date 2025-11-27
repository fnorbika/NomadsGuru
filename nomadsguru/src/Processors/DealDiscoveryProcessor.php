<?php

namespace NomadsGuru\Processors;

use NomadsGuru\Core\Database;
use NomadsGuru\Utils\DateHelper;

class DealDiscoveryProcessor {

	/**
	 * Run the discovery process
	 *
	 * @return array Result stats
	 */
	public function run() {
		$sources = $this->get_active_sources();
		$stats   = array(
			'processed_sources' => 0,
			'deals_found'       => 0,
			'deals_saved'       => 0,
			'errors'            => array(),
		);

		foreach ( $sources as $source ) {
			// Check if sync is needed based on interval
			if ( ! $this->should_sync( $source ) ) {
				continue;
			}

			$stats['processed_sources']++;
			
			// Instantiate source class based on type
			// For now, we'll just simulate or use a factory if we had one.
			// In a real implementation, we'd have a SourceFactory.
			$deals = $this->fetch_from_source( $source );

			if ( is_wp_error( $deals ) ) {
				$stats['errors'][] = "Source {$source->source_name}: " . $deals->get_error_message();
				continue;
			}

			$stats['deals_found'] += count( $deals );

			foreach ( $deals as $deal_data ) {
				if ( $this->save_deal( $source->id, $deal_data ) ) {
					$stats['deals_saved']++;
				}
			}

			$this->update_last_sync( $source->id );
		}

		return $stats;
	}

	/**
	 * Get active sources
	 *
	 * @return array
	 */
	private function get_active_sources() {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_deal_sources';
		return $wpdb->get_results( "SELECT * FROM $table WHERE is_active = 1" );
	}

	/**
	 * Check if source should be synced
	 *
	 * @param object $source
	 * @return bool
	 */
	private function should_sync( $source ) {
		if ( ! $source->last_sync ) {
			return true;
		}

		$last_sync = strtotime( $source->last_sync );
		$interval  = $source->sync_interval_minutes * 60;
		
		return ( time() - $last_sync ) >= $interval;
	}

	/**
	 * Fetch deals from source (Placeholder for factory logic)
	 *
	 * @param object $source
	 * @return array|\WP_Error
	 */
	private function fetch_from_source( $source ) {
		// TODO: Implement SourceFactory and actual fetching logic
		// For Phase 2, we return an empty array or mock data
		return array(); 
	}

	/**
	 * Save raw deal to database
	 *
	 * @param int   $source_id
	 * @param array $deal_data
	 * @return bool
	 */
	private function save_deal( $source_id, $deal_data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_raw_deals';

		// Basic validation
		if ( empty( $deal_data['external_id'] ) || empty( $deal_data['title'] ) ) {
			return false;
		}

		$data = array(
			'source_id'        => $source_id,
			'external_id'      => $deal_data['external_id'],
			'deal_data'        => json_encode( $deal_data ),
			'title'            => $deal_data['title'],
			'destination'      => isset( $deal_data['destination'] ) ? $deal_data['destination'] : '',
			'original_price'   => isset( $deal_data['original_price'] ) ? $deal_data['original_price'] : 0,
			'discounted_price' => isset( $deal_data['discounted_price'] ) ? $deal_data['discounted_price'] : 0,
			'currency'         => isset( $deal_data['currency'] ) ? $deal_data['currency'] : 'USD',
			'travel_dates_start' => isset( $deal_data['start_date'] ) ? $deal_data['start_date'] : null,
			'travel_dates_end'   => isset( $deal_data['end_date'] ) ? $deal_data['end_date'] : null,
			'raw_link'         => isset( $deal_data['url'] ) ? $deal_data['url'] : '',
			'created_at'       => DateHelper::now(),
		);

		// Insert or Update (on duplicate key update handled by logic or simple check)
		// Using replace for simplicity in this prototype
		$result = $wpdb->replace( $table, $data );

		return $result !== false;
	}

	/**
	 * Update last sync time
	 *
	 * @param int $source_id
	 */
	private function update_last_sync( $source_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_deal_sources';
		$wpdb->update( 
			$table, 
			array( 'last_sync' => DateHelper::now() ), 
			array( 'id' => $source_id ) 
		);
	}
}

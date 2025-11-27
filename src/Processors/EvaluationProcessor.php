<?php

namespace NomadsGuru\Processors;

use NomadsGuru\Services\AIService;
use NomadsGuru\Core\Database;

class EvaluationProcessor {

	/**
	 * @var AIService
	 */
	private $ai_service;

	public function __construct() {
		$this->ai_service = new AIService();
	}

	/**
	 * Run the evaluation process
	 *
	 * @param int $limit Max deals to evaluate
	 * @return int Number of deals evaluated
	 */
	public function run( $limit = 10 ) {
		$deals = $this->get_unprocessed_deals( $limit );
		$count = 0;

		foreach ( $deals as $deal ) {
			$deal_data = json_decode( $deal->deal_data, true );
			
			// Evaluate
			$evaluation = $this->ai_service->evaluate_deal( $deal_data );

			// Update DB
			$this->update_deal_score( $deal->id, $evaluation );
			
			// Add to processing queue if score is high enough (e.g., > 60)
			if ( $evaluation['score'] >= 60 ) {
				$this->add_to_queue( $deal->id );
			} else {
				// Mark as processed but ignored (or keep for manual review)
				$this->mark_as_processed( $deal->id );
			}

			$count++;
		}

		return $count;
	}

	/**
	 * Get unprocessed deals
	 *
	 * @param int $limit
	 * @return array
	 */
	private function get_unprocessed_deals( $limit ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_raw_deals';
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE is_processed = 0 LIMIT %d", $limit ) );
	}

	/**
	 * Update deal score
	 *
	 * @param int   $id
	 * @param array $evaluation
	 */
	private function update_deal_score( $id, $evaluation ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_raw_deals';
		$wpdb->update(
			$table,
			array(
				'evaluation_score'  => $evaluation['score'],
				'evaluation_reason' => $evaluation['reason'],
			),
			array( 'id' => $id )
		);
	}

	/**
	 * Add to processing queue
	 *
	 * @param int $raw_deal_id
	 */
	private function add_to_queue( $raw_deal_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_processing_queue';
		
		// Check if already in queue
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE raw_deal_id = %d", $raw_deal_id ) );
		
		if ( ! $exists ) {
			$wpdb->insert(
				$table,
				array(
					'raw_deal_id' => $raw_deal_id,
					'status'      => 'pending',
				)
			);
		}
		
		$this->mark_as_processed( $raw_deal_id );
	}

	/**
	 * Mark deal as processed
	 *
	 * @param int $id
	 */
	private function mark_as_processed( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_raw_deals';
		$wpdb->update(
			$table,
			array( 'is_processed' => 1 ),
			array( 'id' => $id )
		);
	}
}

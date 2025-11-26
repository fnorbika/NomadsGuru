<?php

namespace NomadsGuru\Processors;

use NomadsGuru\Utils\DateHelper;

class QueueProcessor {

	/**
	 * Process items in the queue
	 *
	 * @param int $limit Max items to process
	 * @return int Number of items processed
	 */
	public function run( $limit = 5 ) {
		$queue_items = $this->get_pending_items( $limit );
		$count = 0;

		foreach ( $queue_items as $item ) {
			$this->update_status( $item->id, 'processing' );

			try {
				// Get the raw deal
				$deal = $this->get_raw_deal( $item->raw_deal_id );
				
				if ( ! $deal ) {
					throw new \Exception( 'Deal not found' );
				}

				$deal_data = json_decode( $deal->deal_data, true );

				// Generate content
				$content_processor = new ContentGeneratorProcessor();
				$content = $content_processor->process( $deal_data );

				// Find image
				$image_processor = new ImageFinderProcessor();
				$image_url = $image_processor->process( $deal_data );

				// Store generated data back to deal (or create post - we'll do that in Phase 4)
				$this->store_generated_content( $deal->id, $content, $image_url );

				// Mark as completed
				$this->update_status( $item->id, 'completed' );
				$count++;

			} catch ( \Exception $e ) {
				$this->update_status( $item->id, 'failed', $e->getMessage() );
				$this->increment_retry( $item->id );
			}
		}

		return $count;
	}

	/**
	 * Get pending queue items
	 *
	 * @param int $limit
	 * @return array
	 */
	private function get_pending_items( $limit ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_processing_queue';
		return $wpdb->get_results( $wpdb->prepare( 
			"SELECT * FROM $table WHERE status = 'pending' AND retry_count < 3 ORDER BY created_at ASC LIMIT %d", 
			$limit 
		) );
	}

	/**
	 * Get raw deal by ID
	 *
	 * @param int $id
	 * @return object|null
	 */
	private function get_raw_deal( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_raw_deals';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
	}

	/**
	 * Update queue item status
	 *
	 * @param int    $id
	 * @param string $status
	 * @param string $error_message
	 */
	private function update_status( $id, $status, $error_message = '' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_processing_queue';
		
		$data = array(
			'status'     => $status,
			'updated_at' => DateHelper::now(),
		);

		if ( $error_message ) {
			$data['error_message'] = $error_message;
		}

		$wpdb->update( $table, $data, array( 'id' => $id ) );
	}

	/**
	 * Increment retry count
	 *
	 * @param int $id
	 */
	private function increment_retry( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_processing_queue';
		$wpdb->query( $wpdb->prepare( "UPDATE $table SET retry_count = retry_count + 1 WHERE id = %d", $id ) );
	}

	/**
	 * Store generated content (temporary - will be used in Phase 4 for actual post creation)
	 *
	 * @param int    $deal_id
	 * @param array  $content
	 * @param string $image_url
	 */
	private function store_generated_content( $deal_id, $content, $image_url ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_raw_deals';
		
		// For now, we'll store it in the deal_data JSON
		$deal = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $deal_id ) );
		$deal_data = json_decode( $deal->deal_data, true );
		
		$deal_data['generated_content'] = $content;
		$deal_data['featured_image'] = $image_url;
		
		$wpdb->update(
			$table,
			array( 'deal_data' => json_encode( $deal_data ) ),
			array( 'id' => $deal_id )
		);
	}
}

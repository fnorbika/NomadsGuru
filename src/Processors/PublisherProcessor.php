<?php

namespace NomadsGuru\Processors;

use NomadsGuru\Core\Config;
use NomadsGuru\Utils\DateHelper;

class PublisherProcessor {

	/**
	 * Run the publishing process
	 *
	 * @return array Stats
	 */
	public function run() {
		$config = Config::get_publishing_config();
		$mode = $config['publishing_mode'];
		
		$stats = array(
			'published' => 0,
			'queued'    => 0,
			'errors'    => array(),
		);

		// Get top-scored completed deals
		$deals = $this->get_publishable_deals( $config['max_articles_per_batch'] );

		if ( count( $deals ) < $config['min_articles_per_batch'] ) {
			$stats['errors'][] = 'Not enough deals to meet minimum batch size';
			return $stats;
		}

		foreach ( $deals as $deal ) {
			if ( $mode === 'automatic' ) {
				if ( $this->publish_deal( $deal ) ) {
					$stats['published']++;
				}
			} else {
				// Manual mode - just mark as ready for review
				$stats['queued']++;
			}
		}

		return $stats;
	}

	/**
	 * Get publishable deals
	 *
	 * @param int $limit
	 * @return array
	 */
	private function get_publishable_deals( $limit ) {
		global $wpdb;
		$deals_table = $wpdb->prefix . 'ng_raw_deals';
		$queue_table = $wpdb->prefix . 'ng_processing_queue';

		// Get deals that have been processed and are in completed queue
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT d.* FROM $deals_table d
			INNER JOIN $queue_table q ON d.id = q.raw_deal_id
			WHERE q.status = 'completed' AND d.post_id IS NULL
			ORDER BY d.evaluation_score DESC
			LIMIT %d",
			$limit
		) );
	}

	/**
	 * Publish a deal as a WordPress post
	 *
	 * @param object $deal
	 * @return bool
	 */
	private function publish_deal( $deal ) {
		$deal_data = json_decode( $deal->deal_data, true );
		$content = isset( $deal_data['generated_content'] ) ? $deal_data['generated_content'] : array();

		if ( empty( $content ) ) {
			return false;
		}

		// Transform affiliate links
		$affiliate_processor = new AffiliateLinkerProcessor();
		$body = $affiliate_processor->process( $content['body'], $deal->raw_link );

		// Create post
		$post_data = array(
			'post_title'   => $content['title'],
			'post_content' => $body,
			'post_status'  => 'publish',
			'post_type'    => 'post',
			'post_excerpt' => $content['meta_description'],
			'meta_input'   => array(
				'_ng_deal_id'          => $deal->id,
				'_ng_destination'      => $deal->destination,
				'_ng_original_price'   => $deal->original_price,
				'_ng_discounted_price' => $deal->discounted_price,
				'_ng_currency'         => $deal->currency,
				'_ng_evaluation_score' => $deal->evaluation_score,
				'_ng_affiliate_link'   => $affiliate_processor->transform_link( $deal->raw_link ),
			),
		);

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// Set featured image if available
		if ( isset( $deal_data['featured_image'] ) ) {
			$this->set_featured_image( $post_id, $deal_data['featured_image'] );
		}

		// Update deal with post_id
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'ng_raw_deals',
			array( 'post_id' => $post_id ),
			array( 'id' => $deal->id )
		);

		return true;
	}

	/**
	 * Set featured image from URL
	 *
	 * @param int    $post_id
	 * @param string $image_url
	 */
	private function set_featured_image( $post_id, $image_url ) {
		// This is a simplified version - in production, you'd download and attach the image
		// For now, we'll just store the URL in post meta
		update_post_meta( $post_id, '_ng_featured_image_url', $image_url );
	}
}

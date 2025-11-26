<?php

namespace NomadsGuru\Admin;

class QueueManager {

	/**
	 * Render the Queue Management page
	 */
	public function render() {
		$queue_items = $this->get_pending_queue();
		?>
		<div class="nomadsguru-wrap">
			<div class="ng-header">
				<h1><?php esc_html_e( 'Approval Queue', 'nomadsguru' ); ?> (<?php echo count( $queue_items ); ?> pending)</h1>
				<div>
					<button id="ng-approve-all" class="ng-button-primary"><?php esc_html_e( 'Approve All', 'nomadsguru' ); ?></button>
					<button id="ng-reject-all" class="ng-button-danger"><?php esc_html_e( 'Reject All', 'nomadsguru' ); ?></button>
				</div>
			</div>

			<div class="ng-card">
				<?php if ( empty( $queue_items ) ) : ?>
					<p><?php esc_html_e( 'No deals pending approval.', 'nomadsguru' ); ?></p>
				<?php else : ?>
					<?php foreach ( $queue_items as $item ) : ?>
						<?php $this->render_queue_item( $item ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a queue item
	 *
	 * @param object $item
	 */
	private function render_queue_item( $item ) {
		global $wpdb;
		$deals_table = $wpdb->prefix . 'ng_raw_deals';
		$deal        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $deals_table WHERE id = %d", $item->raw_deal_id ) );

		if ( ! $deal ) {
			return;
		}

		$deal_data = json_decode( $deal->deal_data, true );
		$content   = isset( $deal_data['generated_content'] ) ? $deal_data['generated_content'] : array();
		?>
		<div class="ng-queue-item" data-id="<?php echo esc_attr( $item->id ); ?>">
			<div class="ng-queue-item-header">
				<div class="ng-queue-item-info">
					<?php if ( isset( $deal_data['featured_image'] ) ) : ?>
						<img src="<?php echo esc_url( $deal_data['featured_image'] ); ?>" alt="Deal" class="ng-queue-thumbnail">
					<?php endif; ?>
					<div>
						<h3><?php echo esc_html( $deal->title ); ?></h3>
						<p class="ng-queue-meta">
							<span class="ng-badge active">Score: <?php echo esc_html( $deal->evaluation_score ); ?>/100</span>
							<span><?php echo esc_html( $deal->currency . ' ' . $deal->discounted_price ); ?></span>
							<span><?php echo esc_html( $deal->destination ); ?></span>
						</p>
					</div>
				</div>
				<div class="ng-queue-actions">
					<button class="ng-button-secondary ng-preview-deal" data-id="<?php echo esc_attr( $item->id ); ?>">
						<?php esc_html_e( 'Preview', 'nomadsguru' ); ?>
					</button>
					<button class="ng-button-primary ng-approve-deal" data-id="<?php echo esc_attr( $item->id ); ?>">
						✓ <?php esc_html_e( 'Approve', 'nomadsguru' ); ?>
					</button>
					<button class="ng-button-danger ng-reject-deal" data-id="<?php echo esc_attr( $item->id ); ?>">
						✗ <?php esc_html_e( 'Reject', 'nomadsguru' ); ?>
					</button>
				</div>
			</div>
			<?php if ( ! empty( $content ) ) : ?>
				<div class="ng-queue-preview" style="display:none;">
					<h4><?php esc_html_e( 'Generated Content Preview', 'nomadsguru' ); ?></h4>
					<p><strong><?php esc_html_e( 'Title:', 'nomadsguru' ); ?></strong> <?php echo esc_html( $content['title'] ?? '' ); ?></p>
					<p><strong><?php esc_html_e( 'Meta Description:', 'nomadsguru' ); ?></strong> <?php echo esc_html( $content['meta_description'] ?? '' ); ?></p>
					<div class="ng-content-preview"><?php echo wp_kses_post( substr( $content['body'] ?? '', 0, 500 ) . '...' ); ?></div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get pending queue items
	 *
	 * @return array
	 */
	private function get_pending_queue() {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_processing_queue';
		return $wpdb->get_results( "SELECT * FROM $table WHERE status = 'completed' ORDER BY created_at DESC LIMIT 50" );
	}
}

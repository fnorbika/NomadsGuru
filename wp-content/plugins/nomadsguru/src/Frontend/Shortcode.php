<?php

namespace NomadsGuru\Frontend;

class Shortcode {

	/**
	 * Initialize shortcode
	 */
	public function init() {
		add_shortcode( 'nomadsguru_deals', array( $this, 'render' ) );
	}

	/**
	 * Render the shortcode
	 *
	 * @param array $atts
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts( array(
			'count'       => 6,
			'columns'     => 3,
			'destination' => '',
			'min_score'   => 60,
		), $atts );

		$deals = $this->get_deals( $atts );

		ob_start();
		?>
		<div class="ng-deals-grid ng-cols-<?php echo esc_attr( $atts['columns'] ); ?>">
			<?php if ( empty( $deals ) ) : ?>
				<p><?php esc_html_e( 'No deals found.', 'nomadsguru' ); ?></p>
			<?php else : ?>
				<?php foreach ( $deals as $deal ) : ?>
					<?php $this->render_deal_card( $deal ); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get deals based on attributes
	 *
	 * @param array $atts
	 * @return array
	 */
	private function get_deals( $atts ) {
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => intval( $atts['count'] ),
			'meta_query'     => array(
				array(
					'key'     => '_ng_evaluation_score',
					'value'   => intval( $atts['min_score'] ),
					'compare' => '>=',
					'type'    => 'NUMERIC',
				),
			),
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_ng_evaluation_score',
			'order'          => 'DESC',
		);

		if ( ! empty( $atts['destination'] ) ) {
			$args['meta_query'][] = array(
				'key'     => '_ng_destination',
				'value'   => sanitize_text_field( $atts['destination'] ),
				'compare' => 'LIKE',
			);
		}

		return get_posts( $args );
	}

	/**
	 * Render a deal card
	 *
	 * @param \WP_Post $deal
	 */
	private function render_deal_card( $deal ) {
		$destination = get_post_meta( $deal->ID, '_ng_destination', true );
		$original_price = get_post_meta( $deal->ID, '_ng_original_price', true );
		$discounted_price = get_post_meta( $deal->ID, '_ng_discounted_price', true );
		$currency = get_post_meta( $deal->ID, '_ng_currency', true );
		$score = get_post_meta( $deal->ID, '_ng_evaluation_score', true );
		$affiliate_link = get_post_meta( $deal->ID, '_ng_affiliate_link', true );
		$image_url = get_post_meta( $deal->ID, '_ng_featured_image_url', true );

		$discount = 0;
		if ( $original_price > 0 ) {
			$discount = round( ( ( $original_price - $discounted_price ) / $original_price ) * 100 );
		}
		?>
		<div class="ng-deal-card">
			<?php if ( $image_url ) : ?>
				<div class="ng-deal-image">
					<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $destination ); ?>">
					<?php if ( $discount > 0 ) : ?>
						<span class="ng-discount-badge"><?php echo esc_html( $discount ); ?>% OFF</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<div class="ng-deal-content">
				<h3 class="ng-deal-title"><?php echo esc_html( $deal->post_title ); ?></h3>
				<div class="ng-deal-meta">
					<span class="ng-score">⭐ <?php echo esc_html( $score ); ?>/100</span>
					<span class="ng-destination">📍 <?php echo esc_html( $destination ); ?></span>
				</div>
				<div class="ng-deal-price">
					<span class="ng-price-current"><?php echo esc_html( $currency . ' ' . $discounted_price ); ?></span>
					<?php if ( $original_price > $discounted_price ) : ?>
						<span class="ng-price-original"><?php echo esc_html( $currency . ' ' . $original_price ); ?></span>
					<?php endif; ?>
				</div>
				<div class="ng-deal-actions">
					<a href="<?php echo esc_url( $affiliate_link ); ?>" class="ng-button-book" target="_blank" rel="noopener">
						<?php esc_html_e( 'Book Now', 'nomadsguru' ); ?>
					</a>
					<a href="<?php echo esc_url( get_permalink( $deal->ID ) ); ?>" class="ng-button-details">
						<?php esc_html_e( 'Details', 'nomadsguru' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}
}

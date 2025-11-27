<?php

namespace NomadsGuru\Blocks;

class DealsBlock {

	/**
	 * Register the block
	 */
	public function register() {
		register_block_type(
			NOMADSGURU_PLUGIN_DIR . 'blocks/deals-block',
			array(
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * Render the block
	 *
	 * @param array $attributes
	 * @return string
	 */
	public function render( $attributes ) {
		$defaults = array(
			'perPage'      => 12,
			'columns'      => 3,
			'sortBy'       => 'newest',
			'enableFilter' => true,
			'minScore'     => 60,
		);

		$attributes = wp_parse_args( $attributes, $defaults );

		// Get deals
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => intval( $attributes['perPage'] ),
			'meta_query'     => array(
				array(
					'key'     => '_ng_evaluation_score',
					'value'   => intval( $attributes['minScore'] ),
					'compare' => '>=',
					'type'    => 'NUMERIC',
				),
			),
		);

		// Sort
		switch ( $attributes['sortBy'] ) {
			case 'score':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_ng_evaluation_score';
				$args['order']    = 'DESC';
				break;
			case 'price':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_ng_discounted_price';
				$args['order']    = 'ASC';
				break;
			default:
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
		}

		$deals = get_posts( $args );

		ob_start();
		?>
		<div class="ng-deals-block">
			<?php if ( $attributes['enableFilter'] ) : ?>
				<div class="ng-filter-bar">
					<input type="text" class="ng-search" placeholder="<?php esc_attr_e( 'Search destination...', 'nomadsguru' ); ?>">
					<select class="ng-sort">
						<option value="newest"><?php esc_html_e( 'Latest', 'nomadsguru' ); ?></option>
						<option value="score"><?php esc_html_e( 'Top Rated', 'nomadsguru' ); ?></option>
						<option value="price"><?php esc_html_e( 'Cheapest', 'nomadsguru' ); ?></option>
					</select>
				</div>
			<?php endif; ?>

			<div class="ng-deals-grid ng-cols-<?php echo esc_attr( $attributes['columns'] ); ?>">
				<?php if ( empty( $deals ) ) : ?>
					<p><?php esc_html_e( 'No deals found.', 'nomadsguru' ); ?></p>
				<?php else : ?>
					<?php foreach ( $deals as $deal ) : ?>
						<?php $this->render_deal_card( $deal ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a deal card
	 *
	 * @param \WP_Post $deal
	 */
	private function render_deal_card( $deal ) {
		$destination      = get_post_meta( $deal->ID, '_ng_destination', true );
		$original_price   = get_post_meta( $deal->ID, '_ng_original_price', true );
		$discounted_price = get_post_meta( $deal->ID, '_ng_discounted_price', true );
		$currency         = get_post_meta( $deal->ID, '_ng_currency', true );
		$score            = get_post_meta( $deal->ID, '_ng_evaluation_score', true );
		$affiliate_link   = get_post_meta( $deal->ID, '_ng_affiliate_link', true );
		$image_url        = get_post_meta( $deal->ID, '_ng_featured_image_url', true );

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

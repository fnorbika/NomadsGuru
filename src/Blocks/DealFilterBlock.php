<?php

namespace NomadsGuru\Blocks;

class DealFilterBlock {

	/**
	 * Register the block
	 */
	public function register() {
		register_block_type(
			NOMADSGURU_PLUGIN_DIR . 'blocks/deal-filter-block',
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
			'showSearch'      => true,
			'showDestination' => true,
			'showPrice'       => true,
			'showScore'       => true,
			'showDates'       => false,
		);

		$attributes = wp_parse_args( $attributes, $defaults );

		// Get unique destinations for dropdown
		global $wpdb;
		$destinations = $wpdb->get_col( "
			SELECT DISTINCT destination 
			FROM {$wpdb->prefix}ng_raw_deals 
			WHERE destination IS NOT NULL AND destination != '' 
			ORDER BY destination ASC
		" );

		ob_start();
		?>
		<div class="ng-filter-block" id="ng-deal-filters">
			<h3><?php esc_html_e( 'Filter Deals', 'nomadsguru' ); ?></h3>

			<?php if ( $attributes['showSearch'] ) : ?>
				<div class="ng-filter-group">
					<label class="ng-filter-label">
						<span class="dashicons dashicons-search"></span>
						<?php esc_html_e( 'Search', 'nomadsguru' ); ?>
					</label>
					<input type="text" class="ng-filter-input" id="ng-filter-search" placeholder="<?php esc_attr_e( 'Search destination...', 'nomadsguru' ); ?>">
				</div>
			<?php endif; ?>

			<?php if ( $attributes['showDestination'] ) : ?>
				<div class="ng-filter-group">
					<label class="ng-filter-label">
						<span class="dashicons dashicons-location"></span>
						<?php esc_html_e( 'Destination', 'nomadsguru' ); ?>
					</label>
					<select class="ng-filter-input" id="ng-filter-destination">
						<option value=""><?php esc_html_e( 'All Destinations', 'nomadsguru' ); ?></option>
						<?php foreach ( $destinations as $dest ) : ?>
							<option value="<?php echo esc_attr( $dest ); ?>"><?php echo esc_html( $dest ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>

			<?php if ( $attributes['showPrice'] ) : ?>
				<div class="ng-filter-group">
					<label class="ng-filter-label">
						<span class="dashicons dashicons-money-alt"></span>
						<?php esc_html_e( 'Price Range', 'nomadsguru' ); ?>
					</label>
					<div class="ng-price-range">
						<input type="number" class="ng-filter-input ng-price-input" id="ng-filter-min-price" placeholder="<?php esc_attr_e( 'Min', 'nomadsguru' ); ?>">
						<span>-</span>
						<input type="number" class="ng-filter-input ng-price-input" id="ng-filter-max-price" placeholder="<?php esc_attr_e( 'Max', 'nomadsguru' ); ?>">
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $attributes['showScore'] ) : ?>
				<div class="ng-filter-group">
					<label class="ng-filter-label">
						<span class="dashicons dashicons-star-filled"></span>
						<?php esc_html_e( 'Minimum Score', 'nomadsguru' ); ?>
					</label>
					<input type="range" class="ng-filter-range" id="ng-filter-score" min="0" max="100" value="60" step="5">
					<div class="ng-range-value"><span id="ng-score-value">60</span>/100</div>
				</div>
			<?php endif; ?>

			<?php if ( $attributes['showDates'] ) : ?>
				<div class="ng-filter-group">
					<label class="ng-filter-label">
						<span class="dashicons dashicons-calendar-alt"></span>
						<?php esc_html_e( 'Travel Dates', 'nomadsguru' ); ?>
					</label>
					<div class="ng-date-range">
						<input type="date" class="ng-filter-input" id="ng-filter-start-date">
						<span>-</span>
						<input type="date" class="ng-filter-input" id="ng-filter-end-date">
					</div>
				</div>
			<?php endif; ?>

			<div class="ng-filter-actions">
				<button class="ng-button-primary" id="ng-apply-filters">
					<?php esc_html_e( 'Apply Filters', 'nomadsguru' ); ?>
				</button>
				<button class="ng-button-secondary" id="ng-reset-filters">
					<?php esc_html_e( 'Reset', 'nomadsguru' ); ?>
				</button>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

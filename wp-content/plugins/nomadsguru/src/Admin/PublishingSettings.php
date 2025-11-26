<?php

namespace NomadsGuru\Admin;

use NomadsGuru\Core\Config;

class PublishingSettings {

	/**
	 * Initialize
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting( 'nomadsguru_publishing', 'ng_publishing_config' );

		add_settings_section(
			'ng_publishing_section',
			__( 'Publishing Configuration', 'nomadsguru' ),
			null,
			'nomadsguru-publishing'
		);

		add_settings_field(
			'publishing_mode',
			__( 'Publishing Mode', 'nomadsguru' ),
			array( $this, 'render_mode_field' ),
			'nomadsguru-publishing',
			'ng_publishing_section'
		);

		add_settings_field(
			'min_articles',
			__( 'Minimum Articles Per Batch', 'nomadsguru' ),
			array( $this, 'render_min_articles_field' ),
			'nomadsguru-publishing',
			'ng_publishing_section'
		);

		add_settings_field(
			'max_articles',
			__( 'Maximum Articles Per Batch', 'nomadsguru' ),
			array( $this, 'render_max_articles_field' ),
			'nomadsguru-publishing',
			'ng_publishing_section'
		);
	}

	/**
	 * Render mode field
	 */
	public function render_mode_field() {
		$config = Config::get_publishing_config();
		$mode = isset( $config['publishing_mode'] ) ? $config['publishing_mode'] : 'automatic';
		?>
		<select name="ng_publishing_config[publishing_mode]">
			<option value="automatic" <?php selected( $mode, 'automatic' ); ?>><?php esc_html_e( 'Automatic', 'nomadsguru' ); ?></option>
			<option value="manual" <?php selected( $mode, 'manual' ); ?>><?php esc_html_e( 'Manual', 'nomadsguru' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Automatic: Publish deals immediately. Manual: Require approval.', 'nomadsguru' ); ?></p>
		<?php
	}

	/**
	 * Render min articles field
	 */
	public function render_min_articles_field() {
		$config = Config::get_publishing_config();
		$min = isset( $config['min_articles_per_batch'] ) ? $config['min_articles_per_batch'] : 1;
		?>
		<input type="number" name="ng_publishing_config[min_articles_per_batch]" value="<?php echo esc_attr( $min ); ?>" min="1" max="100">
		<?php
	}

	/**
	 * Render max articles field
	 */
	public function render_max_articles_field() {
		$config = Config::get_publishing_config();
		$max = isset( $config['max_articles_per_batch'] ) ? $config['max_articles_per_batch'] : 10;
		?>
		<input type="number" name="ng_publishing_config[max_articles_per_batch]" value="<?php echo esc_attr( $max ); ?>" min="1" max="100">
		<?php
	}

	/**
	 * Render the page
	 */
	public function render() {
		?>
		<div class="nomadsguru-wrap">
			<div class="ng-header">
				<h1><?php esc_html_e( 'Publishing Settings', 'nomadsguru' ); ?></h1>
			</div>

			<div class="ng-card">
				<form method="post" action="options.php">
					<?php
					settings_fields( 'nomadsguru_publishing' );
					do_settings_sections( 'nomadsguru-publishing' );
					submit_button();
					?>
				</form>
			</div>
		</div>
		<?php
	}
}

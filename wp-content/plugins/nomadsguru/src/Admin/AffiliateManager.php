<?php

namespace NomadsGuru\Admin;

class AffiliateManager {

	/**
	 * Render the Affiliate Programs page
	 */
	public function render() {
		$programs = $this->get_programs();
		?>
		<div class="nomadsguru-wrap">
			<div class="ng-header">
				<h1><?php esc_html_e( 'Affiliate Programs', 'nomadsguru' ); ?></h1>
				<button id="ng-add-affiliate-btn" class="ng-button-primary">
					<span class="dashicons dashicons-plus"></span>
					<?php esc_html_e( 'Add Program', 'nomadsguru' ); ?>
				</button>
			</div>

			<div class="ng-card">
				<table class="ng-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Program Name', 'nomadsguru' ); ?></th>
							<th><?php esc_html_e( 'Type', 'nomadsguru' ); ?></th>
							<th><?php esc_html_e( 'Commission', 'nomadsguru' ); ?></th>
							<th><?php esc_html_e( 'Status', 'nomadsguru' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'nomadsguru' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $programs ) ) : ?>
							<tr>
								<td colspan="5"><?php esc_html_e( 'No affiliate programs found.', 'nomadsguru' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $programs as $program ) : ?>
								<tr>
									<td><?php echo esc_html( $program->program_name ); ?></td>
									<td><?php echo esc_html( $program->program_type ); ?></td>
									<td><?php echo esc_html( $program->commission_rate ); ?>%</td>
									<td>
										<span class="ng-badge <?php echo $program->is_active ? 'active' : 'inactive'; ?>">
											<?php echo $program->is_active ? esc_html__( 'Active', 'nomadsguru' ) : esc_html__( 'Inactive', 'nomadsguru' ); ?>
										</span>
									</td>
									<td>
										<button class="ng-button-secondary ng-edit-affiliate" data-id="<?php echo esc_attr( $program->id ); ?>"><?php esc_html_e( 'Edit', 'nomadsguru' ); ?></button>
										<button class="ng-button-danger ng-delete-affiliate" data-id="<?php echo esc_attr( $program->id ); ?>"><?php esc_html_e( 'Delete', 'nomadsguru' ); ?></button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Add/Edit Modal -->
			<div id="ng-affiliate-modal" class="ng-modal">
				<div class="ng-modal-content">
					<span class="ng-close">&times;</span>
					<h2 id="ng-affiliate-modal-title"><?php esc_html_e( 'Add Affiliate Program', 'nomadsguru' ); ?></h2>
					<form id="ng-affiliate-form">
						<input type="hidden" name="action" value="ng_save_affiliate">
						<input type="hidden" name="id" id="ng-affiliate-id" value="">
						<?php wp_nonce_field( 'ng_save_affiliate', 'nonce' ); ?>

						<div class="ng-form-group">
							<label for="program_name"><?php esc_html_e( 'Program Name', 'nomadsguru' ); ?></label>
							<input type="text" name="program_name" id="ng-program-name" required>
						</div>

						<div class="ng-form-group">
							<label for="program_type"><?php esc_html_e( 'Program Type', 'nomadsguru' ); ?></label>
							<select name="program_type" id="ng-program-type" required>
								<option value="manual_url">Manual URL Replacement</option>
								<option value="api">API Integration</option>
								<option value="cookie_based">Cookie Based</option>
							</select>
						</div>

						<div class="ng-form-group">
							<label for="url_pattern"><?php esc_html_e( 'URL Pattern / Template', 'nomadsguru' ); ?></label>
							<input type="text" name="url_pattern" id="ng-url-pattern" placeholder="https://affiliate.com?ref={id}&url={url}">
							<p class="description"><?php esc_html_e( 'Use {url} for the original link.', 'nomadsguru' ); ?></p>
						</div>

						<div class="ng-form-group">
							<label for="commission_rate"><?php esc_html_e( 'Commission Rate (%)', 'nomadsguru' ); ?></label>
							<input type="number" step="0.01" name="commission_rate" id="ng-commission-rate" value="0.00">
						</div>

						<div class="ng-form-group">
							<button type="submit" class="ng-button-primary"><?php esc_html_e( 'Save Program', 'nomadsguru' ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get all affiliate programs
	 *
	 * @return array
	 */
	private function get_programs() {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_affiliate_programs';
		return $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC" );
	}
}

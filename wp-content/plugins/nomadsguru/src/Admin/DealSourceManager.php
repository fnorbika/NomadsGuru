<?php

namespace NomadsGuru\Admin;

class DealSourceManager {

	/**
	 * Render the Deal Sources page
	 */
	public function render() {
		$sources = $this->get_sources();
		?>
		<div class="nomadsguru-wrap">
			<div class="ng-header">
				<h1><?php esc_html_e( 'Deal Sources', 'nomadsguru' ); ?></h1>
				<button id="ng-add-source-btn" class="ng-button-primary">
					<span class="dashicons dashicons-plus"></span>
					<?php esc_html_e( 'Add Source', 'nomadsguru' ); ?>
				</button>
			</div>

			<div class="ng-card">
				<table class="ng-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Source Name', 'nomadsguru' ); ?></th>
							<th><?php esc_html_e( 'Type', 'nomadsguru' ); ?></th>
							<th><?php esc_html_e( 'Status', 'nomadsguru' ); ?></th>
							<th><?php esc_html_e( 'Last Sync', 'nomadsguru' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'nomadsguru' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $sources ) ) : ?>
							<tr>
								<td colspan="5"><?php esc_html_e( 'No deal sources found.', 'nomadsguru' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $sources as $source ) : ?>
								<tr>
									<td><?php echo esc_html( $source->source_name ); ?></td>
									<td>
										<?php 
										if ($source->source_type === 'website') {
											esc_html_e('Website (URLs/Hyperlinks)', 'nomadsguru');
										} elseif ($source->source_type === 'rss') {
											esc_html_e('XML/RSS Feed', 'nomadsguru');
										} else {
											echo esc_html( $source->source_type );
										}
										?>
									</td>
									<td>
										<span class="ng-badge <?php echo $source->is_active ? 'active' : 'inactive'; ?>">
											<?php echo $source->is_active ? esc_html__( 'Active', 'nomadsguru' ) : esc_html__( 'Inactive', 'nomadsguru' ); ?>
										</span>
									</td>
									<td><?php echo $source->last_sync ? esc_html( $source->last_sync ) : '-'; ?></td>
									<td>
										<button class="ng-button-secondary ng-edit-source" data-id="<?php echo esc_attr( $source->id ); ?>"><?php esc_html_e( 'Edit', 'nomadsguru' ); ?></button>
										<button class="ng-button-danger ng-delete-source" data-id="<?php echo esc_attr( $source->id ); ?>"><?php esc_html_e( 'Delete', 'nomadsguru' ); ?></button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Add/Edit Modal -->
			<div id="ng-source-modal" class="ng-modal">
				<div class="ng-modal-content">
					<span class="ng-close">&times;</span>
					<h2 id="ng-modal-title"><?php esc_html_e( 'Add Deal Source', 'nomadsguru' ); ?></h2>
					<form id="ng-source-form">
						<input type="hidden" name="action" value="ng_save_source">
						<input type="hidden" name="id" id="ng-source-id" value="">
						<?php wp_nonce_field( 'ng_save_source', 'nonce' ); ?>

						<div class="ng-form-group">
							<label for="source_type"><?php esc_html_e( 'Source Type', 'nomadsguru' ); ?></label>
							<select name="source_type" id="ng-source-type" required>
								<option value="website"><?php esc_html_e( 'Website (URLs/Hyperlinks)', 'nomadsguru' ); ?></option>
								<option value="rss"><?php esc_html_e( 'XML/RSS Feed', 'nomadsguru' ); ?></option>
							</select>
						</div>

						<div class="ng-form-group" id="website-url-group">
							<label for="website_url"><?php esc_html_e( 'Website URL', 'nomadsguru' ); ?></label>
							<input type="url" name="website_url" id="ng-website-url" placeholder="https://example.com">
							<p class="description"><?php esc_html_e( 'Enter the base URL of the website to scrape for deals.', 'nomadsguru' ); ?></p>
						</div>

						<div class="ng-form-group" id="rss-feed-group" style="display: none;">
							<label for="rss_feed"><?php esc_html_e( 'RSS Feed URL', 'nomadsguru' ); ?></label>
							<input type="url" name="rss_feed" id="ng-rss-feed" placeholder="https://example.com/feed.xml">
							<p class="description"><?php esc_html_e( 'Enter the URL of the XML/RSS feed to import deals from.', 'nomadsguru' ); ?></p>
						</div>

						<div class="ng-form-group">
							<label for="source_name"><?php esc_html_e( 'Source Name', 'nomadsguru' ); ?></label>
							<input type="text" name="source_name" id="ng-source-name" required>
						</div>

						<div class="ng-form-group">
							<label for="sync_interval"><?php esc_html_e( 'Sync Interval (minutes)', 'nomadsguru' ); ?></label>
							<input type="number" name="sync_interval_minutes" id="ng-source-interval" value="60">
						</div>

						<div class="ng-form-group">
							<button type="submit" class="ng-button-primary"><?php esc_html_e( 'Save Source', 'nomadsguru' ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get all deal sources
	 *
	 * @return array
	 */
	private function get_sources() {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_deal_sources';
		return $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC" );
	}
}

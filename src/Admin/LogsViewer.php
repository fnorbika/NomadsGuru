<?php

namespace NomadsGuru\Admin;

class LogsViewer {

	/**
	 * Render the logs page
	 */
	public function render() {
		$logs = $this->get_logs( 50 );
		?>
		<div class="nomadsguru-wrap">
			<div class="ng-header">
				<h1><?php esc_html_e( 'System Logs', 'nomadsguru' ); ?></h1>
			</div>

			<div class="ng-card">
				<table class="ng-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Level', 'nomadsguru' ); ?></th>
							<th><?php esc_html_e( 'Component', 'nomadsguru' ); ?></th>
							<th><?php esc_html_e( 'Message', 'nomadsguru' ); ?></th>
							<th><?php esc_html_e( 'Time', 'nomadsguru' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $logs ) ) : ?>
							<tr>
								<td colspan="4"><?php esc_html_e( 'No logs found.', 'nomadsguru' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $logs as $log ) : ?>
								<tr>
									<td>
										<span class="ng-badge <?php echo esc_attr( strtolower( $log->log_level ) ); ?>">
											<?php echo esc_html( $log->log_level ); ?>
										</span>
									</td>
									<td><?php echo esc_html( $log->component ); ?></td>
									<td><?php echo esc_html( $log->message ); ?></td>
									<td><?php echo esc_html( $log->created_at ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Get logs
	 *
	 * @param int $limit
	 * @return array
	 */
	private function get_logs( $limit ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_logs';
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d", $limit ) );
	}
}

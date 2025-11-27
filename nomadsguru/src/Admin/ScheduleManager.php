<?php

namespace NomadsGuru\Admin;

class ScheduleManager {

	/**
	 * Render the Schedule Manager page
	 */
	public function render() {
		$schedules = $this->get_cron_schedules();
		?>
		<div class="nomadsguru-wrap">
			<div class="ng-header">
				<h1><?php esc_html_e( 'Schedule Manager', 'nomadsguru' ); ?></h1>
			</div>

			<div class="ng-card">
				<p><?php esc_html_e( 'Manage automated tasks and cron jobs.', 'nomadsguru' ); ?></p>
			</div>

			<?php foreach ( $schedules as $schedule ) : ?>
				<div class="ng-schedule-card">
					<div class="ng-schedule-header">
						<div class="ng-schedule-title">
							<span class="dashicons dashicons-clock"></span>
							<?php echo esc_html( $schedule['name'] ); ?>
						</div>
						<span class="ng-badge <?php echo $schedule['enabled'] ? 'active' : 'inactive'; ?>">
							<?php echo $schedule['enabled'] ? esc_html__( 'Active', 'nomadsguru' ) : esc_html__( 'Disabled', 'nomadsguru' ); ?>
						</span>
					</div>

					<div class="ng-schedule-meta">
						<span>
							<span class="dashicons dashicons-backup"></span>
							<?php echo esc_html( $schedule['interval'] ); ?>
						</span>
						<span>
							<span class="dashicons dashicons-calendar-alt"></span>
							<?php esc_html_e( 'Next Run:', 'nomadsguru' ); ?> <?php echo esc_html( $schedule['next_run'] ); ?>
						</span>
					</div>

					<div class="ng-schedule-actions">
						<button class="ng-button-primary ng-run-now" data-hook="<?php echo esc_attr( $schedule['hook'] ); ?>">
							<?php esc_html_e( 'Run Now', 'nomadsguru' ); ?>
						</button>
						<?php if ( $schedule['enabled'] ) : ?>
							<button class="ng-button-secondary ng-disable-schedule" data-hook="<?php echo esc_attr( $schedule['hook'] ); ?>">
								<?php esc_html_e( 'Disable', 'nomadsguru' ); ?>
							</button>
						<?php else : ?>
							<button class="ng-button-secondary ng-enable-schedule" data-hook="<?php echo esc_attr( $schedule['hook'] ); ?>">
								<?php esc_html_e( 'Enable', 'nomadsguru' ); ?>
							</button>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Get cron schedules
	 *
	 * @return array
	 */
	private function get_cron_schedules() {
		$crons = _get_cron_array();
		$schedules = array();

		// Define our plugin's cron hooks
		$plugin_hooks = array(
			'ng_deal_discovery' => __( 'Deal Discovery', 'nomadsguru' ),
			'ng_queue_processing' => __( 'Queue Processing', 'nomadsguru' ),
			'ng_daily_maintenance' => __( 'Daily Maintenance', 'nomadsguru' ),
		);

		foreach ( $plugin_hooks as $hook => $name ) {
			$next_run = wp_next_scheduled( $hook );
			$enabled = $next_run !== false;

			$schedules[] = array(
				'hook' => $hook,
				'name' => $name,
				'interval' => $this->get_schedule_interval( $hook ),
				'next_run' => $next_run ? date_i18n( 'Y-m-d H:i:s', $next_run ) : __( 'Not scheduled', 'nomadsguru' ),
				'enabled' => $enabled,
			);
		}

		return $schedules;
	}

	/**
	 * Get schedule interval
	 *
	 * @param string $hook
	 * @return string
	 */
	private function get_schedule_interval( $hook ) {
		$crons = _get_cron_array();
		
		foreach ( $crons as $timestamp => $cron ) {
			if ( isset( $cron[ $hook ] ) ) {
				foreach ( $cron[ $hook ] as $event ) {
					if ( isset( $event['schedule'] ) ) {
						$schedules = wp_get_schedules();
						if ( isset( $schedules[ $event['schedule'] ] ) ) {
							return $schedules[ $event['schedule'] ]['display'];
						}
					}
				}
			}
		}

		return __( 'Unknown', 'nomadsguru' );
	}
}

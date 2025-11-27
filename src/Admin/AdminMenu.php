<?php

namespace NomadsGuru\Admin;

class AdminMenu {
	/**
	 * @var AISettings
	 */
	private $ai_settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->ai_settings = new AISettings();
	}

	/**
	 * Initialize Admin Menu
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		
		// Initialize AI settings immediately
		$this->ai_settings->init();
		
		// Also register AI settings on admin_init for proper integration
		add_action( 'admin_init', array( $this->ai_settings, 'register_settings' ), 20 );
	}

	/**
	 * Register plugin settings
	 */
	public function register_settings() {
		$this->ai_settings->register_settings();
	}

	/**
	 * Register the menu page
	 */
	public function register_menu() {
		add_menu_page(
			__( 'NomadsGuru', 'nomadsguru' ),
			__( 'NomadsGuru', 'nomadsguru' ),
			'manage_options',
			'nomadsguru',
			array( $this, 'render_dashboard' ),
			'dashicons-airplane',
			30
		);

		add_submenu_page(
			'nomadsguru',
			__( 'Dashboard', 'nomadsguru' ),
			__( 'Dashboard', 'nomadsguru' ),
			'manage_options',
			'nomadsguru',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'nomadsguru',
			__( 'Queue', 'nomadsguru' ),
			__( 'Queue', 'nomadsguru' ),
			'manage_options',
			'nomadsguru-queue',
			array( $this, 'render_queue' )
		);

		add_submenu_page(
			'nomadsguru',
			__( 'Schedule', 'nomadsguru' ),
			__( 'Schedule', 'nomadsguru' ),
			'manage_options',
			'nomadsguru-schedule',
			array( $this, 'render_schedule' )
		);

		add_submenu_page(
			'nomadsguru',
			__( 'Logs', 'nomadsguru' ),
			__( 'Logs', 'nomadsguru' ),
			'manage_options',
			'nomadsguru-logs',
			array( $this, 'render_logs' )
		);

		add_submenu_page(
			'nomadsguru',
			__( 'Settings', 'nomadsguru' ),
			__( 'Settings', 'nomadsguru' ),
			'manage_options',
			'nomadsguru-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Enqueue Admin Assets
	 */
	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'nomadsguru' ) === false ) {
			return;
		}

		// Always enqueue admin CSS with cache busting
		wp_enqueue_style( 
			'nomadsguru-admin', 
			NOMADSGURU_PLUGIN_URL . 'assets/css/admin.css', 
			array(), 
			NOMADSGURU_VERSION . '-' . filemtime( NOMADSGURU_PLUGIN_DIR . 'assets/css/admin.css' )
		);
		
		wp_enqueue_script( 
			'nomadsguru-admin', 
			NOMADSGURU_PLUGIN_URL . 'assets/js/admin.js', 
			array( 'jquery' ), 
			NOMADSGURU_VERSION . '-' . time(), // Force cache bust with timestamp
			true 
		);
		
		// Enqueue Chart.js for dashboard only
		if ( $hook === 'toplevel_page_nomadsguru' ) {
			wp_enqueue_script( 
				'chartjs', 
				'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', 
				array(), 
				'4.4.0', 
				true 
			);
			
			wp_enqueue_script( 
				'nomadsguru-dashboard', 
				NOMADSGURU_PLUGIN_URL . 'assets/js/dashboard.js', 
				array( 'jquery', 'chartjs' ), 
				NOMADSGURU_VERSION . '-' . filemtime( NOMADSGURU_PLUGIN_DIR . 'assets/js/dashboard.js' ), 
				true 
			);
		}
		
		wp_localize_script( 'nomadsguru-admin', 'nomadsguruParams', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'nomadsguru_admin_nonce' ),
			'pluginsUrl' => admin_url('plugins.php'),
		) );
	}

	/**
	 * Render dashboard page
	 */
	public function render_dashboard() {
		global $wpdb;

		// Get KPIs
		$total_deals = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ng_raw_deals" );
		$active_sources = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ng_deal_sources WHERE is_active = 1" );
		$queue_pending = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ng_processing_queue WHERE status = 'pending'" );
		$published_deals = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ng_raw_deals WHERE post_id IS NOT NULL" );

		// Get deals per month (last 6 months)
		$deals_per_month = $wpdb->get_results( "
			SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
			FROM {$wpdb->prefix}ng_raw_deals 
			WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
			GROUP BY month
			ORDER BY month ASC
		" );

		// Get top destinations
		$top_destinations = $wpdb->get_results( "
			SELECT destination, COUNT(*) as count 
			FROM {$wpdb->prefix}ng_raw_deals 
			WHERE destination IS NOT NULL AND destination != ''
			GROUP BY destination 
			ORDER BY count DESC 
			LIMIT 5
		" );

		// Get processing stats
		$processing_stats = $wpdb->get_results( "
			SELECT status, COUNT(*) as count 
			FROM {$wpdb->prefix}ng_processing_queue 
			GROUP BY status
		" );

		?>
		<div class="nomadsguru-wrap">
			<div class="ng-header">
				<h1><?php esc_html_e( 'Dashboard', 'nomadsguru' ); ?></h1>
				<div>
					<button class="ng-button-secondary" onclick="location.reload()">
						<span class="dashicons dashicons-update"></span>
						<?php esc_html_e( 'Refresh', 'nomadsguru' ); ?>
					</button>
				</div>
			</div>

			<!-- KPI Cards -->
			<div class="ng-kpi-grid">
				<div class="ng-kpi-card">
					<div class="ng-kpi-icon" style="background: rgba(33, 128, 183, 0.1);">
						<span class="dashicons dashicons-chart-bar" style="color: #2180B7;"></span>
					</div>
					<div class="ng-kpi-content">
						<div class="ng-kpi-label"><?php esc_html_e( 'Total Deals', 'nomadsguru' ); ?></div>
						<div class="ng-kpi-value"><?php echo esc_html( number_format( $total_deals ) ); ?></div>
						<div class="ng-kpi-trend ng-trend-up">↑ <?php esc_html_e( 'All time', 'nomadsguru' ); ?></div>
					</div>
				</div>

				<div class="ng-kpi-card">
					<div class="ng-kpi-icon" style="background: rgba(32, 140, 141, 0.1);">
						<span class="dashicons dashicons-yes-alt" style="color: #208C8D;"></span>
					</div>
					<div class="ng-kpi-content">
						<div class="ng-kpi-label"><?php esc_html_e( 'Active Sources', 'nomadsguru' ); ?></div>
						<div class="ng-kpi-value"><?php echo esc_html( $active_sources ); ?></div>
						<div class="ng-kpi-trend"><?php esc_html_e( 'Connected', 'nomadsguru' ); ?></div>
					</div>
				</div>

				<div class="ng-kpi-card">
					<div class="ng-kpi-icon" style="background: rgba(94, 82, 64, 0.1);">
						<span class="dashicons dashicons-admin-post" style="color: #5E5240;"></span>
					</div>
					<div class="ng-kpi-content">
						<div class="ng-kpi-label"><?php esc_html_e( 'Published', 'nomadsguru' ); ?></div>
						<div class="ng-kpi-value"><?php echo esc_html( number_format( $published_deals ) ); ?></div>
						<div class="ng-kpi-trend ng-trend-up">↑ <?php esc_html_e( 'Articles', 'nomadsguru' ); ?></div>
					</div>
				</div>

				<div class="ng-kpi-card">
					<div class="ng-kpi-icon" style="background: rgba(224, 97, 97, 0.1);">
						<span class="dashicons dashicons-clock" style="color: #E06161;"></span>
					</div>
					<div class="ng-kpi-content">
						<div class="ng-kpi-label"><?php esc_html_e( 'Queue Pending', 'nomadsguru' ); ?></div>
						<div class="ng-kpi-value"><?php echo esc_html( $queue_pending ); ?></div>
						<div class="ng-kpi-trend"><?php esc_html_e( 'In queue', 'nomadsguru' ); ?></div>
					</div>
				</div>
			</div>

			<!-- Charts Row -->
			<div class="ng-dashboard-row">
				<div class="ng-card ng-chart-card">
					<h3><?php esc_html_e( 'Deals Per Month', 'nomadsguru' ); ?></h3>
					<canvas id="ng-deals-chart" width="400" height="200"></canvas>
				</div>

				<div class="ng-card">
					<h3><?php esc_html_e( 'Top Destinations', 'nomadsguru' ); ?></h3>
					<div class="ng-destinations-list">
						<?php if ( ! empty( $top_destinations ) ) : ?>
							<?php foreach ( $top_destinations as $index => $dest ) : ?>
								<div class="ng-destination-item">
									<span class="ng-destination-rank"><?php echo esc_html( $index + 1 ); ?>.</span>
									<span class="ng-destination-name"><?php echo esc_html( $dest->destination ); ?></span>
									<span class="ng-destination-count"><?php echo esc_html( $dest->count ); ?> deals</span>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<p><?php esc_html_e( 'No destinations yet.', 'nomadsguru' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Processing Stats -->
			<div class="ng-dashboard-row">
				<div class="ng-card">
					<h3><?php esc_html_e( 'Processing Stats', 'nomadsguru' ); ?></h3>
					<canvas id="ng-processing-chart" width="200" height="200"></canvas>
				</div>

				<div class="ng-card">
					<h3><?php esc_html_e( 'Recent Activity', 'nomadsguru' ); ?></h3>
					<div class="ng-activity-list">
						<?php
						$recent_deals = $wpdb->get_results( "
							SELECT title, created_at 
							FROM {$wpdb->prefix}ng_raw_deals 
							ORDER BY created_at DESC 
							LIMIT 5
						" );
						?>
						<?php if ( ! empty( $recent_deals ) ) : ?>
							<?php foreach ( $recent_deals as $deal ) : ?>
								<div class="ng-activity-item">
									<span class="dashicons dashicons-yes"></span>
									<span><?php echo esc_html( $deal->title ); ?></span>
									<span class="ng-activity-time"><?php echo esc_html( human_time_diff( strtotime( $deal->created_at ), current_time( 'timestamp' ) ) ); ?> ago</span>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<p><?php esc_html_e( 'No recent activity.', 'nomadsguru' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<script>
		// Chart data
		var dealsData = <?php echo json_encode( $deals_per_month ); ?>;
		var processingData = <?php echo json_encode( $processing_stats ); ?>;
		</script>
		<?php
	}

	/**
	 * Render Settings Page with Tabs
	 */
	public function render_settings() {
		if (!current_user_can('manage_options')) {
			return;
		}
		
		$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'ai';
		?>
		<div class="wrap nomadsguru-settings">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			
			<nav class="nav-tab-wrapper">
				<a href="?page=nomadsguru-settings&tab=ai" class="nav-tab <?php echo $active_tab === 'ai' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e('AI Settings', 'nomadsguru'); ?>
				</a>
				<a href="?page=nomadsguru-settings&tab=sources" class="nav-tab <?php echo $active_tab === 'sources' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e('Sources', 'nomadsguru'); ?>
				</a>
				<a href="?page=nomadsguru-settings&tab=affiliates" class="nav-tab <?php echo $active_tab === 'affiliates' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e('Affiliates', 'nomadsguru'); ?>
				</a>
				<a href="?page=nomadsguru-settings&tab=publishing" class="nav-tab <?php echo $active_tab === 'publishing' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e('Publishing', 'nomadsguru'); ?>
				</a>
				<a href="?page=nomadsguru-settings&tab=reset" class="nav-tab <?php echo $active_tab === 'reset' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e('Reset Data', 'nomadsguru'); ?>
				</a>
			</nav>
			
			<div class="tab-content">
				<?php
				switch ($active_tab) {
					case 'sources':
						$this->render_sources_tab();
						break;
					case 'affiliates':
						$this->render_affiliates_tab();
						break;
					case 'publishing':
						$this->render_publishing_tab();
						break;
					case 'reset':
						$this->render_reset_tab();
						break;
					case 'ai':
					default:
						$this->render_ai_tab();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render AI Settings Tab
	 */
	private function render_ai_tab() {
		// Force register settings if not already done
		if (!did_action('admin_init')) {
			$this->ai_settings->register_settings();
		}
		
		// Double-check settings are registered
		global $wp_registered_settings;
		if (!isset($wp_registered_settings['ng_ai_settings'])) {
			$this->ai_settings->register_settings();
		}
		
		// Show error/update messages
		settings_errors('nomadsguru_messages');
		
		// Debug: Check if settings are registered
		$settings = get_option('ng_ai_settings', []);
		echo '<!-- Debug: Current AI Settings: ' . esc_html(print_r($settings, true)) . ' -->';
		?>
		<form action="options.php" method="post">
			<?php
			settings_fields('nomadsguru_ai_settings'); // Updated to match option group
			do_settings_sections('nomadsguru_ai_settings'); // Updated to match option group
			submit_button(__('Save AI Settings', 'nomadsguru'));
			?>
		</form>
		<?php
	}

	/**
	 * Render Sources Tab
	 */
	private function render_sources_tab() {
		$manager = new DealSourceManager();
		$manager->render();
	}

	/**
	 * Render Affiliates Tab
	 */
	private function render_affiliates_tab() {
		$manager = new AffiliateManager();
		$manager->render();
	}

	/**
	 * Render Publishing Tab
	 */
	private function render_publishing_tab() {
		$settings = new PublishingSettings();
		$settings->render();
	}

	/**
	 * Render Reset Tab
	 */
	private function render_reset_tab() {
		?>
		<div class="card">
			<h2><?php esc_html_e('Reset Plugin Data', 'nomadsguru'); ?></h2>
			<p><?php esc_html_e('Danger Zone: These actions are irreversible. Use with caution!', 'nomadsguru'); ?></p>
			
			<div class="reset-plugin-section">
				<p>
					<button type="button" id="reset-plugin-data" class="button button-danger">
						<?php esc_html_e('Reset All Plugin Data', 'nomadsguru'); ?>
					</button>
					<span class="spinner" id="reset-spinner" style="float:none; margin-left: 10px; display: none;"></span>
				</p>
				<p class="description">
					<?php esc_html_e('This will delete all plugin data including settings, imported deals, and logs. This action cannot be undone.', 'nomadsguru'); ?>
				</p>
			</div>
		</div>

		<!-- Reset Confirmation Dialog -->
		<div id="reset-dialog" class="ng-dialog-overlay" style="display: none;">
			<div class="ng-dialog">
				<div class="ng-dialog-header">
					<h3><?php esc_html_e('Confirm Plugin Data Reset', 'nomadsguru'); ?></h3>
					<button type="button" class="ng-dialog-close" id="close-reset-dialog" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="ng-dialog-body">
					<p><?php esc_html_e('This action will permanently delete all plugin data:', 'nomadsguru'); ?></p>
					<ul>
						<li><?php esc_html_e('All imported travel deals', 'nomadsguru'); ?></li>
						<li><?php esc_html_e('All plugin settings and configurations', 'nomadsguru'); ?></li>
						<li><?php esc_html_e('All affiliate programs', 'nomadsguru'); ?></li>
						<li><?php esc_html_e('All processing queue items', 'nomadsguru'); ?></li>
						<li><?php esc_html_e('All logs and history', 'nomadsguru'); ?></li>
					</ul>
					<p><strong><?php esc_html_e('This action cannot be undone!', 'nomadsguru'); ?></strong></p>
					
					<div class="ng-form-group">
						<label for="confirm-delete-input"><?php esc_html_e('To confirm, type DELETE in the field below:', 'nomadsguru'); ?></label>
						<input type="text" id="confirm-delete-input" name="confirm_delete" placeholder="DELETE" autocomplete="off" />
						<small class="description"><?php esc_html_e('This must match exactly (case sensitive)', 'nomadsguru'); ?></small>
					</div>
					
					<div id="reset-result" style="margin-top: 10px; display: none;"></div>
				</div>
				<div class="ng-dialog-footer">
					<button type="button" id="confirm-reset-button" class="button button-primary" disabled>
						<?php esc_html_e('Reset All Data', 'nomadsguru'); ?>
					</button>
					<button type="button" id="cancel-reset-button" class="button">
						<?php esc_html_e('Cancel', 'nomadsguru'); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Queue Page
	 */
	public function render_queue() {
		$manager = new QueueManager();
		$manager->render();
	}

	/**
	 * Render Schedule Page
	 */
	public function render_schedule() {
		$manager = new ScheduleManager();
		$manager->render();
	}

	/**
	 * Render Logs Page
	 */
	public function render_logs() {
		$viewer = new LogsViewer();
		$viewer->render();
	}
}

<?php
/**
 * Dashboard template for NomadsGuru admin
 *
 * @package NomadsGuru
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e( 'NomadsGuru Dashboard', 'nomadsguru' ); ?></h1>
    
    <div class="nomadsguru-dashboard">
        <!-- Welcome Card -->
        <div class="nomadsguru-card">
            <h2><?php esc_html_e( 'Welcome to NomadsGuru', 'nomadsguru' ); ?></h2>
            <p><?php esc_html_e( 'Your AI-powered travel deals automation system is ready to help you discover, evaluate, and publish the best travel deals automatically.', 'nomadsguru' ); ?></p>
        </div>

        <!-- Statistics Cards -->
        <div class="nomadsguru-stats-grid">
            <div class="nomadsguru-stat-card">
                <h3><?php esc_html_e( 'Active Sources', 'nomadsguru' ); ?></h3>
                <div class="stat-number"><?php echo esc_html( $active_sources ); ?></div>
            </div>
            
            <div class="nomadsguru-stat-card">
                <h3><?php esc_html_e( 'Total Deals Found', 'nomadsguru' ); ?></h3>
                <div class="stat-number"><?php echo esc_html( $total_deals ); ?></div>
            </div>
            
            <div class="nomadsguru-stat-card">
                <h3><?php esc_html_e( 'Published Deals', 'nomadsguru' ); ?></h3>
                <div class="stat-number"><?php echo esc_html( $published_deals ); ?></div>
            </div>
            
            <div class="nomadsguru-stat-card">
                <h3><?php esc_html_e( 'Queue Pending', 'nomadsguru' ); ?></h3>
                <div class="stat-number"><?php echo esc_html( $queue_pending ); ?></div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="nomadsguru-card">
            <h3><?php esc_html_e( 'Quick Actions', 'nomadsguru' ); ?></h3>
            <div class="nomadsguru-actions">
                <a href="<?php echo admin_url( 'admin.php?page=nomadsguru-sources' ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Manage Deal Sources', 'nomadsguru' ); ?>
                </a>
                
                <a href="<?php echo admin_url( 'admin.php?page=nomadsguru-settings&tab=ai' ); ?>" class="button">
                    <?php esc_html_e( 'Configure AI Settings', 'nomadsguru' ); ?>
                </a>
                
                <a href="<?php echo admin_url( 'admin.php?page=nomadsguru-queue' ); ?>" class="button">
                    <?php esc_html_e( 'View Processing Queue', 'nomadsguru' ); ?>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="nomadsguru-card">
            <h3><?php esc_html_e( 'Recent Activity', 'nomadsguru' ); ?></h3>
            <div class="nomadsguru-activity">
                <p><?php esc_html_e( 'Activity log will appear here once the system starts processing deals.', 'nomadsguru' ); ?></p>
            </div>
        </div>

        <!-- System Status -->
        <div class="nomadsguru-card">
            <h3><?php esc_html_e( 'System Status', 'nomadsguru' ); ?></h3>
            <div class="nomadsguru-status">
                <div class="status-item">
                    <span class="status-label"><?php esc_html_e( 'AI Service:', 'nomadsguru' ); ?></span>
                    <span class="status-value status-ok"><?php esc_html_e( 'Connected', 'nomadsguru' ); ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label"><?php esc_html_e( 'Database:', 'nomadsguru' ); ?></span>
                    <span class="status-value status-ok"><?php esc_html_e( 'Ready', 'nomadsguru' ); ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label"><?php esc_html_e( 'Cron Jobs:', 'nomadsguru' ); ?></span>
                    <span class="status-value status-ok"><?php esc_html_e( 'Active', 'nomadsguru' ); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.nomadsguru-dashboard {
    max-width: 1200px;
    margin: 20px 0;
}

.nomadsguru-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.nomadsguru-card h2 {
    margin-top: 0;
    color: #1d2327;
}

.nomadsguru-card h3 {
    margin-top: 0;
    color: #1d2327;
}

.nomadsguru-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.nomadsguru-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #0073aa;
    margin: 10px 0;
}

.nomadsguru-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.nomadsguru-status .status-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.nomadsguru-status .status-item:last-child {
    border-bottom: none;
}

.status-label {
    font-weight: 500;
}

.status-ok {
    color: #00a32a;
    font-weight: 500;
}

.status-warning {
    color: #d63638;
    font-weight: 500;
}
</style>

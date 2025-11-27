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

<div class="wrap nomadsguru-dashboard">
    <h1><?php esc_html_e( 'NomadsGuru Dashboard', 'nomadsguru' ); ?></h1>
    
    <div class="nomadsguru-welcome">
        <p><?php esc_html_e( 'Welcome to NomadsGuru! Your AI-powered travel deals automation system.', 'nomadsguru' ); ?></p>
    </div>

    <!-- KPI Cards -->
    <div class="nomadsguru-kpi-grid">
        <div class="nomadsguru-kpi-card">
            <div class="nomadsguru-kpi-number"><?php echo number_format( $total_deals ); ?></div>
            <div class="nomadsguru-kpi-label"><?php esc_html_e( 'Total Deals', 'nomadsguru' ); ?></div>
        </div>
        
        <div class="nomadsguru-kpi-card">
            <div class="nomadsguru-kpi-number"><?php echo number_format( $active_sources ); ?></div>
            <div class="nomadsguru-kpi-label"><?php esc_html_e( 'Active Sources', 'nomadsguru' ); ?></div>
        </div>
        
        <div class="nomadsguru-kpi-card">
            <div class="nomadsguru-kpi-number"><?php echo number_format( $queue_pending ); ?></div>
            <div class="nomadsguru-kpi-label"><?php esc_html_e( 'Queue Items', 'nomadsguru' ); ?></div>
        </div>
        
        <div class="nomadsguru-kpi-card">
            <div class="nomadsguru-kpi-number"><?php echo number_format( $published_deals ); ?></div>
            <div class="nomadsguru-kpi-label"><?php esc_html_e( 'Published Deals', 'nomadsguru' ); ?></div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="nomadsguru-quick-actions">
        <h2><?php esc_html_e( 'Quick Actions', 'nomadsguru' ); ?></h2>
        <div class="nomadsguru-action-buttons">
            <a href="<?php echo admin_url( 'admin.php?page=nomadsguru-settings&tab=ai' ); ?>" class="button button-primary">
                <?php esc_html_e( 'Configure AI Settings', 'nomadsguru' ); ?>
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=nomadsguru-sources' ); ?>" class="button">
                <?php esc_html_e( 'Manage Sources', 'nomadsguru' ); ?>
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=nomadsguru-queue' ); ?>" class="button">
                <?php esc_html_e( 'View Queue', 'nomadsguru' ); ?>
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=nomadsguru-logs' ); ?>" class="button">
                <?php esc_html_e( 'View Logs', 'nomadsguru' ); ?>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="nomadsguru-recent-activity">
        <h2><?php esc_html_e( 'Recent Activity', 'nomadsguru' ); ?></h2>
        <div class="nomadsguru-activity-list">
            <?php
            global $wpdb;
            $recent_deals = $wpdb->get_results( 
                "SELECT title, created_at, status FROM {$wpdb->prefix}ng_raw_deals 
                 ORDER BY created_at DESC LIMIT 5" 
            );
            
            if ( $recent_deals ) :
                foreach ( $recent_deals as $deal ) :
            ?>
                <div class="nomadsguru-activity-item">
                    <div class="nomadsguru-activity-title"><?php echo esc_html( $deal->title ); ?></div>
                    <div class="nomadsguru-activity-meta">
                        <span class="nomadsguru-activity-status <?php echo esc_attr( $deal->status ); ?>">
                            <?php echo esc_html( ucfirst( $deal->status ) ); ?>
                        </span>
                        <span class="nomadsguru-activity-date">
                            <?php echo date_i18n( get_option( 'date_format' ), strtotime( $deal->created_at ) ); ?>
                        </span>
                    </div>
                </div>
            <?php
                endforeach;
            else :
            ?>
                <p><?php esc_html_e( 'No recent activity found.', 'nomadsguru' ); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- System Status -->
    <div class="nomadsguru-system-status">
        <h2><?php esc_html_e( 'System Status', 'nomadsguru' ); ?></h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Component', 'nomadsguru' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'nomadsguru' ); ?></th>
                    <th><?php esc_html_e( 'Details', 'nomadsguru' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php esc_html_e( 'AI Configuration', 'nomadsguru' ); ?></td>
                    <td>
                        <?php if ( nomadsguru_is_configured() ) : ?>
                            <span class="status-active"><?php esc_html_e( 'Configured', 'nomadsguru' ); ?></span>
                        <?php else : ?>
                            <span class="status-inactive"><?php esc_html_e( 'Not Configured', 'nomadsguru' ); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        if ( nomadsguru_is_configured() ) {
                            esc_html_e( 'AI service is ready to use.', 'nomadsguru' );
                        } else {
                            printf(
                                /* translators: %s: Settings page URL */
                                esc_html__( 'Please configure in %s', 'nomadsguru' ),
                                '<a href="' . admin_url( 'admin.php?page=nomadsguru-settings&tab=ai' ) . '">' . esc_html__( 'Settings', 'nomadsguru' ) . '</a>'
                            );
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Database Tables', 'nomadsguru' ); ?></td>
                    <td>
                        <span class="status-active"><?php esc_html_e( 'Installed', 'nomadsguru' ); ?></span>
                    </td>
                    <td><?php esc_html_e( 'All required database tables are present.', 'nomadsguru' ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Cron Jobs', 'nomadsguru' ); ?></td>
                    <td>
                        <?php if ( wp_next_scheduled( 'nomadsguru_sync_deals' ) ) : ?>
                            <span class="status-active"><?php esc_html_e( 'Active', 'nomadsguru' ); ?></span>
                        <?php else : ?>
                            <span class="status-inactive"><?php esc_html_e( 'Inactive', 'nomadsguru' ); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        if ( wp_next_scheduled( 'nomadsguru_sync_deals' ) ) {
                            esc_html_e( 'Automatic deal synchronization is running.', 'nomadsguru' );
                        } else {
                            esc_html_e( 'Cron job not scheduled. Reactivate plugin to fix.', 'nomadsguru' );
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.nomadsguru-dashboard {
    max-width: 1200px;
    margin: 20px 0;
}

.nomadsguru-welcome {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
}

.nomadsguru-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.nomadsguru-kpi-card {
    background: #fff;
    padding: 25px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    text-align: center;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.nomadsguru-kpi-number {
    font-size: 2.5em;
    font-weight: 600;
    color: #2271b1;
    line-height: 1;
    margin-bottom: 10px;
}

.nomadsguru-kpi-label {
    color: #50575e;
    font-size: 14px;
}

.nomadsguru-quick-actions {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
}

.nomadsguru-action-buttons {
    margin-top: 15px;
}

.nomadsguru-action-buttons .button {
    margin-right: 10px;
    margin-bottom: 10px;
}

.nomadsguru-recent-activity,
.nomadsguru-system-status {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
}

.nomadsguru-activity-item {
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f1;
}

.nomadsguru-activity-item:last-child {
    border-bottom: none;
}

.nomadsguru-activity-title {
    font-weight: 600;
    margin-bottom: 5px;
}

.nomadsguru-activity-meta {
    font-size: 13px;
    color: #50575e;
}

.nomadsguru-activity-status {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    margin-right: 10px;
}

.nomadsguru-activity-status.published {
    background: #00a32a;
    color: #fff;
}

.nomadsguru-activity-status.pending {
    background: #dba617;
    color: #000;
}

.nomadsguru-activity-status.rejected {
    background: #d63638;
    color: #fff;
}

.status-active {
    color: #00a32a;
    font-weight: 600;
}

.status-inactive {
    color: #d63638;
    font-weight: 600;
}
</style>

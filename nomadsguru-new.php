<?php
/**
 * Plugin Name: NomadsGuru - Travel Deals AI
 * Plugin URI:  https://nomadsguru.com
 * Description: Automatically discovers, evaluates, and publishes travel deals using AI. Lightweight and robust solution for travel content automation.
 * Version:     1.1.4
 * Author:      NomadsGuru Team
 * Author URI:  https://nomadsguru.com
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: nomadsguru
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define Plugin Constants
define( 'NOMADSGURU_VERSION', '1.1.4' );
define( 'NOMADSGURU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NOMADSGURU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NOMADSGURU_PLUGIN_FILE', __FILE__ );

// Check PHP version
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
    deactivate_plugins( plugin_basename( __FILE__ ) );
    wp_die( sprintf(
        /* translators: %s: Required PHP version */
        esc_html__( 'NomadsGuru requires PHP version %s or higher. Your current version is %s.', 'nomadsguru' ),
        '7.4',
        PHP_VERSION
    ) );
}

// Check WordPress version
if ( version_compare( $GLOBALS['wp_version'], '5.8', '<' ) ) {
    deactivate_plugins( plugin_basename( __FILE__ ) );
    wp_die( sprintf(
        /* translators: %s: Required WordPress version */
        esc_html__( 'NomadsGuru requires WordPress version %s or higher.', 'nomadsguru' ),
        '5.8'
    ) );
}

// Simple autoloader for the new structure
spl_autoload_register( function ( $class ) {
    // Only handle NomadsGuru classes
    if ( strpos( $class, 'NomadsGuru' ) !== 0 ) {
        return;
    }

    // Convert namespace to file path
    $class_file = str_replace( [ 'NomadsGuru\\', '\\' ], [ '', '/' ], $class );
    $class_file = strtolower( $class_file );
    $class_file = str_replace( '_', '-', $class_file );
    
    // Build the file path
    $file_path = NOMADSGURU_PLUGIN_DIR . 'includes/class-' . $class_file . '.php';
    
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
});

/**
 * Initialize the plugin
 */
function nomadsguru_init() {
    // Load core class
    if ( class_exists( 'NomadsGuru_Core' ) ) {
        NomadsGuru_Core::get_instance();
    }
}

// Initialize the plugin
add_action( 'plugins_loaded', 'nomadsguru_init' );

/**
 * Handle legacy AJAX requests for backward compatibility
 */
function nomadsguru_handle_test_ai_connection() {
    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['nonce'], 'nomadsguru_admin_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
    }

    // Check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
    }

    // Load AI service
    if ( class_exists( 'NomadsGuru_AI' ) ) {
        $ai_service = NomadsGuru_AI::get_instance();
        $result = $ai_service->test_connection();
        
        if ( $result['success'] ) {
            wp_send_json_success( array( 'message' => $result['message'] ) );
        } else {
            wp_send_json_error( array( 'message' => $result['message'] ) );
        }
    } else {
        wp_send_json_error( array( 'message' => __( 'AI service not available.', 'nomadsguru' ) ) );
    }
}
add_action( 'wp_ajax_ng_test_ai_connection', 'nomadsguru_handle_test_ai_connection' );

/**
 * Handle legacy AJAX requests for data reset
 */
function nomadsguru_handle_reset_plugin_data() {
    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['nonce'], 'nomadsguru_admin_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
    }

    // Check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
    }

    global $wpdb;
    
    // Delete all plugin data
    $tables = [
        $wpdb->prefix . 'ng_deal_sources',
        $wpdb->prefix . 'ng_affiliate_programs',
        $wpdb->prefix . 'ng_raw_deals',
        $wpdb->prefix . 'ng_processing_queue'
    ];

    foreach ( $tables as $table ) {
        $wpdb->query( "DELETE FROM $table" );
    }

    // Delete options
    $options = [
        'ng_ai_settings',
        'ng_publishing_settings',
        'ng_usage_stats'
    ];

    foreach ( $options as $option ) {
        delete_option( $option );
    }

    wp_send_json_success( array( 'message' => __( 'Plugin data has been reset successfully.', 'nomadsguru' ) ) );
}
add_action( 'wp_ajax_nomadsguru_reset_plugin_data', 'nomadsguru_handle_reset_plugin_data' );

/**
 * Get AI settings (legacy function for backward compatibility)
 */
function nomadsguru_get_ai_settings() {
    return get_option( 'ng_ai_settings', [] );
}

/**
 * Get publishing settings (legacy function for backward compatibility)
 */
function nomadsguru_get_publishing_settings() {
    return get_option( 'ng_publishing_settings', [] );
}

/**
 * Schedule deal sync (legacy function)
 */
function nomadsguru_schedule_sync() {
    if ( ! wp_next_scheduled( 'nomadsguru_sync_deals' ) ) {
        wp_schedule_event( time(), 'hourly', 'nomadsguru_sync_deals' );
    }
}

/**
 * Unschedule deal sync (legacy function)
 */
function nomadsguru_unschedule_sync() {
    wp_clear_scheduled_hook( 'nomadsguru_sync_deals' );
}

/**
 * Plugin activation hook
 */
register_activation_hook( __FILE__, function() {
    // Create database tables
    if ( class_exists( 'NomadsGuru_Core' ) ) {
        $core = NomadsGuru_Core::get_instance();
        $core->activate();
    }
    
    // Schedule sync
    nomadsguru_schedule_sync();
    
    // Flush rewrite rules
    flush_rewrite_rules();
} );

/**
 * Plugin deactivation hook
 */
register_deactivation_hook( __FILE__, function() {
    // Unschedule sync
    nomadsguru_unschedule_sync();
    
    // Flush rewrite rules
    flush_rewrite_rules();
} );

/**
 * Plugin uninstall hook (for future use)
 */
register_uninstall_hook( __FILE__, function() {
    // Clean up all data if user wants complete removal
    // This will be implemented when we add an uninstall option
} );

/**
 * Get plugin info
 */
function nomadsguru_get_plugin_info() {
    return [
        'name' => 'NomadsGuru - Travel Deals AI',
        'version' => NOMADSGURU_VERSION,
        'author' => 'NomadsGuru Team',
        'url' => 'https://nomadsguru.com',
        'requires_wp' => '5.8',
        'requires_php' => '7.4',
        'text_domain' => 'nomadsguru'
    ];
}

/**
 * Check if plugin is properly configured
 */
function nomadsguru_is_configured() {
    $ai_settings = get_option( 'ng_ai_settings', [] );
    return !empty( $ai_settings['api_key'] );
}

/**
 * Get usage statistics
 */
function nomadsguru_get_usage_stats() {
    return get_option( 'ng_usage_stats', [
        'total_requests' => 0,
        'total_cost' => 0,
        'last_reset' => current_time( 'mysql' )
    ] );
}

/**
 * Log event (simple logging for debugging)
 */
function nomadsguru_log( $message, $level = 'info' ) {
    if ( WP_DEBUG && WP_DEBUG_LOG ) {
        $log_message = sprintf(
            '[%s] [%s] NomadsGuru: %s',
            current_time( 'mysql' ),
            strtoupper( $level ),
            $message
        );
        error_log( $log_message );
    }
}

// Add admin notice if not configured
add_action( 'admin_notices', function() {
    if ( is_admin() && current_user_can( 'manage_options' ) && ! nomadsguru_is_configured() ) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php esc_html_e( 'NomadsGuru', 'nomadsguru' ); ?></strong> - 
                <?php 
                printf(
                    /* translators: %s: Settings page URL */
                    esc_html__( 'Please configure your AI settings in the %s to start using the plugin.', 'nomadsguru' ),
                    '<a href="' . admin_url( 'admin.php?page=nomadsguru-settings&tab=ai' ) . '">' . esc_html__( 'Settings page', 'nomadsguru' ) . '</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }
} );

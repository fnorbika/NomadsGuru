<?php
/**
 * Plugin Name: NomadsGuru
 * Plugin URI:  https://nomadsguru.com
 * Description: Automatically discovers, evaluates, and publishes travel deals using AI.
 * Version:     1.0.1
 * Author:      NomadsGuru Team
 * Author URI:  https://nomadsguru.com
 * License:     GPL-2.0+
 * Text Domain: nomadsguru
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Plugin Constants
define( 'NOMADSGURU_VERSION', '1.0.1' );
define( 'NOMADSGURU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NOMADSGURU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NOMADSGURU_PLUGIN_FILE', __FILE__ );

// Require Composer Autoloader
// Require Composer Autoloader
if ( file_exists( NOMADSGURU_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once NOMADSGURU_PLUGIN_DIR . 'vendor/autoload.php';
} else {
	// Fallback Autoloader
	spl_autoload_register( function ( $class ) {
		$prefix   = 'NomadsGuru\\';
		$base_dir = NOMADSGURU_PLUGIN_DIR . 'src/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	} );
}

/**
 * Main Plugin Class
 */
use NomadsGuru\Core\Loader;

function nomadsguru_init() {
	$loader = Loader::get_instance();
	$loader->init();
}

// Initialize the plugin
add_action( 'plugins_loaded', 'nomadsguru_init' );

// Activation Hook
register_activation_hook( __FILE__, array( 'NomadsGuru\\Core\\Loader', 'activate' ) );

// Deactivation Hook
register_deactivation_hook( __FILE__, array( 'NomadsGuru\\Core\\Loader', 'deactivate' ) );

/**
 * Handle AJAX request to save deal source
 */
function nomadsguru_handle_save_source() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'ng_save_source')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'nomadsguru')));
    }

    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'nomadsguru')));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ng_deal_sources';
    
    $source_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $source_type = sanitize_text_field($_POST['source_type']);
    $source_name = sanitize_text_field($_POST['source_name']);
    $website_url = isset($_POST['website_url']) ? esc_url_raw($_POST['website_url']) : '';
    $rss_feed = isset($_POST['rss_feed']) ? esc_url_raw($_POST['rss_feed']) : '';
    $api_endpoint = isset($_POST['api_endpoint']) ? esc_url_raw($_POST['api_endpoint']) : '';
    $sync_interval = isset($_POST['sync_interval_minutes']) ? intval($_POST['sync_interval_minutes']) : 60;

    // Validate required fields
    if (empty($source_type) || empty($source_name)) {
        wp_send_json_error(array('message' => __('Source type and name are required.', 'nomadsguru')));
    }

    if ($source_type === 'website' && empty($website_url)) {
        wp_send_json_error(array('message' => __('Website URL is required for website sources.', 'nomadsguru')));
    }

    if ($source_type === 'rss' && empty($rss_feed)) {
        wp_send_json_error(array('message' => __('RSS feed URL is required for RSS sources.', 'nomadsguru')));
    }

    $data = array(
        'source_type' => $source_type,
        'source_name' => $source_name,
        'website_url' => $website_url,
        'rss_feed' => $rss_feed,
        'api_endpoint' => $api_endpoint,
        'sync_interval_minutes' => $sync_interval,
        'is_active' => 1,
        'updated_at' => current_time('mysql')
    );

    if ($source_id > 0) {
        // Update existing source
        $result = $wpdb->update($table, $data, array('id' => $source_id));
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Source updated successfully.', 'nomadsguru')));
        }
    } else {
        // Insert new source
        $data['created_at'] = current_time('mysql');
        $result = $wpdb->insert($table, $data);
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Source added successfully.', 'nomadsguru')));
        }
    }

    wp_send_json_error(array('message' => __('Failed to save source.', 'nomadsguru')));
}
add_action('wp_ajax_ng_save_source', 'nomadsguru_handle_save_source');

/**
 * Handle AJAX request to get deal source
 */
function nomadsguru_handle_get_source() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'ng_save_source')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'nomadsguru')));
    }

    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'nomadsguru')));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ng_deal_sources';
    $source_id = intval($_POST['source_id']);

    $source = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d",
        $source_id
    ));

    if ($source) {
        wp_send_json_success($source);
    } else {
        wp_send_json_error(array('message' => __('Source not found.', 'nomadsguru')));
    }
}
add_action('wp_ajax_ng_get_source', 'nomadsguru_handle_get_source');

/**
 * Handle AJAX request to delete deal source
 */
function nomadsguru_handle_delete_source() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'ng_save_source')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'nomadsguru')));
    }

    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'nomadsguru')));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ng_deal_sources';
    $source_id = intval($_POST['source_id']);

    $result = $wpdb->delete($table, array('id' => $source_id));
    
    if ($result !== false) {
        wp_send_json_success(array('message' => __('Source deleted successfully.', 'nomadsguru')));
    } else {
        wp_send_json_error(array('message' => __('Failed to delete source.', 'nomadsguru')));
    }
}
add_action('wp_ajax_ng_delete_source', 'nomadsguru_handle_delete_source');

/**
 * Handle AJAX request to save affiliate program
 */
function nomadsguru_handle_save_affiliate() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'ng_save_affiliate')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'nomadsguru')));
    }

    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'nomadsguru')));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ng_affiliate_programs';
    
    $affiliate_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $program_name = sanitize_text_field($_POST['program_name']);
    $tracking_id = sanitize_text_field($_POST['tracking_id']);
    $commission_rate = floatval($_POST['commission_rate']);
    $custom_params = sanitize_textarea_field($_POST['custom_params']);

    // Validate required fields
    if (empty($program_name) || empty($tracking_id)) {
        wp_send_json_error(array('message' => __('Program name and tracking ID are required.', 'nomadsguru')));
    }

    $data = array(
        'program_name' => $program_name,
        'tracking_id' => $tracking_id,
        'commission_rate' => $commission_rate,
        'custom_params' => $custom_params,
        'is_active' => 1,
        'updated_at' => current_time('mysql')
    );

    if ($affiliate_id > 0) {
        // Update existing affiliate
        $result = $wpdb->update($table, $data, array('id' => $affiliate_id));
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Affiliate program updated successfully.', 'nomadsguru')));
        }
    } else {
        // Insert new affiliate
        $data['created_at'] = current_time('mysql');
        $result = $wpdb->insert($table, $data);
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Affiliate program added successfully.', 'nomadsguru')));
        }
    }

    wp_send_json_error(array('message' => __('Failed to save affiliate program.', 'nomadsguru')));
}
add_action('wp_ajax_ng_save_affiliate', 'nomadsguru_handle_save_affiliate');

/**
 * Handle AJAX request to delete affiliate program
 */
function nomadsguru_handle_delete_affiliate() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'ng_save_affiliate')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'nomadsguru')));
    }

    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'nomadsguru')));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ng_affiliate_programs';
    $affiliate_id = intval($_POST['id']);

    $result = $wpdb->delete($table, array('id' => $affiliate_id));
    
    if ($result !== false) {
        wp_send_json_success(array('message' => __('Affiliate program deleted successfully.', 'nomadsguru')));
    } else {
        wp_send_json_error(array('message' => __('Failed to delete affiliate program.', 'nomadsguru')));
    }
}
add_action('wp_ajax_ng_delete_affiliate', 'nomadsguru_handle_delete_affiliate');

/**
 * Handle AJAX request to reset plugin data
 */
function nomadsguru_handle_reset_plugin_data() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'nomadsguru_admin_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'nomadsguru')));
    }

    // Check user capabilities
    if (!current_user_can('activate_plugins')) {
        wp_send_json_error(array('message' => __('You do not have sufficient permissions to perform this action.', 'nomadsguru')));
    }

    global $wpdb;

    // Start transaction
    $wpdb->query('START TRANSACTION');

    try {
        // List of plugin tables to truncate
        $tables = array(
            $wpdb->prefix . 'ng_raw_deals',
            $wpdb->prefix . 'ng_deal_sources',
            $wpdb->prefix . 'ng_processing_queue',
            $wpdb->prefix . 'ng_affiliate_programs',
            $wpdb->prefix . 'ng_publishing_queue'
        );

        // Truncate each table if it exists
        foreach ($tables as $table) {
            $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
            if ($table_exists === $table) {
                $wpdb->query("TRUNCATE TABLE `$table`");
            }
        }

        // Delete all plugin options
        $options = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                'ng_%'
            )
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }

        // Clear all plugin caches
        wp_cache_flush();
        
        // Commit transaction
        $wpdb->query('COMMIT');

        // Deactivate the plugin
        if (is_plugin_active('nomadsguru/nomadsguru.php')) {
            deactivate_plugins('nomadsguru/nomadsguru.php', false, is_network_admin());
        }

        wp_send_json_success(array(
            'message' => __('Plugin data has been reset successfully. The plugin has been deactivated.', 'nomadsguru'),
            'redirect' => admin_url('plugins.php')
        ));

    } catch (Exception $e) {
        // Rollback on error
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => __('Error resetting plugin data: ', 'nomadsguru') . $e->getMessage()));
    }
}
add_action('wp_ajax_nomadsguru_reset_plugin_data', 'nomadsguru_handle_reset_plugin_data');

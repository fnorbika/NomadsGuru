<?php

/**
 * Admin functionality for NomadsGuru
 * 
 * @package NomadsGuru
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NomadsGuru_Admin {

    /**
     * Single instance of the class
     * @var NomadsGuru_Admin|null
     */
    private static $instance = null;

    /**
     * AI Settings instance
     * @var object
     */
    private $ai_settings;

    /**
     * Get singleton instance
     * 
     * @return NomadsGuru_Admin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Admin menu
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        
        // Admin assets
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_ng_save_source', array( $this, 'handle_save_source' ) );
        add_action( 'wp_ajax_ng_get_source', array( $this, 'handle_get_source' ) );
        add_action( 'wp_ajax_ng_delete_source', array( $this, 'handle_delete_source' ) );
        add_action( 'wp_ajax_ng_save_affiliate', array( $this, 'handle_save_affiliate' ) );
        add_action( 'wp_ajax_ng_delete_affiliate', array( $this, 'handle_delete_affiliate' ) );
        add_action( 'wp_ajax_ng_test_ai_connection', array( $this, 'handle_test_ai_connection' ) );
        add_action( 'wp_ajax_ng_fetch_deals', array( $this, 'handle_fetch_deals' ) );
        add_action( 'wp_ajax_ng_create_sample_csv', array( $this, 'handle_create_sample_csv' ) );
        add_action( 'wp_ajax_nomadsguru_reset_plugin_data', array( $this, 'handle_reset_data' ) );
        
        // Settings registration
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Register admin menu
     */
    public function register_menu() {
        // Main menu
        add_menu_page(
            __( 'NomadsGuru', 'nomadsguru' ),
            __( 'NomadsGuru', 'nomadsguru' ),
            'manage_options',
            'nomadsguru',
            array( $this, 'render_dashboard' ),
            'dashicons-airplane',
            25
        );

        // Dashboard submenu
        add_submenu_page(
            'nomadsguru',
            __( 'Dashboard', 'nomadsguru' ),
            __( 'Dashboard', 'nomadsguru' ),
            'manage_options',
            'nomadsguru',
            array( $this, 'render_dashboard' )
        );

        // Settings submenu
        add_submenu_page(
            'nomadsguru',
            __( 'Settings', 'nomadsguru' ),
            __( 'Settings', 'nomadsguru' ),
            'manage_options',
            'nomadsguru-settings',
            array( $this, 'render_settings' )
        );

        // Sources submenu
        add_submenu_page(
            'nomadsguru',
            __( 'Sources', 'nomadsguru' ),
            __( 'Sources', 'nomadsguru' ),
            'manage_options',
            'nomadsguru-sources',
            array( $this, 'render_sources' )
        );

        // Queue submenu
        add_submenu_page(
            'nomadsguru',
            __( 'Queue', 'nomadsguru' ),
            __( 'Queue', 'nomadsguru' ),
            'manage_options',
            'nomadsguru-queue',
            array( $this, 'render_queue' )
        );

        // Logs submenu
        add_submenu_page(
            'nomadsguru',
            __( 'Logs', 'nomadsguru' ),
            __( 'Logs', 'nomadsguru' ),
            'manage_options',
            'nomadsguru-logs',
            array( $this, 'render_logs' )
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets( $hook ) {
        // Only load on NomadsGuru pages
        if ( strpos( $hook, 'nomadsguru' ) === false ) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'nomadsguru-admin',
            NOMADSGURU_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            NOMADSGURU_VERSION . '-' . filemtime( NOMADSGURU_PLUGIN_DIR . 'assets/css/admin.css' )
        );

        // JavaScript
        wp_enqueue_script(
            'nomadsguru-admin',
            NOMADSGURU_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            NOMADSGURU_VERSION . '-' . time(), // Force cache refresh
            true
        );

        // Localize script
        wp_localize_script( 'nomadsguru-admin', 'nomadsguruParams', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'nomadsguru_admin_nonce' ),
            'pluginsUrl' => admin_url( 'plugins.php' ),
        ) );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // AI Settings
        register_setting(
            'nomadsguru_ai_settings',
            'ng_ai_settings',
            array(
                'type' => 'object',
                'sanitize_callback' => array( $this, 'sanitize_ai_settings' ),
                'default' => array(
                    'provider' => 'openai',
                    'api_key' => '',
                    'model' => 'gpt-3.5-turbo',
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                    'image_api_keys' => array()
                )
            )
        );

        // Publishing Settings
        register_setting(
            'nomadsguru_publishing',
            'ng_publishing_settings',
            array(
                'type' => 'object',
                'sanitize_callback' => array( $this, 'sanitize_publishing_settings' ),
                'default' => array(
                    'auto_publish' => 0,
                    'default_category' => 0,
                    'default_author' => 1,
                    'publish_threshold' => 7.0
                )
            )
        );

        // AI Settings Section
        add_settings_section(
            'ng_ai_section',
            __( 'AI Configuration', 'nomadsguru' ),
            array( $this, 'render_ai_section_header' ),
            'nomadsguru_ai_settings'
        );

        // AI Settings Fields
        add_settings_field(
            'ai_provider',
            __( 'AI Provider', 'nomadsguru' ),
            array( $this, 'render_provider_field' ),
            'nomadsguru_ai_settings',
            'ng_ai_section'
        );

        add_settings_field(
            'api_key',
            __( 'API Key', 'nomadsguru' ),
            array( $this, 'render_api_key_field' ),
            'nomadsguru_ai_settings',
            'ng_ai_section'
        );

        add_settings_field(
            'model',
            __( 'Model', 'nomadsguru' ),
            array( $this, 'render_model_field' ),
            'nomadsguru_ai_settings',
            'ng_ai_section'
        );

        add_settings_field(
            'temperature',
            __( 'Temperature', 'nomadsguru' ),
            array( $this, 'render_temperature_field' ),
            'nomadsguru_ai_settings',
            'ng_ai_section'
        );

        add_settings_field(
            'max_tokens',
            __( 'Max Tokens', 'nomadsguru' ),
            array( $this, 'render_max_tokens_field' ),
            'nomadsguru_ai_settings',
            'ng_ai_section'
        );

        add_settings_field(
            'image_api_keys',
            __( 'Image API Keys', 'nomadsguru' ),
            array( $this, 'render_image_api_keys_field' ),
            'nomadsguru_ai_settings',
            'ng_ai_section'
        );

        add_settings_field(
            'test_connection',
            __( 'Test Connection', 'nomadsguru' ),
            array( $this, 'render_test_connection_field' ),
            'nomadsguru_ai_settings',
            'ng_ai_section'
        );
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

        include NOMADSGURU_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    /**
     * Render settings page
     */
    public function render_settings() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'ai';
        ?>
        <div class="wrap nomadsguru-settings">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=nomadsguru-settings&tab=ai" class="nav-tab <?php echo $active_tab === 'ai' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'AI Settings', 'nomadsguru' ); ?>
                </a>
                <a href="?page=nomadsguru-settings&tab=sources" class="nav-tab <?php echo $active_tab === 'sources' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Deal Sources', 'nomadsguru' ); ?>
                </a>
                <a href="?page=nomadsguru-settings&tab=publishing" class="nav-tab <?php echo $active_tab === 'publishing' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Publishing', 'nomadsguru' ); ?>
                </a>
                <a href="?page=nomadsguru-settings&tab=reset" class="nav-tab <?php echo $active_tab === 'reset' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Reset Data', 'nomadsguru' ); ?>
                </a>
            </nav>
            
            <div class="tab-content">
                <?php
                switch ( $active_tab ) {
                    case 'sources':
                        $this->render_sources_tab();
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
     * Render AI Settings tab
     */
    private function render_ai_tab() {
        // Show error/update messages
        settings_errors( 'nomadsguru_messages' );
        ?>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'nomadsguru_ai_settings' );
            do_settings_sections( 'nomadsguru_ai_settings' );
            submit_button( __( 'Save AI Settings', 'nomadsguru' ) );
            ?>
        </form>
        <?php
    }

    /**
     * Render Publishing tab
     */
    private function render_publishing_tab() {
        ?>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'nomadsguru_publishing' );
            do_settings_sections( 'nomadsguru_publishing' );
            submit_button( __( 'Save Publishing Settings', 'nomadsguru' ) );
            ?>
        </form>
        <?php
    }

    /**
     * Render Deal Sources tab
     */
    private function render_sources_tab() {
        // Get deal sources manager
        $sources_manager = NomadsGuru_Deal_Sources::get_instance();
        $sources = $sources_manager->get_sources();
        $statistics = $sources_manager->get_source_statistics();
        
        ?>
        <div class="ng-sources-management">
            <div class="ng-sources-header">
                <h2><?php esc_html_e( 'Deal Sources Management', 'nomadsguru' ); ?></h2>
                <p><?php esc_html_e( 'Configure and manage your deal sources. The system supports CSV files, RSS feeds, web scrapers, and API integrations.', 'nomadsguru' ); ?></p>
            </div>
            
            <!-- Manual Fetch Section -->
            <div class="ng-manual-fetch">
                <h3><?php esc_html_e( 'Manual Deal Fetch', 'nomadsguru' ); ?></h3>
                <p><?php esc_html_e( 'Manually trigger deal fetching from all active sources.', 'nomadsguru' ); ?></p>
                
                <button type="button" id="ng_fetch_deals" class="button button-primary">
                    <?php esc_html_e( 'Fetch Deals Now', 'nomadsguru' ); ?>
                </button>
                
                <div id="ng_fetch_results" class="ng-fetch-results"></div>
            </div>
            
            <!-- Active Sources -->
            <div class="ng-active-sources">
                <h3><?php esc_html_e( 'Active Sources', 'nomadsguru' ); ?></h3>
                
                <?php if ( empty( $sources ) ): ?>
                    <div class="ng-no-sources">
                        <p><?php esc_html_e( 'No active deal sources configured. Add sources below to get started.', 'nomadsguru' ); ?></p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Source Name', 'nomadsguru' ); ?></th>
                                <th><?php esc_html_e( 'Type', 'nomadsguru' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'nomadsguru' ); ?></th>
                                <th><?php esc_html_e( 'Last Fetch', 'nomadsguru' ); ?></th>
                                <th><?php esc_html_e( 'Total Deals', 'nomadsguru' ); ?></th>
                                <th><?php esc_html_e( 'Actions', 'nomadsguru' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $sources as $name => $source ): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html( $source->get_name() ); ?></strong>
                                        <br>
                                        <small><?php echo esc_html( $source->get_type() ); ?> source</small>
                                    </td>
                                    <td>
                                        <span class="ng-source-type ng-type-<?php echo esc_attr( $source->get_type() ); ?>">
                                            <?php echo esc_html( ucfirst( $source->get_type() ) ); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ( $source->is_active() ): ?>
                                            <span class="ng-status ng-status-active"><?php esc_html_e( 'Active', 'nomadsguru' ); ?></span>
                                        <?php else: ?>
                                            <span class="ng-status ng-status-inactive"><?php esc_html_e( 'Inactive', 'nomadsguru' ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $last_fetch = $source->get_last_fetch();
                                        if ( $last_fetch > 0 ) {
                                            echo esc_html( human_time_diff( $last_fetch ) ) . ' ' . esc_html__( 'ago', 'nomadsguru' );
                                        } else {
                                            esc_html_e( 'Never', 'nomadsguru' );
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $source_stats = array_filter( $statistics, function( $stat ) use ( $name ) {
                                            return $stat['source'] === $name;
                                        });
                                        if ( ! empty( $source_stats ) ) {
                                            $stat = reset( $source_stats );
                                            echo esc_html( $stat['total_deals'] );
                                        } else {
                                            echo '0';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small ng-test-source" data-source="<?php echo esc_attr( $name ); ?>">
                                            <?php esc_html_e( 'Test', 'nomadsguru' ); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Source Statistics -->
            <?php if ( ! empty( $statistics ) ): ?>
                <div class="ng-source-statistics">
                    <h3><?php esc_html_e( 'Source Statistics', 'nomadsguru' ); ?></h3>
                    
                    <div class="ng-stats-grid">
                        <?php foreach ( $statistics as $stat ): ?>
                            <div class="ng-stat-card">
                                <h4><?php echo esc_html( $stat['source'] ); ?></h4>
                                <div class="ng-stat-details">
                                    <div class="ng-stat-item">
                                        <span class="ng-stat-label"><?php esc_html_e( 'Total Deals:', 'nomadsguru' ); ?></span>
                                        <span class="ng-stat-value"><?php echo esc_html( $stat['total_deals'] ); ?></span>
                                    </div>
                                    <div class="ng-stat-item">
                                        <span class="ng-stat-label"><?php esc_html_e( 'Pending:', 'nomadsguru' ); ?></span>
                                        <span class="ng-stat-value"><?php echo esc_html( $stat['pending_deals'] ); ?></span>
                                    </div>
                                    <div class="ng-stat-item">
                                        <span class="ng-stat-label"><?php esc_html_e( 'Approved:', 'nomadsguru' ); ?></span>
                                        <span class="ng-stat-value"><?php echo esc_html( $stat['approved_deals'] ); ?></span>
                                    </div>
                                    <div class="ng-stat-item">
                                        <span class="ng-stat-label"><?php esc_html_e( 'Last Fetch:', 'nomadsguru' ); ?></span>
                                        <span class="ng-stat-value">
                                            <?php 
                                            if ( $stat['last_fetch'] ) {
                                                echo esc_html( human_time_diff( strtotime( $stat['last_fetch'] ) ) ) . ' ' . esc_html__( 'ago', 'nomadsguru' );
                                            } else {
                                                esc_html_e( 'Never', 'nomadsguru' );
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- CSV Source Management -->
            <div class="ng-csv-source">
                <h3><?php esc_html_e( 'CSV Manual Source', 'nomadsguru' ); ?></h3>
                <p><?php esc_html_e( 'Manage your manual CSV deal source. The CSV file should contain columns: title, destination, original_price, discounted_price, currency, travel_start, travel_end, booking_url, description', 'nomadsguru' ); ?></p>
                
                <div class="ng-csv-actions">
                    <button type="button" id="ng_create_sample_csv" class="button button-secondary">
                        <?php esc_html_e( 'Create Sample CSV', 'nomadsguru' ); ?>
                    </button>
                    
                    <div class="ng-csv-info">
                        <p><strong><?php esc_html_e( 'Current CSV File:', 'nomadsguru' ); ?></strong></p>
                        <p><?php echo esc_html( NOMADSGURU_PLUGIN_DIR . 'data/manual-deals.csv' ); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Add Custom Source -->
            <div class="ng-add-source">
                <h3><?php esc_html_e( 'Add Custom Source', 'nomadsguru' ); ?></h3>
                <p><?php esc_html_e( 'Add custom RSS feeds, API endpoints, or web scrapers.', 'nomadsguru' ); ?></p>
                
                <div class="ng-source-types">
                    <button type="button" class="button ng-add-rss" data-type="rss">
                        <?php esc_html_e( 'Add RSS Feed', 'nomadsguru' ); ?>
                    </button>
                    <button type="button" class="button ng-add-api" data-type="api">
                        <?php esc_html_e( 'Add API Source', 'nomadsguru' ); ?>
                    </button>
                    <button type="button" class="button ng-add-scraper" data-type="scraper">
                        <?php esc_html_e( 'Add Web Scraper', 'nomadsguru' ); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Manual fetch
            $('#ng_fetch_deals').on('click', function() {
                var $button = $(this);
                var $results = $('#ng_fetch_results');
                
                $button.prop('disabled', true).text('<?php esc_html_e( 'Fetching...', 'nomadsguru' ); ?>');
                $results.html('<div class="ng-loading"><?php esc_html_e( 'Fetching deals from all sources...', 'nomadsguru' ); ?></div>');
                
                $.ajax({
                    url: ajaxurl,
                    data: {
                        action: 'ng_fetch_deals',
                        nonce: '<?php echo wp_create_nonce( 'nomadsguru_admin_nonce' ); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php esc_html_e( 'Fetch Deals Now', 'nomadsguru' ); ?>');
                        
                        if (response.success) {
                            var html = '<div class="ng-success">';
                            html += '<h4><?php esc_html_e( 'Fetch Complete!', 'nomadsguru' ); ?></h4>';
                            html += '<p><?php esc_html_e( 'Sources processed:', 'nomadsguru' ); ?> ' + response.data.sources_processed + '</p>';
                            html += '<p><?php esc_html_e( 'Total deals fetched:', 'nomadsguru' ); ?> ' + response.data.total_deals + '</p>';
                            html += '<p><?php esc_html_e( 'New deals saved:', 'nomadsguru' ); ?> ' + response.data.new_deals + '</p>';
                            html += '</div>';
                            $results.html(html);
                            
                            // Reload page after delay to show updated stats
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $results.html('<div class="ng-error">' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php esc_html_e( 'Fetch Deals Now', 'nomadsguru' ); ?>');
                        $results.html('<div class="ng-error"><?php esc_html_e( 'Request failed. Please try again.', 'nomadsguru' ); ?></div>');
                    }
                });
            });
            
            // Create sample CSV
            $('#ng_create_sample_csv').on('click', function() {
                var $button = $(this);
                
                $button.prop('disabled', true).text('<?php esc_html_e( 'Creating...', 'nomadsguru' ); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    data: {
                        action: 'ng_create_sample_csv',
                        nonce: '<?php echo wp_create_nonce( 'nomadsguru_admin_nonce' ); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php esc_html_e( 'Create Sample CSV', 'nomadsguru' ); ?>');
                        
                        if (response.success) {
                            alert('<?php esc_html_e( 'Sample CSV file created successfully!', 'nomadsguru' ); ?>');
                        } else {
                            alert(response.data.message || '<?php esc_html_e( 'Failed to create sample CSV.', 'nomadsguru' ); ?>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php esc_html_e( 'Create Sample CSV', 'nomadsguru' ); ?>');
                        alert('<?php esc_html_e( 'Request failed. Please try again.', 'nomadsguru' ); ?>');
                    }
                });
            });
        });
        </script>
        
        <style>
        .ng-sources-management {
            max-width: 1200px;
        }
        .ng-sources-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .ng-manual-fetch, .ng-active-sources, .ng-source-statistics, .ng-csv-source, .ng-add-source {
            margin-bottom: 40px;
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .ng-fetch-results {
            margin-top: 15px;
            padding: 15px;
            border-radius: 4px;
        }
        .ng-fetch-results .ng-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .ng-fetch-results .ng-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .ng-fetch-results .ng-loading {
            background: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
        }
        .ng-source-type {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .ng-type-csv { background: #e3f2fd; color: #1976d2; }
        .ng-type-rss { background: #f3e5f5; color: #7b1fa2; }
        .ng-type-api { background: #e8f5e8; color: #388e3c; }
        .ng-type-scraper { background: #fff3e0; color: #f57c00; }
        .ng-status {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .ng-status-active { background: #d4edda; color: #155724; }
        .ng-status-inactive { background: #f8d7da; color: #721c24; }
        .ng-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .ng-stat-card {
            padding: 20px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .ng-stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .ng-stat-label {
            font-weight: 500;
        }
        .ng-stat-value {
            font-weight: bold;
        }
        .ng-csv-actions {
            margin-top: 20px;
        }
        .ng-source-types {
            margin-top: 20px;
        }
        .ng-source-types button {
            margin-right: 10px;
        }
        </style>
        <?php
    }

    /**
     * Render Reset tab
     */
    private function render_reset_tab() {
        include NOMADSGURU_PLUGIN_DIR . 'templates/admin/reset-tab.php';
    }

    /**
     * Render sources page
     */
    public function render_sources() {
        include NOMADSGURU_PLUGIN_DIR . 'templates/admin/sources.php';
    }

    /**
     * Render queue page
     */
    public function render_queue() {
        include NOMADSGURU_PLUGIN_DIR . 'templates/admin/queue.php';
    }

    /**
     * Render logs page
     */
    public function render_logs() {
        include NOMADSGURU_PLUGIN_DIR . 'templates/admin/logs.php';
    }

    /**
     * Render AI settings section header
     */
    public function render_ai_section_header() {
        echo '<p>' . esc_html__( 'Configure AI settings for content generation and evaluation.', 'nomadsguru' ) . '</p>';
    }

    /**
     * Render provider field
     */
    public function render_provider_field() {
        $settings = get_option( 'ng_ai_settings', [] );
        $current_provider = $settings['provider'] ?? 'openai';
        ?>
        <select name="ng_ai_settings[provider]" id="ai_provider">
            <option value="openai" <?php selected( $current_provider, 'openai' ); ?>>OpenAI (GPT-3.5/4)</option>
            <option value="gemini" <?php selected( $current_provider, 'gemini' ); ?>>Google Gemini</option>
            <option value="grok" <?php selected( $current_provider, 'grok' ); ?>>xAI Grok</option>
            <option value="perplexity" <?php selected( $current_provider, 'perplexity' ); ?>>Perplexity AI</option>
        </select>
        <?php
    }

    /**
     * Render API key field
     */
    public function render_api_key_field() {
        $settings = get_option( 'ng_ai_settings', [] );
        $current_provider = $settings['provider'] ?? 'openai';
        $api_key = $settings['api_key'] ?? '';
        $decrypted_key = !empty( $api_key ) ? '••••••••' . substr( base64_decode( $api_key ), -4 ) : '';
        
        $api_links = [
            'openai' => '<a href="https://platform.openai.com/api-keys" target="_blank">OpenAI</a>',
            'gemini' => '<a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>',
            'grok' => '<a href="https://console.x.ai/api-keys" target="_blank">xAI</a>',
            'perplexity' => '<a href="https://www.perplexity.ai/settings/api" target="_blank">Perplexity</a>'
        ];
        
        $provider_link = $api_links[$current_provider] ?? $api_links['openai'];
        ?>
        <input type="password" 
               name="ng_ai_settings[api_key]" 
               id="api_key" 
               value="<?php echo esc_attr( $decrypted_key ); ?>" 
               class="regular-text" 
               placeholder="<?php esc_attr_e( 'Enter your API key', 'nomadsguru' ); ?>"
               autocomplete="off"
        />
        <p class="description">
            <?php 
            printf(
                esc_html__( 'Get your API key from %s', 'nomadsguru' ),
                $provider_link
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render model field
     */
    public function render_model_field() {
        $settings = get_option( 'ng_ai_settings', [] );
        $current_provider = $settings['provider'] ?? 'openai';
        $current_model = $settings['model'] ?? '';
        
        $models = [
            'openai' => [
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Fast & Cost-effective)',
                'gpt-4' => 'GPT-4 (Better quality, more expensive)',
                'gpt-4-turbo' => 'GPT-4 Turbo (Latest, balanced)',
            ],
            'gemini' => [
                'gemini-1.5-flash' => 'Gemini 1.5 Flash (Fast)',
                'gemini-1.5-pro' => 'Gemini 1.5 Pro (Advanced)',
                'gemini-pro' => 'Gemini Pro (Standard)',
            ],
            'grok' => [
                'grok-2' => 'Grok-2 (Latest)',
                'grok-2-mini' => 'Grok-2 Mini (Fast)',
            ],
            'perplexity' => [
                'llama-3.1-70b' => 'Llama 3.1 70B',
                'llama-3.1-8b' => 'Llama 3.1 8B (Fast)',
                'mixtral-8x7b' => 'Mixtral 8x7B',
            ]
        ];
        
        $provider_models = $models[$current_provider] ?? $models['openai'];
        
        if ( empty( $current_model ) || !isset( $provider_models[$current_model] ) ) {
            $current_model = key( $provider_models );
        }
        ?>
        <select name="ng_ai_settings[model]" id="ai_model">
            <?php foreach ( $provider_models as $value => $label ) : ?>
                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_model, $value ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render temperature field
     */
    public function render_temperature_field() {
        $settings = get_option( 'ng_ai_settings', [] );
        $temperature = $settings['temperature'] ?? 0.7;
        ?>
        <input type="range" 
               name="ng_ai_settings[temperature]" 
               id="ai_temperature" 
               value="<?php echo esc_attr( $temperature ); ?>" 
               min="0" 
               max="2" 
               step="0.1"
        />
        <span class="temperature-value"><?php echo esc_html( $temperature ); ?></span>
        <p class="description">
            <?php esc_html_e( 'Controls randomness: 0 = focused, 2 = creative', 'nomadsguru' ); ?>
        </p>
        <?php
    }

    /**
     * Render max tokens field
     */
    public function render_max_tokens_field() {
        $settings = get_option( 'ng_ai_settings', [] );
        $max_tokens = $settings['max_tokens'] ?? 500;
        ?>
        <input type="number" 
               name="ng_ai_settings[max_tokens]" 
               id="ai_max_tokens" 
               value="<?php echo esc_attr( $max_tokens ); ?>" 
               min="100" 
               max="4000" 
               step="50"
        />
        <p class="description">
            <?php esc_html_e( 'Maximum response length (100-4000 tokens)', 'nomadsguru' ); ?>
        </p>
        <?php
    }

    /**
     * Render test connection field
     */
    public function render_test_connection_field() {
        ?>
        <button type="button" id="test_ai_connection" class="button button-secondary">
            <?php esc_html_e( 'Test API Connection', 'nomadsguru' ); ?>
        </button>
        <div id="test_result"></div>
        <?php
    }

    /**
     * Render Image API Keys field
     */
    public function render_image_api_keys_field() {
        $settings = get_option( 'ng_ai_settings', [] );
        $image_keys = $settings['image_api_keys'] ?? [];
        ?>
        <div class="ng-image-api-keys">
            <p class="description">
                <?php esc_html_e( 'Configure API keys for image providers. Pixabay works without a key (demo key included).', 'nomadsguru' ); ?>
            </p>
            
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="pixabay_key">
                            <?php esc_html_e( 'Pixabay API Key', 'nomadsguru' ); ?>
                            <span class="description">(<?php esc_html_e( 'Optional - free tier available', 'nomadsguru' ); ?>)</span>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               name="ng_ai_settings[image_api_keys][pixabay]" 
                               id="pixabay_key" 
                               value="<?php echo esc_attr( $image_keys['pixabay'] ?? '' ); ?>" 
                               class="regular-text" 
                               placeholder="<?php esc_attr_e( 'Get your free API key from pixabay.com', 'nomadsguru' ); ?>"
                        />
                        <p class="description">
                            <?php 
                            printf(
                                /* translators: %s: URL to Pixabay API */
                                esc_html__( 'Get your free API key from %s. 5,000 requests/hour.', 'nomadsguru' ),
                                '<a href="https://pixabay.com/api/docs/" target="_blank">Pixabay</a>'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="pexels_key">
                            <?php esc_html_e( 'Pexels API Key', 'nomadsguru' ); ?>
                            <span class="description">(<?php esc_html_e( 'Optional', 'nomadsguru' ); ?>)</span>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               name="ng_ai_settings[image_api_keys][pexels]" 
                               id="pexels_key" 
                               value="<?php echo esc_attr( $image_keys['pexels'] ?? '' ); ?>" 
                               class="regular-text" 
                               placeholder="<?php esc_attr_e( 'Get your free API key from pexels.com', 'nomadsguru' ); ?>"
                        />
                        <p class="description">
                            <?php 
                            printf(
                                /* translators: %s: URL to Pexels API */
                                esc_html__( 'Get your free API key from %s. 200 requests/hour.', 'nomadsguru' ),
                                '<a href="https://www.pexels.com/api/" target="_blank">Pexels</a>'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="unsplash_key">
                            <?php esc_html_e( 'Unsplash API Key', 'nomadsguru' ); ?>
                            <span class="description">(<?php esc_html_e( 'Optional', 'nomadsguru' ); ?>)</span>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               name="ng_ai_settings[image_api_keys][unsplash]" 
                               id="unsplash_key" 
                               value="<?php echo esc_attr( $image_keys['unsplash'] ?? '' ); ?>" 
                               class="regular-text" 
                               placeholder="<?php esc_attr_e( 'Get your free API key from unsplash.com', 'nomadsguru' ); ?>"
                        />
                        <p class="description">
                            <?php 
                            printf(
                                /* translators: %s: URL to Unsplash API */
                                esc_html__( 'Get your free API key from %s. 50 requests/hour (demo), unlimited on approval.', 'nomadsguru' ),
                                '<a href="https://unsplash.com/developers" target="_blank">Unsplash</a>'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <div class="ng-image-provider-info">
                <h4><?php esc_html_e( 'Provider Priority', 'nomadsguru' ); ?></h4>
                <p><?php esc_html_e( 'Images are searched in this order: Pixabay → Pexels → Unsplash → Placeholder', 'nomadsguru' ); ?></p>
                <ul>
                    <li><strong>Pixabay:</strong> <?php esc_html_e( 'Free, 5,000 req/hour, no key required for demo', 'nomadsguru' ); ?></li>
                    <li><strong>Pexels:</strong> <?php esc_html_e( 'Free, 200 req/hour, requires API key', 'nomadsguru' ); ?></li>
                    <li><strong>Unsplash:</strong> <?php esc_html_e( 'Free, 50 req/hour demo, requires API key', 'nomadsguru' ); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Sanitize AI settings
     */
    public function sanitize_ai_settings( $input ) {
        $sanitized = [];
        
        // Sanitize provider
        $allowed_providers = ['openai', 'gemini', 'grok', 'perplexity'];
        $sanitized['provider'] = in_array( $input['provider'] ?? '', $allowed_providers, true ) 
            ? $input['provider'] 
            : 'openai';

        // Sanitize API key
        if ( !empty( $input['api_key'] ) ) {
            // Check if this is the masked value (starts with ••••)
            if ( strpos( $input['api_key'], '••••' ) === 0 ) {
                // Keep existing API key if masked value is submitted
                $existing_settings = get_option( 'ng_ai_settings', [] );
                $sanitized['api_key'] = $existing_settings['api_key'] ?? '';
            } else {
                // Save new API key (no validation)
                $sanitized['api_key'] = base64_encode( $input['api_key'] );
            }
        } else {
            // Keep existing API key if field is empty (user didn't change it)
            $existing_settings = get_option( 'ng_ai_settings', [] );
            $sanitized['api_key'] = $existing_settings['api_key'] ?? '';
        }

        // Sanitize model
        $sanitized['model'] = sanitize_text_field( $input['model'] ?? 'gpt-3.5-turbo' );
        
        // Sanitize temperature
        $temp = floatval( $input['temperature'] ?? 0.7 );
        $sanitized['temperature'] = max( 0, min( 2, $temp ) );
        
        // Sanitize max tokens
        $tokens = intval( $input['max_tokens'] ?? 500 );
        $sanitized['max_tokens'] = max( 100, min( 4000, $tokens ) );

        // Sanitize image API keys
        $image_keys = [];
        if ( !empty( $input['image_api_keys'] ) && is_array( $input['image_api_keys'] ) ) {
            foreach ( $input['image_api_keys'] as $provider => $key ) {
                if ( !empty( $key ) ) {
                    $image_keys[$provider] = sanitize_text_field( $key );
                }
            }
        }
        $sanitized['image_api_keys'] = $image_keys;

        // Add success message
        add_settings_error(
            'nomadsguru_messages',
            'ai_settings_saved',
            sprintf(
                '<span class="ng-success-icon">✓</span><span class="ng-success-title">%s</span><span class="ng-success-message">%s</span>',
                __( 'AI Settings Saved Successfully!', 'nomadsguru' ),
                __( 'Your AI configuration has been updated and is ready to use.', 'nomadsguru' )
            ),
            'success'
        );

        return $sanitized;
    }

    /**
     * Sanitize publishing settings
     */
    public function sanitize_publishing_settings( $input ) {
        $sanitized = [];
        
        $sanitized['auto_publish'] = isset( $input['auto_publish'] ) ? 1 : 0;
        $sanitized['default_category'] = intval( $input['default_category'] ?? 0 );
        $sanitized['default_author'] = intval( $input['default_author'] ?? 1 );
        $sanitized['publish_threshold'] = floatval( $input['publish_threshold'] ?? 7.0 );
        $sanitized['publish_threshold'] = max( 1, min( 10, $sanitized['publish_threshold'] ) );

        // Add success message
        add_settings_error(
            'nomadsguru_messages',
            'publishing_settings_saved',
            sprintf(
                '<span class="ng-success-icon">✓</span><span class="ng-success-title">%s</span><span class="ng-success-message">%s</span>',
                __( 'Publishing Settings Saved!', 'nomadsguru' ),
                __( 'Your publishing configuration has been updated.', 'nomadsguru' )
            ),
            'success'
        );

        return $sanitized;
    }

    /**
     * Handle AJAX request to save deal source
     */
    public function handle_save_source() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'nomadsguru_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ng_deal_sources';
        
        // Create table if it doesn't exist
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            source_type varchar(50) DEFAULT '',
            source_name varchar(255) DEFAULT '',
            website_url text,
            rss_feed text,
            sync_interval_minutes int(11) DEFAULT 60,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_source_type (source_type),
            KEY idx_is_active (is_active)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        
        $source_id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        $source_type = sanitize_text_field( $_POST['source_type'] );
        $source_name = sanitize_text_field( $_POST['source_name'] );
        $website_url = isset( $_POST['website_url'] ) ? esc_url_raw( $_POST['website_url'] ) : '';
        $rss_feed = isset( $_POST['rss_feed'] ) ? esc_url_raw( $_POST['rss_feed'] ) : '';
        $sync_interval = isset( $_POST['sync_interval_minutes'] ) ? intval( $_POST['sync_interval_minutes'] ) : 60;

        // Validate required fields
        if ( empty( $source_type ) || empty( $source_name ) ) {
            wp_send_json_error( array( 'message' => __( 'Source type and name are required.', 'nomadsguru' ) ) );
        }

        if ( $source_type === 'website' && empty( $website_url ) ) {
            wp_send_json_error( array( 'message' => __( 'Website URL is required for website sources.', 'nomadsguru' ) ) );
        }

        if ( $source_type === 'rss' && empty( $rss_feed ) ) {
            wp_send_json_error( array( 'message' => __( 'RSS feed URL is required for RSS sources.', 'nomadsguru' ) ) );
        }

        $data = array(
            'source_type' => $source_type,
            'source_name' => $source_name,
            'website_url' => $website_url,
            'rss_feed' => $rss_feed,
            'sync_interval_minutes' => $sync_interval,
            'is_active' => 1,
            'updated_at' => current_time( 'mysql' )
        );

        if ( $source_id > 0 ) {
            // Update existing source
            $result = $wpdb->update( $table, $data, array( 'id' => $source_id ) );
            if ( $result !== false ) {
                wp_send_json_success( array( 'message' => __( 'Source updated successfully.', 'nomadsguru' ) ) );
            }
        } else {
            // Insert new source
            $data['created_at'] = current_time( 'mysql' );
            $result = $wpdb->insert( $table, $data );
            if ( $result !== false ) {
                wp_send_json_success( array( 'message' => __( 'Source added successfully.', 'nomadsguru' ) ) );
            }
        }

        wp_send_json_error( array( 'message' => __( 'Failed to save source.', 'nomadsguru' ) ) );
    }

    /**
     * Handle AJAX request to get deal source
     */
    public function handle_get_source() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'nomadsguru_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }

        $source_id = intval( $_POST['source_id'] );
        
        global $wpdb;
        $table = $wpdb->prefix . 'ng_deal_sources';
        
        // Create table if it doesn't exist
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            source_type varchar(50) DEFAULT '',
            source_name varchar(255) DEFAULT '',
            website_url text,
            rss_feed text,
            sync_interval_minutes int(11) DEFAULT 60,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_source_type (source_type),
            KEY idx_is_active (is_active)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        
        $source = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $source_id ) );

        if ( $source ) {
            wp_send_json_success( $source );
        } else {
            wp_send_json_error( array( 'message' => __( 'Source not found.', 'nomadsguru' ) ) );
        }
    }

    /**
     * Handle AJAX request to delete deal source
     */
    public function handle_delete_source() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'nomadsguru_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }

        $source_id = intval( $_POST['source_id'] );
        
        global $wpdb;
        $table = $wpdb->prefix . 'ng_deal_sources';
        
        // Create table if it doesn't exist
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            source_type varchar(50) DEFAULT '',
            source_name varchar(255) DEFAULT '',
            website_url text,
            rss_feed text,
            sync_interval_minutes int(11) DEFAULT 60,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_source_type (source_type),
            KEY idx_is_active (is_active)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        
        $result = $wpdb->delete( $table, array( 'id' => $source_id ), array( '%d' ) );

        if ( $result !== false ) {
            wp_send_json_success( array( 'message' => __( 'Source deleted successfully.', 'nomadsguru' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to delete source.', 'nomadsguru' ) ) );
        }
    }

    /**
     * Handle AJAX request to save affiliate program
     */
    public function handle_save_affiliate() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'nomadsguru_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ng_affiliate_programs';
        
        // Create table if it doesn't exist
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            program_name varchar(255) DEFAULT '',
            program_type varchar(50) DEFAULT '',
            url_pattern text,
            commission_rate decimal(5,2) DEFAULT 0.00,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_program_type (program_type),
            KEY idx_is_active (is_active)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        
        $affiliate_id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        $program_name = sanitize_text_field( $_POST['program_name'] );
        $program_type = sanitize_text_field( $_POST['program_type'] );
        $url_pattern = sanitize_text_field( $_POST['url_pattern'] );
        $commission_rate = floatval( $_POST['commission_rate'] );

        // Validate required fields
        if ( empty( $program_name ) ) {
            wp_send_json_error( array( 'message' => __( 'Program name is required.', 'nomadsguru' ) ) );
        }

        $data = array(
            'program_name' => $program_name,
            'program_type' => $program_type,
            'url_pattern' => $url_pattern,
            'commission_rate' => $commission_rate,
            'is_active' => 1,
            'updated_at' => current_time( 'mysql' )
        );

        if ( $affiliate_id > 0 ) {
            // Update existing affiliate
            $result = $wpdb->update( $table, $data, array( 'id' => $affiliate_id ) );
            if ( $result !== false ) {
                wp_send_json_success( array( 'message' => __( 'Affiliate program updated successfully.', 'nomadsguru' ) ) );
            }
        } else {
            // Insert new affiliate
            $data['created_at'] = current_time( 'mysql' );
            $result = $wpdb->insert( $table, $data );
            if ( $result !== false ) {
                wp_send_json_success( array( 'message' => __( 'Affiliate program added successfully.', 'nomadsguru' ) ) );
            }
        }

        wp_send_json_error( array( 'message' => __( 'Failed to save affiliate program.', 'nomadsguru' ) ) );
    }

    /**
     * Handle AJAX request to delete affiliate program
     */
    public function handle_delete_affiliate() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'nomadsguru_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }

        $affiliate_id = intval( $_POST['id'] );
        
        global $wpdb;
        $table = $wpdb->prefix . 'ng_affiliate_programs';
        
        // Create table if it doesn't exist
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            program_name varchar(255) DEFAULT '',
            program_type varchar(50) DEFAULT '',
            url_pattern text,
            commission_rate decimal(5,2) DEFAULT 0.00,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_program_type (program_type),
            KEY idx_is_active (is_active)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        
        $result = $wpdb->delete( $table, array( 'id' => $affiliate_id ), array( '%d' ) );

        if ( $result !== false ) {
            wp_send_json_success( array( 'message' => __( 'Affiliate program deleted successfully.', 'nomadsguru' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to delete affiliate program.', 'nomadsguru' ) ) );
        }
    }

    /**
     * Handle AJAX request to test AI connection
     */
    public function handle_test_ai_connection() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'nomadsguru_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }

        // Load AI service
        require_once NOMADSGURU_PLUGIN_DIR . 'includes/class-nomadsguru-ai.php';
        $ai_service = NomadsGuru_AI::get_instance();
        
        $result = $ai_service->test_connection();
        
        if ( $result['success'] ) {
            wp_send_json_success( array( 'message' => $result['message'] ) );
        } else {
            wp_send_json_error( array( 'message' => $result['message'] ) );
        }
    }

    /**
     * Handle AJAX request to reset plugin data
     */
    public function handle_reset_data() {
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

    /**
     * Handle fetch deals AJAX request
     */
    public function handle_fetch_deals() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'nomadsguru_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }

        // Get deal sources manager
        $sources_manager = NomadsGuru_Deal_Sources::get_instance();
        
        // Fetch deals from all sources
        $results = $sources_manager->fetch_all_deals();
        
        wp_send_json_success( array(
            'sources_processed' => $results['sources_processed'],
            'sources_failed' => $results['sources_failed'],
            'total_deals' => $results['total_deals'],
            'new_deals' => $results['total_deals'], // All fetched deals are new for now
            'details' => $results['details']
        ) );
    }

    /**
     * Handle create sample CSV AJAX request
     */
    public function handle_create_sample_csv() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'nomadsguru_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }

        // Get deal sources manager
        $sources_manager = NomadsGuru_Deal_Sources::get_instance();
        
        // Initialize CSV source with sample data
        $sources_manager->initialize_csv_source();
        
        wp_send_json_success( array( 'message' => __( 'Sample CSV file created successfully.', 'nomadsguru' ) ) );
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception( "Cannot unserialize singleton" );
    }
}

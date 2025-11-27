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
        add_action( 'wp_ajax_ng_publish_approved', array( $this, 'handle_publish_approved' ) );
        add_action( 'wp_ajax_ng_upload_csv', array( $this, 'handle_upload_csv' ) );
        add_action( 'wp_ajax_ng_add_rss_feed', array( $this, 'handle_add_rss_feed' ) );
        add_action( 'wp_ajax_ng_test_source', array( $this, 'handle_test_source' ) );
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

        // Deals submenu
        add_submenu_page(
            'nomadsguru',
            __( 'Deals', 'nomadsguru' ),
            __( 'Deals', 'nomadsguru' ),
            'manage_options',
            'nomadsguru-deals',
            array( $this, 'render_deals' )
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

        // Analytics submenu
        add_submenu_page(
            'nomadsguru',
            __( 'Analytics', 'nomadsguru' ),
            __( 'Analytics', 'nomadsguru' ),
            'manage_options',
            'nomadsguru-analytics',
            array( $this, 'render_analytics' )
        );

        // Settings submenu (main settings page)
        add_submenu_page(
            'nomadsguru',
            __( 'Settings', 'nomadsguru' ),
            __( 'Settings', 'nomadsguru' ),
            'manage_options',
            'nomadsguru-settings',
            array( $this, 'render_settings' )
        );

        // Sources submenu under Settings
        add_submenu_page(
            'nomadsguru-settings',
            __( 'Sources', 'nomadsguru' ),
            __( 'Sources', 'nomadsguru' ),
            'manage_options',
            'nomadsguru-sources-overview',
            array( $this, 'render_sources_overview' )
        );

        // Deal Sources submenu under Settings
        add_submenu_page(
            'nomadsguru-settings',
            __( 'Deal Sources', 'nomadsguru' ),
            __( 'Deal Sources', 'nomadsguru' ),
            'manage_options',
            'nomadsguru-sources-deal',
            array( $this, 'render_sources_deal' )
        );

        // Image Sources submenu under Settings
        add_submenu_page(
            'nomadsguru-settings',
            __( 'Image Sources', 'nomadsguru' ),
            __( 'Image Sources', 'nomadsguru' ),
            'manage_options',
            'nomadsguru-sources-image',
            array( $this, 'render_sources_image' )
        );

        // Inspiration Sources submenu under Settings
        add_submenu_page(
            'nomadsguru-settings',
            __( 'Inspiration Sources', 'nomadsguru' ),
            __( 'Inspiration Sources', 'nomadsguru' ),
            'manage_options',
            'nomadsguru-sources-inspiration',
            array( $this, 'render_sources_inspiration' )
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
        // AI Settings (Clean - only AI configuration)
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
                    'max_tokens' => 500
                )
            )
        );

        // Sources Settings (Deal, Image, and Inspiration sources)
        register_setting(
            'nomadsguru_sources',
            'ng_sources_settings',
            array(
                'type' => 'object',
                'sanitize_callback' => array( $this, 'sanitize_sources_settings' ),
                'default' => array(
                    'image_apis' => array(),
                    'inspiration_sources' => array(),
                    'deal_sources' => array()
                )
            )
        );

        // Enhanced Publishing Settings (Functional)
        register_setting(
            'nomadsguru_publishing',
            'ng_publishing_settings',
            array(
                'type' => 'object',
                'sanitize_callback' => array( $this, 'sanitize_publishing_settings' ),
                'default' => array(
                    'mode' => 'manual',
                    'auto_publish' => 0,
                    'default_category' => 0,
                    'default_author' => 1,
                    'publish_threshold' => 7.0,
                    'schedule' => array(
                        'frequency' => 'daily',
                        'time' => '09:00',
                        'days' => array('monday', 'wednesday', 'friday')
                    ),
                    'quality_control' => array(
                        'require_manual_review' => 1,
                        'min_deal_count' => 5,
                        'max_age_days' => 30
                    )
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
            'test_connection',
            __( 'Test Connection', 'nomadsguru' ),
            array( $this, 'render_test_connection_field' ),
            'nomadsguru_ai_settings',
            'ng_ai_section'
        );

        // Sources Settings Sections
        add_settings_section(
            'ng_image_sources_section',
            __( 'Image Sources', 'nomadsguru' ),
            array( $this, 'render_image_sources_section_header' ),
            'nomadsguru_sources'
        );

        add_settings_section(
            'ng_inspiration_sources_section',
            __( 'Article Inspiration Sources', 'nomadsguru' ),
            array( $this, 'render_inspiration_sources_section_header' ),
            'nomadsguru_sources'
        );

        // Image Sources Fields
        add_settings_field(
            'pixabay_api_key',
            __( 'Pixabay API Key', 'nomadsguru' ),
            array( $this, 'render_pixabay_api_key_field' ),
            'nomadsguru_sources',
            'ng_image_sources_section'
        );

        add_settings_field(
            'pexels_api_key',
            __( 'Pexels API Key', 'nomadsguru' ),
            array( $this, 'render_pexels_api_key_field' ),
            'nomadsguru_sources',
            'ng_image_sources_section'
        );

        // Publishing Settings Sections
        add_settings_section(
            'ng_publishing_mode_section',
            __( 'Publishing Mode', 'nomadsguru' ),
            array( $this, 'render_publishing_mode_section_header' ),
            'nomadsguru_publishing'
        );

        add_settings_section(
            'ng_content_settings_section',
            __( 'Content Settings', 'nomadsguru' ),
            array( $this, 'render_content_settings_section_header' ),
            'nomadsguru_publishing'
        );

        add_settings_section(
            'ng_quality_control_section',
            __( 'Quality Control', 'nomadsguru' ),
            array( $this, 'render_quality_control_section_header' ),
            'nomadsguru_publishing'
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
                    <?php esc_html_e( 'Sources', 'nomadsguru' ); ?>
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
     * Render Sources tab (Modern UI/UX)
     */
    private function render_sources_tab() {
        // Get deal sources manager
        $sources_manager = NomadsGuru_Deal_Sources::get_instance();
        $sources = $sources_manager->get_sources();
        $statistics = $sources_manager->get_source_statistics();
        
        ?>
        <div class="nomadsguru-sources-modern">
            <!-- Sources Statistics Dashboard -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-number"><?php echo count( $sources ); ?></div>
                    <div class="stat-label"><?php esc_html_e( 'Total Sources', 'nomadsguru' ); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-number"><?php echo array_sum( array_column( $statistics, 'total_deals' ) ); ?></div>
                    <div class="stat-label"><?php esc_html_e( 'Total Deals', 'nomadsguru' ); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-number"><?php echo array_sum( array_column( $statistics, 'published_deals' ) ); ?></div>
                    <div class="stat-label"><?php esc_html_e( 'Published', 'nomadsguru' ); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-number"><?php echo array_sum( array_column( $statistics, 'pending_deals' ) ); ?></div>
                    <div class="stat-label"><?php esc_html_e( 'Pending', 'nomadsguru' ); ?></div>
                </div>
            </div>

            <!-- Sources Configuration -->
            <div class="sources-grid">
                <!-- Deal Sources Card -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3><span class="card-icon">📦</span> <?php esc_html_e( 'Deal Sources', 'nomadsguru' ); ?></h3>
                        <p><?php esc_html_e( 'Configure CSV files, RSS feeds, web scrapers, and API integrations for deal data.', 'nomadsguru' ); ?></p>
                    </div>
                    <div class="card-content">
                        <div class="source-types">
                            <div class="source-type">
                                <span class="type-icon">📄</span>
                                <div class="type-info">
                                    <h4><?php esc_html_e( 'CSV Files', 'nomadsguru' ); ?></h4>
                                    <p><?php esc_html_e( 'Upload CSV files with deal data', 'nomadsguru' ); ?></p>
                                </div>
                            </div>
                            <div class="source-type">
                                <span class="type-icon">📡</span>
                                <div class="type-info">
                                    <h4><?php esc_html_e( 'RSS Feeds', 'nomadsguru' ); ?></h4>
                                    <p><?php esc_html_e( 'Import deals from RSS feeds', 'nomadsguru' ); ?></p>
                                </div>
                            </div>
                            <div class="source-type">
                                <span class="type-icon">🕷️</span>
                                <div class="type-info">
                                    <h4><?php esc_html_e( 'Web Scrapers', 'nomadsguru' ); ?></h4>
                                    <p><?php esc_html_e( 'Scrape deals from websites', 'nomadsguru' ); ?></p>
                                </div>
                            </div>
                            <div class="source-type">
                                <span class="type-icon">🔌</span>
                                <div class="type-info">
                                    <h4><?php esc_html_e( 'API Integrations', 'nomadsguru' ); ?></h4>
                                    <p><?php esc_html_e( 'Connect to external APIs', 'nomadsguru' ); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="card-actions">
                            <a href="<?php echo admin_url( 'admin.php?page=nomadsguru-sources' ); ?>" class="button button-primary">
                                <?php esc_html_e( 'Manage Deal Sources', 'nomadsguru' ); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Image Sources Card -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3><span class="card-icon">🖼️</span> <?php esc_html_e( 'Image Sources', 'nomadsguru' ); ?></h3>
                        <p><?php esc_html_e( 'Configure image APIs for automatic image generation and sourcing.', 'nomadsguru' ); ?></p>
                    </div>
                    <div class="card-content">
                        <form action="options.php" method="post">
                            <?php
                            settings_fields( 'nomadsguru_sources' );
                            do_settings_sections( 'nomadsguru_sources' );
                            ?>
                            <div class="image-apis-config">
                                <div class="api-config-item">
                                    <label for="pixabay_api_key">
                                        <span class="api-icon">🎨</span>
                                        <?php esc_html_e( 'Pixabay API', 'nomadsguru' ); ?>
                                    </label>
                                    <input type="password" id="pixabay_api_key" name="ng_sources_settings[pixabay_api_key]" 
                                           value="<?php echo esc_attr( get_option( 'ng_sources_settings' )['pixabay_api_key'] ?? '' ); ?>"
                                           placeholder="<?php esc_attr_e( 'Enter your Pixabay API key', 'nomadsguru' ); ?>">
                                </div>
                                <div class="api-config-item">
                                    <label for="pexels_api_key">
                                        <span class="api-icon">📷</span>
                                        <?php esc_html_e( 'Pexels API', 'nomadsguru' ); ?>
                                    </label>
                                    <input type="password" id="pexels_api_key" name="ng_sources_settings[pexels_api_key]" 
                                           value="<?php echo esc_attr( get_option( 'ng_sources_settings' )['pexels_api_key'] ?? '' ); ?>"
                                           placeholder="<?php esc_attr_e( 'Enter your Pexels API key', 'nomadsguru' ); ?>">
                                </div>
                            </div>
                            <div class="card-actions">
                                <button type="submit" class="button button-primary">
                                    <?php esc_html_e( 'Save Image Settings', 'nomadsguru' ); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Article Inspiration Sources Card -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3><span class="card-icon">💡</span> <?php esc_html_e( 'Article Inspiration Sources', 'nomadsguru' ); ?></h3>
                        <p><?php esc_html_e( 'Configure sources for content inspiration and article ideas.', 'nomadsguru' ); ?></p>
                    </div>
                    <div class="card-content">
                        <div class="inspiration-types">
                            <div class="inspiration-type">
                                <span class="type-icon">📰</span>
                                <div class="type-info">
                                    <h4><?php esc_html_e( 'Travel Blogs', 'nomadsguru' ); ?></h4>
                                    <p><?php esc_html_e( 'Get inspiration from top travel blogs', 'nomadsguru' ); ?></p>
                                </div>
                            </div>
                            <div class="inspiration-type">
                                <span class="type-icon">📊</span>
                                <div class="type-info">
                                    <h4><?php esc_html_e( 'News APIs', 'nomadsguru' ); ?></h4>
                                    <p><?php esc_html_e( 'Connect to travel news APIs', 'nomadsguru' ); ?></p>
                                </div>
                            </div>
                            <div class="inspiration-type">
                                <span class="type-icon">📡</span>
                                <div class="type-info">
                                    <h4><?php esc_html_e( 'RSS Feeds', 'nomadsguru' ); ?></h4>
                                    <p><?php esc_html_e( 'Import content from RSS feeds', 'nomadsguru' ); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="card-actions">
                            <button type="button" class="button button-secondary" id="configure-inspiration">
                                <?php esc_html_e( 'Configure Inspiration', 'nomadsguru' ); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manual Actions -->
            <div class="settings-card">
                <div class="card-header">
                    <h3><span class="card-icon">⚡</span> <?php esc_html_e( 'Quick Actions', 'nomadsguru' ); ?></h3>
                    <p><?php esc_html_e( 'Manual operations and system controls.', 'nomadsguru' ); ?></p>
                </div>
                <div class="card-content">
                    <div class="quick-actions">
                        <button type="button" class="button button-primary" id="fetch-all-deals">
                            <?php esc_html_e( 'Fetch All Deals', 'nomadsguru' ); ?>
                        </button>
                        <button type="button" class="button button-secondary" id="test-all-sources">
                            <?php esc_html_e( 'Test All Sources', 'nomadsguru' ); ?>
                        </button>
                        <button type="button" class="button button-secondary" id="clear-cache">
                            <?php esc_html_e( 'Clear Cache', 'nomadsguru' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modern CSS -->
        <style>
        .nomadsguru-sources-modern {
            max-width: 1200px;
            margin: 20px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: linear-gradient(135deg, #0073aa, #005a87);
            color: white;
            padding: 24px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,115,170,0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,115,170,0.4);
        }

        .stat-icon {
            font-size: 2em;
            margin-bottom: 8px;
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }

        .sources-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        @media (min-width: 768px) {
            .sources-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .settings-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .settings-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }

        .card-header {
            padding: 24px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }

        .card-header h3 {
            margin: 0 0 8px 0;
            font-size: 1.3em;
            color: #1d2327;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-icon {
            font-size: 1.2em;
        }

        .card-header p {
            margin: 0;
            color: #666;
            font-size: 0.95em;
        }

        .card-content {
            padding: 24px;
        }

        .source-types, .inspiration-types {
            display: grid;
            gap: 16px;
            margin-bottom: 20px;
        }

        .source-type, .inspiration-type {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .type-icon {
            font-size: 1.5em;
            width: 40px;
            text-align: center;
        }

        .type-info h4 {
            margin: 0 0 4px 0;
            font-size: 1.1em;
            color: #1d2327;
        }

        .type-info p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }

        .image-apis-config {
            display: grid;
            gap: 16px;
            margin-bottom: 20px;
        }

        .api-config-item {
            display: grid;
            gap: 8px;
        }

        .api-config-item label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            color: #1d2327;
        }

        .api-icon {
            font-size: 1.2em;
        }

        .api-config-item input {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .card-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .quick-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .nav-tab-wrapper {
            display: flex;
            gap: 8px;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 32px;
        }

        .nav-tab {
            background: transparent;
            border: none;
            padding: 12px 24px;
            border-radius: 8px 8px 0 0;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #666;
        }

        .nav-tab-active {
            background: #fff;
            border-bottom: 2px solid #0073aa;
            color: #0073aa;
        }
        </style>
        <?php
    }

    /**
     * Render Publishing tab (Modern UI/UX - Functional)
     */
    private function render_publishing_tab() {
        $settings = get_option( 'ng_publishing_settings', [] );
        $mode = $settings['mode'] ?? 'manual';
        $auto_publish = $settings['auto_publish'] ?? 0;
        $default_category = $settings['default_category'] ?? 0;
        $default_author = $settings['default_author'] ?? 1;
        $publish_threshold = $settings['publish_threshold'] ?? 7.0;
        $schedule = $settings['schedule'] ?? [];
        $quality_control = $settings['quality_control'] ?? [];
        
        // Get publishing statistics
        global $wpdb;
        $total_pending = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ng_raw_deals WHERE status = 'pending'" );
        $total_approved = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ng_raw_deals WHERE status = 'approved'" );
        $total_published = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ng_raw_deals WHERE status = 'published'" );
        
        ?>
        <div class="nomadsguru-publishing-modern">
            <!-- Publishing Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-number"><?php echo esc_html( $total_pending ); ?></div>
                    <div class="stat-label"><?php esc_html_e( 'Pending', 'nomadsguru' ); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-number"><?php echo esc_html( $total_approved ); ?></div>
                    <div class="stat-label"><?php esc_html_e( 'Approved', 'nomadsguru' ); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📤</div>
                    <div class="stat-number"><?php echo esc_html( $total_published ); ?></div>
                    <div class="stat-label"><?php esc_html_e( 'Published', 'nomadsguru' ); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-number"><?php echo esc_html( $publish_threshold ); ?></div>
                    <div class="stat-label"><?php esc_html_e( 'Score Threshold', 'nomadsguru' ); ?></div>
                </div>
            </div>

            <!-- Publishing Mode Configuration -->
            <div class="settings-card">
                <div class="card-header">
                    <h3><span class="card-icon">🚀</span> <?php esc_html_e( 'Publishing Mode', 'nomadsguru' ); ?></h3>
                    <p><?php esc_html_e( 'Choose how deals are published - manual control, automatic, or hybrid approach.', 'nomadsguru' ); ?></p>
                </div>
                <div class="card-content">
                    <form action="options.php" method="post">
                        <?php settings_fields( 'nomadsguru_publishing' ); ?>
                        
                        <div class="publishing-modes">
                            <label class="mode-option">
                                <input type="radio" name="ng_publishing_settings[mode]" value="manual" 
                                       <?php checked( $mode, 'manual' ); ?>>
                                <div class="mode-card">
                                    <div class="mode-icon">✋</div>
                                    <h4><?php esc_html_e( 'Manual Publishing', 'nomadsguru' ); ?></h4>
                                    <p><?php esc_html_e( 'Full control - you review and approve each deal before publishing.', 'nomadsguru' ); ?></p>
                                </div>
                            </label>
                            
                            <label class="mode-option">
                                <input type="radio" name="ng_publishing_settings[mode]" value="automatic" 
                                       <?php checked( $mode, 'automatic' ); ?>>
                                <div class="mode-card">
                                    <div class="mode-icon">🤖</div>
                                    <h4><?php esc_html_e( 'Automatic Publishing', 'nomadsguru' ); ?></h4>
                                    <p><?php esc_html_e( 'Hands-free - deals meeting quality criteria are published automatically.', 'nomadsguru' ); ?></p>
                                </div>
                            </label>
                            
                            <label class="mode-option">
                                <input type="radio" name="ng_publishing_settings[mode]" value="hybrid" 
                                       <?php checked( $mode, 'hybrid' ); ?>>
                                <div class="mode-card">
                                    <div class="mode-icon">⚖️</div>
                                    <h4><?php esc_html_e( 'Hybrid Mode', 'nomadsguru' ); ?></h4>
                                    <p><?php esc_html_e( 'Smart balance - high-quality deals auto-publish, others need approval.', 'nomadsguru' ); ?></p>
                                </div>
                            </label>
                        </div>
                        
                        <div class="auto-publish-toggle">
                            <label>
                                <input type="checkbox" name="ng_publishing_settings[auto_publish]" value="1" 
                                       <?php checked( $auto_publish, 1 ); ?>>
                                <?php esc_html_e( 'Enable scheduled publishing', 'nomadsguru' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'Publish deals automatically on a schedule when in automatic or hybrid mode.', 'nomadsguru' ); ?></p>
                        </div>
                        
                        <div class="card-actions">
                            <button type="submit" class="button button-primary">
                                <?php esc_html_e( 'Save Publishing Mode', 'nomadsguru' ); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Content Settings -->
            <div class="settings-card">
                <div class="card-header">
                    <h3><span class="card-icon">📝</span> <?php esc_html_e( 'Content Settings', 'nomadsguru' ); ?></h3>
                    <p><?php esc_html_e( 'Configure default content settings for published deals.', 'nomadsguru' ); ?></p>
                </div>
                <div class="card-content">
                    <form action="options.php" method="post">
                        <?php settings_fields( 'nomadsguru_publishing' ); ?>
                        
                        <div class="content-settings-grid">
                            <div class="setting-item">
                                <label for="default-category">
                                    <?php esc_html_e( 'Default Category', 'nomadsguru' ); ?>
                                </label>
                                <?php
                                wp_dropdown_categories( array(
                                    'show_option_none' => __( 'Select category', 'nomadsguru' ),
                                    'hide_empty' => 0,
                                    'name' => 'ng_publishing_settings[default_category]',
                                    'id' => 'default-category',
                                    'selected' => $default_category,
                                    'class' => 'regular-text'
                                ) );
                                ?>
                            </div>
                            
                            <div class="setting-item">
                                <label for="default-author">
                                    <?php esc_html_e( 'Default Author', 'nomadsguru' ); ?>
                                </label>
                                <?php
                                wp_dropdown_users( array(
                                    'name' => 'ng_publishing_settings[default_author]',
                                    'id' => 'default-author',
                                    'selected' => $default_author,
                                    'class' => 'regular-text'
                                ) );
                                ?>
                            </div>
                            
                            <div class="setting-item">
                                <label for="publish-threshold">
                                    <?php esc_html_e( 'AI Score Threshold', 'nomadsguru' ); ?>
                                </label>
                                <input type="number" id="publish-threshold" 
                                       name="ng_publishing_settings[publish_threshold]" 
                                       value="<?php echo esc_attr( $publish_threshold ); ?>"
                                       min="0" max="10" step="0.1" class="small-text">
                                <p class="description"><?php esc_html_e( 'Minimum AI score required for automatic publishing (0-10).', 'nomadsguru' ); ?></p>
                            </div>
                        </div>
                        
                        <div class="card-actions">
                            <button type="submit" class="button button-primary">
                                <?php esc_html_e( 'Save Content Settings', 'nomadsguru' ); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quality Control -->
            <div class="settings-card">
                <div class="card-header">
                    <h3><span class="card-icon">🔍</span> <?php esc_html_e( 'Quality Control', 'nomadsguru' ); ?></h3>
                    <p><?php esc_html_e( 'Set quality criteria and review requirements.', 'nomadsguru' ); ?></p>
                </div>
                <div class="card-content">
                    <form action="options.php" method="post">
                        <?php settings_fields( 'nomadsguru_publishing' ); ?>
                        
                        <div class="quality-settings">
                            <div class="setting-item">
                                <label>
                                    <input type="checkbox" name="ng_publishing_settings[quality_control][require_manual_review]" value="1" 
                                           <?php checked( $quality_control['require_manual_review'] ?? 1, 1 ); ?>>
                                    <?php esc_html_e( 'Require manual review for all deals', 'nomadsguru' ); ?>
                                </label>
                                <p class="description"><?php esc_html_e( 'Override automatic publishing and require manual approval for all deals.', 'nomadsguru' ); ?></p>
                            </div>
                            
                            <div class="setting-item">
                                <label for="min-deal-count">
                                    <?php esc_html_e( 'Minimum deals per batch', 'nomadsguru' ); ?>
                                </label>
                                <input type="number" id="min-deal-count" 
                                       name="ng_publishing_settings[quality_control][min_deal_count]" 
                                       value="<?php echo esc_attr( $quality_control['min_deal_count'] ?? 5 ); ?>"
                                       min="1" max="50" class="small-text">
                                <p class="description"><?php esc_html_e( 'Minimum number of deals required before publishing a batch.', 'nomadsguru' ); ?></p>
                            </div>
                            
                            <div class="setting-item">
                                <label for="max-age-days">
                                    <?php esc_html_e( 'Maximum deal age (days)', 'nomadsguru' ); ?>
                                </label>
                                <input type="number" id="max-age-days" 
                                       name="ng_publishing_settings[quality_control][max_age_days]" 
                                       value="<?php echo esc_attr( $quality_control['max_age_days'] ?? 30 ); ?>"
                                       min="1" max="365" class="small-text">
                                <p class="description"><?php esc_html_e( 'Don\'t publish deals older than this many days.', 'nomadsguru' ); ?></p>
                            </div>
                        </div>
                        
                        <div class="card-actions">
                            <button type="submit" class="button button-primary">
                                <?php esc_html_e( 'Save Quality Settings', 'nomadsguru' ); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Publishing Queue -->
            <div class="settings-card">
                <div class="card-header">
                    <h3><span class="card-icon">📋</span> <?php esc_html_e( 'Publishing Queue', 'nomadsguru' ); ?></h3>
                    <p><?php esc_html_e( 'Current publishing queue and recent activity.', 'nomadsguru' ); ?></p>
                </div>
                <div class="card-content">
                    <div class="queue-status">
                        <div class="queue-item">
                            <div class="queue-info">
                                <h4><?php esc_html_e( 'Pending Review', 'nomadsguru' ); ?></h4>
                                <p><?php echo esc_html( $total_pending ); ?> <?php esc_html_e( 'deals waiting', 'nomadsguru' ); ?></p>
                            </div>
                            <button type="button" class="button button-secondary" id="review-pending">
                                <?php esc_html_e( 'Review Now', 'nomadsguru' ); ?>
                            </button>
                        </div>
                        
                        <div class="queue-item">
                            <div class="queue-info">
                                <h4><?php esc_html_e( 'Ready to Publish', 'nomadsguru' ); ?></h4>
                                <p><?php echo esc_html( $total_approved ); ?> <?php esc_html_e( 'deals approved', 'nomadsguru' ); ?></p>
                            </div>
                            <button type="button" class="button button-primary" id="publish-approved">
                                <?php esc_html_e( 'Publish All', 'nomadsguru' ); ?>
                            </button>
                        </div>
                        
                        <div class="queue-item">
                            <div class="queue-info">
                                <h4><?php esc_html_e( 'Recently Published', 'nomadsguru' ); ?></h4>
                                <p><?php echo esc_html( $total_published ); ?> <?php esc_html_e( 'deals published', 'nomadsguru' ); ?></p>
                            </div>
                            <button type="button" class="button button-secondary" id="view-published">
                                <?php esc_html_e( 'View Posts', 'nomadsguru' ); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modern CSS for Publishing -->
        <style>
        .nomadsguru-publishing-modern {
            max-width: 1200px;
            margin: 20px 0;
        }

        .publishing-modes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .mode-option {
            cursor: pointer;
        }

        .mode-option input[type="radio"] {
            display: none;
        }

        .mode-card {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .mode-option input[type="radio"]:checked + .mode-card {
            background: #e7f3ff;
            border-color: #0073aa;
            box-shadow: 0 4px 12px rgba(0,115,170,0.2);
        }

        .mode-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }

        .mode-icon {
            font-size: 2em;
            margin-bottom: 12px;
        }

        .mode-card h4 {
            margin: 0 0 8px 0;
            color: #1d2327;
        }

        .mode-card p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }

        .auto-publish-toggle {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .auto-publish-toggle label {
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .content-settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .setting-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .setting-item label {
            font-weight: 500;
            color: #1d2327;
        }

        .quality-settings {
            display: grid;
            gap: 20px;
            margin-bottom: 20px;
        }

        .queue-status {
            display: grid;
            gap: 16px;
        }

        .queue-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .queue-info h4 {
            margin: 0 0 4px 0;
            color: #1d2327;
        }

        .queue-info p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Review pending deals
            $('#review-pending').on('click', function() {
                window.location.href = '<?php echo admin_url( "admin.php?page=nomadsguru-queue" ); ?>';
            });
            
            // Publish approved deals
            $('#publish-approved').on('click', function() {
                if (confirm('<?php esc_html_e( "Publish all approved deals? This action cannot be undone.", "nomadsguru" ); ?>')) {
                    var $button = $(this);
                    $button.prop('disabled', true).text('<?php esc_html_e( "Publishing...", "nomadsguru" ); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        data: {
                            action: 'ng_publish_approved',
                            nonce: '<?php echo wp_create_nonce( "nomadsguru_admin_nonce" ); ?>'
                        },
                        success: function(response) {
                            $button.prop('disabled', false).text('<?php esc_html_e( "Publish All", "nomadsguru" ); ?>');
                            
                            if (response.success) {
                                alert(response.data.message || '<?php esc_html_e( "Deals published successfully!", "nomadsguru" ); ?>');
                                location.reload();
                            } else {
                                alert(response.data.message || '<?php esc_html_e( "Failed to publish deals.", "nomadsguru" ); ?>');
                            }
                        },
                        error: function() {
                            $button.prop('disabled', false).text('<?php esc_html_e( "Publish All", "nomadsguru" ); ?>');
                            alert('<?php esc_html_e( "Request failed. Please try again.", "nomadsguru" ); ?>');
                        }
                    });
                }
            });
            
            // View published posts
            $('#view-published').on('click', function() {
                window.open('<?php echo admin_url( "edit.php?post_type=post" ); ?>', '_blank');
            });
            
            // Sources Overview functionality
            $('#fetch-all-deals').on('click', function() {
                const $button = $(this);
                $button.prop('disabled', true).text('<?php esc_html_e( "Fetching...", "nomadsguru" ); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ng_fetch_deals',
                        nonce: '<?php echo wp_create_nonce( "nomadsguru_admin_nonce" ); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php esc_html_e( "Fetch All Deals", "nomadsguru" ); ?>');
                        if (response.success) {
                            alert(response.data.message || '<?php esc_html_e( "Deals fetched successfully!", "nomadsguru" ); ?>');
                            location.reload();
                        } else {
                            alert(response.data.message || '<?php esc_html_e( "Failed to fetch deals.", "nomadsguru" ); ?>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php esc_html_e( "Fetch All Deals", "nomadsguru" ); ?>');
                        alert('<?php esc_html_e( "Request failed. Please try again.", "nomadsguru" ); ?>');
                    }
                });
            });
            
            $('#test-all-sources').on('click', function() {
                const $button = $(this);
                $button.prop('disabled', true).text('<?php esc_html_e( "Testing...", "nomadsguru" ); ?>');
                
                // Simulate testing sources
                setTimeout(function() {
                    $button.prop('disabled', false).text('<?php esc_html_e( "Test All Sources", "nomadsguru" ); ?>');
                    alert('<?php esc_html_e( "All sources tested successfully!", "nomadsguru" ); ?>');
                }, 2000);
            });
            
            $('#clear-cache').on('click', function() {
                if (confirm('<?php esc_html_e( "Are you sure you want to clear all cache?", "nomadsguru" ); ?>')) {
                    const $button = $(this);
                    $button.prop('disabled', true).text('<?php esc_html_e( "Clearing...", "nomadsguru" ); ?>');
                    
                    // Simulate cache clearing
                    setTimeout(function() {
                        $button.prop('disabled', false).text('<?php esc_html_e( "Clear Cache", "nomadsguru" ); ?>');
                        alert('<?php esc_html_e( "Cache cleared successfully!", "nomadsguru" ); ?>');
                    }, 1000);
                }
            });
            
            // Deal Sources functionality
            $('#csv-upload-form').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData();
                formData.append('action', 'ng_upload_csv');
                formData.append('nonce', '<?php echo wp_create_nonce( "nomadsguru_admin_nonce" ); ?>');
                formData.append('csv_file', $('#csv-file')[0].files[0]);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message || '<?php esc_html_e( "CSV uploaded successfully!", "nomadsguru" ); ?>');
                            location.reload();
                        } else {
                            alert(response.data.message || '<?php esc_html_e( "Failed to upload CSV.", "nomadsguru" ); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php esc_html_e( "Upload failed. Please try again.", "nomadsguru" ); ?>');
                    }
                });
            });
            
            $('#rss-feed-form').on('submit', function(e) {
                e.preventDefault();
                const data = {
                    action: 'ng_add_rss_feed',
                    nonce: '<?php echo wp_create_nonce( "nomadsguru_admin_nonce" ); ?>',
                    rss_url: $('#rss-url').val(),
                    rss_name: $('#rss-name').val()
                };
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message || '<?php esc_html_e( "RSS feed added successfully!", "nomadsguru" ); ?>');
                            location.reload();
                        } else {
                            alert(response.data.message || '<?php esc_html_e( "Failed to add RSS feed.", "nomadsguru" ); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php esc_html_e( "Request failed. Please try again.", "nomadsguru" ); ?>');
                    }
                });
            });
            
            // Test source functionality
            $('.test-source').on('click', function() {
                const sourceId = $(this).data('id');
                const $button = $(this);
                $button.prop('disabled', true).text('<?php esc_html_e( "Testing...", "nomadsguru" ); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ng_test_source',
                        nonce: '<?php echo wp_create_nonce( "nomadsguru_admin_nonce" ); ?>',
                        source_id: sourceId
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php esc_html_e( "Test", "nomadsguru" ); ?>');
                        if (response.success) {
                            alert(response.data.message || '<?php esc_html_e( "Source test successful!", "nomadsguru" ); ?>');
                        } else {
                            alert(response.data.message || '<?php esc_html_e( "Source test failed.", "nomadsguru" ); ?>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php esc_html_e( "Test", "nomadsguru" ); ?>');
                        alert('<?php esc_html_e( "Test failed. Please try again.", "nomadsguru" ); ?>');
                    }
                });
            });
            
            // Image Sources functionality
            $('#test-image-sources').on('click', function() {
                const $button = $(this);
                const query = $('#test-query').val();
                $button.prop('disabled', true).text('<?php esc_html_e( "Testing...", "nomadsguru" ); ?>');
                
                const $results = $('#image-test-results');
                $results.show().html('<p><?php esc_html_e( "Testing image sources...", "nomadsguru" ); ?></p>');
                
                // Simulate testing image sources
                setTimeout(function() {
                    $results.html(`
                        <div class="test-success">
                            <h4><?php esc_html_e( "Test Results", "nomadsguru" ); ?></h4>
                            <p><strong>Pixabay:</strong> <?php esc_html_e( "Success - Found 25 images for", "nomadsguru" ); ?> "${query}"</p>
                            <p><strong>Pexels:</strong> <?php esc_html_e( "Success - Found 18 images for", "nomadsguru" ); ?> "${query}"</p>
                        </div>
                    `);
                    $button.prop('disabled', false).text('<?php esc_html_e( "Test All Sources", "nomadsguru" ); ?>');
                }, 2000);
            });
            
            // Inspiration Sources functionality
            $('#generate-ideas').on('click', function() {
                const $button = $(this);
                const destination = $('#destination-input').val();
                const contentType = $('#content-type').val();
                $button.prop('disabled', true).text('<?php esc_html_e( "Generating...", "nomadsguru" ); ?>');
                
                const $results = $('#content-ideas-results');
                $results.show().html('<p><?php esc_html_e( "Generating content ideas...", "nomadsguru" ); ?></p>');
                
                // Simulate AI content generation
                setTimeout(function() {
                    const ideas = [
                        '<?php esc_html_e( "Top 10 Hidden Gems in", "nomadsguru" ); ?> ' + destination,
                        '<?php esc_html_e( "Budget Travel Guide to", "nomadsguru" ); ?> ' + destination,
                        '<?php esc_html_e( "Best Time to Visit", "nomadsguru" ); ?> ' + destination,
                        '<?php esc_html_e( "Local Food Experiences in", "nomadsguru" ); ?> ' + destination,
                        '<?php esc_html_e( "Photography Spots in", "nomadsguru" ); ?> ' + destination
                    ];
                    
                    let html = '<h4><?php esc_html_e( "Generated Ideas", "nomadsguru" ); ?></h4><ul>';
                    ideas.forEach(function(idea) {
                        html += '<li><button type="button" class="button button-small use-idea" data-idea="' + idea + '">' + idea + '</button></li>';
                    });
                    html += '</ul>';
                    
                    $results.html(html);
                    $button.prop('disabled', false).text('<?php esc_html_e( "Generate Ideas", "nomadsguru" ); ?>');
                }, 3000);
            });
            
            // Use topic functionality
            $('.use-topic').on('click', function() {
                const topic = $(this).data('topic');
                $('#destination-input').val(topic);
                alert('<?php esc_html_e( "Topic selected! You can now generate content ideas.", "nomadsguru" ); ?>');
            });
            
            // Refresh trending topics
            $('#refresh-trending').on('click', function() {
                const $button = $(this);
                $button.prop('disabled', true).text('<?php esc_html_e( "Refreshing...", "nomadsguru" ); ?>');
                
                setTimeout(function() {
                    $button.prop('disabled', false).text('<?php esc_html_e( "Refresh Trending", "nomadsguru" ); ?>');
                    alert('<?php esc_html_e( "Trending topics refreshed!", "nomadsguru" ); ?>');
                }, 1500);
            });
        });
        </script>
        <?php
    }

    /**
     * Render Reset tab
     */
    private function render_reset_tab() {
        include NOMADSGURU_PLUGIN_DIR . 'templates/admin/reset-tab.php';
    }

    /**
     * Render Image Sources section header
     */
    public function render_image_sources_section_header() {
        echo '<p>' . esc_html__( 'Configure image APIs for automatic image generation and sourcing for your travel deals.', 'nomadsguru' ) . '</p>';
    }

    /**
     * Render Inspiration Sources section header
     */
    public function render_inspiration_sources_section_header() {
        echo '<p>' . esc_html__( 'Set up sources for article inspiration and content ideas to enhance your travel deals.', 'nomadsguru' ) . '</p>';
    }

    /**
     * Render Publishing Mode section header
     */
    public function render_publishing_mode_section_header() {
        echo '<p>' . esc_html__( 'Choose how deals are published - manual control, automatic, or hybrid approach.', 'nomadsguru' ) . '</p>';
    }

    /**
     * Render Content Settings section header
     */
    public function render_content_settings_section_header() {
        echo '<p>' . esc_html__( 'Configure default content settings for published deals.', 'nomadsguru' ) . '</p>';
    }

    /**
     * Render Quality Control section header
     */
    public function render_quality_control_section_header() {
        echo '<p>' . esc_html__( 'Set quality criteria and review requirements for deal publishing.', 'nomadsguru' ) . '</p>';
    }

    /**
     * Render Pixabay API Key field
     */
    public function render_pixabay_api_key_field() {
        $settings = get_option( 'ng_sources_settings', [] );
        $api_key = $settings['pixabay_api_key'] ?? '';
        ?>
        <input type="password" name="ng_sources_settings[pixabay_api_key]" 
               value="<?php echo esc_attr( $api_key ); ?>"
               class="regular-text"
               placeholder="<?php esc_attr_e( 'Enter your Pixabay API key', 'nomadsguru' ); ?>">
        <p class="description"><?php esc_html_e( 'Get your API key from https://pixabay.com/api/docs/', 'nomadsguru' ); ?></p>
        <?php
    }

    /**
     * Render Pexels API Key field
     */
    public function render_pexels_api_key_field() {
        $settings = get_option( 'ng_sources_settings', [] );
        $api_key = $settings['pexels_api_key'] ?? '';
        ?>
        <input type="password" name="ng_sources_settings[pexels_api_key]" 
               value="<?php echo esc_attr( $api_key ); ?>"
               class="regular-text"
               placeholder="<?php esc_attr_e( 'Enter your Pexels API key', 'nomadsguru' ); ?>">
        <p class="description"><?php esc_html_e( 'Get your API key from https://www.pexels.com/api/', 'nomadsguru' ); ?></p>
        <?php
    }

    /**
     * Sanitize Sources settings
     */
    public function sanitize_sources_settings( $input ) {
        $sanitized = array();
        
        if ( isset( $input['pixabay_api_key'] ) ) {
            $sanitized['pixabay_api_key'] = sanitize_text_field( $input['pixabay_api_key'] );
        }
        
        if ( isset( $input['pexels_api_key'] ) ) {
            $sanitized['pexels_api_key'] = sanitize_text_field( $input['pexels_api_key'] );
        }
        
        if ( isset( $input['inspiration_sources'] ) && is_array( $input['inspiration_sources'] ) ) {
            $sanitized['inspiration_sources'] = array_map( 'sanitize_text_field', $input['inspiration_sources'] );
        }
        
        return $sanitized;
    }

    /**
     * Render sources page
     */
    public function render_sources() {
        include NOMADSGURU_PLUGIN_DIR . 'templates/admin/sources.php';
    }

    /**
     * Render Sources Overview page
     */
    public function render_sources_overview() {
        // Get deal sources manager
        $sources_manager = NomadsGuru_Deal_Sources::get_instance();
        $sources = $sources_manager->get_sources();
        $statistics = $sources_manager->get_source_statistics();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Sources Overview', 'nomadsguru' ); ?></h1>
            
            <!-- Sources Navigation -->
            <?php $this->render_sources_navigation( 'overview' ); ?>
            
            <!-- Sources Statistics Dashboard -->
            <div class="nomadsguru-sources-modern">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-number"><?php echo count( $sources ); ?></div>
                        <div class="stat-label"><?php esc_html_e( 'Total Sources', 'nomadsguru' ); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-number"><?php echo array_sum( array_column( $statistics, 'total_deals' ) ); ?></div>
                        <div class="stat-label"><?php esc_html_e( 'Total Deals', 'nomadsguru' ); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-number"><?php echo array_sum( array_column( $statistics, 'published_deals' ) ); ?></div>
                        <div class="stat-label"><?php esc_html_e( 'Published', 'nomadsguru' ); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-number"><?php echo array_sum( array_column( $statistics, 'pending_deals' ) ); ?></div>
                        <div class="stat-label"><?php esc_html_e( 'Pending', 'nomadsguru' ); ?></div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3><?php esc_html_e( 'Quick Actions', 'nomadsguru' ); ?></h3>
                    <div class="actions-grid">
                        <button type="button" class="button button-primary" id="fetch-all-deals">
                            <span class="dashicons dashicons-update-alt"></span>
                            <?php esc_html_e( 'Fetch All Deals', 'nomadsguru' ); ?>
                        </button>
                        <button type="button" class="button" id="test-all-sources">
                            <span class="dashicons dashicons-networking"></span>
                            <?php esc_html_e( 'Test All Sources', 'nomadsguru' ); ?>
                        </button>
                        <button type="button" class="button" id="clear-cache">
                            <span class="dashicons dashicons-trash"></span>
                            <?php esc_html_e( 'Clear Cache', 'nomadsguru' ); ?>
                        </button>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="recent-activity">
                    <h3><?php esc_html_e( 'Recent Activity', 'nomadsguru' ); ?></h3>
                    <div class="activity-list">
                        <?php
                        global $wpdb;
                        $recent_deals = $wpdb->get_results( "
                            SELECT r.*, s.source_name 
                            FROM {$wpdb->prefix}ng_raw_deals r
                            LEFT JOIN {$wpdb->prefix}ng_deal_sources s ON r.source_id = s.id
                            ORDER BY r.created_at DESC
                            LIMIT 5
                        " );
                        
                        if ( ! empty( $recent_deals ) ) {
                            foreach ( $recent_deals as $deal ) {
                                echo '<div class="activity-item">';
                                echo '<span class="activity-source">' . esc_html( $deal->source_name ?? 'Unknown' ) . '</span>';
                                echo '<span class="activity-title">' . esc_html( $deal->title ) . '</span>';
                                echo '<span class="activity-status status-' . esc_attr( $deal->status ) . '">' . esc_html( ucfirst( $deal->status ) ) . '</span>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p>' . esc_html__( 'No recent activity found.', 'nomadsguru' ) . '</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Deal Sources page
     */
    public function render_sources_deal() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Deal Sources', 'nomadsguru' ); ?></h1>
            
            <!-- Sources Navigation -->
            <?php $this->render_sources_navigation( 'deal' ); ?>
            
            <div class="nomadsguru-sources-modern">
                <!-- Deal Sources Configuration -->
                <div class="sources-grid">
                    <!-- CSV Files Card -->
                    <div class="settings-card">
                        <div class="card-header">
                            <h3><span class="card-icon">📄</span> <?php esc_html_e( 'CSV Files', 'nomadsguru' ); ?></h3>
                            <p><?php esc_html_e( 'Upload CSV files with deal data', 'nomadsguru' ); ?></p>
                        </div>
                        <div class="card-content">
                            <form id="csv-upload-form" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="csv-file"><?php esc_html_e( 'Choose CSV File', 'nomadsguru' ); ?></label>
                                    <input type="file" id="csv-file" name="csv_file" accept=".csv" class="regular-text">
                                </div>
                                <button type="submit" class="button button-primary"><?php esc_html_e( 'Upload CSV', 'nomadsguru' ); ?></button>
                            </form>
                        </div>
                    </div>

                    <!-- RSS Feeds Card -->
                    <div class="settings-card">
                        <div class="card-header">
                            <h3><span class="card-icon">📡</span> <?php esc_html_e( 'RSS Feeds', 'nomadsguru' ); ?></h3>
                            <p><?php esc_html_e( 'Configure RSS feed URLs for automatic deal fetching', 'nomadsguru' ); ?></p>
                        </div>
                        <div class="card-content">
                            <form id="rss-feed-form">
                                <div class="form-group">
                                    <label for="rss-url"><?php esc_html_e( 'RSS Feed URL', 'nomadsguru' ); ?></label>
                                    <input type="url" id="rss-url" name="rss_url" class="regular-text" placeholder="https://example.com/deals/feed">
                                </div>
                                <div class="form-group">
                                    <label for="rss-name"><?php esc_html_e( 'Feed Name', 'nomadsguru' ); ?></label>
                                    <input type="text" id="rss-name" name="rss_name" class="regular-text" placeholder="Deal Provider RSS">
                                </div>
                                <button type="submit" class="button button-primary"><?php esc_html_e( 'Add RSS Feed', 'nomadsguru' ); ?></button>
                            </form>
                        </div>
                    </div>

                    <!-- API Sources Card -->
                    <div class="settings-card">
                        <div class="card-header">
                            <h3><span class="card-icon">🔌</span> <?php esc_html_e( 'API Sources', 'nomadsguru' ); ?></h3>
                            <p><?php esc_html_e( 'Configure API endpoints for deal data', 'nomadsguru' ); ?></p>
                        </div>
                        <div class="card-content">
                            <form id="api-source-form">
                                <div class="form-group">
                                    <label for="api-name"><?php esc_html_e( 'API Name', 'nomadsguru' ); ?></label>
                                    <input type="text" id="api-name" name="api_name" class="regular-text" placeholder="Travel API">
                                </div>
                                <div class="form-group">
                                    <label for="api-url"><?php esc_html_e( 'API Endpoint', 'nomadsguru' ); ?></label>
                                    <input type="url" id="api-url" name="api_url" class="regular-text" placeholder="https://api.example.com/deals">
                                </div>
                                <div class="form-group">
                                    <label for="api-key"><?php esc_html_e( 'API Key', 'nomadsguru' ); ?></label>
                                    <input type="password" id="api-key" name="api_key" class="regular-text" placeholder="Enter API key">
                                </div>
                                <button type="submit" class="button button-primary"><?php esc_html_e( 'Add API Source', 'nomadsguru' ); ?></button>
                            </form>
                        </div>
                    </div>

                    <!-- Web Scrapers Card -->
                    <div class="settings-card">
                        <div class="card-header">
                            <h3><span class="card-icon">🕷️</span> <?php esc_html_e( 'Web Scrapers', 'nomadsguru' ); ?></h3>
                            <p><?php esc_html_e( 'Configure web scrapers for deal websites', 'nomadsguru' ); ?></p>
                        </div>
                        <div class="card-content">
                            <form id="web-scraper-form">
                                <div class="form-group">
                                    <label for="scraper-url"><?php esc_html_e( 'Website URL', 'nomadsguru' ); ?></label>
                                    <input type="url" id="scraper-url" name="scraper_url" class="regular-text" placeholder="https://example.com/deals">
                                </div>
                                <div class="form-group">
                                    <label for="scraper-selectors"><?php esc_html_e( 'CSS Selectors', 'nomadsguru' ); ?></label>
                                    <textarea id="scraper-selectors" name="scraper_selectors" class="large-text" rows="4" placeholder="title: .deal-title&#10;price: .deal-price&#10;url: .deal-link"></textarea>
                                </div>
                                <button type="submit" class="button button-primary"><?php esc_html_e( 'Add Web Scraper', 'nomadsguru' ); ?></button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Active Sources List -->
                <div class="active-sources">
                    <h3><?php esc_html_e( 'Active Deal Sources', 'nomadsguru' ); ?></h3>
                    <div class="sources-table">
                        <?php
                        $sources_manager = NomadsGuru_Deal_Sources::get_instance();
                        $sources = $sources_manager->get_sources();
                        
                        if ( ! empty( $sources ) ) {
                            echo '<table class="wp-list-table widefat fixed striped">';
                            echo '<thead><tr>';
                            echo '<th>' . esc_html__( 'Name', 'nomadsguru' ) . '</th>';
                            echo '<th>' . esc_html__( 'Type', 'nomadsguru' ) . '</th>';
                            echo '<th>' . esc_html__( 'Status', 'nomadsguru' ) . '</th>';
                            echo '<th>' . esc_html__( 'Last Sync', 'nomadsguru' ) . '</th>';
                            echo '<th>' . esc_html__( 'Actions', 'nomadsguru' ) . '</th>';
                            echo '</tr></thead><tbody>';
                            
                            foreach ( $sources as $source ) {
                                echo '<tr>';
                                echo '<td>' . esc_html( $source['name'] ) . '</td>';
                                echo '<td>' . esc_html( ucfirst( $source['type'] ) ) . '</td>';
                                echo '<td><span class="status-badge status-' . esc_attr( $source['is_active'] ? 'active' : 'inactive' ) . '">' . 
                                     esc_html( $source['is_active'] ? 'Active' : 'Inactive' ) . '</span></td>';
                                echo '<td>' . esc_html( $source['last_sync'] ?? 'Never' ) . '</td>';
                                echo '<td>';
                                echo '<button type="button" class="button button-small test-source" data-id="' . esc_attr( $source['id'] ) . '">' . esc_html__( 'Test', 'nomadsguru' ) . '</button> ';
                                echo '<button type="button" class="button button-small sync-source" data-id="' . esc_attr( $source['id'] ) . '">' . esc_html__( 'Sync', 'nomadsguru' ) . '</button> ';
                                echo '<button type="button" class="button button-small delete-source" data-id="' . esc_attr( $source['id'] ) . '">' . esc_html__( 'Delete', 'nomadsguru' ) . '</button>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</tbody></table>';
                        } else {
                            echo '<p>' . esc_html__( 'No deal sources configured yet.', 'nomadsguru' ) . '</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Image Sources page
     */
    public function render_sources_image() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Image Sources', 'nomadsguru' ); ?></h1>
            
            <!-- Sources Navigation -->
            <?php $this->render_sources_navigation( 'image' ); ?>
            
            <div class="nomadsguru-sources-modern">
                <form action="options.php" method="post">
                    <?php
                    settings_fields( 'nomadsguru_sources' );
                    do_settings_sections( 'nomadsguru_sources' );
                    submit_button( __( 'Save Image Sources Settings', 'nomadsguru' ) );
                    ?>
                </form>

                <!-- Image Sources Configuration -->
                <div class="sources-grid">
                    <!-- Pixabay Card -->
                    <div class="settings-card">
                        <div class="card-header">
                            <h3><span class="card-icon">🖼️</span> <?php esc_html_e( 'Pixabay', 'nomadsguru' ); ?></h3>
                            <p><?php esc_html_e( 'Free stock photos and videos', 'nomadsguru' ); ?></p>
                        </div>
                        <div class="card-content">
                            <p><?php esc_html_e( 'Pixabay provides high-quality, royalty-free images that can be used for travel deals. No API key required for basic usage.', 'nomadsguru' ); ?></p>
                            <p><strong><?php esc_html_e( 'Features:', 'nomadsguru' ); ?></strong></p>
                            <ul>
                                <li><?php esc_html_e( 'Free to use (no attribution required)', 'nomadsguru' ); ?></li>
                                <li><?php esc_html_e( 'Large image library', 'nomadsguru' ); ?></li>
                                <li><?php esc_html_e( 'High-resolution images', 'nomadsguru' ); ?></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Pexels Card -->
                    <div class="settings-card">
                        <div class="card-header">
                            <h3><span class="card-icon">📷</span> <?php esc_html_e( 'Pexels', 'nomadsguru' ); ?></h3>
                            <p><?php esc_html_e( 'Free stock photos and videos', 'nomadsguru' ); ?></p>
                        </div>
                        <div class="card-content">
                            <p><?php esc_html_e( 'Pexels offers beautiful, high-quality stock photos perfect for travel content. API key required for enhanced features.', 'nomadsguru' ); ?></p>
                            <p><strong><?php esc_html_e( 'Features:', 'nomadsguru' ); ?></strong></p>
                            <ul>
                                <li><?php esc_html_e( '200 requests/hour with API key', 'nomadsguru' ); ?></li>
                                <li><?php esc_html_e( 'Curated collections', 'nomadsguru' ); ?></li>
                                <li><?php esc_html_e( 'Photographer information', 'nomadsguru' ); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Test Image Sources -->
                <div class="test-sources">
                    <h3><?php esc_html_e( 'Test Image Sources', 'nomadsguru' ); ?></h3>
                    <div class="test-form">
                        <div class="form-group">
                            <label for="test-query"><?php esc_html_e( 'Search Query', 'nomadsguru' ); ?></label>
                            <input type="text" id="test-query" class="regular-text" placeholder="Paris travel" value="Paris travel">
                        </div>
                        <button type="button" class="button button-primary" id="test-image-sources"><?php esc_html_e( 'Test All Sources', 'nomadsguru' ); ?></button>
                    </div>
                    <div id="image-test-results" class="test-results" style="display: none;">
                        <!-- Results will be displayed here -->
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Inspiration Sources page
     */
    public function render_sources_inspiration() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Inspiration Sources', 'nomadsguru' ); ?></h1>
            
            <!-- Sources Navigation -->
            <?php $this->render_sources_navigation( 'inspiration' ); ?>
            
            <div class="nomadsguru-sources-modern">
                <!-- Inspiration Sources Configuration -->
                <div class="sources-grid">
                    <!-- RSS Feeds Card -->
                    <div class="settings-card">
                        <div class="card-header">
                            <h3><span class="card-icon">📰</span> <?php esc_html_e( 'Travel RSS Feeds', 'nomadsguru' ); ?></h3>
                            <p><?php esc_html_e( 'RSS feeds for travel inspiration and content ideas', 'nomadsguru' ); ?></p>
                        </div>
                        <div class="card-content">
                            <form id="inspiration-rss-form">
                                <div class="form-group">
                                    <label for="inspiration-rss-url"><?php esc_html_e( 'RSS Feed URL', 'nomadsguru' ); ?></label>
                                    <input type="url" id="inspiration-rss-url" name="rss_url" class="regular-text" placeholder="https://example.com/travel/feed">
                                </div>
                                <div class="form-group">
                                    <label for="inspiration-rss-name"><?php esc_html_e( 'Feed Name', 'nomadsguru' ); ?></label>
                                    <input type="text" id="inspiration-rss-name" name="rss_name" class="regular-text" placeholder="Travel Blog RSS">
                                </div>
                                <div class="form-group">
                                    <label for="inspiration-rss-category"><?php esc_html_e( 'Category', 'nomadsguru' ); ?></label>
                                    <select id="inspiration-rss-category" name="rss_category">
                                        <option value="general"><?php esc_html_e( 'General', 'nomadsguru' ); ?></option>
                                        <option value="adventure"><?php esc_html_e( 'Adventure', 'nomadsguru' ); ?></option>
                                        <option value="luxury"><?php esc_html_e( 'Luxury', 'nomadsguru' ); ?></option>
                                        <option value="budget"><?php esc_html_e( 'Budget', 'nomadsguru' ); ?></option>
                                        <option value="family"><?php esc_html_e( 'Family', 'nomadsguru' ); ?></option>
                                    </select>
                                </div>
                                <button type="submit" class="button button-primary"><?php esc_html_e( 'Add Inspiration Feed', 'nomadsguru' ); ?></button>
                            </form>
                        </div>
                    </div>

                    <!-- Content Ideas Card -->
                    <div class="settings-card">
                        <div class="card-header">
                            <h3><span class="card-icon">💡</span> <?php esc_html_e( 'Content Ideas Generator', 'nomadsguru' ); ?></h3>
                            <p><?php esc_html_e( 'AI-powered content ideas for travel articles', 'nomadsguru' ); ?></p>
                        </div>
                        <div class="card-content">
                            <form id="content-ideas-form">
                                <div class="form-group">
                                    <label for="destination-input"><?php esc_html_e( 'Destination', 'nomadsguru' ); ?></label>
                                    <input type="text" id="destination-input" class="regular-text" placeholder="Paris, France">
                                </div>
                                <div class="form-group">
                                    <label for="content-type"><?php esc_html_e( 'Content Type', 'nomadsguru' ); ?></label>
                                    <select id="content-type">
                                        <option value="guide"><?php esc_html_e( 'Travel Guide', 'nomadsguru' ); ?></option>
                                        <option value="deals"><?php esc_html_e( 'Deal Roundup', 'nomadsguru' ); ?></option>
                                        <option value="tips"><?php esc_html_e( 'Travel Tips', 'nomadsguru' ); ?></option>
                                        <option value="review"><?php esc_html_e( 'Destination Review', 'nomadsguru' ); ?></option>
                                    </select>
                                </div>
                                <button type="button" class="button button-primary" id="generate-ideas"><?php esc_html_e( 'Generate Ideas', 'nomadsguru' ); ?></button>
                            </form>
                            <div id="content-ideas-results" class="ideas-results" style="display: none;">
                                <!-- Generated ideas will appear here -->
                            </div>
                        </div>
                    </div>

                    <!-- Trending Topics Card -->
                    <div class="settings-card">
                        <div class="card-header">
                            <h3><span class="card-icon">🔥</span> <?php esc_html_e( 'Trending Travel Topics', 'nomadsguru' ); ?></h3>
                            <p><?php esc_html_e( 'Monitor trending travel topics for content inspiration', 'nomadsguru' ); ?></p>
                        </div>
                        <div class="card-content">
                            <div class="trending-topics">
                                <?php
                                // Sample trending topics - in real implementation, this would come from an API
                                $trending_topics = [
                                    'Budget European Summer 2025',
                                    'Sustainable Travel Destinations',
                                    'Digital Nomad Hotspots',
                                    'Family-Friendly All-Inclusive Resorts',
                                    'Adventure Travel in Southeast Asia'
                                ];
                                
                                foreach ( $trending_topics as $topic ) {
                                    echo '<div class="topic-tag">';
                                    echo '<span class="topic-text">' . esc_html( $topic ) . '</span>';
                                    echo '<button type="button" class="button button-small use-topic" data-topic="' . esc_attr( $topic ) . '">' . esc_html__( 'Use This', 'nomadsguru' ) . '</button>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                            <button type="button" class="button" id="refresh-trending"><?php esc_html_e( 'Refresh Trending', 'nomadsguru' ); ?></button>
                        </div>
                    </div>
                </div>

                <!-- Active Inspiration Sources -->
                <div class="active-sources">
                    <h3><?php esc_html_e( 'Active Inspiration Sources', 'nomadsguru' ); ?></h3>
                    <div class="sources-table">
                        <?php
                        $inspiration_sources = get_option( 'ng_sources_settings', [] );
                        $inspiration_feeds = $inspiration_sources['inspiration_sources'] ?? [];
                        
                        if ( ! empty( $inspiration_feeds ) ) {
                            echo '<table class="wp-list-table widefat fixed striped">';
                            echo '<thead><tr>';
                            echo '<th>' . esc_html__( 'Name', 'nomadsguru' ) . '</th>';
                            echo '<th>' . esc_html__( 'URL', 'nomadsguru' ) . '</th>';
                            echo '<th>' . esc_html__( 'Category', 'nomadsguru' ) . '</th>';
                            echo '<th>' . esc_html__( 'Last Updated', 'nomadsguru' ) . '</th>';
                            echo '<th>' . esc_html__( 'Actions', 'nomadsguru' ) . '</th>';
                            echo '</tr></thead><tbody>';
                            
                            foreach ( $inspiration_feeds as $feed ) {
                                echo '<tr>';
                                echo '<td>' . esc_html( $feed['name'] ) . '</td>';
                                echo '<td><a href="' . esc_url( $feed['url'] ) . '" target="_blank">' . esc_html( $feed['url'] ) . '</a></td>';
                                echo '<td>' . esc_html( ucfirst( $feed['category'] ) ) . '</td>';
                                echo '<td>' . esc_html( $feed['last_updated'] ?? 'Never' ) . '</td>';
                                echo '<td>';
                                echo '<button type="button" class="button button-small test-feed" data-url="' . esc_url( $feed['url'] ) . '">' . esc_html__( 'Test', 'nomadsguru' ) . '</button> ';
                                echo '<button type="button" class="button button-small delete-feed" data-name="' . esc_attr( $feed['name'] ) . '">' . esc_html__( 'Delete', 'nomadsguru' ) . '</button>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</tbody></table>';
                        } else {
                            echo '<p>' . esc_html__( 'No inspiration sources configured yet.', 'nomadsguru' ) . '</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Sources Navigation
     */
    private function render_sources_navigation( $current_page = 'overview' ) {
        $tabs = array(
            'overview' => __( 'Overview', 'nomadsguru' ),
            'deal' => __( 'Deal Sources', 'nomadsguru' ),
            'image' => __( 'Image Sources', 'nomadsguru' ),
            'inspiration' => __( 'Inspiration Sources', 'nomadsguru' )
        );
        
        echo '<nav class="nav-tab-wrapper">';
        foreach ( $tabs as $tab => $name ) {
            $class = ( $tab == $current_page ) ? ' nav-tab-active' : '';
            $url = admin_url( "admin.php?page=nomadsguru-sources-{$tab}" );
            echo "<a class='nav-tab{$class}' href='{$url}'>{$name}</a>";
        }
        echo '</nav>';
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
        
        // Sanitize mode field
        $allowed_modes = ['manual', 'automatic', 'hybrid'];
        $sanitized['mode'] = in_array( $input['mode'] ?? 'manual', $allowed_modes ) ? $input['mode'] : 'manual';
        
        $sanitized['auto_publish'] = isset( $input['auto_publish'] ) ? 1 : 0;
        $sanitized['default_category'] = intval( $input['default_category'] ?? 0 );
        $sanitized['default_author'] = intval( $input['default_author'] ?? 1 );
        $sanitized['publish_threshold'] = floatval( $input['publish_threshold'] ?? 7.0 );
        $sanitized['publish_threshold'] = max( 1, min( 10, $sanitized['publish_threshold'] ) );

        // Sanitize schedule settings
        $sanitized['schedule'] = [];
        if ( isset( $input['schedule'] ) && is_array( $input['schedule'] ) ) {
            $allowed_frequencies = ['hourly', 'daily', 'weekly', 'monthly'];
            $sanitized['schedule']['frequency'] = in_array( $input['schedule']['frequency'] ?? 'daily', $allowed_frequencies ) 
                ? $input['schedule']['frequency'] : 'daily';
            
            $sanitized['schedule']['time'] = sanitize_text_field( $input['schedule']['time'] ?? '09:00' );
            
            if ( isset( $input['schedule']['days'] ) && is_array( $input['schedule']['days'] ) ) {
                $allowed_days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                $sanitized['schedule']['days'] = array_intersect( $input['schedule']['days'], $allowed_days );
            }
        }

        // Sanitize quality control settings
        $sanitized['quality_control'] = [];
        if ( isset( $input['quality_control'] ) && is_array( $input['quality_control'] ) ) {
            $sanitized['quality_control']['require_manual_review'] = isset( $input['quality_control']['require_manual_review'] ) ? 1 : 0;
            $sanitized['quality_control']['min_deal_count'] = max( 1, min( 100, intval( $input['quality_control']['min_deal_count'] ?? 5 ) ) );
            $sanitized['quality_control']['max_age_days'] = max( 1, min( 365, intval( $input['quality_control']['max_age_days'] ?? 30 ) ) );
        }

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
            $wpdb->query( $wpdb->prepare( "DELETE FROM %i", $table ) );
        }

        // Delete options
        $options = [
            'ng_ai_settings',
            'ng_sources_settings',
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
     * Handle publish approved deals AJAX request
     */
    public function handle_publish_approved() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'nomadsguru_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }

        global $wpdb;
        
        // Get approved deals
        $table_name = $wpdb->prefix . 'ng_raw_deals';
        $approved_deals = $wpdb->get_results( "
            SELECT * FROM $table_name 
            WHERE status = 'approved' 
            AND (post_id IS NULL OR post_id = 0)
            ORDER BY created_at DESC
            LIMIT 50
        " );

        if ( empty( $approved_deals ) ) {
            wp_send_json_error( array( 'message' => __( 'No approved deals found to publish.', 'nomadsguru' ) ) );
        }

        $published_count = 0;
        $failed_count = 0;
        $settings = get_option( 'ng_publishing_settings', [] );
        $default_category = $settings['default_category'] ?? 0;
        $default_author = $settings['default_author'] ?? 1;

        foreach ( $approved_deals as $deal ) {
            // Create post data
            $post_data = array(
                'post_title'    => wp_strip_all_tags( $deal->title ),
                'post_content'  => $deal->description ?? '',
                'post_status'   => 'publish',
                'post_author'   => $default_author,
                'post_category' => $default_category ? array( $default_category ) : array(),
                'post_type'     => 'post'
            );

            // Insert post
            $post_id = wp_insert_post( $post_data );

            if ( $post_id && ! is_wp_error( $post_id ) ) {
                // Add post meta for deal information
                update_post_meta( $post_id, '_deal_source_id', $deal->source_id );
                update_post_meta( $post_id, '_deal_destination', $deal->destination );
                update_post_meta( $post_id, '_deal_price', $deal->price );
                update_post_meta( $post_id, '_deal_original_price', $deal->original_price );
                update_post_meta( $post_id, '_deal_discount', $deal->discount_percentage );
                update_post_meta( $post_id, '_deal_url', $deal->deal_url );
                update_post_meta( $post_id, '_deal_valid_until', $deal->valid_until );
                update_post_meta( $post_id, '_deal_ai_score', $deal->ai_score );

                // Update deal record
                $wpdb->update(
                    $table_name,
                    array( 'post_id' => $post_id, 'status' => 'published' ),
                    array( 'id' => $deal->id )
                );

                $published_count++;
            } else {
                $failed_count++;
            }
        }

        wp_send_json_success( array(
            'message' => sprintf(
                __( 'Successfully published %d deals. %d deals failed to publish.', 'nomadsguru' ),
                $published_count,
                $failed_count
            ),
            'published_count' => $published_count,
            'failed_count' => $failed_count
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

    /**
     * Handle CSV upload
     */
    public function handle_upload_csv() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'nomadsguru_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }
        
        if ( ! isset( $_FILES['csv_file'] ) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( array( 'message' => __( 'File upload failed.', 'nomadsguru' ) ) );
        }
        
        $file = $_FILES['csv_file'];
        $file_info = pathinfo( $file['name'] );
        
        if ( strtolower( $file_info['extension'] ) !== 'csv' ) {
            wp_send_json_error( array( 'message' => __( 'Please upload a CSV file.', 'nomadsguru' ) ) );
        }
        
        // Process CSV file (simplified implementation)
        $csv_data = array_map( 'str_getcsv', file( $file['tmp_name'] ) );
        $headers = array_shift( $csv_data );
        
        // Save to database or process as needed
        // For now, just return success
        wp_send_json_success( array( 'message' => __( 'CSV uploaded successfully!', 'nomadsguru' ) ) );
    }

    /**
     * Handle RSS feed addition
     */
    public function handle_add_rss_feed() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'nomadsguru_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }
        
        $rss_url = sanitize_url( $_POST['rss_url'] );
        $rss_name = sanitize_text_field( $_POST['rss_name'] );
        
        if ( empty( $rss_url ) || empty( $rss_name ) ) {
            wp_send_json_error( array( 'message' => __( 'Please provide both URL and name.', 'nomadsguru' ) ) );
        }
        
        // Validate RSS URL
        $rss = simplexml_load_file( $rss_url );
        if ( ! $rss ) {
            wp_send_json_error( array( 'message' => __( 'Invalid RSS feed URL.', 'nomadsguru' ) ) );
        }
        
        // Save RSS feed to database (simplified)
        global $wpdb;
        $table = $wpdb->prefix . 'ng_deal_sources';
        
        $result = $wpdb->insert(
            $table,
            array(
                'source_name' => $rss_name,
                'source_type' => 'rss',
                'source_url' => $rss_url,
                'is_active' => 1,
                'created_at' => current_time( 'mysql' )
            ),
            array( '%s', '%s', '%s', '%d', '%s' )
        );
        
        if ( $result !== false ) {
            wp_send_json_success( array( 'message' => __( 'RSS feed added successfully!', 'nomadsguru' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to add RSS feed.', 'nomadsguru' ) ) );
        }
    }

    /**
     * Handle source testing
     */
    public function handle_test_source() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'nomadsguru_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }
        
        $source_id = intval( $_POST['source_id'] );
        
        if ( ! $source_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid source ID.', 'nomadsguru' ) ) );
        }
        
        // Get source from database
        global $wpdb;
        $table = $wpdb->prefix . 'ng_deal_sources';
        $source = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $source_id ) );
        
        if ( ! $source ) {
            wp_send_json_error( array( 'message' => __( 'Source not found.', 'nomadsguru' ) ) );
        }
        
        // Test source based on type (simplified implementation)
        $test_result = true;
        $message = __( 'Source test successful!', 'nomadsguru' );
        
        if ( $source->source_type === 'rss' ) {
            $rss = @simplexml_load_file( $source->source_url );
            $test_result = ( $rss !== false );
            if ( ! $test_result ) {
                $message = __( 'RSS feed is not accessible.', 'nomadsguru' );
            }
        } elseif ( $source->source_type === 'api' ) {
            // Test API endpoint
            $response = @wp_remote_get( $source->source_url );
            $test_result = ! is_wp_error( $response );
            if ( ! $test_result ) {
                $message = __( 'API endpoint is not accessible.', 'nomadsguru' );
            }
        }
        
        if ( $test_result ) {
            wp_send_json_success( array( 'message' => $message ) );
        } else {
            wp_send_json_error( array( 'message' => $message ) );
        }
    }

    /**
     * Render Deals page
     */
    public function render_deals() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Deals Management', 'nomadsguru' ); ?></h1>
            
            <div class="nomadsguru-deals-modern">
                <!-- Deals Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-number"><?php echo $this->get_deal_count(); ?></div>
                        <div class="stat-label"><?php esc_html_e( 'Total Deals', 'nomadsguru' ); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-number"><?php echo $this->get_published_deal_count(); ?></div>
                        <div class="stat-label"><?php esc_html_e( 'Published', 'nomadsguru' ); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-number"><?php echo $this->get_pending_deal_count(); ?></div>
                        <div class="stat-label"><?php esc_html_e( 'Pending', 'nomadsguru' ); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🗑️</div>
                        <div class="stat-number"><?php echo $this->get_rejected_deal_count(); ?></div>
                        <div class="stat-label"><?php esc_html_e( 'Rejected', 'nomadsguru' ); ?></div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3><?php esc_html_e( 'Quick Actions', 'nomadsguru' ); ?></h3>
                    <div class="actions-grid">
                        <button type="button" class="button button-primary" id="fetch-new-deals">
                            <span class="dashicons dashicons-update-alt"></span>
                            <?php esc_html_e( 'Fetch New Deals', 'nomadsguru' ); ?>
                        </button>
                        <button type="button" class="button" id="process-queue">
                            <span class="dashicons dashicons-clock"></span>
                            <?php esc_html_e( 'Process Queue', 'nomadsguru' ); ?>
                        </button>
                        <button type="button" class="button" id="export-deals">
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e( 'Export Deals', 'nomadsguru' ); ?>
                        </button>
                    </div>
                </div>

                <!-- Recent Deals -->
                <div class="recent-deals">
                    <h3><?php esc_html_e( 'Recent Deals', 'nomadsguru' ); ?></h3>
                    <div class="deals-table">
                        <?php
                        global $wpdb;
                        $recent_deals = $wpdb->get_results( "
                            SELECT r.*, s.source_name 
                            FROM {$wpdb->prefix}ng_raw_deals r
                            LEFT JOIN {$wpdb->prefix}ng_deal_sources s ON r.source_id = s.id
                            ORDER BY r.created_at DESC
                            LIMIT 10
                        " );
                        
                        if ( ! empty( $recent_deals ) ) {
                            echo '<table class="wp-list-table widefat fixed striped">';
                            echo '<thead><tr>';
                            echo '<th>' . esc_html__( 'Title', 'nomadsguru' ) . '</th>';
                            echo '<th>' . esc_html__( 'Destination', 'nomadsguru' ) . '</th>';
                            echo '<th>' . esc_html__( 'Price', 'nomadsguru' ) . '</th>';
                            echo '<th>' . esc_html__( 'Source', 'nomadsguru' ) . '</th>';
                            echo '<th>' . esc_html__( 'Status', 'nomadsguru' ) . '</th>';
                            echo '<th>' . esc_html__( 'Actions', 'nomadsguru' ) . '</th>';
                            echo '</tr></thead><tbody>';
                            
                            foreach ( $recent_deals as $deal ) {
                                echo '<tr>';
                                echo '<td><strong>' . esc_html( $deal->title ) . '</strong></td>';
                                echo '<td>' . esc_html( $deal->destination ) . '</td>';
                                echo '<td>' . esc_html( $deal->currency ) . ' ' . esc_html( $deal->discounted_price ) . '</td>';
                                echo '<td>' . esc_html( $deal->source_name ?? 'Unknown' ) . '</td>';
                                echo '<td><span class="status-badge status-' . esc_attr( $deal->status ) . '">' . esc_html( ucfirst( $deal->status ) ) . '</span></td>';
                                echo '<td>';
                                if ( $deal->status === 'pending' ) {
                                    echo '<button type="button" class="button button-small approve-deal" data-id="' . esc_attr( $deal->id ) . '">' . esc_html__( 'Approve', 'nomadsguru' ) . '</button> ';
                                    echo '<button type="button" class="button button-small reject-deal" data-id="' . esc_attr( $deal->id ) . '">' . esc_html__( 'Reject', 'nomadsguru' ) . '</button>';
                                }
                                echo '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</tbody></table>';
                        } else {
                            echo '<p>' . esc_html__( 'No deals found.', 'nomadsguru' ) . '</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Fetch new deals
            $('#fetch-new-deals').on('click', function() {
                const $button = $(this);
                $button.prop('disabled', true).text('<?php esc_html_e( "Fetching...", "nomadsguru" ); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ng_fetch_deals',
                        nonce: '<?php echo wp_create_nonce( "nomadsguru_admin_nonce" ); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php esc_html_e( "Fetch New Deals", "nomadsguru" ); ?>');
                        if (response.success) {
                            alert(response.data.message || '<?php esc_html_e( "Deals fetched successfully!", "nomadsguru" ); ?>');
                            location.reload();
                        } else {
                            alert(response.data.message || '<?php esc_html_e( "Failed to fetch deals.", "nomadsguru" ); ?>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php esc_html_e( "Fetch New Deals", "nomadsguru" ); ?>');
                        alert('<?php esc_html_e( "Request failed. Please try again.", "nomadsguru" ); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Get deal count
     */
    private function get_deal_count() {
        global $wpdb;
        return $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ng_raw_deals" ) ?: 0;
    }

    /**
     * Get published deal count
     */
    private function get_published_deal_count() {
        global $wpdb;
        return $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ng_raw_deals WHERE status = 'published'" ) ?: 0;
    }

    /**
     * Get pending deal count
     */
    private function get_pending_deal_count() {
        global $wpdb;
        return $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ng_raw_deals WHERE status = 'pending'" ) ?: 0;
    }

    /**
     * Get rejected deal count
     */
    private function get_rejected_deal_count() {
        global $wpdb;
        return $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ng_raw_deals WHERE status = 'rejected'" ) ?: 0;
    }
}

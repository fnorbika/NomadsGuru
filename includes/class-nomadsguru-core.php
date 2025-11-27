<?php

/**
 * Core plugin class for NomadsGuru
 * 
 * @package NomadsGuru
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NomadsGuru_Core {

    /**
     * Single instance of the class
     * @var NomadsGuru_Core|null
     */
    private static $instance = null;

    /**
     * Plugin version
     * @var string
     */
    private $version = '1.1.0';

    /**
     * Get singleton instance
     * 
     * @return NomadsGuru_Core
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
        $this->define_constants();
        $this->init_hooks();
    }

    /**
     * Define plugin constants
     */
    private function define_constants() {
        define( 'NOMADSGURU_VERSION', $this->version );
        define( 'NOMADSGURU_PLUGIN_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
        define( 'NOMADSGURU_PLUGIN_URL', plugin_dir_url( dirname( __FILE__ ) ) );
        define( 'NOMADSGURU_PLUGIN_FILE', dirname( __FILE__ ) . '/nomadsguru.php' );
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Plugin initialization
        add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
        
        // Activation/Deactivation hooks
        register_activation_hook( NOMADSGURU_PLUGIN_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( NOMADSGURU_PLUGIN_FILE, array( $this, 'deactivate' ) );
        
        // Text domain
        add_action( 'init', array( $this, 'load_textdomain' ) );
    }

    /**
     * Initialize the plugin
     */
    public function init_plugin() {
        // Conditional loading based on context
        if ( is_admin() ) {
            $this->init_admin();
        } else {
            $this->init_frontend();
        }
        
        // Always load REST API
        $this->init_rest_api();
        
        // Initialize blocks if Gutenberg is available
        if ( function_exists( 'register_block_type' ) ) {
            $this->init_blocks();
        }
    }

    /**
     * Initialize admin components
     */
    private function init_admin() {
        require_once NOMADSGURU_PLUGIN_DIR . 'includes/class-nomadsguru-admin.php';
        NomadsGuru_Admin::get_instance();
    }

    /**
     * Initialize frontend components
     */
    private function init_frontend() {
        require_once NOMADSGURU_PLUGIN_DIR . 'includes/class-nomadsguru-shortcodes.php';
        NomadsGuru_Shortcodes::get_instance();
    }

    /**
     * Initialize REST API
     */
    private function init_rest_api() {
        require_once NOMADSGURU_PLUGIN_DIR . 'includes/class-nomadsguru-rest.php';
        NomadsGuru_REST::get_instance();
    }

    /**
     * Initialize Gutenberg blocks
     */
    private function init_blocks() {
        add_action( 'init', array( $this, 'register_blocks' ) );
    }

    /**
     * Register Gutenberg blocks
     */
    public function register_blocks() {
        // Register blocks here
        // For now, we'll keep the existing block structure but could consolidate later
        if ( file_exists( NOMADSGURU_PLUGIN_DIR . 'src/Blocks/DealsBlock.php' ) ) {
            require_once NOMADSGURU_PLUGIN_DIR . 'src/Blocks/DealsBlock.php';
            $deals_block = new \NomadsGuru\Blocks\DealsBlock();
            $deals_block->register();
        }
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'nomadsguru',
            false,
            dirname( plugin_basename( NOMADSGURU_PLUGIN_FILE ) ) . '/languages'
        );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up scheduled tasks
        wp_clear_scheduled_hook( 'nomadsguru_sync_deals' );
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Table: Deal Sources
        $table_sources = $wpdb->prefix . 'ng_deal_sources';
        $sql_sources = "CREATE TABLE $table_sources (
            id INT AUTO_INCREMENT PRIMARY KEY,
            source_type VARCHAR(50) NOT NULL,
            source_name VARCHAR(255) NOT NULL,
            website_url VARCHAR(500),
            rss_feed VARCHAR(500),
            is_active BOOLEAN DEFAULT 1,
            last_sync DATETIME,
            sync_interval_minutes INT DEFAULT 60,
            created_at DATETIME,
            updated_at DATETIME,
            UNIQUE KEY unique_source (source_type, source_name)
        ) $charset_collate;";
        dbDelta( $sql_sources );

        // Table: Affiliate Programs
        $table_affiliates = $wpdb->prefix . 'ng_affiliate_programs';
        $sql_affiliates = "CREATE TABLE $table_affiliates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            program_name VARCHAR(255) NOT NULL,
            program_type ENUM('api', 'manual_url', 'cookie_based') DEFAULT 'manual_url',
            api_endpoint VARCHAR(500),
            credentials_encrypted LONGTEXT,
            url_pattern VARCHAR(1000),
            commission_rate DECIMAL(5,2),
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME,
            updated_at DATETIME,
            UNIQUE KEY unique_program (program_name)
        ) $charset_collate;";
        dbDelta( $sql_affiliates );

        // Table: Raw Deals
        $table_deals = $wpdb->prefix . 'ng_raw_deals';
        $sql_deals = "CREATE TABLE $table_deals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            source_id INT,
            title VARCHAR(500) NOT NULL,
            description TEXT,
            destination VARCHAR(255),
            price DECIMAL(10,2),
            original_price DECIMAL(10,2),
            discount_percentage DECIMAL(5,2),
            deal_url VARCHAR(1000),
            image_url VARCHAR(1000),
            valid_until DATETIME,
            ai_score DECIMAL(3,1),
            ai_reasoning TEXT,
            status ENUM('pending', 'approved', 'rejected', 'published') DEFAULT 'pending',
            post_id BIGINT,
            created_at DATETIME,
            updated_at DATETIME,
            FOREIGN KEY (source_id) REFERENCES {$table_sources}(id) ON DELETE SET NULL
        ) $charset_collate;";
        dbDelta( $sql_deals );

        // Table: Processing Queue
        $table_queue = $wpdb->prefix . 'ng_processing_queue';
        $sql_queue = "CREATE TABLE $table_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_type ENUM('deal', 'source_sync') NOT NULL,
            item_id INT,
            status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
            priority INT DEFAULT 5,
            attempts INT DEFAULT 0,
            max_attempts INT DEFAULT 3,
            error_message TEXT,
            scheduled_at DATETIME,
            processed_at DATETIME,
            created_at DATETIME,
            INDEX idx_status_priority (status, priority),
            INDEX idx_scheduled (scheduled_at)
        ) $charset_collate;";
        dbDelta( $sql_queue );
    }

    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'ng_ai_settings' => array(
                'provider' => 'openai',
                'api_key' => '',
                'model' => 'gpt-3.5-turbo',
                'temperature' => 0.7,
                'max_tokens' => 500
            ),
            'ng_publishing_settings' => array(
                'auto_publish' => 0,
                'default_category' => 0,
                'default_author' => 1,
                'publish_threshold' => 7.0
            ),
            'ng_usage_stats' => array(
                'total_requests' => 0,
                'total_cost' => 0,
                'last_reset' => current_time('mysql')
            )
        );

        foreach ( $default_options as $option => $value ) {
            if ( get_option( $option ) === false ) {
                add_option( $option, $value );
            }
        }
    }

    /**
     * Get plugin version
     * 
     * @return string
     */
    public function get_version() {
        return $this->version;
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

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
        add_action( 'wp_ajax_ng_validate_api_key', array( $this, 'handle_validate_api_key' ) );
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
                    'max_tokens' => 500
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
        <div class="api-key-input-group">
            <input type="password" 
                   name="ng_ai_settings[api_key]" 
                   id="api_key" 
                   value="<?php echo esc_attr( $decrypted_key ); ?>" 
                   class="regular-text" 
                   placeholder="<?php esc_attr_e( 'Enter your API key', 'nomadsguru' ); ?>"
                   autocomplete="off"
            />
            <button type="button" id="manual_validate_key" class="button button-small" style="margin-left: 8px;">
                <?php esc_html_e( 'Validate', 'nomadsguru' ); ?>
            </button>
        </div>
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
     * Validate API key format based on provider
     * 
     * @param string $api_key
     * @param string $provider
     * @return array Validation result with message
     */
    private function validate_api_key_format( $api_key, $provider ) {
        $api_key = trim( $api_key );
        
        error_log("Validating API key for provider: " . $provider);
        error_log("API Key: '" . $api_key . "'");
        error_log("Length: " . strlen($api_key));
        
        switch ( $provider ) {
            case 'openai':
                // OpenAI keys: sk- (51+ chars) or sk-proj- (56+ chars)
                if ( strlen( $api_key ) < 20 ) { // More lenient minimum
                    error_log("OpenAI: Too short - " . strlen($api_key) . " chars");
                    return [
                        'valid' => false,
                        'message' => __( 'OpenAI API key must be at least 20 characters long', 'nomadsguru' )
                    ];
                }
                if ( strpos( $api_key, 'sk-' ) !== 0 ) {
                    error_log("OpenAI: Wrong prefix");
                    return [
                        'valid' => false,
                        'message' => __( 'OpenAI API key must start with "sk-"', 'nomadsguru' )
                    ];
                }
                if ( ! preg_match( '/^sk-[a-zA-Z0-9_-]+$/', $api_key ) ) {
                    error_log("OpenAI: Contains invalid characters");
                    return [
                        'valid' => false,
                        'message' => __( 'OpenAI API key must contain only letters, numbers, underscores, and hyphens', 'nomadsguru' )
                    ];
                }
                break;
                
            case 'gemini':
                // Gemini keys: Very lenient validation - just check prefix and basic format
                error_log("Gemini: Checking prefix for: " . substr($api_key, 0, 10) . "...");
                
                // Just check if it starts with AIzaSy and has reasonable length
                if ( strpos( $api_key, 'AIzaSy' ) !== 0 ) {
                    error_log("Gemini: Wrong prefix - doesn't start with AIzaSy");
                    return [
                        'valid' => false,
                        'message' => __( 'Google Gemini API key must start with "AIzaSy"', 'nomadsguru' )
                    ];
                }
                
                // Very lenient length check - just needs to be longer than the prefix
                if ( strlen( $api_key ) <= 10 ) {
                    error_log("Gemini: Too short");
                    return [
                        'valid' => false,
                        'message' => __( 'Google Gemini API key is too short', 'nomadsguru' )
                    ];
                }
                
                error_log("Gemini: Basic validation passed");
                break;
                
            case 'grok':
                // Grok keys: More lenient validation
                if ( strlen( $api_key ) < 10 ) {
                    return [
                        'valid' => false,
                        'message' => __( 'xAI Grok API key must be at least 10 characters long', 'nomadsguru' )
                    ];
                }
                if ( ! preg_match( '/^[a-zA-Z0-9_-]+$/', $api_key ) ) {
                    return [
                        'valid' => false,
                        'message' => __( 'xAI Grok API key must contain only letters, numbers, underscores, and hyphens', 'nomadsguru' )
                    ];
                }
                break;
                
            case 'perplexity':
                // Perplexity keys: More lenient validation
                if ( strlen( $api_key ) < 10 ) {
                    return [
                        'valid' => false,
                        'message' => __( 'Perplexity API key must be at least 10 characters long', 'nomadsguru' )
                    ];
                }
                if ( ! preg_match( '/^[a-zA-Z0-9_-]+$/', $api_key ) ) {
                    return [
                        'valid' => false,
                        'message' => __( 'Perplexity API key must contain only letters, numbers, underscores, and hyphens', 'nomadsguru' )
                    ];
                }
                break;
                
            default:
                return [
                    'valid' => false,
                    'message' => __( 'Unknown AI provider', 'nomadsguru' )
                ];
        }
        
        error_log("Validation: Returning valid");
        return [ 'valid' => true, 'message' => '' ];
    }

    /**
     * Sanitize AI settings with validation
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
                // Validate new API key format
                $validation = $this->validate_api_key_format( $input['api_key'], $sanitized['provider'] );
                if ( ! $validation['valid'] ) {
                    // Add validation error
                    add_settings_error(
                        'nomadsguru_messages',
                        'api_key_invalid',
                        sprintf(
                            '<span class="ng-error-icon">⚠</span><span class="ng-error-title">%s</span><span class="ng-error-message">%s</span>',
                            __( 'Invalid API Key', 'nomadsguru' ),
                            $validation['message']
                        ),
                        'error'
                    );
                    // Don't save invalid key
                    $existing_settings = get_option( 'ng_ai_settings', [] );
                    $sanitized['api_key'] = $existing_settings['api_key'] ?? '';
                } else {
                    // Save valid API key
                    $sanitized['api_key'] = base64_encode( $input['api_key'] );
                }
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
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ng_save_source' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ng_deal_sources';
        
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
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ng_save_source' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }

        $source_id = intval( $_POST['source_id'] );
        
        global $wpdb;
        $table = $wpdb->prefix . 'ng_deal_sources';
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
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ng_save_source' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }

        $source_id = intval( $_POST['source_id'] );
        
        global $wpdb;
        $table = $wpdb->prefix . 'ng_deal_sources';
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
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ng_save_affiliate' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ng_affiliate_programs';
        
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
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ng_save_affiliate' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }

        $affiliate_id = intval( $_POST['id'] );
        
        global $wpdb;
        $table = $wpdb->prefix . 'ng_affiliate_programs';
        $result = $wpdb->delete( $table, array( 'id' => $affiliate_id ), array( '%d' ) );

        if ( $result !== false ) {
            wp_send_json_success( array( 'message' => __( 'Affiliate program deleted successfully.', 'nomadsguru' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to delete affiliate program.', 'nomadsguru' ) ) );
        }
    }

    /**
     * Handle AJAX request to validate API key
     */
    public function handle_validate_api_key() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'nomadsguru_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nomadsguru' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'nomadsguru' ) ) );
        }

        $api_key = sanitize_text_field( $_POST['api_key'] ?? '' );
        $provider = sanitize_text_field( $_POST['provider'] ?? 'openai' );

        // Debug logging
        error_log("API Key Validation Debug:");
        error_log("Provider: " . $provider);
        error_log("API Key length: " . strlen($api_key));
        error_log("API Key: " . substr($api_key, 0, 10) . "...");

        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 
                'message' => __( 'API key is required', 'nomadsguru' ),
                'field' => 'api_key'
            ) );
        }

        // Modern validation: Test the API key with actual API call
        $test_result = $this->test_api_key_live( $api_key, $provider );
        
        if ( $test_result['success'] ) {
            wp_send_json_success( array( 
                'message' => __( 'API key is valid and working!', 'nomadsguru' ),
                'field' => 'api_key'
            ) );
        } else {
            wp_send_json_error( array( 
                'message' => $test_result['message'],
                'field' => 'api_key'
            ) );
        }
    }

    /**
     * Test API key with live API call
     */
    private function test_api_key_live( $api_key, $provider ) {
        switch ( $provider ) {
            case 'gemini':
                return $this->test_gemini_api_key( $api_key );
            case 'openai':
                return $this->test_openai_api_key( $api_key );
            case 'grok':
                return $this->test_grok_api_key( $api_key );
            case 'perplexity':
                return $this->test_perplexity_api_key( $api_key );
            default:
                return [
                    'success' => false,
                    'message' => __( 'Unknown AI provider', 'nomadsguru' )
                ];
        }
    }

    /**
     * Test Gemini API key with real API call
     */
    private function test_gemini_api_key( $api_key ) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
        
        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Hello']
                    ]
                ]
            ]
        ];
        
        $response = wp_remote_post( $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $api_key
            ],
            'body' => json_encode( $body ),
            'timeout' => 15,
            'method' => 'POST'
        ]);
        
        if ( is_wp_error( $response ) ) {
            error_log('Gemini API test error: ' . $response->get_error_message());
            return [
                'success' => false,
                'message' => __( 'API connection failed: ', 'nomadsguru' ) . $response->get_error_message()
            ];
        }
        
        $http_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        
        error_log('Gemini API response code: ' . $http_code);
        error_log('Gemini API response body: ' . substr($body, 0, 200));
        
        if ( $http_code === 200 ) {
            return [
                'success' => true,
                'message' => __( 'API key is valid and working', 'nomadsguru' )
            ];
        } elseif ( $http_code === 401 ) {
            return [
                'success' => false,
                'message' => __( 'Invalid API key - please check your key and try again', 'nomadsguru' )
            ];
        } elseif ( $http_code === 403 ) {
            return [
                'success' => false,
                'message' => __( 'API key lacks permissions or billing issue', 'nomadsguru' )
            ];
        } elseif ( $http_code === 429 ) {
            return [
                'success' => false,
                'message' => __( 'Rate limit exceeded - please wait and try again', 'nomadsguru' )
            ];
        } else {
            return [
                'success' => false,
                'message' => __( 'API test failed with status ', 'nomadsguru' ) . $http_code . ': ' . substr($body, 0, 100)
            ];
        }
    }

    /**
     * Test OpenAI API key with real API call
     */
    private function test_openai_api_key( $api_key ) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $body = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello']
            ],
            'max_tokens' => 5
        ];
        
        $response = wp_remote_post( $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'body' => json_encode( $body ),
            'timeout' => 15,
            'method' => 'POST'
        ]);
        
        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => __( 'API connection failed: ', 'nomadsguru' ) . $response->get_error_message()
            ];
        }
        
        $http_code = wp_remote_retrieve_response_code( $response );
        
        if ( $http_code === 200 ) {
            return [
                'success' => true,
                'message' => __( 'API key is valid and working', 'nomadsguru' )
            ];
        } elseif ( $http_code === 401 ) {
            return [
                'success' => false,
                'message' => __( 'Invalid API key - please check your key and try again', 'nomadsguru' )
            ];
        } else {
            return [
                'success' => false,
                'message' => __( 'API test failed with status ', 'nomadsguru' ) . $http_code
            ];
        }
    }

    /**
     * Test Grok API key with real API call
     */
    private function test_grok_api_key( $api_key ) {
        $url = 'https://api.x.ai/v1/chat/completions';
        
        $body = [
            'model' => 'grok-beta',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello']
            ],
            'max_tokens' => 5
        ];
        
        $response = wp_remote_post( $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'body' => json_encode( $body ),
            'timeout' => 15,
            'method' => 'POST'
        ]);
        
        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => __( 'API connection failed: ', 'nomadsguru' ) . $response->get_error_message()
            ];
        }
        
        $http_code = wp_remote_retrieve_response_code( $response );
        
        if ( $http_code === 200 ) {
            return [
                'success' => true,
                'message' => __( 'API key is valid and working', 'nomadsguru' )
            ];
        } elseif ( $http_code === 401 ) {
            return [
                'success' => false,
                'message' => __( 'Invalid API key - please check your key and try again', 'nomadsguru' )
            ];
        } else {
            return [
                'success' => false,
                'message' => __( 'API test failed with status ', 'nomadsguru' ) . $http_code
            ];
        }
    }

    /**
     * Test Perplexity API key with real API call
     */
    private function test_perplexity_api_key( $api_key ) {
        $url = 'https://api.perplexity.ai/chat/completions';
        
        $body = [
            'model' => 'llama-3.1-sonar-small-128k-online',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello']
            ],
            'max_tokens' => 5
        ];
        
        $response = wp_remote_post( $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'body' => json_encode( $body ),
            'timeout' => 15,
            'method' => 'POST'
        ]);
        
        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => __( 'API connection failed: ', 'nomadsguru' ) . $response->get_error_message()
            ];
        }
        
        $http_code = wp_remote_retrieve_response_code( $response );
        
        if ( $http_code === 200 ) {
            return [
                'success' => true,
                'message' => __( 'API key is valid and working', 'nomadsguru' )
            ];
        } elseif ( $http_code === 401 ) {
            return [
                'success' => false,
                'message' => __( 'Invalid API key - please check your key and try again', 'nomadsguru' )
            ];
        } else {
            return [
                'success' => false,
                'message' => __( 'API test failed with status ', 'nomadsguru' ) . $http_code
            ];
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

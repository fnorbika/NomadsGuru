<?php
/**
 * Abstract Deal Source
 * 
 * Base class for all deal sources
 * 
 * @package NomadsGuru
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class AbstractDealSource implements DealSourceInterface {
    
    /**
     * Source configuration
     * @var array
     */
    protected $config = [];
    
    /**
     * Source name
     * @var string
     */
    protected $name = '';
    
    /**
     * Source type
     * @var string
     */
    protected $type = '';
    
    /**
     * Constructor
     * 
     * @param array $config Source configuration
     */
    public function __construct( $config = [] ) {
        $this->config = array_merge( $this->get_default_config(), $config );
    }
    
    /**
     * Get default configuration
     * 
     * @return array Default config
     */
    protected function get_default_config() {
        return [
            'active' => true,
            'fetch_interval' => 3600, // 1 hour
            'max_deals' => 50,
            'timeout' => 30,
            'retry_count' => 3,
            'retry_delay' => 5
        ];
    }
    
    /**
     * Get source name
     * 
     * @return string Source name
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Get source type
     * 
     * @return string Source type
     */
    public function get_type() {
        return $this->type;
    }
    
    /**
     * Check if source is active
     * 
     * @return bool True if active
     */
    public function is_active() {
        return ! empty( $this->config['active'] );
    }
    
    /**
     * Get source configuration
     * 
     * @return array Configuration array
     */
    public function get_config() {
        return $this->config;
    }
    
    /**
     * Validate source configuration
     * 
     * @return bool True if valid
     */
    public function validate_config() {
        return ! empty( $this->config['active'] ) && $this->is_configured();
    }
    
    /**
     * Check if source is properly configured
     * 
     * @return bool True if configured
     */
    abstract protected function is_configured();
    
    /**
     * Get last fetch timestamp
     * 
     * @return int Timestamp or 0 if never fetched
     */
    public function get_last_fetch() {
        return get_option( 'ng_source_last_fetch_' . $this->name, 0 );
    }
    
    /**
     * Update last fetch timestamp
     * 
     * @return void
     */
    public function update_last_fetch() {
        update_option( 'ng_source_last_fetch_' . $this->name, time() );
    }
    
    /**
     * Log source activity
     * 
     * @param string $message Log message
     * @param string $level Log level (info, warning, error)
     * @return void
     */
    protected function log( $message, $level = 'info' ) {
        $log_entry = [
            'timestamp' => current_time( 'mysql' ),
            'source' => $this->name,
            'level' => $level,
            'message' => $message
        ];
        
        $logs = get_option( 'ng_source_logs', [] );
        array_unshift( $logs, $log_entry );
        
        // Keep only last 1000 entries
        $logs = array_slice( $logs, 0, 1000 );
        
        update_option( 'ng_source_logs', $logs );
    }
    
    /**
     * Make HTTP request with retry logic
     * 
     * @param string $url Request URL
     * @param array $args Request args
     * @return array|WP_Error Response
     */
    protected function make_request( $url, $args = [] ) {
        $default_args = [
            'timeout' => $this->config['timeout'],
            'headers' => [
                'User-Agent' => 'NomadsGuru-Plugin/' . NOMADSGURU_VERSION
            ]
        ];
        
        $args = array_merge( $default_args, $args );
        
        $retry_count = 0;
        $last_error = null;
        
        while ( $retry_count < $this->config['retry_count'] ) {
            $response = wp_remote_get( $url, $args );
            
            if ( ! is_wp_error( $response ) ) {
                return $response;
            }
            
            $last_error = $response;
            $retry_count++;
            
            if ( $retry_count < $this->config['retry_count'] ) {
                $this->log( "Request failed, retrying in {$this->config['retry_delay']}s: " . $response->get_error_message(), 'warning' );
                sleep( $this->config['retry_delay'] );
            }
        }
        
        $this->log( "Request failed after {$retry_count} attempts: " . $last_error->get_error_message(), 'error' );
        return $last_error;
    }
    
    /**
     * Normalize deal data
     * 
     * @param array $raw_data Raw deal data
     * @return array Normalized deal data
     */
    protected function normalize_deal( $raw_data ) {
        return [
            'title' => sanitize_text_field( $raw_data['title'] ?? '' ),
            'description' => sanitize_textarea_field( $raw_data['description'] ?? '' ),
            'destination' => sanitize_text_field( $raw_data['destination'] ?? '' ),
            'original_price' => floatval( $raw_data['original_price'] ?? 0 ),
            'discounted_price' => floatval( $raw_data['discounted_price'] ?? 0 ),
            'currency' => sanitize_text_field( $raw_data['currency'] ?? 'USD' ),
            'travel_start' => sanitize_text_field( $raw_data['travel_start'] ?? '' ),
            'travel_end' => sanitize_text_field( $raw_data['travel_end'] ?? '' ),
            'booking_url' => esc_url_raw( $raw_data['booking_url'] ?? '' ),
            'source' => $this->name,
            'source_type' => $this->type,
            'raw_data' => maybe_serialize( $raw_data ),
            'created_at' => current_time( 'mysql' )
        ];
    }
    
    /**
     * Save deals to database
     * 
     * @param array $deals Array of normalized deals
     * @return int Number of deals saved
     */
    protected function save_deals( $deals ) {
        global $wpdb;
        
        $saved_count = 0;
        $table_name = $wpdb->prefix . 'ng_raw_deals';
        
        foreach ( $deals as $deal ) {
            // Check if deal already exists (based on title and destination)
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM $table_name WHERE title = %s AND destination = %s AND booking_url = %s",
                $deal['title'], $deal['destination'], $deal['booking_url']
            ) );
            
            if ( ! $exists ) {
                $result = $wpdb->insert( $table_name, $deal );
                
                if ( $result !== false ) {
                    $saved_count++;
                } else {
                    $this->log( "Failed to save deal: " . $deal['title'], 'error' );
                }
            }
        }
        
        $this->log( "Saved {$saved_count} new deals from source", 'info' );
        return $saved_count;
    }
}

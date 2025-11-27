<?php
/**
 * API Deal Source
 * 
 * Generic API-based deal source
 * 
 * @package NomadsGuru
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ApiDealSource extends AbstractDealSource {
    
    /**
     * Constructor
     * 
     * @param array $config Source configuration
     */
    public function __construct( $config = [] ) {
        $this->name = $config['name'] ?? 'api_source';
        $this->type = 'api';
        parent::__construct( $config );
    }
    
    /**
     * Fetch deals from API
     * 
     * @return array Array of deals
     */
    public function fetch_deals() {
        $api_url = $this->config['api_url'] ?? '';
        $api_key = $this->config['api_key'] ?? '';
        $method = $this->config['method'] ?? 'GET';
        
        if ( empty( $api_url ) ) {
            $this->log( "API URL not configured", 'error' );
            return [];
        }
        
        // Build request arguments
        $args = $this->build_request_args( $api_key, $method );
        
        // Make API request
        if ( strtoupper( $method ) === 'POST' ) {
            $response = wp_remote_post( $api_url, $args );
        } else {
            $response = wp_remote_get( $api_url, $args );
        }
        
        if ( is_wp_error( $response ) ) {
            $this->log( "API request failed: " . $response->get_error_message(), 'error' );
            return [];
        }
        
        $body = wp_remote_retrieve_body( $response );
        $status_code = wp_remote_retrieve_response_code( $response );
        
        if ( $status_code !== 200 ) {
            $this->log( "API returned status {$status_code}: {$body}", 'error' );
            return [];
        }
        
        // Parse API response
        $data = json_decode( $body, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $this->log( "Failed to parse API JSON: " . json_last_error_msg(), 'error' );
            return [];
        }
        
        // Extract deals from response
        $deals = $this->extract_deals_from_response( $data );
        
        $this->log( "Fetched " . count( $deals ) . " deals from API", 'info' );
        $this->update_last_fetch();
        
        return $deals;
    }
    
    /**
     * Build API request arguments
     * 
     * @param string $api_key API key
     * @param string $method HTTP method
     * @return array Request arguments
     */
    private function build_request_args( $api_key, $method ) {
        $args = [
            'timeout' => $this->config['timeout'],
            'headers' => [
                'User-Agent' => 'NomadsGuru-Plugin/' . NOMADSGURU_VERSION,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]
        ];
        
        // Add API key to headers or parameters
        if ( ! empty( $api_key ) ) {
            $auth_type = $this->config['auth_type'] ?? 'header';
            
            switch ( $auth_type ) {
                case 'header':
                    $args['headers']['Authorization'] = 'Bearer ' . $api_key;
                    break;
                case 'api_key_header':
                    $args['headers']['X-API-Key'] = $api_key;
                    break;
                case 'query_param':
                    $this->config['api_url'] = add_query_arg( 'api_key', $api_key, $this->config['api_url'] );
                    break;
            }
        }
        
        // Add POST body if needed
        if ( strtoupper( $method ) === 'POST' && ! empty( $this->config['post_data'] ) ) {
            $args['body'] = json_encode( $this->config['post_data'] );
        }
        
        return $args;
    }
    
    /**
     * Extract deals from API response
     * 
     * @param array $response_data API response data
     * @return array Array of deals
     */
    private function extract_deals_from_response( $response_data ) {
        $deals = [];
        $data_path = $this->config['data_path'] ?? '';
        
        // Navigate to deals data using dot notation
        $deals_data = $this->get_nested_value( $response_data, $data_path );
        
        if ( ! is_array( $deals_data ) ) {
            $this->log( "No deals data found in API response at path: {$data_path}", 'warning' );
            return [];
        }
        
        $max_deals = $this->config['max_deals'];
        $deal_count = 0;
        
        foreach ( $deals_data as $raw_deal ) {
            if ( $deal_count >= $max_deals ) {
                break;
            }
            
            $deal_data = $this->parse_api_deal( $raw_deal );
            
            if ( $deal_data && $this->is_valid_deal( $deal_data ) ) {
                $deals[] = $this->normalize_deal( $deal_data );
                $deal_count++;
            }
        }
        
        return $deals;
    }
    
    /**
     * Get nested value from array using dot notation
     * 
     * @param array $array Source array
     * @param string $path Dot notation path
     * @return mixed Found value or null
     */
    private function get_nested_value( $array, $path ) {
        if ( empty( $path ) ) {
            return $array;
        }
        
        $keys = explode( '.', $path );
        $value = $array;
        
        foreach ( $keys as $key ) {
            if ( ! is_array( $value ) || ! isset( $value[$key] ) ) {
                return null;
            }
            $value = $value[$key];
        }
        
        return $value;
    }
    
    /**
     * Parse deal from API response
     * 
     * @param array $raw_deal Raw deal data from API
     * @return array|null Parsed deal data
     */
    private function parse_api_deal( $raw_deal ) {
        $field_mapping = $this->config['field_mapping'] ?? [];
        
        $deal_data = [];
        
        // Map fields according to configuration
        foreach ( $field_mapping as $target_field => $source_field ) {
            $value = $this->get_nested_value( $raw_deal, $source_field );
            
            if ( $value !== null ) {
                $deal_data[$target_field] = $value;
            }
        }
        
        return empty( $deal_data ) ? null : $deal_data;
    }
    
    /**
     * Check if deal is valid
     * 
     * @param array $deal_data Deal data
     * @return bool True if valid
     */
    private function is_valid_deal( $deal_data ) {
        // Must have title
        if ( empty( $deal_data['title'] ) ) {
            return false;
        }
        
        // Must have some price information
        $has_price = ! empty( $deal_data['original_price'] ) || ! empty( $deal_data['discounted_price'] );
        
        if ( ! $has_price ) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if source is properly configured
     * 
     * @return bool True if configured
     */
    protected function is_configured() {
        $required = ['api_url', 'field_mapping'];
        
        foreach ( $required as $key ) {
            if ( empty( $this->config[$key] ) ) {
                return false;
            }
        }
        
        // Validate API URL
        if ( ! filter_var( $this->config['api_url'], FILTER_VALIDATE_URL ) ) {
            return false;
        }
        
        // Validate field mapping
        $required_fields = ['title'];
        $field_mapping = $this->config['field_mapping'];
        
        foreach ( $required_fields as $field ) {
            if ( empty( $field_mapping[$field] ) ) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get predefined API configurations
     * 
     * @return array Predefined configurations
     */
    public static function get_predefined_configs() {
        return [
            'skyscanner' => [
                'name' => 'skyscanner_api',
                'api_url' => 'https://partners.api.skyscanner.net/apiservices/v3/flights/indicative',
                'auth_type' => 'header',
                'data_path' => 'deals',
                'field_mapping' => [
                    'title' => 'title',
                    'description' => 'description',
                    'destination' => 'destination.city',
                    'original_price' => 'price.original',
                    'discounted_price' => 'price.discounted',
                    'currency' => 'price.currency',
                    'travel_start' => 'dates.departure',
                    'travel_end' => 'dates.return',
                    'booking_url' => 'deeplink'
                ]
            ],
            'booking_com' => [
                'name' => 'booking_com_api',
                'api_url' => 'https://distribution-xml.booking.com/json/bookings.getHotels',
                'auth_type' => 'query_param',
                'data_path' => 'result',
                'field_mapping' => [
                    'title' => 'hotel_name',
                    'description' => 'hotel_description',
                    'destination' => 'city',
                    'original_price' => 'price',
                    'discounted_price' => 'discounted_price',
                    'currency' => 'currency_code',
                    'booking_url' => 'hotel_url'
                ]
            ],
            'google_flights' => [
                'name' => 'google_flights_api',
                'api_url' => 'https://serpapi.com/search.json',
                'auth_type' => 'query_param',
                'data_path' => 'deals_flights',
                'field_mapping' => [
                    'title' => 'title',
                    'description' => 'description',
                    'destination' => 'destination',
                    'original_price' => 'price',
                    'discounted_price' => 'price',
                    'currency' => 'currency',
                    'travel_start' => 'departure_date',
                    'travel_end' => 'return_date',
                    'booking_url' => 'link'
                ]
            ]
        ];
    }
}

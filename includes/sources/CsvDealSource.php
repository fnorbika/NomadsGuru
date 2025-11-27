<?php
/**
 * CSV Deal Source
 * 
 * Manual CSV file deal source
 * 
 * @package NomadsGuru
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CsvDealSource extends AbstractDealSource {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'csv_manual';
        $this->type = 'csv';
        parent::__construct();
    }
    
    /**
     * Fetch deals from CSV file
     * 
     * @return array Array of deals
     */
    public function fetch_deals() {
        $csv_file = $this->get_csv_file_path();
        
        if ( ! file_exists( $csv_file ) ) {
            $this->log( "CSV file not found: {$csv_file}", 'error' );
            return [];
        }
        
        $deals = [];
        $handle = fopen( $csv_file, 'r' );
        
        if ( ! $handle ) {
            $this->log( "Failed to open CSV file: {$csv_file}", 'error' );
            return [];
        }
        
        // Skip header row
        $headers = fgetcsv( $handle );
        
        if ( ! $headers ) {
            $this->log( "CSV file is empty or invalid: {$csv_file}", 'error' );
            fclose( $handle );
            return [];
        }
        
        $row_count = 0;
        $max_deals = $this->config['max_deals'];
        
        while ( ( $row = fgetcsv( $handle ) ) !== false && $row_count < $max_deals ) {
            $deal_data = $this->parse_csv_row( $headers, $row );
            
            if ( $deal_data ) {
                $deals[] = $this->normalize_deal( $deal_data );
                $row_count++;
            }
        }
        
        fclose( $handle );
        
        $this->log( "Fetched {$row_count} deals from CSV file", 'info' );
        $this->update_last_fetch();
        
        return $deals;
    }
    
    /**
     * Get CSV file path
     * 
     * @return string CSV file path
     */
    private function get_csv_file_path() {
        // Check for custom CSV file in settings
        $settings = get_option( 'ng_source_settings', [] );
        $custom_file = $settings['csv_file_path'] ?? '';
        
        if ( ! empty( $custom_file ) && file_exists( $custom_file ) ) {
            return $custom_file;
        }
        
        // Default CSV file
        return NOMADSGURU_PLUGIN_DIR . 'data/manual-deals.csv';
    }
    
    /**
     * Parse CSV row into deal data
     * 
     * @param array $headers CSV headers
     * @param array $row CSV row data
     * @return array|null Deal data or null if invalid
     */
    private function parse_csv_row( $headers, $row ) {
        if ( count( $headers ) !== count( $row ) ) {
            return null;
        }
        
        $data = array_combine( $headers, $row );
        
        // Validate required fields
        $required_fields = ['title', 'destination', 'original_price', 'discounted_price'];
        foreach ( $required_fields as $field ) {
            if ( empty( $data[$field] ) ) {
                return null;
            }
        }
        
        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'destination' => $data['destination'],
            'original_price' => floatval( $data['original_price'] ),
            'discounted_price' => floatval( $data['discounted_price'] ),
            'currency' => $data['currency'] ?? 'USD',
            'travel_start' => $data['travel_start'] ?? '',
            'travel_end' => $data['travel_end'] ?? '',
            'booking_url' => $data['booking_url'] ?? ''
        ];
    }
    
    /**
     * Check if source is properly configured
     * 
     * @return bool True if configured
     */
    protected function is_configured() {
        return file_exists( $this->get_csv_file_path() );
    }
    
    /**
     * Create sample CSV file
     * 
     * @return void
     */
    public function create_sample_csv() {
        $csv_file = NOMADSGURU_PLUGIN_DIR . 'data/manual-deals.csv';
        
        // Create data directory if it doesn't exist
        $data_dir = dirname( $csv_file );
        if ( ! file_exists( $data_dir ) ) {
            wp_mkdir_p( $data_dir );
        }
        
        $sample_data = [
            ['title', 'destination', 'original_price', 'discounted_price', 'currency', 'travel_start', 'travel_end', 'booking_url', 'description'],
            ['Paris Getaway Package', 'Paris, France', '899', '499', 'USD', '2025-06-01', '2025-06-07', 'https://example.com/paris', 'Amazing 6-day Paris adventure with Eiffel Tower tour and Seine cruise'],
            ['Tokyo Adventure', 'Tokyo, Japan', '1299', '799', 'USD', '2025-07-15', '2025-07-22', 'https://example.com/tokyo', 'Experience the best of Tokyo with guided tours and traditional cultural experiences'],
            ['London Escape', 'London, UK', '699', '399', 'GBP', '2025-05-10', '2025-05-17', 'https://example.com/london', 'Historic London tour including Tower of London, Buckingham Palace, and West End shows'],
            ['Bali Paradise', 'Bali, Indonesia', '1499', '899', 'USD', '2025-08-20', '2025-08-27', 'https://example.com/bali', 'Tropical paradise with beach resort, spa treatments, and temple tours'],
            ['New York City Break', 'New York, USA', '1199', '699', 'USD', '2025-09-05', '2025-09-12', 'https://example.com/nyc', 'Big Apple adventure with Broadway shows, Statue of Liberty, and Central Park tours'],
            ['Rome Historical Tour', 'Rome, Italy', '999', '599', 'EUR', '2025-10-15', '2025-10-22', 'https://example.com/rome', 'Ancient Rome experience with Colosseum, Vatican, and authentic Italian cuisine'],
            ['Dubai Luxury', 'Dubai, UAE', '1899', '1199', 'AED', '2025-11-01', '2025-11-08', 'https://example.com/dubai', 'Luxury Dubai experience with Burj Khalifa, desert safari, and shopping festivals'],
            ['Sydney Harbour', 'Sydney, Australia', '1599', '999', 'AUD', '2025-12-10', '2025-12-17', 'https://example.com/sydney', 'Australian adventure with Opera House, Harbour Bridge, and Bondi Beach experience']
        ];
        
        $handle = fopen( $csv_file, 'w' );
        if ( $handle ) {
            foreach ( $sample_data as $row ) {
                fputcsv( $handle, $row );
            }
            fclose( $handle );
            $this->log( "Sample CSV file created: {$csv_file}", 'info' );
        }
    }
}

<?php
/**
 * Deal Sources Manager
 * 
 * Manages all deal sources and coordinates deal fetching
 * 
 * @package NomadsGuru
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NomadsGuru_Deal_Sources {
    
    /**
     * Singleton instance
     * @var NomadsGuru_Deal_Sources
     */
    private static $instance = null;
    
    /**
     * Registered sources
     * @var array
     */
    private $sources = [];
    
    /**
     * Get singleton instance
     * 
     * @return NomadsGuru_Deal_Sources
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
        $this->init();
    }
    
    /**
     * Initialize deal sources
     */
    private function init() {
        $this->register_core_sources();
        $this->register_custom_sources();
        $this->create_database_tables();
    }
    
    /**
     * Register core deal sources
     */
    private function register_core_sources() {
        // CSV Manual Source
        $this->register_source( new CsvDealSource() );
        
        // Register predefined API sources
        $api_configs = ApiDealSource::get_predefined_configs();
        foreach ( $api_configs as $config ) {
            $settings = get_option( 'ng_source_settings', [] );
            $source_config = array_merge( $config, $settings[$config['name']] ?? [] );
            
            if ( ! empty( $source_config['api_key'] ) ) {
                $this->register_source( new ApiDealSource( $source_config ) );
            }
        }
        
        // Register predefined scraper sources
        $scraper_configs = WebScraperSource::get_predefined_configs();
        foreach ( $scraper_configs as $config ) {
            $settings = get_option( 'ng_source_settings', [] );
            $source_config = array_merge( $config, $settings[$config['name']] ?? [] );
            
            $this->register_source( new WebScraperSource( $source_config ) );
        }
    }
    
    /**
     * Register custom sources from settings
     */
    private function register_custom_sources() {
        $settings = get_option( 'ng_source_settings', [] );
        $custom_sources = $settings['custom_sources'] ?? [];
        
        foreach ( $custom_sources as $source_config ) {
            if ( empty( $source_config['active'] ) ) {
                continue;
            }
            
            $source = $this->create_source_from_config( $source_config );
            if ( $source ) {
                $this->register_source( $source );
            }
        }
    }
    
    /**
     * Create source from configuration
     * 
     * @param array $config Source configuration
     * @return DealSourceInterface|null Source instance or null
     */
    private function create_source_from_config( $config ) {
        $type = $config['type'] ?? '';
        
        switch ( $type ) {
            case 'rss':
                return new RssDealSource( $config );
            case 'api':
                return new ApiDealSource( $config );
            case 'scraper':
                return new WebScraperSource( $config );
            default:
                return null;
        }
    }
    
    /**
     * Register a deal source
     * 
     * @param DealSourceInterface $source Source instance
     */
    public function register_source( DealSourceInterface $source ) {
        if ( $source->is_active() && $source->validate_config() ) {
            $this->sources[$source->get_name()] = $source;
        }
    }
    
    /**
     * Get all registered sources
     * 
     * @return array Registered sources
     */
    public function get_sources() {
        return $this->sources;
    }
    
    /**
     * Get source by name
     * 
     * @param string $name Source name
     * @return DealSourceInterface|null Source instance or null
     */
    public function get_source( $name ) {
        return $this->sources[$name] ?? null;
    }
    
    /**
     * Fetch deals from all active sources
     * 
     * @return array Fetch results
     */
    public function fetch_all_deals() {
        $results = [
            'total_deals' => 0,
            'sources_processed' => 0,
            'sources_failed' => 0,
            'details' => []
        ];
        
        foreach ( $this->sources as $name => $source ) {
            $source_result = $this->fetch_from_source( $source );
            
            $results['details'][$name] = $source_result;
            $results['total_deals'] += $source_result['deals_count'];
            
            if ( $source_result['success'] ) {
                $results['sources_processed']++;
            } else {
                $results['sources_failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Fetch deals from a specific source
     * 
     * @param DealSourceInterface $source Source instance
     * @return array Fetch result
     */
    private function fetch_from_source( DealSourceInterface $source ) {
        $start_time = microtime( true );
        
        try {
            $deals = $source->fetch_deals();
            $deals_count = count( $deals );
            
            // Save deals to database
            if ( $deals_count > 0 ) {
                $saved_count = $this->save_deals_to_db( $deals );
            } else {
                $saved_count = 0;
            }
            
            $execution_time = round( microtime( true ) - $start_time, 2 );
            
            return [
                'success' => true,
                'deals_count' => $deals_count,
                'saved_count' => $saved_count,
                'execution_time' => $execution_time,
                'message' => "Successfully fetched {$deals_count} deals"
            ];
            
        } catch ( Exception $e ) {
            $execution_time = round( microtime( true ) - $start_time, 2 );
            
            return [
                'success' => false,
                'deals_count' => 0,
                'saved_count' => 0,
                'execution_time' => $execution_time,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Save deals to database
     * 
     * @param array $deals Array of normalized deals
     * @return int Number of deals saved
     */
    private function save_deals_to_db( $deals ) {
        global $wpdb;
        
        $saved_count = 0;
        $table_name = $wpdb->prefix . 'ng_raw_deals';
        
        foreach ( $deals as $deal ) {
            // Check if deal already exists
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM $table_name WHERE title = %s AND destination = %s AND booking_url = %s",
                $deal['title'], $deal['destination'], $deal['booking_url']
            ) );
            
            if ( ! $exists ) {
                $result = $wpdb->insert( $table_name, $deal );
                
                if ( $result !== false ) {
                    $saved_count++;
                }
            }
        }
        
        return $saved_count;
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ng_raw_deals';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title text NOT NULL,
            description text,
            destination varchar(255) DEFAULT '',
            original_price decimal(10,2) DEFAULT 0,
            discounted_price decimal(10,2) DEFAULT 0,
            currency varchar(10) DEFAULT 'USD',
            travel_start date DEFAULT NULL,
            travel_end date DEFAULT NULL,
            booking_url text NOT NULL,
            source varchar(100) DEFAULT '',
            source_type varchar(50) DEFAULT '',
            raw_data longtext,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_source (source),
            KEY idx_status (status),
            KEY idx_destination (destination),
            KEY idx_created_at (created_at)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    
    /**
     * Get deals from database
     * 
     * @param array $args Query arguments
     * @return array Deals data
     */
    public function get_deals( $args = [] ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ng_raw_deals';
        $defaults = [
            'status' => 'pending',
            'limit' => 50,
            'offset' => 0,
            'order' => 'DESC',
            'orderby' => 'created_at'
        ];
        
        $args = array_merge( $defaults, $args );
        
        $where = "WHERE 1=1";
        $limit = '';
        
        if ( ! empty( $args['status'] ) ) {
            $where .= $wpdb->prepare( " AND status = %s", $args['status'] );
        }
        
        if ( ! empty( $args['source'] ) ) {
            $where .= $wpdb->prepare( " AND source = %s", $args['source'] );
        }
        
        if ( ! empty( $args['destination'] ) ) {
            $where .= $wpdb->prepare( " AND destination LIKE %s", '%' . $wpdb->esc_like( $args['destination'] ) . '%' );
        }
        
        if ( $args['limit'] > 0 ) {
            $limit = $wpdb->prepare( "LIMIT %d OFFSET %d", $args['limit'], $args['offset'] );
        }
        
        $sql = "SELECT * FROM $table_name $where ORDER BY {$args['orderby']} {$args['order']} $limit";
        
        return $wpdb->get_results( $sql, ARRAY_A );
    }
    
    /**
     * Update deal status
     * 
     * @param int $deal_id Deal ID
     * @param string $status New status
     * @return bool Success status
     */
    public function update_deal_status( $deal_id, $status ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ng_raw_deals';
        
        $result = $wpdb->update(
            $table_name,
            ['status' => $status, 'updated_at' => current_time( 'mysql' )],
            ['id' => $deal_id],
            ['%s', '%s'],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Get source statistics
     * 
     * @return array Statistics data
     */
    public function get_source_statistics() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ng_raw_deals';
        
        $stats = $wpdb->get_results("
            SELECT 
                source,
                source_type,
                COUNT(*) as total_deals,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_deals,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_deals,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_deals,
                MAX(created_at) as last_fetch
            FROM $table_name
            GROUP BY source, source_type
            ORDER BY total_deals DESC
        ", ARRAY_A );
        
        return $stats;
    }
    
    /**
     * Initialize CSV source with sample data
     */
    public function initialize_csv_source() {
        $csv_source = $this->get_source( 'csv_manual' );
        
        if ( $csv_source && method_exists( $csv_source, 'create_sample_csv' ) ) {
            $csv_source->create_sample_csv();
        }
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

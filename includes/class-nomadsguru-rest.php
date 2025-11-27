<?php

/**
 * REST API for NomadsGuru
 * 
 * @package NomadsGuru
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NomadsGuru_REST {

    /**
     * Single instance of the class
     * @var NomadsGuru_REST|null
     */
    private static $instance = null;

    /**
     * API namespace
     * @var string
     */
    private $namespace = 'nomadsguru/v1';

    /**
     * Get singleton instance
     * 
     * @return NomadsGuru_REST
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
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register REST routes
     */
    public function register_routes() {
        // Deals endpoints
        register_rest_route( $this->namespace, '/deals', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_deals' ),
                'permission_callback' => array( $this, 'check_permissions' )
            ),
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'create_deal' ),
                'permission_callback' => array( $this, 'check_permissions' )
            )
        ) );

        register_rest_route( $this->namespace, '/deals/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_deal' ),
                'permission_callback' => array( $this, 'check_permissions' )
            ),
            array(
                'methods' => 'PUT',
                'callback' => array( $this, 'update_deal' ),
                'permission_callback' => array( $this, 'check_permissions' )
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array( $this, 'delete_deal' ),
                'permission_callback' => array( $this, 'check_permissions' )
            )
        ) );

        // Sources endpoints
        register_rest_route( $this->namespace, '/sources', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_sources' ),
                'permission_callback' => array( $this, 'check_permissions' )
            ),
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'create_source' ),
                'permission_callback' => array( $this, 'check_permissions' )
            )
        ) );

        register_rest_route( $this->namespace, '/sources/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_source' ),
                'permission_callback' => array( $this, 'check_permissions' )
            ),
            array(
                'methods' => 'PUT',
                'callback' => array( $this, 'update_source' ),
                'permission_callback' => array( $this, 'check_permissions' )
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array( $this, 'delete_source' ),
                'permission_callback' => array( $this, 'check_permissions' )
            )
        ) );

        // Affiliates endpoints
        register_rest_route( $this->namespace, '/affiliates', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_affiliates' ),
                'permission_callback' => array( $this, 'check_permissions' )
            ),
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'create_affiliate' ),
                'permission_callback' => array( $this, 'check_permissions' )
            )
        ) );

        register_rest_route( $this->namespace, '/affiliates/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_affiliate' ),
                'permission_callback' => array( $this, 'check_permissions' )
            ),
            array(
                'methods' => 'PUT',
                'callback' => array( $this, 'update_affiliate' ),
                'permission_callback' => array( $this, 'check_permissions' )
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array( $this, 'delete_affiliate' ),
                'permission_callback' => array( $this, 'check_permissions' )
            )
        ) );

        // Config endpoints
        register_rest_route( $this->namespace, '/config', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_config' ),
                'permission_callback' => array( $this, 'check_permissions' )
            ),
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'update_config' ),
                'permission_callback' => array( $this, 'check_permissions' )
            )
        ) );

        // Stats endpoints
        register_rest_route( $this->namespace, '/stats', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_stats' ),
                'permission_callback' => array( $this, 'check_permissions' )
            )
        ) );

        // Queue endpoints
        register_rest_route( $this->namespace, '/queue', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_queue' ),
                'permission_callback' => array( $this, 'check_permissions' )
            )
        ) );

        // Test AI endpoint
        register_rest_route( $this->namespace, '/test-ai', array(
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'test_ai_connection' ),
                'permission_callback' => array( $this, 'check_permissions' )
            )
        ) );
    }

    /**
     * Check API permissions
     * 
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function check_permissions( $request ) {
        return current_user_can( 'manage_options' );
    }

    /**
     * Get deals
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_deals( $request ) {
        global $wpdb;
        
        $page = $request->get_param( 'page' ) ?: 1;
        $per_page = $request->get_param( 'per_page' ) ?: 20;
        $status = $request->get_param( 'status' );
        $source_id = $request->get_param( 'source_id' );
        
        $offset = ( $page - 1 ) * $per_page;
        $table = $wpdb->prefix . 'ng_raw_deals';
        
        $where = "1=1";
        $where_args = [];
        
        if ( $status ) {
            $where .= " AND status = %s";
            $where_args[] = $status;
        }
        
        if ( $source_id ) {
            $where .= " AND source_id = %d";
            $where_args[] = $source_id;
        }
        
        // Get total count
        $total_query = "SELECT COUNT(*) FROM $table WHERE $where";
        $total = $wpdb->get_var( $wpdb->prepare( $total_query, $where_args ) );
        
        // Get deals
        $query = "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $deals = $wpdb->get_results( $wpdb->prepare( $query, array_merge( $where_args, [ $per_page, $offset ] ) ) );
        
        // Format deals
        $formatted_deals = array_map( function( $deal ) {
            return [
                'id' => intval( $deal->id ),
                'source_id' => intval( $deal->source_id ),
                'title' => $deal->title,
                'description' => $deal->description,
                'destination' => $deal->destination,
                'price' => floatval( $deal->price ),
                'original_price' => floatval( $deal->original_price ),
                'discount_percentage' => floatval( $deal->discount_percentage ),
                'deal_url' => $deal->deal_url,
                'image_url' => $deal->image_url,
                'valid_until' => $deal->valid_until,
                'ai_score' => floatval( $deal->ai_score ),
                'ai_reasoning' => $deal->ai_reasoning,
                'status' => $deal->status,
                'post_id' => $deal->post_id ? intval( $deal->post_id ) : null,
                'created_at' => $deal->created_at,
                'updated_at' => $deal->updated_at
            ];
        }, $deals );
        
        return new WP_REST_Response( [
            'data' => $formatted_deals,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => intval( $total ),
                'total_pages' => ceil( $total / $per_page )
            ]
        ] );
    }

    /**
     * Get single deal
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_deal( $request ) {
        global $wpdb;
        
        $id = intval( $request['id'] );
        $table = $wpdb->prefix . 'ng_raw_deals';
        
        $deal = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
        
        if ( ! $deal ) {
            return new WP_Error( 'not_found', 'Deal not found', [ 'status' => 404 ] );
        }
        
        return new WP_REST_Response( [
            'id' => intval( $deal->id ),
            'source_id' => intval( $deal->source_id ),
            'title' => $deal->title,
            'description' => $deal->description,
            'destination' => $deal->destination,
            'price' => floatval( $deal->price ),
            'original_price' => floatval( $deal->original_price ),
            'discount_percentage' => floatval( $deal->discount_percentage ),
            'deal_url' => $deal->deal_url,
            'image_url' => $deal->image_url,
            'valid_until' => $deal->valid_until,
            'ai_score' => floatval( $deal->ai_score ),
            'ai_reasoning' => $deal->ai_reasoning,
            'status' => $deal->status,
            'post_id' => $deal->post_id ? intval( $deal->post_id ) : null,
            'created_at' => $deal->created_at,
            'updated_at' => $deal->updated_at
        ] );
    }

    /**
     * Create deal
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function create_deal( $request ) {
        global $wpdb;
        
        $data = $this->prepare_deal_data( $request );
        
        if ( is_wp_error( $data ) ) {
            return $data;
        }
        
        $table = $wpdb->prefix . 'ng_raw_deals';
        $result = $wpdb->insert( $table, $data );
        
        if ( $result === false ) {
            return new WP_Error( 'db_error', 'Failed to create deal', [ 'status' => 500 ] );
        }
        
        $deal_id = $wpdb->insert_id;
        
        return new WP_REST_Response( [
            'id' => $deal_id,
            'message' => 'Deal created successfully'
        ], 201 );
    }

    /**
     * Update deal
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function update_deal( $request ) {
        global $wpdb;
        
        $id = intval( $request['id'] );
        $data = $this->prepare_deal_data( $request );
        
        if ( is_wp_error( $data ) ) {
            return $data;
        }
        
        $table = $wpdb->prefix . 'ng_raw_deals';
        $result = $wpdb->update( $table, $data, [ 'id' => $id ], [ '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%f', '%s', '%s', '%d' ], [ '%d' ] );
        
        if ( $result === false ) {
            return new WP_Error( 'db_error', 'Failed to update deal', [ 'status' => 500 ] );
        }
        
        return new WP_REST_Response( [
            'id' => $id,
            'message' => 'Deal updated successfully'
        ] );
    }

    /**
     * Delete deal
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function delete_deal( $request ) {
        global $wpdb;
        
        $id = intval( $request['id'] );
        $table = $wpdb->prefix . 'ng_raw_deals';
        
        $result = $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
        
        if ( $result === false ) {
            return new WP_Error( 'db_error', 'Failed to delete deal', [ 'status' => 500 ] );
        }
        
        return new WP_REST_Response( [
            'id' => $id,
            'message' => 'Deal deleted successfully'
        ] );
    }

    /**
     * Get sources
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_sources( $request ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ng_deal_sources';
        $sources = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC" );
        
        $formatted_sources = array_map( function( $source ) {
            return [
                'id' => intval( $source->id ),
                'source_type' => $source->source_type,
                'source_name' => $source->source_name,
                'website_url' => $source->website_url,
                'rss_feed' => $source->rss_feed,
                'is_active' => (bool) $source->is_active,
                'last_sync' => $source->last_sync,
                'sync_interval_minutes' => intval( $source->sync_interval_minutes ),
                'created_at' => $source->created_at,
                'updated_at' => $source->updated_at
            ];
        }, $sources );
        
        return new WP_REST_Response( $formatted_sources );
    }

    /**
     * Create source
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function create_source( $request ) {
        global $wpdb;
        
        $data = $this->prepare_source_data( $request );
        
        if ( is_wp_error( $data ) ) {
            return $data;
        }
        
        $table = $wpdb->prefix . 'ng_deal_sources';
        $result = $wpdb->insert( $table, $data );
        
        if ( $result === false ) {
            return new WP_Error( 'db_error', 'Failed to create source', [ 'status' => 500 ] );
        }
        
        $source_id = $wpdb->insert_id;
        
        return new WP_REST_Response( [
            'id' => $source_id,
            'message' => 'Source created successfully'
        ], 201 );
    }

    /**
     * Get affiliates
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_affiliates( $request ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ng_affiliate_programs';
        $affiliates = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC" );
        
        $formatted_affiliates = array_map( function( $affiliate ) {
            return [
                'id' => intval( $affiliate->id ),
                'program_name' => $affiliate->program_name,
                'program_type' => $affiliate->program_type,
                'api_endpoint' => $affiliate->api_endpoint,
                'url_pattern' => $affiliate->url_pattern,
                'commission_rate' => floatval( $affiliate->commission_rate ),
                'is_active' => (bool) $affiliate->is_active,
                'created_at' => $affiliate->created_at,
                'updated_at' => $affiliate->updated_at
            ];
        }, $affiliates );
        
        return new WP_REST_Response( $formatted_affiliates );
    }

    /**
     * Get config
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_config( $request ) {
        $ai_settings = get_option( 'ng_ai_settings', [] );
        $publishing_settings = get_option( 'ng_publishing_settings', [] );
        
        return new WP_REST_Response( [
            'ai_settings' => $ai_settings,
            'publishing_settings' => $publishing_settings
        ] );
    }

    /**
     * Update config
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function update_config( $request ) {
        $ai_settings = $request->get_param( 'ai_settings' );
        $publishing_settings = $request->get_param( 'publishing_settings' );
        
        if ( $ai_settings ) {
            update_option( 'ng_ai_settings', $ai_settings );
        }
        
        if ( $publishing_settings ) {
            update_option( 'ng_publishing_settings', $publishing_settings );
        }
        
        return new WP_REST_Response( [
            'message' => 'Configuration updated successfully'
        ] );
    }

    /**
     * Get stats
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_stats( $request ) {
        global $wpdb;
        
        // Get counts
        $total_deals = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ng_raw_deals" );
        $active_sources = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ng_deal_sources WHERE is_active = 1" );
        $queue_pending = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ng_processing_queue WHERE status = 'pending'" );
        $published_deals = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ng_raw_deals WHERE post_id IS NOT NULL" );
        
        // Get usage stats
        $usage_stats = get_option( 'ng_usage_stats', [
            'total_requests' => 0,
            'total_cost' => 0,
            'last_reset' => current_time( 'mysql' )
        ] );
        
        return new WP_REST_Response( [
            'deals' => [
                'total' => intval( $total_deals ),
                'published' => intval( $published_deals ),
                'pending' => intval( $total_deals ) - intval( $published_deals )
            ],
            'sources' => [
                'total' => intval( $active_sources ),
                'active' => intval( $active_sources )
            ],
            'queue' => [
                'pending' => intval( $queue_pending )
            ],
            'usage' => $usage_stats
        ] );
    }

    /**
     * Get queue
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_queue( $request ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ng_processing_queue';
        $queue_items = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC LIMIT 50" );
        
        $formatted_items = array_map( function( $item ) {
            return [
                'id' => intval( $item->id ),
                'item_type' => $item->item_type,
                'item_id' => intval( $item->item_id ),
                'status' => $item->status,
                'priority' => intval( $item->priority ),
                'attempts' => intval( $item->attempts ),
                'max_attempts' => intval( $item->max_attempts ),
                'error_message' => $item->error_message,
                'scheduled_at' => $item->scheduled_at,
                'processed_at' => $item->processed_at,
                'created_at' => $item->created_at
            ];
        }, $queue_items );
        
        return new WP_REST_Response( $formatted_items );
    }

    /**
     * Test AI connection
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function test_ai_connection( $request ) {
        require_once NOMADSGURU_PLUGIN_DIR . 'includes/class-nomadsguru-ai.php';
        $ai_service = NomadsGuru_AI::get_instance();
        
        $result = $ai_service->test_connection();
        
        if ( $result['success'] ) {
            return new WP_REST_Response( [
                'success' => true,
                'message' => $result['message']
            ] );
        } else {
            return new WP_Error( 'connection_failed', $result['message'], [ 'status' => 400 ] );
        }
    }

    /**
     * Prepare deal data for database
     * 
     * @param WP_REST_Request $request
     * @return array|WP_Error
     */
    private function prepare_deal_data( $request ) {
        $data = [];
        
        $fields = [
            'source_id' => '%d',
            'title' => '%s',
            'description' => '%s',
            'destination' => '%s',
            'price' => '%f',
            'original_price' => '%f',
            'discount_percentage' => '%f',
            'deal_url' => '%s',
            'image_url' => '%s',
            'valid_until' => '%s',
            'ai_score' => '%f',
            'ai_reasoning' => '%s',
            'status' => '%s',
            'post_id' => '%d'
        ];
        
        foreach ( $fields as $field => $format ) {
            $value = $request->get_param( $field );
            
            if ( $value !== null ) {
                if ( $format === '%d' ) {
                    $data[$field] = intval( $value );
                } elseif ( $format === '%f' ) {
                    $data[$field] = floatval( $value );
                } else {
                    $data[$field] = sanitize_text_field( $value );
                }
            }
        }
        
        // Add timestamps
        $data['updated_at'] = current_time( 'mysql' );
        
        if ( ! isset( $data['source_id'] ) || ! isset( $data['title'] ) ) {
            return new WP_Error( 'invalid_data', 'Missing required fields', [ 'status' => 400 ] );
        }
        
        return $data;
    }

    /**
     * Prepare source data for database
     * 
     * @param WP_REST_Request $request
     * @return array|WP_Error
     */
    private function prepare_source_data( $request ) {
        $data = [];
        
        $fields = [
            'source_type' => '%s',
            'source_name' => '%s',
            'website_url' => '%s',
            'rss_feed' => '%s',
            'sync_interval_minutes' => '%d'
        ];
        
        foreach ( $fields as $field => $format ) {
            $value = $request->get_param( $field );
            
            if ( $value !== null ) {
                if ( $format === '%d' ) {
                    $data[$field] = intval( $value );
                } else {
                    $data[$field] = sanitize_text_field( $value );
                }
            }
        }
        
        // Add defaults and timestamps
        $data['is_active'] = 1;
        $data['created_at'] = current_time( 'mysql' );
        $data['updated_at'] = current_time( 'mysql' );
        
        // Validation
        if ( empty( $data['source_type'] ) || empty( $data['source_name'] ) ) {
            return new WP_Error( 'invalid_data', 'Source type and name are required', [ 'status' => 400 ] );
        }
        
        if ( $data['source_type'] === 'website' && empty( $data['website_url'] ) ) {
            return new WP_Error( 'invalid_data', 'Website URL is required for website sources', [ 'status' => 400 ] );
        }
        
        if ( $data['source_type'] === 'rss' && empty( $data['rss_feed'] ) ) {
            return new WP_Error( 'invalid_data', 'RSS feed URL is required for RSS sources', [ 'status' => 400 ] );
        }
        
        return $data;
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

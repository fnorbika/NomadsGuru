<?php

/**
 * Frontend Shortcodes for NomadsGuru
 * 
 * @package NomadsGuru
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NomadsGuru_Shortcodes {

    /**
     * Single instance of the class
     * @var NomadsGuru_Shortcodes|null
     */
    private static $instance = null;

    /**
     * Get singleton instance
     * 
     * @return NomadsGuru_Shortcodes
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
        // Register shortcodes
        add_shortcode( 'nomadsguru_deals', array( $this, 'render_deals' ) );
        add_shortcode( 'nomadsguru_deal_filter', array( $this, 'render_deal_filter' ) );
        add_shortcode( 'nomadsguru_featured_deals', array( $this, 'render_featured_deals' ) );
        
        // Enqueue frontend assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        // Only load on pages with our shortcodes
        global $post;
        
        if ( $post && ( has_shortcode( $post->post_content, 'nomadsguru_deals' ) || 
                       has_shortcode( $post->post_content, 'nomadsguru_deal_filter' ) || 
                       has_shortcode( $post->post_content, 'nomadsguru_featured_deals' ) ) ) {
            
            // CSS
            wp_enqueue_style(
                'nomadsguru-frontend',
                NOMADSGURU_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                NOMADSGURU_VERSION . '-' . filemtime( NOMADSGURU_PLUGIN_DIR . 'assets/css/frontend.css' )
            );

            // JavaScript
            wp_enqueue_script(
                'nomadsguru-frontend',
                NOMADSGURU_PLUGIN_URL . 'assets/js/frontend.js',
                array( 'jquery' ),
                NOMADSGURU_VERSION . '-' . filemtime( NOMADSGURU_PLUGIN_DIR . 'assets/js/frontend.js' ),
                true
            );

            // Localize script
            wp_localize_script( 'nomadsguru-frontend', 'nomadsguruFrontend', array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'nomadsguru_frontend_nonce' ),
                'restUrl' => rest_url( 'nomadsguru/v1/' ),
            ) );
        }
    }

    /**
     * Render deals shortcode
     * 
     * @param array $atts
     * @return string
     */
    public function render_deals( $atts ) {
        $atts = shortcode_atts( array(
            'limit' => 10,
            'destination' => '',
            'min_score' => 5,
            'status' => 'published',
            'orderby' => 'created_at',
            'order' => 'DESC'
        ), $atts );

        $deals = $this->get_deals_data( $atts );
        
        ob_start();
        include NOMADSGURU_PLUGIN_DIR . 'templates/shortcodes/deals.php';
        return ob_get_clean();
    }

    /**
     * Render deal filter shortcode
     * 
     * @param array $atts
     * @return string
     */
    public function render_deal_filter( $atts ) {
        $atts = shortcode_atts( array(
            'show_destination' => 'true',
            'show_price_range' => 'true',
            'show_score' => 'true'
        ), $atts );

        ob_start();
        include NOMADSGURU_PLUGIN_DIR . 'templates/shortcodes/deal-filter.php';
        return ob_get_clean();
    }

    /**
     * Render featured deals shortcode
     * 
     * @param array $atts
     * @return string
     */
    public function render_featured_deals( $atts ) {
        $atts = shortcode_atts( array(
            'limit' => 3,
            'min_score' => 8,
            'show_images' => 'true'
        ), $atts );

        $atts['status'] = 'published';
        $deals = $this->get_deals_data( $atts );
        
        ob_start();
        include NOMADSGURU_PLUGIN_DIR . 'templates/shortcodes/featured-deals.php';
        return ob_get_clean();
    }

    /**
     * Get deals data from database
     * 
     * @param array $args
     * @return array
     */
    private function get_deals_data( $args ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ng_raw_deals';
        $limit = intval( $args['limit'] );
        $status = sanitize_text_field( $args['status'] );
        $min_score = floatval( $args['min_score'] );
        $destination = sanitize_text_field( $args['destination'] );
        $orderby = sanitize_sql_orderby( $args['orderby'] );
        $order = strtoupper( sanitize_text_field( $args['order'] ) ) === 'ASC' ? 'ASC' : 'DESC';
        
        $where = "status = %s AND ai_score >= %f";
        $where_args = [ $status, $min_score ];
        
        if ( ! empty( $destination ) ) {
            $where .= " AND destination LIKE %s";
            $where_args[] = '%' . $wpdb->esc_like( $destination ) . '%';
        }
        
        $query = "SELECT * FROM $table WHERE $where ORDER BY $orderby $order LIMIT %d";
        $query_args = array_merge( $where_args, [ $limit ] );
        
        $deals = $wpdb->get_results( $wpdb->prepare( $query, $query_args ) );
        
        return array_map( function( $deal ) {
            return [
                'id' => intval( $deal->id ),
                'title' => esc_html( $deal->title ),
                'description' => esc_html( $deal->description ),
                'destination' => esc_html( $deal->destination ),
                'price' => floatval( $deal->price ),
                'original_price' => floatval( $deal->original_price ),
                'discount_percentage' => floatval( $deal->discount_percentage ),
                'deal_url' => esc_url( $deal->deal_url ),
                'image_url' => esc_url( $deal->image_url ),
                'valid_until' => $deal->valid_until,
                'ai_score' => floatval( $deal->ai_score ),
                'ai_reasoning' => esc_html( $deal->ai_reasoning ),
                'post_id' => $deal->post_id ? intval( $deal->post_id ) : null,
                'created_at' => $deal->created_at
            ];
        }, $deals );
    }

    /**
     * Format price
     * 
     * @param float $price
     * @return string
     */
    public static function format_price( $price ) {
        return '$' . number_format( $price, 2 );
    }

    /**
     * Format discount
     * 
     * @param float $percentage
     * @return string
     */
    public static function format_discount( $percentage ) {
        return number_format( $percentage, 0 ) . '%';
    }

    /**
     * Format date
     * 
     * @param string $date
     * @return string
     */
    public static function format_date( $date ) {
        return date_i18n( get_option( 'date_format' ), strtotime( $date ) );
    }

    /**
     * Get deal permalink
     * 
     * @param array $deal
     * @return string
     */
    public static function get_deal_permalink( $deal ) {
        if ( $deal['post_id'] ) {
            return get_permalink( $deal['post_id'] );
        }
        
        return $deal['deal_url'] ?: '#';
    }

    /**
     * Get unique destinations for filtering
     * 
     * @return array
     */
    public static function get_destinations() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ng_raw_deals';
        $destinations = $wpdb->get_col( "SELECT DISTINCT destination FROM $table WHERE status = 'published' AND destination != '' ORDER BY destination" );
        
        return array_map( 'esc_html', $destinations );
    }

    /**
     * Get price range for filtering
     * 
     * @return array
     */
    public static function get_price_range() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ng_raw_deals';
        $range = $wpdb->get_row( "SELECT MIN(price) as min_price, MAX(price) as max_price FROM $table WHERE status = 'published'" );
        
        return [
            'min' => floatval( $range->min_price ),
            'max' => floatval( $range->max_price )
        ];
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

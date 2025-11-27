<?php
/**
 * Deal Source Interface
 * 
 * All deal sources must implement this interface
 * 
 * @package NomadsGuru
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface DealSourceInterface {
    
    /**
     * Fetch deals from the source
     * 
     * @return array Array of deals
     */
    public function fetch_deals();
    
    /**
     * Get source name
     * 
     * @return string Source name
     */
    public function get_name();
    
    /**
     * Get source type
     * 
     * @return string Source type (api, csv, rss, scraper)
     */
    public function get_type();
    
    /**
     * Check if source is active
     * 
     * @return bool True if active
     */
    public function is_active();
    
    /**
     * Get source configuration
     * 
     * @return array Configuration array
     */
    public function get_config();
    
    /**
     * Validate source configuration
     * 
     * @return bool True if valid
     */
    public function validate_config();
    
    /**
     * Get last fetch timestamp
     * 
     * @return int Timestamp or 0 if never fetched
     */
    public function get_last_fetch();
    
    /**
     * Update last fetch timestamp
     * 
     * @return void
     */
    public function update_last_fetch();
}

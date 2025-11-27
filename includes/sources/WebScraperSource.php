<?php
/**
 * Web Scraper Deal Source
 * 
 * Custom web scraper for deal sources
 * 
 * @package NomadsGuru
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WebScraperSource extends AbstractDealSource {
    
    /**
     * Constructor
     * 
     * @param array $config Source configuration
     */
    public function __construct( $config = [] ) {
        $this->name = 'web_scraper';
        $this->type = 'scraper';
        parent::__construct( $config );
    }
    
    /**
     * Fetch deals from web scraper
     * 
     * @return array Array of deals
     */
    public function fetch_deals() {
        $target_url = $this->config['target_url'] ?? '';
        
        if ( empty( $target_url ) ) {
            $this->log( "Scraper target URL not configured", 'error' );
            return [];
        }
        
        // Fetch web page
        $response = $this->make_request( $target_url );
        
        if ( is_wp_error( $response ) ) {
            $this->log( "Failed to fetch target URL: " . $response->get_error_message(), 'error' );
            return [];
        }
        
        $html = wp_remote_retrieve_body( $response );
        
        if ( empty( $html ) ) {
            $this->log( "Target URL returned empty content", 'error' );
            return [];
        }
        
        // Parse HTML and extract deals
        $deals = $this->parse_deals_from_html( $html );
        
        $this->log( "Scraped " . count( $deals ) . " deals from web page", 'info' );
        $this->update_last_fetch();
        
        return $deals;
    }
    
    /**
     * Parse deals from HTML content
     * 
     * @param string $html HTML content
     * @return array Array of deals
     */
    private function parse_deals_from_html( $html ) {
        $deals = [];
        $selectors = $this->config['selectors'] ?? [];
        
        if ( empty( $selectors ) ) {
            $this->log( "No CSS selectors configured for scraper", 'error' );
            return [];
        }
        
        // Use DOMDocument for HTML parsing
        $dom = new DOMDocument();
        
        // Suppress warnings for malformed HTML
        libxml_use_internal_errors( true );
        $dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
        libxml_clear_errors();
        
        $xpath = new DOMXPath( $dom );
        
        // Find deal containers
        $deal_containers = $xpath->query( $selectors['container'] ?? '' );
        
        if ( $deal_containers->length === 0 ) {
            $this->log( "No deal containers found with selector: " . ($selectors['container'] ?? ''), 'warning' );
            return [];
        }
        
        $max_deals = $this->config['max_deals'];
        $deal_count = 0;
        
        foreach ( $deal_containers as $container ) {
            if ( $deal_count >= $max_deals ) {
                break;
            }
            
            $deal_data = $this->extract_deal_from_container( $xpath, $container, $selectors );
            
            if ( $deal_data && $this->is_valid_deal( $deal_data ) ) {
                $deals[] = $this->normalize_deal( $deal_data );
                $deal_count++;
            }
        }
        
        return $deals;
    }
    
    /**
     * Extract deal data from container element
     * 
     * @param DOMXPath $xpath XPath instance
     * @param DOMElement $container Container element
     * @param array $selectors CSS selectors
     * @return array|null Deal data or null if invalid
     */
    private function extract_deal_from_container( $xpath, $container, $selectors ) {
        $deal_data = [];
        
        // Extract title
        $title_selector = $selectors['title'] ?? '';
        if ( ! empty( $title_selector ) ) {
            $title_node = $xpath->query( $title_selector, $container );
            if ( $title_node->length > 0 ) {
                $deal_data['title'] = trim( $title_node->item( 0 )->textContent );
            }
        }
        
        // Extract description
        $desc_selector = $selectors['description'] ?? '';
        if ( ! empty( $desc_selector ) ) {
            $desc_node = $xpath->query( $desc_selector, $container );
            if ( $desc_node->length > 0 ) {
                $deal_data['description'] = trim( $desc_node->item( 0 )->textContent );
            }
        }
        
        // Extract price information
        $price_selectors = $selectors['prices'] ?? [];
        if ( ! empty( $price_selectors ) ) {
            $prices = $this->extract_prices( $xpath, $container, $price_selectors );
            $deal_data = array_merge( $deal_data, $prices );
        }
        
        // Extract destination
        $dest_selector = $selectors['destination'] ?? '';
        if ( ! empty( $dest_selector ) ) {
            $dest_node = $xpath->query( $dest_selector, $container );
            if ( $dest_node->length > 0 ) {
                $deal_data['destination'] = trim( $dest_node->item( 0 )->textContent );
            }
        }
        
        // Extract booking URL
        $link_selector = $selectors['link'] ?? '';
        if ( ! empty( $link_selector ) ) {
            $link_node = $xpath->query( $link_selector, $container );
            if ( $link_node->length > 0 ) {
                $link_element = $link_node->item( 0 );
                if ( $link_element->hasAttribute( 'href' ) ) {
                    $deal_data['booking_url'] = $link_element->getAttribute( 'href' );
                    
                    // Convert relative URLs to absolute
                    if ( ! filter_var( $deal_data['booking_url'], FILTER_VALIDATE_URL ) ) {
                        $base_url = $this->config['target_url'];
                        $deal_data['booking_url'] = rtrim( $base_url, '/' ) . '/' . ltrim( $deal_data['booking_url'], '/' );
                    }
                }
            }
        }
        
        // Extract travel dates
        $date_selectors = $selectors['dates'] ?? [];
        if ( ! empty( $date_selectors ) ) {
            $dates = $this->extract_dates( $xpath, $container, $date_selectors );
            $deal_data = array_merge( $deal_data, $dates );
        }
        
        return empty( $deal_data ) ? null : $deal_data;
    }
    
    /**
     * Extract price information
     * 
     * @param DOMXPath $xpath XPath instance
     * @param DOMElement $container Container element
     * @param array $selectors Price selectors
     * @return array Price data
     */
    private function extract_prices( $xpath, $container, $selectors ) {
        $prices = [
            'original_price' => 0,
            'discounted_price' => 0,
            'currency' => 'USD'
        ];
        
        // Extract original price
        if ( ! empty( $selectors['original'] ) ) {
            $price_node = $xpath->query( $selectors['original'], $container );
            if ( $price_node->length > 0 ) {
                $price_text = trim( $price_node->item( 0 )->textContent );
                $prices['original_price'] = $this->parse_price( $price_text );
            }
        }
        
        // Extract discounted price
        if ( ! empty( $selectors['discounted'] ) ) {
            $price_node = $xpath->query( $selectors['discounted'], $container );
            if ( $price_node->length > 0 ) {
                $price_text = trim( $price_node->item( 0 )->textContent );
                $prices['discounted_price'] = $this->parse_price( $price_text );
            }
        }
        
        // Extract currency
        if ( ! empty( $selectors['currency'] ) ) {
            $currency_node = $xpath->query( $selectors['currency'], $container );
            if ( $currency_node->length > 0 ) {
                $prices['currency'] = trim( $currency_node->item( 0 )->textContent );
            }
        }
        
        return $prices;
    }
    
    /**
     * Extract travel dates
     * 
     * @param DOMXPath $xpath XPath instance
     * @param DOMElement $container Container element
     * @param array $selectors Date selectors
     * @return array Date data
     */
    private function extract_dates( $xpath, $container, $selectors ) {
        $dates = [
            'travel_start' => '',
            'travel_end' => ''
        ];
        
        // Extract start date
        if ( ! empty( $selectors['start'] ) ) {
            $date_node = $xpath->query( $selectors['start'], $container );
            if ( $date_node->length > 0 ) {
                $dates['travel_start'] = trim( $date_node->item( 0 )->textContent );
            }
        }
        
        // Extract end date
        if ( ! empty( $selectors['end'] ) ) {
            $date_node = $xpath->query( $selectors['end'], $container );
            if ( $date_node->length > 0 ) {
                $dates['travel_end'] = trim( $date_node->item( 0 )->textContent );
            }
        }
        
        return $dates;
    }
    
    /**
     * Parse price from text
     * 
     * @param string $text Price text
     * @return float Parsed price
     */
    private function parse_price( $text ) {
        // Remove currency symbols and extract numbers
        $clean_text = preg_replace( '/[^\d.,]/', '', $text );
        $clean_text = str_replace( ',', '', $clean_text );
        
        return floatval( $clean_text );
    }
    
    /**
     * Check if deal is valid
     * 
     * @param array $deal_data Deal data
     * @return bool True if valid
     */
    private function is_valid_deal( $deal_data ) {
        // Must have title and some price information
        if ( empty( $deal_data['title'] ) ) {
            return false;
        }
        
        $has_price = ! empty( $deal_data['original_price'] ) || ! empty( $deal_data['discounted_price'] );
        
        if ( ! $has_price ) {
            return false;
        }
        
        // Must have booking URL
        if ( empty( $deal_data['booking_url'] ) ) {
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
        $required = ['target_url', 'selectors'];
        
        foreach ( $required as $key ) {
            if ( empty( $this->config[$key] ) ) {
                return false;
            }
        }
        
        // Validate target URL
        if ( ! filter_var( $this->config['target_url'], FILTER_VALIDATE_URL ) ) {
            return false;
        }
        
        // Validate selectors structure
        $selectors = $this->config['selectors'];
        $required_selectors = ['container', 'title'];
        
        foreach ( $required_selectors as $selector ) {
            if ( empty( $selectors[$selector] ) ) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get predefined scraper configurations
     * 
     * @return array Predefined configurations
     */
    public static function get_predefined_configs() {
        return [
            'expedia_deals' => [
                'name' => 'Expedia Deal Scraper',
                'target_url' => 'https://www.expedia.com/deals',
                'selectors' => [
                    'container' => '//div[contains(@class, "deal-card")]',
                    'title' => './/h3[contains(@class, "deal-title")]',
                    'description' => './/p[contains(@class, "deal-description")]',
                    'prices' => [
                        'original' => './/span[contains(@class, "original-price")]',
                        'discounted' => './/span[contains(@class, "deal-price")]',
                        'currency' => './/span[contains(@class, "currency")]'
                    ],
                    'destination' => './/span[contains(@class, "destination")]',
                    'link' => './/a[contains(@class, "deal-link")]',
                    'dates' => [
                        'start' => './/span[contains(@class, "start-date")]',
                        'end' => './/span[contains(@class, "end-date")]'
                    ]
                ]
            ],
            'booking_com_deals' => [
                'name' => 'Booking.com Deal Scraper',
                'target_url' => 'https://www.booking.com/deals',
                'selectors' => [
                    'container' => '//div[contains(@class, "bui-card")]',
                    'title' => './/h3[contains(@class, "bui-card__title")]',
                    'description' => './/div[contains(@class, "bui-card__text")]',
                    'prices' => [
                        'original' => './/span[contains(@class, "original-price")]',
                        'discounted' => './/span[contains(@class, "price")]',
                        'currency' => './/span[contains(@class, "currency")]'
                    ],
                    'destination' => './/span[contains(@class, "location")]',
                    'link' => './/a[contains(@class, "bui-card__link")]'
                ]
            ],
            'kayak_deals' => [
                'name' => 'Kayak Deal Scraper',
                'target_url' => 'https://www.kayak.com/deals',
                'selectors' => [
                    'container' => '//div[contains(@class, "deal-item")]',
                    'title' => './/div[contains(@class, "deal-title")]',
                    'description' => './/div[contains(@class, "deal-description")]',
                    'prices' => [
                        'discounted' => './/span[contains(@class, "price")]',
                        'currency' => './/span[contains(@class, "currency")]'
                    ],
                    'destination' => './/div[contains(@class, "destination")]',
                    'link' => './/a[contains(@class, "deal-link")]'
                ]
            ]
        ];
    }
}

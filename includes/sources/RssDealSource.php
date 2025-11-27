<?php
/**
 * RSS Feed Deal Source
 * 
 * Generic RSS feed parser for deal sources
 * 
 * @package NomadsGuru
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RssDealSource extends AbstractDealSource {
    
    /**
     * Constructor
     * 
     * @param array $config Source configuration
     */
    public function __construct( $config = [] ) {
        $this->name = 'rss_feed';
        $this->type = 'rss';
        parent::__construct( $config );
    }
    
    /**
     * Fetch deals from RSS feed
     * 
     * @return array Array of deals
     */
    public function fetch_deals() {
        $feed_url = $this->config['feed_url'] ?? '';
        
        if ( empty( $feed_url ) ) {
            $this->log( "RSS feed URL not configured", 'error' );
            return [];
        }
        
        // Fetch RSS feed
        $response = $this->make_request( $feed_url );
        
        if ( is_wp_error( $response ) ) {
            $this->log( "Failed to fetch RSS feed: " . $response->get_error_message(), 'error' );
            return [];
        }
        
        $body = wp_remote_retrieve_body( $response );
        
        if ( empty( $body ) ) {
            $this->log( "RSS feed returned empty content", 'error' );
            return [];
        }
        
        // Parse RSS feed
        $xml = simplexml_load_string( $body );
        
        if ( ! $xml ) {
            $this->log( "Failed to parse RSS XML", 'error' );
            return [];
        }
        
        $deals = [];
        $max_deals = $this->config['max_deals'];
        $deal_count = 0;
        
        // Handle different RSS formats
        $items = [];
        if ( isset( $xml->channel->item ) ) {
            // RSS 2.0 format
            $items = $xml->channel->item;
        } elseif ( isset( $xml->entry ) ) {
            // Atom format
            $items = $xml->entry;
        }
        
        foreach ( $items as $item ) {
            if ( $deal_count >= $max_deals ) {
                break;
            }
            
            $deal_data = $this->parse_rss_item( $item );
            
            if ( $deal_data && $this->is_travel_deal( $deal_data ) ) {
                $deals[] = $this->normalize_deal( $deal_data );
                $deal_count++;
            }
        }
        
        $this->log( "Fetched {$deal_count} travel deals from RSS feed", 'info' );
        $this->update_last_fetch();
        
        return $deals;
    }
    
    /**
     * Parse RSS item into deal data
     * 
     * @param SimpleXMLElement $item RSS item
     * @return array|null Deal data or null if invalid
     */
    private function parse_rss_item( $item ) {
        $namespaces = $item->getNamespaces( true );
        
        // Extract basic fields
        $title = (string) $item->title;
        $link = (string) $item->link;
        $description = (string) $item->description;
        
        // Try to extract content from different namespaces
        $content = '';
        if ( isset( $namespaces['content'] ) && isset( $item->children( $namespaces['content'] )->encoded ) ) {
            $content = (string) $item->children( $namespaces['content'] )->encoded;
        }
        
        // Use description if content is empty
        if ( empty( $content ) ) {
            $content = $description;
        }
        
        // Extract publication date
        $date = '';
        if ( isset( $item->pubDate ) ) {
            $date = (string) $item->pubDate;
        } elseif ( isset( $item->published ) ) {
            $date = (string) $item->published;
        }
        
        // Extract categories as tags
        $categories = [];
        if ( isset( $item->category ) ) {
            foreach ( $item->category as $category ) {
                $categories[] = (string) $category;
            }
        }
        
        return [
            'title' => $title,
            'description' => wp_strip_all_tags( $content ),
            'booking_url' => $link,
            'categories' => $categories,
            'published_date' => $date
        ];
    }
    
    /**
     * Check if RSS item is a travel deal
     * 
     * @param array $deal_data Parsed deal data
     * @return bool True if travel deal
     */
    private function is_travel_deal( $deal_data ) {
        $title = strtolower( $deal_data['title'] ?? '' );
        $description = strtolower( $deal_data['description'] ?? '' );
        $categories = array_map( 'strtolower', $deal_data['categories'] ?? [] );
        
        // Travel-related keywords
        $travel_keywords = [
            'travel', 'deal', 'vacation', 'holiday', 'trip', 'flight', 'hotel', 'resort',
            'package', 'tour', 'cruise', 'booking', 'destination', 'getaway', 'escape',
            'paris', 'london', 'tokyo', 'bali', 'dubai', 'new york', 'sydney', 'rome'
        ];
        
        // Deal-related keywords
        $deal_keywords = [
            'discount', 'save', 'off', '%', 'cheap', 'budget', 'sale', 'offer', 'special',
            'price', 'cost', 'rate', 'fare', 'deal', 'bargain'
        ];
        
        $all_text = $title . ' ' . $description . ' ' . implode( ' ', $categories );
        
        // Check for travel keywords
        $has_travel_keyword = false;
        foreach ( $travel_keywords as $keyword ) {
            if ( strpos( $all_text, $keyword ) !== false ) {
                $has_travel_keyword = true;
                break;
            }
        }
        
        // Check for deal keywords (optional, but increases confidence)
        $has_deal_keyword = false;
        foreach ( $deal_keywords as $keyword ) {
            if ( strpos( $all_text, $keyword ) !== false ) {
                $has_deal_keyword = true;
                break;
            }
        }
        
        // Must have travel keyword, deal keyword is optional
        return $has_travel_keyword;
    }
    
    /**
     * Extract destination from text
     * 
     * @param string $text Text to search
     * @return string Destination or empty string
     */
    private function extract_destination( $text ) {
        // List of common destinations
        $destinations = [
            'Paris, France', 'London, UK', 'Tokyo, Japan', 'Bali, Indonesia',
            'New York, USA', 'Sydney, Australia', 'Rome, Italy', 'Dubai, UAE',
            'Barcelona, Spain', 'Amsterdam, Netherlands', 'Bangkok, Thailand',
            'Singapore', 'Hong Kong', 'Los Angeles, USA', 'San Francisco, USA'
        ];
        
        $text_lower = strtolower( $text );
        
        foreach ( $destinations as $destination ) {
            if ( strpos( $text_lower, strtolower( $destination ) ) !== false ) {
                return $destination;
            }
        }
        
        return '';
    }
    
    /**
     * Extract prices from text
     * 
     * @param string $text Text to search
     * @return array Original and discounted prices
     */
    private function extract_prices( $text ) {
        $original_price = 0;
        $discounted_price = 0;
        
        // Look for price patterns like "$899" or "€599"
        if ( preg_match_all( '/[\$\€£\¥]\s*(\d+(?:,\d{3})*(?:\.\d{2})?)/', $text, $matches ) ) {
            $prices = [];
            foreach ( $matches[1] as $price ) {
                $prices[] = floatval( str_replace( ',', '', $price ) );
            }
            
            if ( count( $prices ) >= 2 ) {
                // Assume first is original, second is discounted
                $original_price = max( $prices );
                $discounted_price = min( $prices );
            } elseif ( count( $prices ) == 1 ) {
                // Single price, assume it's the deal price
                $discounted_price = $prices[0];
                $original_price = $discounted_price * 1.5; // Estimate original price
            }
        }
        
        return [
            'original_price' => $original_price,
            'discounted_price' => $discounted_price
        ];
    }
    
    /**
     * Override normalize_deal to add RSS-specific processing
     * 
     * @param array $raw_data Raw deal data
     * @return array Normalized deal data
     */
    protected function normalize_deal( $raw_data ) {
        $base_deal = parent::normalize_deal( $raw_data );
        
        // Extract destination from title/description
        if ( empty( $base_deal['destination'] ) ) {
            $base_deal['destination'] = $this->extract_destination( $raw_data['title'] . ' ' . $raw_data['description'] );
        }
        
        // Extract prices from description
        if ( empty( $base_deal['original_price'] ) || empty( $base_deal['discounted_price'] ) ) {
            $prices = $this->extract_prices( $raw_data['description'] );
            $base_deal['original_price'] = $base_deal['original_price'] ?: $prices['original_price'];
            $base_deal['discounted_price'] = $base_deal['discounted_price'] ?: $prices['discounted_price'];
        }
        
        return $base_deal;
    }
    
    /**
     * Check if source is properly configured
     * 
     * @return bool True if configured
     */
    protected function is_configured() {
        return ! empty( $this->config['feed_url'] ) && filter_var( $this->config['feed_url'], FILTER_VALIDATE_URL );
    }
}

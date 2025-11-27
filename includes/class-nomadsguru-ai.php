<?php

/**
 * AI Service for NomadsGuru
 * 
 * @package NomadsGuru
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NomadsGuru_AI {

    /**
     * Single instance of the class
     * @var NomadsGuru_AI|null
     */
    private static $instance = null;

    /**
     * API endpoints
     * @var array
     */
    private $api_endpoints = [
        'openai' => 'https://api.openai.com/v1/chat/completions',
        'gemini' => 'https://generativelanguage.googleapis.com/v1beta/models',
        'grok' => 'https://api.x.ai/v1/chat/completions',
        'perplexity' => 'https://api.perplexity.ai/chat/completions'
    ];

    /**
     * Image API endpoints
     * @var array
     */
    private $image_api_endpoints = [
        'pixabay' => 'https://pixabay.com/api/',
        'pexels' => 'https://api.pexels.com/v1/search',
        'unsplash' => 'https://api.unsplash.com/search/photos'
    ];

    /**
     * Get singleton instance
     * 
     * @return NomadsGuru_AI
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
        // Private constructor to prevent direct instantiation
    }

    /**
     * Get API key for current provider
     * 
     * @return string
     */
    private function get_api_key() {
        $settings = get_option( 'ng_ai_settings', [] );
        $api_key = $settings['api_key'] ?? '';
        
        return !empty( $api_key ) ? base64_decode( $api_key ) : '';
    }

    /**
     * Get current AI provider
     * 
     * @return string
     */
    private function get_provider() {
        $settings = get_option( 'ng_ai_settings', [] );
        return $settings['provider'] ?? 'openai';
    }

    /**
     * Get AI model settings
     * 
     * @return array
     */
    private function get_model_settings() {
        $settings = get_option( 'ng_ai_settings', [] );
        return [
            'model' => $settings['model'] ?? 'gpt-3.5-turbo',
            'temperature' => floatval( $settings['temperature'] ?? 0.7 ),
            'max_tokens' => intval( $settings['max_tokens'] ?? 500 )
        ];
    }

    /**
     * Evaluate a deal based on criteria using AI
     * 
     * @param array $deal_data
     * @return array Score and reasoning
     */
    public function evaluate_deal( $deal_data ) {
        $api_key = $this->get_api_key();
        
        if ( empty( $api_key ) ) {
            return $this->get_fallback_evaluation( $deal_data, 'API key not configured' );
        }

        $prompt = $this->build_evaluation_prompt( $deal_data );
        $response = $this->make_api_call( $api_key, $prompt, 'evaluation' );
        
        if ( is_wp_error( $response ) ) {
            return $this->get_fallback_evaluation( $deal_data, $response->get_error_message() );
        }

        return $this->parse_evaluation_response( $response );
    }

    /**
     * Generate content for a deal
     * 
     * @param array $deal_data
     * @return array Generated content
     */
    public function generate_content( $deal_data ) {
        $api_key = $this->get_api_key();
        
        if ( empty( $api_key ) ) {
            return $this->get_fallback_content( $deal_data, 'API key not configured' );
        }

        $prompt = $this->build_content_prompt( $deal_data );
        $response = $this->make_api_call( $api_key, $prompt, 'content' );
        
        if ( is_wp_error( $response ) ) {
            return $this->get_fallback_content( $deal_data, $response->get_error_message() );
        }

        return $this->parse_content_response( $response );
    }

    /**
     * Test API connection
     * 
     * @return array Test result
     */
    public function test_connection() {
        $api_key = $this->get_api_key();
        
        if ( empty( $api_key ) ) {
            return [
                'success' => false,
                'message' => __( 'API key not configured', 'nomadsguru' )
            ];
        }

        $provider = $this->get_provider();
        $test_prompt = 'Respond with "Connection successful" if you receive this message.';
        
        $response = $this->make_api_call( $api_key, $test_prompt, 'test' );
        
        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }

        return [
            'success' => true,
            'message' => __( 'Connection successful!', 'nomadsguru' )
        ];
    }

    /**
     * Find and download travel-related image
     * 
     * @param string $destination
     * @param array $keywords
     * @param string $preferred_provider
     * @return array Image data or fallback
     */
    public function find_travel_image( $destination, $keywords = [], $preferred_provider = 'pixabay' ) {
        // Build search query
        $search_terms = array_merge( [$destination], $keywords );
        $query = implode( ' ', array_filter( $search_terms ) );
        
        // Try preferred provider first, then fallbacks
        $providers = [$preferred_provider, 'pixabay', 'pexels', 'unsplash'];
        
        foreach ( $providers as $provider ) {
            $result = $this->try_image_provider( $provider, $query );
            if ( $result['success'] ) {
                return $result;
            }
        }
        
        // All providers failed, return placeholder
        return $this->get_placeholder_image( $destination );
    }

    /**
     * Try to get image from specific provider
     * 
     * @param string $provider
     * @param string $query
     * @return array Result data
     */
    private function try_image_provider( $provider, $query ) {
        switch ( $provider ) {
            case 'pixabay':
                return $this->get_pixabay_image( $query );
            case 'pexels':
                return $this->get_pexels_image( $query );
            case 'unsplash':
                return $this->get_unsplash_image( $query );
            default:
                return ['success' => false, 'message' => 'Unknown image provider'];
        }
    }

    /**
     * Get image from Pixabay (no API key required)
     * 
     * @param string $query
     * @return array Result data
     */
    private function get_pixabay_image( $query ) {
        $api_key = $this->get_image_api_key( 'pixabay' );
        $url = add_query_arg([
            'key' => $api_key,
            'q' => $query,
            'category' => 'travel',
            'per_page' => 3,
            'safesearch' => 'true',
            'image_type' => 'photo'
        ], $this->image_api_endpoints['pixabay']);

        $response = wp_remote_get( $url, ['timeout' => 15] );
        
        if ( is_wp_error( $response ) ) {
            return ['success' => false, 'message' => 'Pixabay API error: ' . $response->get_error_message()];
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE || empty( $data['hits'] ) ) {
            return ['success' => false, 'message' => 'No images found on Pixabay'];
        }
        
        // Get best quality image
        $photo = $data['hits'][0];
        $image_url = $photo['largeImageURL'] ?? $photo['webformatURL'];
        
        return $this->download_and_attach_image( $image_url, $query, 'pixabay', [
            'photographer' => $photo['user'] ?? 'Unknown',
            'photographer_url' => $photo['userImageURL'] ?? '',
            'page_url' => $photo['pageURL'] ?? ''
        ]);
    }

    /**
     * Get image from Pexels
     * 
     * @param string $query
     * @return array Result data
     */
    private function get_pexels_image( $query ) {
        $api_key = $this->get_image_api_key( 'pexels' );
        
        if ( empty( $api_key ) ) {
            return ['success' => false, 'message' => 'Pexels API key not configured'];
        }
        
        $url = add_query_arg([
            'query' => $query,
            'per_page' => 1,
            'orientation' => 'landscape'
        ], $this->image_api_endpoints['pexels']);

        $response = wp_remote_get( $url, [
            'headers' => ['Authorization' => $api_key],
            'timeout' => 15
        ]);
        
        if ( is_wp_error( $response ) ) {
            return ['success' => false, 'message' => 'Pexels API error: ' . $response->get_error_message()];
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE || empty( $data['photos'] ) ) {
            return ['success' => false, 'message' => 'No images found on Pexels'];
        }
        
        $photo = $data['photos'][0];
        $image_url = $photo['src']['large'] ?? $photo['src']['original'];
        
        return $this->download_and_attach_image( $image_url, $query, 'pexels', [
            'photographer' => $photo['photographer'] ?? 'Unknown',
            'photographer_url' => $photo['photographer_url'] ?? '',
            'page_url' => $photo['url'] ?? ''
        ]);
    }

    /**
     * Get image from Unsplash
     * 
     * @param string $query
     * @return array Result data
     */
    private function get_unsplash_image( $query ) {
        $api_key = $this->get_image_api_key( 'unsplash' );
        
        if ( empty( $api_key ) ) {
            return ['success' => false, 'message' => 'Unsplash API key not configured'];
        }
        
        $url = add_query_arg([
            'query' => $query,
            'per_page' => 1,
            'orientation' => 'landscape'
        ], $this->image_api_endpoints['unsplash']);

        $response = wp_remote_get( $url, [
            'headers' => ['Authorization' => 'Client-ID ' . $api_key],
            'timeout' => 15
        ]);
        
        if ( is_wp_error( $response ) ) {
            return ['success' => false, 'message' => 'Unsplash API error: ' . $response->get_error_message()];
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE || empty( $data['results'] ) ) {
            return ['success' => false, 'message' => 'No images found on Unsplash'];
        }
        
        $photo = $data['results'][0];
        $image_url = $photo['urls']['regular'] ?? $photo['urls']['full'];
        
        return $this->download_and_attach_image( $image_url, $query, 'unsplash', [
            'photographer' => $photo['user']['name'] ?? 'Unknown',
            'photographer_url' => $photo['user']['links']['html'] ?? '',
            'page_url' => $photo['links']['html'] ?? ''
        ]);
    }

    /**
     * Build evaluation prompt with improved structure
     * 
     * @param array $deal_data
     * @return string
     */
    private function build_evaluation_prompt( $deal_data ) {
        $prompt = "You are a travel deal expert. Analyze this travel deal and provide a score from 1-100 (higher is better) with detailed reasoning.\n\n";
        $prompt .= "DEAL INFORMATION:\n";
        
        if ( !empty( $deal_data['title'] ) ) {
            $prompt .= "Title: " . $deal_data['title'] . "\n";
        }
        
        if ( !empty( $deal_data['description'] ) ) {
            $prompt .= "Description: " . $deal_data['description'] . "\n";
        }
        
        if ( !empty( $deal_data['destination'] ) ) {
            $prompt .= "Destination: " . $deal_data['destination'] . "\n";
        }
        
        if ( !empty( $deal_data['price'] ) ) {
            $prompt .= "Price: $" . number_format( $deal_data['price'], 2 ) . "\n";
        }
        
        if ( !empty( $deal_data['original_price'] ) && $deal_data['original_price'] > $deal_data['price'] ) {
            $discount = round( (($deal_data['original_price'] - $deal_data['price']) / $deal_data['original_price']) * 100, 1 );
            $prompt .= "Original Price: $" . number_format( $deal_data['original_price'], 2 ) . " ({$discount}% discount)\n";
        }
        
        if ( !empty( $deal_data['valid_until'] ) ) {
            $prompt .= "Valid Until: " . $deal_data['valid_until'] . "\n";
        }
        
        $prompt .= "\nEVALUATION CRITERIA:\n";
        $prompt .= "- Value for money (discount amount vs quality)\n";
        $prompt .= "- Destination appeal and seasonality\n";
        $prompt .= "- Deal urgency (expiration date)\n";
        $prompt .= "- Overall travel experience quality\n";
        
        $prompt .= "\nRespond ONLY with valid JSON:\n";
        $prompt .= "{\n";
        $prompt .= "  \"score\": 85,\n";
        $prompt .= "  \"reasoning\": \"Detailed explanation of why this score was given, considering value, destination, timing, and quality\",\n";
        $prompt .= "  \"value_score\": 90,\n";
        $prompt .= "  \"destination_score\": 80,\n";
        $prompt .= "  \"urgency_score\": 85,\n";
        $prompt .= "  \"recommendation\": \"Excellent deal for budget-conscious travelers seeking good value\"\n";
        $prompt .= "}";
        
        return $prompt;
    }

    /**
     * Build content generation prompt with improved structure
     * 
     * @param array $deal_data
     * @return string
     */
    private function build_content_prompt( $deal_data ) {
        $prompt = "You are a professional travel writer. Create engaging, SEO-optimized content for this travel deal.\n\n";
        $prompt .= "DEAL INFORMATION:\n";
        
        if ( !empty( $deal_data['title'] ) ) {
            $prompt .= "Title: " . $deal_data['title'] . "\n";
        }
        
        if ( !empty( $deal_data['description'] ) ) {
            $prompt .= "Description: " . $deal_data['description'] . "\n";
        }
        
        if ( !empty( $deal_data['destination'] ) ) {
            $prompt .= "Destination: " . $deal_data['destination'] . "\n";
        }
        
        if ( !empty( $deal_data['price'] ) ) {
            $prompt .= "Price: $" . number_format( $deal_data['price'], 2 ) . "\n";
        }
        
        if ( !empty( $deal_data['original_price'] ) && $deal_data['original_price'] > $deal_data['price'] ) {
            $discount = round( (($deal_data['original_price'] - $deal_data['price']) / $deal_data['original_price']) * 100, 1 );
            $prompt .= "Original Price: $" . number_format( $deal_data['original_price'], 2 ) . " ({$discount}% discount)\n";
        }
        
        $prompt .= "\nCONTENT REQUIREMENTS:\n";
        $prompt .= "- Write an engaging blog post (800-1200 words)\n";
        $prompt .= "- Include SEO keywords naturally\n";
        $prompt .= "- Use HTML formatting (h2, h3, p, strong, em, ul, li)\n";
        $prompt .= "- Create compelling headings and subheadings\n";
        $prompt .= "- Include a call-to-action at the end\n";
        $prompt .= "- Write in a friendly, enthusiastic tone\n";
        $prompt .= "- Focus on travel inspiration and practical tips\n";
        
        $prompt .= "\nRespond ONLY with valid JSON:\n";
        $prompt .= "{\n";
        $prompt .= "  \"title\": \"Engaging SEO-optimized blog post title (50-60 characters)\",\n";
        $prompt .= "  \"content\": \"Full blog post content in HTML format with proper heading structure\",\n";
        $prompt .= "  \"excerpt\": \"Compelling excerpt (150-160 characters) that summarizes the deal\",\n";
        $prompt .= "  \"tags\": [\"travel\", \"deals\", \"budget travel\", \"vacation\", \"discount\"],\n";
        $prompt .= "  \"meta_description\": \"SEO meta description (150-160 characters)\"\n";
        $prompt .= "}";
        
        return $prompt;
    }

    /**
     * Make API call to AI provider
     * 
     * @param string $api_key
     * @param string $prompt
     * @param string $type
     * @return array|WP_Error
     */
    private function make_api_call( $api_key, $prompt, $type ) {
        $provider = $this->get_provider();
        $model_settings = $this->get_model_settings();
        
        // Build request based on provider
        $request = $this->build_request( $provider, $api_key, $prompt, $model_settings );
        
        // Make HTTP request
        $response = wp_remote_post( $this->api_endpoints[$provider], $request );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $http_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        
        if ( $http_code !== 200 ) {
            return new WP_Error( 'api_error', "API request failed with status $http_code: $body" );
        }
        
        $data = json_decode( $body, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'json_error', 'Failed to parse API response' );
        }
        
        // Update usage stats
        $this->update_usage_stats( $provider, $data );
        
        return $data;
    }

    /**
     * Build API request based on provider
     * 
     * @param string $provider
     * @param string $api_key
     * @param string $prompt
     * @param array $model_settings
     * @return array
     */
    private function build_request( $provider, $api_key, $prompt, $model_settings ) {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
        
        $body = [];
        
        switch ( $provider ) {
            case 'openai':
            case 'grok':
            case 'perplexity':
                $headers['Authorization'] = 'Bearer ' . $api_key;
                $body = [
                    'model' => $model_settings['model'],
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => $model_settings['temperature'],
                    'max_tokens' => $model_settings['max_tokens']
                ];
                break;
                
            case 'gemini':
                $headers['x-goog-api-key'] = $api_key;
                $model = $model_settings['model'];
                $body = [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => $model_settings['temperature'],
                        'maxOutputTokens' => $model_settings['max_tokens']
                    ]
                ];
                break;
        }
        
        return [
            'headers' => $headers,
            'body' => json_encode( $body ),
            'timeout' => 30,
            'method' => 'POST'
        ];
    }

    /**
     * Parse evaluation response with enhanced structure
     * 
     * @param array $response
     * @return array
     */
    private function parse_evaluation_response( $response ) {
        $provider = $this->get_provider();
        $content = '';
        
        // Extract content based on provider
        switch ( $provider ) {
            case 'openai':
            case 'grok':
            case 'perplexity':
                $content = $response['choices'][0]['message']['content'] ?? '';
                break;
                
            case 'gemini':
                $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
                break;
        }
        
        // Clean and extract JSON from response
        $content = $this->extract_json_from_response( $content );
        
        // Parse JSON response
        $data = json_decode( $content, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return [
                'score' => 50.0,
                'reasoning' => 'Failed to parse AI response: ' . json_last_error_msg(),
                'value_score' => 50.0,
                'destination_score' => 50.0,
                'urgency_score' => 50.0,
                'recommendation' => 'Unable to generate recommendation'
            ];
        }
        
        return [
            'score' => max( 1, min( 100, floatval( $data['score'] ?? 50.0 ) ) ),
            'reasoning' => sanitize_text_field( $data['reasoning'] ?? 'No reasoning provided' ),
            'value_score' => max( 1, min( 100, floatval( $data['value_score'] ?? 50.0 ) ) ),
            'destination_score' => max( 1, min( 100, floatval( $data['destination_score'] ?? 50.0 ) ) ),
            'urgency_score' => max( 1, min( 100, floatval( $data['urgency_score'] ?? 50.0 ) ) ),
            'recommendation' => sanitize_text_field( $data['recommendation'] ?? 'No recommendation available' )
        ];
    }

    /**
     * Extract JSON from AI response (handles markdown formatting)
     * 
     * @param string $content
     * @return string
     */
    private function extract_json_from_response( $content ) {
        // Remove markdown code blocks if present
        $content = preg_replace( '/```json\s*(.*?)\s*```/s', '$1', $content );
        $content = preg_replace( '/```\s*(.*?)\s*```/s', '$1', $content );
        
        // Find JSON object in the content
        if ( preg_match( '/\{.*\}/s', $content, $matches ) ) {
            return $matches[0];
        }
        
        return $content;
    }

    /**
     * Parse content generation response
     * 
     * @param array $response
     * @return array
     */
    private function parse_content_response( $response ) {
        $provider = $this->get_provider();
        $content = '';
        
        // Extract content based on provider
        switch ( $provider ) {
            case 'openai':
            case 'grok':
            case 'perplexity':
                $content = $response['choices'][0]['message']['content'] ?? '';
                break;
                
            case 'gemini':
                $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
                break;
        }
        
        // Clean and extract JSON from response
        $content = $this->extract_json_from_response( $content );
        
        // Parse JSON response
        $data = json_decode( $content, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return [
                'title' => 'Travel Deal Available',
                'content' => '<p>We found an amazing travel deal for you. Check out the details and book your next adventure!</p>',
                'excerpt' => 'Great travel deal available now',
                'tags' => ['travel', 'deals'],
                'meta_description' => 'Amazing travel deal waiting for you'
            ];
        }
        
        return [
            'title' => sanitize_text_field( $data['title'] ?? 'Travel Deal Available' ),
            'content' => wp_kses_post( $data['content'] ?? '<p>Great travel opportunity available!</p>' ),
            'excerpt' => sanitize_text_field( $data['excerpt'] ?? 'Check out this amazing travel deal' ),
            'tags' => array_map( 'sanitize_text_field', $data['tags'] ?? ['travel', 'deals'] ),
            'meta_description' => sanitize_text_field( $data['meta_description'] ?? 'Great travel deal available now' )
        ];
    }

    /**
     * Download and attach image to WordPress media library
     * 
     * @param string $image_url
     * @param string $query
     * @param string $provider
     * @param array $attribution
     * @return array Result data
     */
    private function download_and_attach_image( $image_url, $query, $provider, $attribution ) {
        // Include WordPress file functions
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        
        // Download file
        $tmp = download_url( $image_url );
        
        if ( is_wp_error( $tmp ) ) {
            return ['success' => false, 'message' => 'Failed to download image'];
        }
        
        // Prepare file array
        $file_array = [
            'name' => sanitize_file_name( $query . '-' . $provider . '.jpg' ),
            'tmp_name' => $tmp,
            'error' => 0,
            'size' => filesize( $tmp )
        ];
        
        // Handle upload
        $overrides = ['test_form' => false];
        $attachment_id = wp_handle_sideload( $file_array, $overrides );
        
        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $tmp );
            return ['success' => false, 'message' => 'Failed to process image'];
        }
        
        // Get attachment ID if it's an array
        if ( is_array( $attachment_id ) ) {
            $attachment_id = $attachment_id['file'];
        }
        
        // Set as attachment and get ID
        $attachment_id = wp_insert_attachment([
            'post_title' => sanitize_text_field( $query ),
            'post_content' => $this->build_image_caption( $attribution, $provider ),
            'post_excerpt' => $this->build_image_caption( $attribution, $provider ),
            'post_mime_type' => wp_check_filetype( basename( $attachment_id ) )['type']
        ], $attachment_id );
        
        if ( ! $attachment_id ) {
            @unlink( $tmp );
            return ['success' => false, 'message' => 'Failed to create attachment'];
        }
        
        // Generate thumbnails
        $attachment_data = wp_generate_attachment_metadata( $attachment_id, $attachment_id );
        wp_update_attachment_metadata( $attachment_id, $attachment_data );
        
        // Clean up temp file
        @unlink( $tmp );
        
        return [
            'success' => true,
            'attachment_id' => $attachment_id,
            'url' => wp_get_attachment_url( $attachment_id ),
            'attribution' => $attribution,
            'provider' => $provider
        ];
    }

    /**
     * Build image caption with attribution
     * 
     * @param array $attribution
     * @param string $provider
     * @return string
     */
    private function build_image_caption( $attribution, $provider ) {
        $caption = '';
        
        if ( ! empty( $attribution['photographer'] ) ) {
            $photographer = $attribution['photographer'];
            $caption .= "Photo by {$photographer} on {$provider}";
            
            if ( ! empty( $attribution['photographer_url'] ) ) {
                $caption = '<a href="' . esc_url( $attribution['photographer_url'] ) . '" target="_blank">' . $caption . '</a>';
            }
        }
        
        return $caption;
    }

    /**
     * Get placeholder image when all providers fail
     * 
     * @param string $destination
     * @return array Placeholder data
     */
    private function get_placeholder_image( $destination ) {
        // Create a simple placeholder or use a default image
        $placeholder_url = NOMADSGURU_PLUGIN_URL . 'assets/images/placeholder-travel.jpg';
        
        // Try to find an existing placeholder in media library
        $existing = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'meta_query' => [
                [
                    'key' => '_ng_placeholder_image',
                    'value' => '1'
                ]
            ],
            'posts_per_page' => 1
        ]);
        
        if ( ! empty( $existing ) ) {
            $attachment_id = $existing[0]->ID;
            return [
                'success' => true,
                'attachment_id' => $attachment_id,
                'url' => wp_get_attachment_url( $attachment_id ),
                'attribution' => ['photographer' => 'Placeholder'],
                'provider' => 'placeholder'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'No image available - placeholder image not found',
            'url' => $placeholder_url,
            'provider' => 'placeholder'
        ];
    }

    /**
     * Get image API key for provider
     * 
     * @param string $provider
     * @return string API key or empty string
     */
    private function get_image_api_key( $provider ) {
        $settings = get_option( 'ng_ai_settings', [] );
        $image_keys = $settings['image_api_keys'] ?? [];
        
        // For Pixabay, use a default key if none provided (you can get a free key)
        if ( $provider === 'pixabay' && empty( $image_keys['pixabay'] ) ) {
            return '9353029-a40945811fa698560c58b388c'; // Demo key - replace with your own
        }
        
        return $image_keys[$provider] ?? '';
    }

    /**
     * Get fallback evaluation with enhanced structure
     * 
     * @param array $deal_data
     * @param string $reason
     * @return array
     */
    private function get_fallback_evaluation( $deal_data, $reason ) {
        $score = 50.0; // Default score (1-100 scale)
        
        // Simple scoring based on available data
        if ( !empty( $deal_data['price'] ) && !empty( $deal_data['original_price'] ) ) {
            $discount = ( $deal_data['original_price'] - $deal_data['price'] ) / $deal_data['original_price'];
            $score = min( 100, max( 1, 50 + ( $discount * 100 ) ) );
        }
        
        return [
            'score' => round( $score, 1 ),
            'reasoning' => "Fallback evaluation: $reason",
            'value_score' => round( $score, 1 ),
            'destination_score' => 50.0,
            'urgency_score' => 50.0,
            'recommendation' => 'Manual review recommended due to AI service unavailability'
        ];
    }

    /**
     * Get fallback content
     * 
     * @param array $deal_data
     * @param string $reason
     * @return array
     */
    private function get_fallback_content( $deal_data, $reason ) {
        $title = !empty( $deal_data['title'] ) ? $deal_data['title'] : 'Travel Deal';
        $destination = !empty( $deal_data['destination'] ) ? $deal_data['destination'] : 'Unknown';
        $price = !empty( $deal_data['price'] ) ? '$' . $deal_data['price'] : 'Price not available';
        
        return [
            'title' => $title,
            'content' => "<h2>Amazing Deal to $destination</h2><p>Don't miss this incredible travel offer starting from just $price!</p><p>This deal was generated automatically. Contact us for more details.</p>",
            'excerpt' => "Great travel deal to $destination starting from $price.",
            'tags' => ['travel', 'deals', strtolower( $destination )],
            'meta_description' => "Amazing travel deal to $destination starting from $price. Book now!"
        ];
    }

    /**
     * Update usage statistics
     * 
     * @param string $provider
     * @param array $response
     */
    private function update_usage_stats( $provider, $response ) {
        $stats = get_option( 'ng_usage_stats', [
            'total_requests' => 0,
            'total_cost' => 0,
            'last_reset' => current_time( 'mysql' )
        ]);
        
        $stats['total_requests']++;
        
        // Estimate cost (rough calculation)
        $tokens_used = $this->estimate_tokens_used( $provider, $response );
        $cost = $this->estimate_cost( $provider, $tokens_used );
        
        $stats['total_cost'] += $cost;
        
        update_option( 'ng_usage_stats', $stats );
    }

    /**
     * Estimate tokens used in response
     * 
     * @param string $provider
     * @param array $response
     * @return int
     */
    private function estimate_tokens_used( $provider, $response ) {
        // Rough estimation - in production, you'd want more accurate calculation
        $tokens = 0;
        
        switch ( $provider ) {
            case 'openai':
            case 'grok':
            case 'perplexity':
                $tokens = $response['usage']['total_tokens'] ?? 0;
                break;
                
            case 'gemini':
                // Gemini doesn't provide token count in response
                $tokens = 100; // Rough estimate
                break;
        }
        
        return intval( $tokens );
    }

    /**
     * Estimate cost based on tokens used
     * 
     * @param string $provider
     * @param int $tokens
     * @return float
     */
    private function estimate_cost( $provider, $tokens ) {
        $cost_per_1k = [
            'openai' => 0.002, // GPT-3.5-turbo
            'gemini' => 0.0005,
            'grok' => 0.003,
            'perplexity' => 0.001
        ];
        
        $rate = $cost_per_1k[$provider] ?? 0.002;
        return ( $tokens / 1000 ) * $rate;
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

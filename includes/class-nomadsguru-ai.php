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
     * Build evaluation prompt
     * 
     * @param array $deal_data
     * @return string
     */
    private function build_evaluation_prompt( $deal_data ) {
        $prompt = "Evaluate this travel deal and provide a score from 1-10 with reasoning:\n\n";
        
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
            $prompt .= "Price: $" . $deal_data['price'] . "\n";
        }
        
        if ( !empty( $deal_data['original_price'] ) ) {
            $prompt .= "Original Price: $" . $deal_data['original_price'] . "\n";
        }
        
        if ( !empty( $deal_data['valid_until'] ) ) {
            $prompt .= "Valid Until: " . $deal_data['valid_until'] . "\n";
        }
        
        $prompt .= "\nPlease respond in JSON format:\n";
        $prompt .= "{\n";
        $prompt .= "  \"score\": 1-10,\n";
        $prompt .= "  \"reasoning\": \"Brief explanation of the score\"\n";
        $prompt .= "}";
        
        return $prompt;
    }

    /**
     * Build content generation prompt
     * 
     * @param array $deal_data
     * @return string
     */
    private function build_content_prompt( $deal_data ) {
        $prompt = "Generate engaging content for this travel deal:\n\n";
        
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
            $prompt .= "Price: $" . $deal_data['price'] . "\n";
        }
        
        $prompt .= "\nPlease respond in JSON format:\n";
        $prompt .= "{\n";
        $prompt .= "  \"title\": \"Engaging blog post title\",\n";
        $prompt .= "  \"content\": \"Full blog post content in HTML format\",\n";
        $prompt .= "  \"excerpt\": \"Brief excerpt\",\n";
        $prompt .= "  \"tags\": [\"tag1\", \"tag2\", \"tag3\"]\n";
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
     * Parse evaluation response
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
        
        // Parse JSON response
        $data = json_decode( $content, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return [
                'score' => 5.0,
                'reasoning' => 'Failed to parse AI response'
            ];
        }
        
        return [
            'score' => floatval( $data['score'] ?? 5.0 ),
            'reasoning' => sanitize_text_field( $data['reasoning'] ?? 'No reasoning provided' )
        ];
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
        
        // Parse JSON response
        $data = json_decode( $content, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return $this->get_fallback_content( [], 'Failed to parse AI response' );
        }
        
        return [
            'title' => sanitize_text_field( $data['title'] ?? 'Generated Deal' ),
            'content' => wp_kses_post( $data['content'] ?? '<p>Generated content will appear here.</p>' ),
            'excerpt' => sanitize_textarea_field( $data['excerpt'] ?? 'Generated travel deal content.' ),
            'tags' => array_map( 'sanitize_text_field', $data['tags'] ?? [] )
        ];
    }

    /**
     * Get fallback evaluation
     * 
     * @param array $deal_data
     * @param string $reason
     * @return array
     */
    private function get_fallback_evaluation( $deal_data, $reason ) {
        $score = 5.0; // Default score
        
        // Simple scoring based on available data
        if ( !empty( $deal_data['price'] ) && !empty( $deal_data['original_price'] ) ) {
            $discount = ( $deal_data['original_price'] - $deal_data['price'] ) / $deal_data['original_price'];
            $score = min( 10, max( 1, 5 + ( $discount * 10 ) ) );
        }
        
        return [
            'score' => $score,
            'reasoning' => "Fallback evaluation: $reason"
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
            'tags' => ['travel', 'deals', strtolower( $destination )]
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

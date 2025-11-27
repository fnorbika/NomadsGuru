<?php

namespace NomadsGuru\Services;

class AIService {

	/**
	 * API endpoint for OpenAI
	 */
	const OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';

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

		$result = $this->parse_evaluation_response( $response );
		
		// Track usage for cost monitoring
		$this->track_usage( 'evaluation', $response['usage'] ?? [] );
		
		return $result;
	}

	/**
	 * Generate content for a deal using AI
	 *
	 * @param array $deal_data
	 * @return array Title, description, body
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

		$result = $this->parse_content_response( $response );
		
		// Track usage for cost monitoring
		$this->track_usage( 'content', $response['usage'] ?? [] );
		
		return $result;
	}

	/**
	 * Get API key from settings
	 *
	 * @return string|null
	 */
	private function get_api_key() {
		$settings = get_option( 'ng_ai_settings', [] );
		return $settings['api_key'] ?? null;
	}

	/**
	 * Build evaluation prompt for AI
	 *
	 * @param array $deal_data
	 * @return string
	 */
	private function build_evaluation_prompt( $deal_data ) {
		$prompt = "Evaluate this travel deal on a scale of 0-100:\n";
		$prompt .= "Destination: " . ( $deal_data['destination'] ?? 'Unknown' ) . "\n";
		$prompt .= "Price: " . ( $deal_data['currency'] ?? 'USD' ) . " " . ( $deal_data['discounted_price'] ?? 'N/A' ) . "\n";
		$prompt .= "Original Price: " . ( $deal_data['currency'] ?? 'USD' ) . " " . ( $deal_data['original_price'] ?? 'N/A' ) . "\n";
		
		if ( isset( $deal_data['travel_start'] ) && isset( $deal_data['travel_end'] ) ) {
			$prompt .= "Travel Dates: " . $deal_data['travel_start'] . " to " . $deal_data['travel_end'] . "\n";
		}
		
		$prompt .= "\nReturn ONLY a JSON object with: {\"score\": 85, \"reasoning\": \"detailed explanation\"}";
		
		return $prompt;
	}

	/**
	 * Build content generation prompt for AI
	 *
	 * @param array $deal_data
	 * @return string
	 */
	private function build_content_prompt( $deal_data ) {
		$prompt = "Write a travel article for this deal:\n";
		$prompt .= "Destination: " . ( $deal_data['destination'] ?? 'Unknown' ) . "\n";
		$prompt .= "Price: " . ( $deal_data['currency'] ?? 'USD' ) . " " . ( $deal_data['discounted_price'] ?? 'N/A' ) . "\n";
		
		if ( isset( $deal_data['original_price'] ) ) {
			$discount = 0;
			if ( $deal_data['original_price'] > 0 ) {
				$discount = ( ( $deal_data['original_price'] - $deal_data['discounted_price'] ) / $deal_data['original_price'] ) * 100;
			}
			$prompt .= "Discount: " . round( $discount ) . "%\n";
		}
		
		$prompt .= "\nGenerate JSON with: {\"title\": \"catchy title\", \"meta_description\": \"SEO-friendly description\", \"body\": \"HTML content with headings and paragraphs\"}";
		
		return $prompt;
	}

	/**
	 * Make API call to OpenAI
	 *
	 * @param string $api_key
	 * @param string $prompt
	 * @param string $type
	 * @return array|\WP_Error
	 */
	private function make_api_call( $api_key, $prompt, $type ) {
		$settings = get_option( 'ng_ai_settings', [] );
		$model = $settings['model'] ?? 'gpt-3.5-turbo';
		$temperature = floatval( $settings['temperature'] ?? 0.7 );
		$max_tokens = intval( $settings['max_tokens'] ?? 500 );
		
		// Adjust tokens based on type
		if ( $type === 'content' ) {
			$max_tokens = max( $max_tokens, 1000 );
		}

		$body = [
			'model' => $model,
			'messages' => [
				[
					'role' => 'system',
					'content' => $type === 'evaluation' 
						? 'You are a travel deal expert. Evaluate deals objectively and provide detailed reasoning.'
						: 'You are a travel writer. Create engaging, SEO-friendly content about travel deals.'
				],
				[
					'role' => 'user',
					'content' => $prompt
				]
			],
			'temperature' => $temperature,
			'max_tokens' => $max_tokens
		];

		$response = wp_remote_post( self::OPENAI_API_URL, [
			'headers' => [
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type' => 'application/json'
			],
			'body' => json_encode( $body ),
			'timeout' => $type === 'content' ? 45 : 30,
			'user-agent' => 'NomadsGuru-Plugin/1.0'
		] );

		if ( is_wp_error( $response ) ) {
			error_log( 'NomadsGuru AI API Error: ' . $response->get_error_message() );
			return $response;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $http_code !== 200 ) {
			$error_msg = $body['error']['message'] ?? 'Unknown API error';
			error_log( "NomadsGuru AI API Error (HTTP $http_code): $error_msg" );
			return new \WP_Error( 'api_error', $error_msg, [ 'status' => $http_code ] );
		}

		return $body;
	}

	/**
	 * Parse evaluation response
	 *
	 * @param array $response
	 * @return array
	 */
	private function parse_evaluation_response( $response ) {
		$content = $response['choices'][0]['message']['content'] ?? '';
		$result = json_decode( $content, true );

		if ( ! $result || ! isset( $result['score'] ) ) {
			return [
				'score' => 50,
				'reasoning' => 'Failed to parse AI response'
			];
		}

		return [
			'score' => max( 0, min( 100, intval( $result['score'] ) ) ),
			'reasoning' => $result['reasoning'] ?? 'No reasoning provided'
		];
	}

	/**
	 * Parse content response
	 *
	 * @param array $response
	 * @return array
	 */
	private function parse_content_response( $response ) {
		$content = $response['choices'][0]['message']['content'] ?? '';
		$result = json_decode( $content, true );

		if ( ! $result || ! isset( $result['title'] ) ) {
			return $this->get_fallback_content( [], 'Failed to parse AI response' );
		}

		return [
			'title' => sanitize_text_field( $result['title'] ),
			'meta_description' => sanitize_text_field( $result['meta_description'] ?? '' ),
			'body' => wp_kses_post( $result['body'] ?? '' )
		];
	}

	/**
	 * Get fallback evaluation when AI fails
	 *
	 * @param array $deal_data
	 * @param string $error_reason
	 * @return array
	 */
	private function get_fallback_evaluation( $deal_data, $error_reason ) {
		$score = 0;
		$reason = [];

		// Basic discount calculation
		if ( ! empty( $deal_data['original_price'] ) && ! empty( $deal_data['discounted_price'] ) ) {
			$discount = ( ( $deal_data['original_price'] - $deal_data['discounted_price'] ) / $deal_data['original_price'] ) * 100;
			
			if ( $discount > 50 ) {
				$score += 40;
				$reason[] = "Excellent discount of " . round( $discount ) . "%";
			} elseif ( $discount > 30 ) {
				$score += 30;
				$reason[] = "Good discount of " . round( $discount ) . "%";
			} else {
				$score += 20;
				$reason[] = "Moderate discount of " . round( $discount ) . "%";
			}
		} else {
			$score += 25;
			$reason[] = "Price information incomplete";
		}

		// Add basic scoring for other factors
		$score += 25; // Base score
		$reason[] = "AI evaluation unavailable: $error_reason";

		return [
			'score' => min( 100, $score ),
			'reasoning' => implode( "; ", $reason )
		];
	}

	/**
	 * Get fallback content when AI fails
	 *
	 * @param array $deal_data
	 * @param string $error_reason
	 * @return array
	 */
	private function get_fallback_content( $deal_data, $error_reason ) {
		$destination = isset( $deal_data['destination'] ) ? $deal_data['destination'] : 'Unknown Destination';
		$price = isset( $deal_data['discounted_price'] ) ? $deal_data['discounted_price'] : 'N/A';

		return [
			'title' => "Travel Deal: Fly to $destination",
			'meta_description' => "Discover amazing deals to $destination. Book now and save on your next adventure.",
			'body' => "
				<h2>Discover $destination</h2>
				<p>Experience the beauty of $destination with this exclusive deal.</p>
				<p><strong>Price:</strong> $$price</p>
				<p><strong>Note:</strong> AI-generated content unavailable ($error_reason). This is basic template content.</p>
			"
		];
	}

	/**
	 * Track API usage for cost monitoring
	 *
	 * @param string $type
	 * @param array $usage
	 */
	private function track_usage( $type, $usage ) {
		$stats = get_option( 'ng_ai_usage_stats', [] );
		$today = date( 'Y-m-d' );
		
		if ( ! isset( $stats[$today] ) ) {
			$stats[$today] = [
				'evaluation_calls' => 0,
				'content_calls' => 0,
				'total_tokens' => 0,
				'total_cost' => 0.0
			];
		}

		$stats[$today][ $type . '_calls' ]++;
		$stats[$today]['total_tokens'] += $usage['total_tokens'] ?? 0;
		
		// Rough cost calculation (adjust based on actual pricing)
		$cost_per_token = 0.000002; // ~$0.002 per 1K tokens for GPT-3.5
		$stats[$today]['total_cost'] += ( $usage['total_tokens'] ?? 0 ) * $cost_per_token;

		update_option( 'ng_ai_usage_stats', $stats );
	}

	/**
	 * Get usage statistics
	 *
	 * @return array
	 */
	public function get_usage_stats() {
		return get_option( 'ng_ai_usage_stats', [] );
	}

	/**
	 * Test API connection
	 *
	 * @return array|\WP_Error
	 */
	public function test_connection() {
		$api_key = $this->get_api_key();
		
		if ( empty( $api_key ) ) {
			return new \WP_Error( 'no_key', 'API key not configured' );
		}

		$test_deal = [
			'destination' => 'Test City',
			'currency' => 'USD',
			'discounted_price' => 299,
			'original_price' => 599,
			'travel_start' => '2025-06-01',
			'travel_end' => '2025-06-07'
		];

		$result = $this->evaluate_deal( $test_deal );
		
		if ( isset( $result['score'] ) && $result['score'] > 0 ) {
			return [
				'success' => true,
				'message' => 'API connection successful',
				'test_score' => $result['score']
			];
		}

		return new \WP_Error( 'test_failed', 'API test failed' );
	}
}

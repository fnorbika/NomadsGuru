<?php
/**
 * Core plugin logic for WP Autoplugin Content Curator.
 * Handles cron job scheduling, content extraction, AI integration, and post creation.
 *
 * @package WACC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WACC_Core class.
 * Manages the core functionalities of the plugin.
 */
class WACC_Core {

	/**
	 * The cron hook name.
	 */
	const CRON_HOOK = 'wp_autoplugin_monitor_articles_cron_hook';

	/**
	 * Constructor.
	 * Initializes the class.
	 */
	public function __construct() {
		// No actions hooked in constructor, handled by run() method.
	}

	/**
	 * Runs the core plugin logic.
	 * Hooks the cron job callback.
	 */
	public function run() {
		add_action( self::CRON_HOOK, array( $this, 'monitor_articles_cron_callback' ) );
	}

	/**
	 * Activates the plugin.
	 * Schedules the cron job to run daily.
	 */
	public static function activate() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Deactivates the plugin.
	 * Clears the scheduled cron job.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Cron job callback function.
	 * Monitors websites, extracts content, uses AI for rewriting, and creates posts.
	 */
	public function monitor_articles_cron_callback() {
		// Retrieve settings.
		$keywords_raw        = get_option( 'wacc_customer_keywords', '' );
		$target_websites_raw = get_option( 'wacc_target_websites', '' );
		$gemini_api_key      = get_option( 'wacc_gemini_api_key', '' );
		$ai_model_identifier = get_option( 'wacc_ai_model_identifier', 'gemini-apu-custom' ); // Default to the custom model ID.
		$post_status         = get_option( 'wacc_post_status', 'draft' );

		// Validate essential settings.
		if ( empty( $gemini_api_key ) ) {
			error_log( 'WACC Error: Gemini API Key is not set. Skipping content monitoring.' );
			return;
		}

		$keywords        = array_filter( array_map( 'trim', explode( "\n", $keywords_raw ) ) );
		$target_websites = array_filter( array_map( 'trim', explode( "\n", $target_websites_raw ) ) );

		if ( empty( $target_websites ) ) {
			error_log( 'WACC Info: No target websites configured. Skipping content monitoring.' );
			return;
		}

		// Iterate through each target website.
		foreach ( $target_websites as $url ) {
			$this->process_single_website( $url, $keywords, $gemini_api_key, $ai_model_identifier, $post_status );
		}
	}

	/**
	 * Processes a single target website for content extraction and AI rewriting.
	 *
	 * @param string $url                 The URL of the website to monitor.
	 * @param array  $keywords            Array of customer keywords.
	 * @param string $gemini_api_key      The Gemini API key.
	 * @param string $ai_model_identifier The identifier for the AI model to use.
	 * @param string $post_status         The desired status for new posts.
	 */
	private function process_single_website( $url, $keywords, $gemini_api_key, $ai_model_identifier, $post_status ) {
		// Check if this URL has already been processed (a post with this original URL as meta already exists).
		$args = array(
			'post_type'      => 'post',
			'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
			'meta_query'     => array(
				array(
					'key'   => '_wacc_original_url',
					'value' => $url,
				),
			),
			'posts_per_page' => 1,
			'fields'         => 'ids',
		);
		$existing_posts = get_posts( $args );

		if ( ! empty( $existing_posts ) ) {
			error_log( sprintf( 'WACC Info: Article from %s already processed (Post ID: %d). Skipping.', $url, $existing_posts[0] ) );
			return;
		}

		// Fetch website content.
		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			error_log( sprintf( 'WACC Error: Failed to fetch content from %s. Error: %s', $url, $response->get_error_message() ) );
			return;
		}

		$html = wp_remote_retrieve_body( $response );
		if ( empty( $html ) ) {
			error_log( sprintf( 'WACC Info: No content retrieved from %s.', $url ) );
			return;
		}

		// Content Extraction (Best Effort).
		$extracted_content = $this->extract_main_content( $html );

		if ( empty( $extracted_content ) ) {
			error_log( sprintf( 'WACC Info: Could not extract main article content from %s.', $url ) );
			return;
		}

		// Keyword Filtering.
		$found_keyword = false;
		if ( ! empty( $keywords ) ) {
			foreach ( $keywords as $keyword ) {
				if ( stripos( $extracted_content, $keyword ) !== false ) {
					$found_keyword = true;
					break;
				}
			}
			if ( ! $found_keyword ) {
				error_log( sprintf( 'WACC Info: Article from %s does not contain any specified keywords. Skipping.', $url ) );
				return;
			}
		}

		// Content Evaluation (Basic heuristics).
		// Minimum word count.
		$word_count = str_word_count( strip_tags( $extracted_content ) );
		if ( $word_count < 200 ) { // Arbitrary minimum word count for "useful" content.
			error_log( sprintf( 'WACC Info: Article from %s has insufficient word count (%d words). Skipping.', $url, $word_count ) );
			return;
		}

		// AI Rewriting.
		$rewritten_content = $this->rewrite_content_with_gemini( $extracted_content, $gemini_api_key, $ai_model_identifier );

		if ( is_wp_error( $rewritten_content ) ) {
			error_log( sprintf( 'WACC Error: AI rewriting failed for %s. Error: %s', $url, $rewritten_content->get_error_message() ) );
			return;
		}

		if ( empty( $rewritten_content ) ) {
			error_log( sprintf( 'WACC Error: AI returned empty content for %s.', $url ) );
			return;
		}

		// Attempt to generate a title from the rewritten content or original URL.
		$post_title = $this->generate_post_title( $rewritten_content, $url );

		// WordPress Post Creation.
		$new_post_args = array(
			'post_title'   => wp_strip_all_tags( $post_title ),
			'post_content' => $rewritten_content,
			'post_status'  => $post_status,
			'post_type'    => 'post',
			'post_author'  => get_current_user_id() ? get_current_user_id() : 1, // Assign to current user or admin.
		);

		$post_id = wp_insert_post( $new_post_args, true );

		if ( is_wp_error( $post_id ) ) {
			error_log( sprintf( 'WACC Error: Failed to create WordPress post for %s. Error: %s', $url, $post_id->get_error_message() ) );
		} else {
			update_post_meta( $post_id, '_wacc_original_url', $url );
			update_post_meta( $post_id, '_wacc_ai_model', $ai_model_identifier );
			error_log( sprintf( 'WACC Success: New post created (ID: %d) from %s.', $post_id, $url ) );
		}
	}

	/**
	 * Extracts the main article content from HTML using DOMDocument.
	 * This is a best-effort approach and may not work for all websites.
	 *
	 * @param string $html The HTML content of the page.
	 * @return string The extracted main content, or empty string if not found.
	 */
	private function extract_main_content( $html ) {
		libxml_use_internal_errors( true ); // Suppress HTML parsing errors.
		$dom = new DOMDocument();

		// Check for mbstring extension before using mb_convert_encoding.
		if ( extension_loaded( 'mbstring' ) ) {
			// Use mb_convert_encoding to handle various character encodings.
			$dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
		} else {
			// Fallback if mbstring is not available.
			error_log( 'WACC Warning: mbstring extension not loaded. Content encoding might be less robust without it.' );
			$dom->loadHTML( $html );
		}

		$xpath = new DOMXPath( $dom );

		$content_nodes = array();

		// Common article containers.
		$queries = array(
			'//article',
			'//main',
			'//div[contains(@class, "entry-content")]',
			'//div[contains(@class, "post-content")]',
			'//div[contains(@class, "article-content")]',
			'//div[contains(@id, "content")]',
			'//div[contains(@id, "main-content")]',
		);

		foreach ( $queries as $query ) {
			$nodes = $xpath->query( $query );
			if ( $nodes->length > 0 ) {
				foreach ( $nodes as $node ) {
					$content_nodes[] = $node;
				}
			}
		}

		// If multiple nodes found, try to pick the largest one by text length.
		$best_content = '';
		$max_length   = 0;

		foreach ( $content_nodes as $node ) {
			$node_html = $dom->saveHTML( $node );
			$text      = strip_tags( $node_html );
			if ( strlen( $text ) > $max_length ) {
				$max_length   = strlen( $text );
				$best_content = $node_html;
			}
		}

		libxml_clear_errors();
		return $best_content;
	}

	/**
	 * Rewrites content using the Gemini API.
	 *
	 * @param string $content             The content to rewrite.
	 * @param string $api_key             The Gemini API key.
	 * @param string $model_identifier    The identifier for the AI model to use.
	 * @return string|WP_Error Rewritten content on success, WP_Error on failure.
	 */
	private function rewrite_content_with_gemini( $content, $api_key, $model_identifier ) {
		$endpoint = sprintf( 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s', $model_identifier, $api_key );

		$prompt = "Rewrite the following article content to be clear, engaging, and factually accurate based *only* on the provided source material. Do not add new information or speculate. Ensure the tone is appropriate for a blog post. Provide a suitable title for the rewritten article at the beginning, followed by the content.
		\n\nOriginal Content:\n" . $content;

		$body = array(
			'contents'         => array(
				array(
					'parts' => array(
						array( 'text' => $prompt ),
					),
				),
			),
			'generationConfig' => array(
				'temperature'     => 0.7,
				'topP'            => 0.95,
				'topK'            => 40,
				'maxOutputTokens' => 2048, // Adjust as needed for desired output length.
			),
		);

		$response = wp_remote_post(
			$endpoint,
			array(
				'headers'     => array(
					'Content-Type' => 'application/json',
				),
				'body'        => wp_json_encode( $body ),
				'timeout'     => 60, // Increased timeout for AI requests.
				'data_format' => 'body',
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'wacc_gemini_api_error', $response->get_error_message() );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data          = json_decode( $response_body, true );

		if ( 200 !== $response_code ) {
			$error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Unknown Gemini API error.';
			return new WP_Error( 'wacc_gemini_api_error', sprintf( 'Gemini API returned status %d: %s', $response_code, $error_message ) );
		}

		if ( ! isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			return new WP_Error( 'wacc_gemini_api_error', 'Gemini API response missing expected content.' );
		}

		return $data['candidates'][0]['content']['parts'][0]['text'];
	}

	/**
	 * Generates a post title from the AI-rewritten content or a fallback.
	 *
	 * @param string $rewritten_content The AI-generated content.
	 * @param string $original_url      The original URL as a fallback.
	 * @return string The generated post title.
	 */
	private function generate_post_title( $rewritten_content, $original_url ) {
		// Attempt to extract the first line as a title if the AI was prompted to include one.
		$lines = explode( "\n", $rewritten_content );
		if ( ! empty( $lines[0] ) && strlen( $lines[0] ) < 150 ) { // Heuristic for a title: first line, reasonable length.
			return trim( $lines[0] );
		}

		// Fallback: Use the original URL and try to make it readable.
		$parsed_url = wp_parse_url( $original_url );
		$host       = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
		$path       = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';

		if ( ! empty( $path ) && '/' !== $path ) {
			$path_parts = array_filter( explode( '/', $path ) );
			$last_part  = end( $path_parts );
			$title      = str_replace( array( '-', '_', '.html', '.php' ), ' ', $last_part );
			$title      = ucwords( trim( $title ) );
			if ( ! empty( $title ) ) {
				return 'Curated: ' . $title;
			}
		}

		return 'Curated Content from ' . $host;
	}
}
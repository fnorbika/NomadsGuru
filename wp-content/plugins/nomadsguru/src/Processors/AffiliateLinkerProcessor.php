<?php

namespace NomadsGuru\Processors;

class AffiliateLinkerProcessor {

	/**
	 * Process content and replace links with affiliate links
	 *
	 * @param string $content
	 * @param string $original_link
	 * @return string
	 */
	public function process( $content, $original_link ) {
		$affiliate_link = $this->transform_link( $original_link );
		
		// Replace the original link in content with affiliate link
		$content = str_replace( $original_link, $affiliate_link, $content );
		
		return $content;
	}

	/**
	 * Transform a link into an affiliate link
	 *
	 * @param string $url
	 * @return string
	 */
	public function transform_link( $url ) {
		// Get active affiliate programs
		$programs = $this->get_active_programs();

		if ( empty( $programs ) ) {
			return $url;
		}

		// For simplicity, use the first active program
		$program = $programs[0];

		// Apply URL pattern
		if ( ! empty( $program->url_pattern ) ) {
			return str_replace( '{url}', urlencode( $url ), $program->url_pattern );
		}

		return $url;
	}

	/**
	 * Get active affiliate programs
	 *
	 * @return array
	 */
	private function get_active_programs() {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_affiliate_programs';
		return $wpdb->get_results( "SELECT * FROM $table WHERE is_active = 1 LIMIT 1" );
	}
}

<?php

namespace NomadsGuru\Integrations;

interface DealSourceInterface {

	/**
	 * Get the unique identifier for the source type (e.g., 'skyscanner', 'booking')
	 *
	 * @return string
	 */
	public function get_id();

	/**
	 * Get the display name for the source
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Validate credentials
	 *
	 * @param array $credentials
	 * @return bool|\WP_Error
	 */
	public function validate_credentials( $credentials );

	/**
	 * Fetch deals from the source
	 *
	 * @param array $config Source configuration (credentials, etc.)
	 * @return array|\WP_Error Array of raw deal data
	 */
	public function fetch_deals( $config );

	/**
	 * Map raw deal data to standardized format
	 *
	 * @param array $raw_data
	 * @return array Standardized deal array
	 */
	public function map_deal( $raw_data );
}

<?php

namespace NomadsGuru\Integrations;

interface AffiliateProgramInterface {

	/**
	 * Get the unique identifier for the program
	 *
	 * @return string
	 */
	public function get_id();

	/**
	 * Get the display name for the program
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Transform a standard URL into an affiliate link
	 *
	 * @param string $url Original URL
	 * @param array  $config Program configuration
	 * @return string Affiliate URL
	 */
	public function generate_link( $url, $config );

	/**
	 * Validate credentials/configuration
	 *
	 * @param array $config
	 * @return bool|\WP_Error
	 */
	public function validate_config( $config );
}

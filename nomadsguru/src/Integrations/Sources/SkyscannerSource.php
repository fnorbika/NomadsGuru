<?php

namespace NomadsGuru\Integrations\Sources;

use NomadsGuru\Integrations\DealSourceInterface;

class SkyscannerSource implements DealSourceInterface {

	/**
	 * Get source name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'skyscanner';
	}

	/**
	 * Get source title
	 *
	 * @return string
	 */
	public function get_title(): string {
		return 'Skyscanner';
	}

	/**
	 * Fetch deals
	 *
	 * @return array
	 */
	public function get_deals(): array {
		// Mock implementation for now
		return array(
			array(
				'external_id'      => 'sky_12345',
				'title'            => 'Flight to London',
				'destination'      => 'London, UK',
				'original_price'   => 500.00,
				'discounted_price' => 350.00,
				'currency'         => 'USD',
				'url'              => 'https://skyscanner.com/example',
			),
		);
	}

	/**
	 * Test connection
	 *
	 * @return bool
	 */
	public function test_connection(): bool {
		// Mock connection test
		return true;
	}

	/**
	 * Get credentials fields
	 *
	 * @return array
	 */
	public function get_credentials_fields(): array {
		return array(
			'api_key' => 'API Key',
		);
	}
}

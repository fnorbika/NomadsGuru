<?php

namespace NomadsGuru\Processors;

use NomadsGuru\Services\ImageService;

class ImageFinderProcessor {

	/**
	 * @var ImageService
	 */
	private $image_service;

	public function __construct() {
		$this->image_service = new ImageService();
	}

	/**
	 * Find images for a deal
	 *
	 * @param array $deal_data
	 * @return string|null Image URL
	 */
	public function process( $deal_data ) {
		$query = isset( $deal_data['destination'] ) ? $deal_data['destination'] : '';
		
		if ( empty( $query ) ) {
			return null;
		}

		$images = $this->image_service->find_images( $query . ' travel', 1 );

		return ! empty( $images ) ? $images[0] : null;
	}
}

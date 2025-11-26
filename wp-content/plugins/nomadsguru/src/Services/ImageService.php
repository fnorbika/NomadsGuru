<?php

namespace NomadsGuru\Services;

class ImageService {

	/**
	 * Find images for a query
	 *
	 * @param string $query
	 * @param int    $count
	 * @return array List of image URLs
	 */
	public function find_images( $query, $count = 1 ) {
		// MOCK IMPLEMENTATION
		// In a real scenario, this would call Pexels/Unsplash API
		
		$mock_images = array(
			'https://images.pexels.com/photos/3278215/pexels-photo-3278215.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1', // Travel
			'https://images.pexels.com/photos/237272/pexels-photo-237272.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1', // Beach
			'https://images.pexels.com/photos/1271619/pexels-photo-1271619.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1', // Mountain
		);

		return array_slice( $mock_images, 0, $count );
	}
}

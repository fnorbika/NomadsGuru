<?php

namespace NomadsGuru\Processors;

use NomadsGuru\Services\AIService;

class ContentGeneratorProcessor {

	/**
	 * @var AIService
	 */
	private $ai_service;

	public function __construct() {
		$this->ai_service = new AIService();
	}

	/**
	 * Generate content for a deal
	 *
	 * @param array $deal_data
	 * @return array Generated content (title, meta_description, body)
	 */
	public function process( $deal_data ) {
		return $this->ai_service->generate_content( $deal_data );
	}
}

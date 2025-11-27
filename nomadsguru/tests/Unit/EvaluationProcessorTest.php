<?php

namespace NomadsGuru\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NomadsGuru\Processors\EvaluationProcessor;

class EvaluationProcessorTest extends TestCase {

	public function test_process_scores_deal() {
		$processor = new EvaluationProcessor();
		
		$raw_deal = array(
			'title'       => 'Test Deal',
			'destination' => 'Test City',
			'price'       => 100,
		);

		$processed_deal = $processor->process( $raw_deal );

		$this->assertArrayHasKey( 'evaluation_score', $processed_deal );
		$this->assertGreaterThanOrEqual( 0, $processed_deal['evaluation_score'] );
		$this->assertLessThanOrEqual( 100, $processed_deal['evaluation_score'] );
	}
}

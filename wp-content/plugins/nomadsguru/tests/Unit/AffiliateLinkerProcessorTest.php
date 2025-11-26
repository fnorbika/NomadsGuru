<?php

namespace NomadsGuru\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NomadsGuru\Processors\AffiliateLinkerProcessor;

class AffiliateLinkerProcessorTest extends TestCase {

	public function test_process_adds_affiliate_params() {
		$processor = new AffiliateLinkerProcessor();
		
		$deal_data = array(
			'url' => 'https://example.com/deal',
		);

		$processed_deal = $processor->process( $deal_data );

		$this->assertArrayHasKey( 'affiliate_url', $processed_deal );
		$this->assertStringContainsString( 'aff_id=nomadsguru-21', $processed_deal['affiliate_url'] );
		$this->assertStringContainsString( 'utm_source=nomadsguru', $processed_deal['affiliate_url'] );
	}
}

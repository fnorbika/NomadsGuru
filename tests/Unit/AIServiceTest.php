<?php

use PHPUnit\Framework\TestCase;
use NomadsGuru\Services\AIService;

class AIServiceTest extends TestCase {

    private $ai_service;
    private $test_deal;

    protected function setUp(): void {
        $this->ai_service = new AIService();
        $this->test_deal = [
            'destination' => 'Paris, France',
            'currency' => 'USD',
            'discounted_price' => 499,
            'original_price' => 899,
            'travel_start' => '2025-06-01',
            'travel_end' => '2025-06-07'
        ];
    }

    /**
     * Test fallback evaluation when no API key is configured
     */
    public function test_fallback_evaluation() {
        // Ensure no API key is set
        update_option('ng_ai_settings', ['api_key' => '']);
        
        $result = $this->ai_service->evaluate_deal($this->test_deal);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('reasoning', $result);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
        $this->assertStringContainsString('unavailable', $result['reasoning']);
    }

    /**
     * Test fallback content generation when no API key is configured
     */
    public function test_fallback_content_generation() {
        // Ensure no API key is set
        update_option('ng_ai_settings', ['api_key' => '']);
        
        $result = $this->ai_service->generate_content($this->test_deal);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('meta_description', $result);
        $this->assertArrayHasKey('body', $result);
        $this->assertStringContainsString('Paris', $result['title']);
        $this->assertStringContainsString('unavailable', $result['body']);
    }

    /**
     * Test evaluation with incomplete deal data
     */
    public function test_evaluation_with_incomplete_data() {
        $incomplete_deal = [
            'destination' => 'Test City'
            // Missing price data
        ];
        
        update_option('ng_ai_settings', ['api_key' => '']);
        
        $result = $this->ai_service->evaluate_deal($incomplete_deal);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('reasoning', $result);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    /**
     * Test content generation with incomplete deal data
     */
    public function test_content_generation_with_incomplete_data() {
        $incomplete_deal = [
            'destination' => 'Test City'
            // Missing price data
        ];
        
        update_option('ng_ai_settings', ['api_key' => '']);
        
        $result = $this->ai_service->generate_content($incomplete_deal);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('meta_description', $result);
        $this->assertArrayHasKey('body', $result);
        $this->assertStringContainsString('Test City', $result['title']);
    }

    /**
     * Test usage statistics tracking
     */
    public function test_usage_statistics_tracking() {
        // Clear existing stats
        delete_option('ng_ai_usage_stats');
        
        // Ensure no API key to use fallback (which still tracks usage)
        update_option('ng_ai_settings', ['api_key' => '']);
        
        // Perform some operations
        $this->ai_service->evaluate_deal($this->test_deal);
        $this->ai_service->generate_content($this->test_deal);
        
        $stats = $this->ai_service->get_usage_stats();
        
        $this->assertIsArray($stats);
        $today = date('Y-m-d');
        $this->assertArrayHasKey($today, $stats);
        
        $today_stats = $stats[$today];
        $this->assertArrayHasKey('evaluation_calls', $today_stats);
        $this->assertArrayHasKey('content_calls', $today_stats);
        $this->assertArrayHasKey('total_tokens', $today_stats);
        $this->assertArrayHasKey('total_cost', $today_stats);
        
        $this->assertEquals(1, $today_stats['evaluation_calls']);
        $this->assertEquals(1, $today_stats['content_calls']);
    }

    /**
     * Test API key retrieval
     */
    public function test_api_key_retrieval() {
        // Test with no API key
        update_option('ng_ai_settings', []);
        $this->assertNull($this->ai_service->get_api_key());
        
        // Test with encrypted API key
        $test_key = 'sk-test123456789';
        $encrypted_key = base64_encode($test_key);
        update_option('ng_ai_settings', ['api_key' => $encrypted_key]);
        
        // Use reflection to access private method for testing
        $reflection = new ReflectionClass($this->ai_service);
        $method = $reflection->getMethod('get_api_key');
        $method->setAccessible(true);
        
        $retrieved_key = $method->invoke($this->ai_service);
        $this->assertEquals($test_key, $retrieved_key);
    }

    /**
     * Test test_connection method with no API key
     */
    public function test_connection_without_api_key() {
        update_option('ng_ai_settings', ['api_key' => '']);
        
        $result = $this->ai_service->test_connection();
        
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('no_key', $result->get_error_code());
    }

    /**
     * Test prompt building
     */
    public function test_prompt_building() {
        // Use reflection to access private methods
        $reflection = new ReflectionClass($this->ai_service);
        
        // Test evaluation prompt
        $eval_method = $reflection->getMethod('build_evaluation_prompt');
        $eval_method->setAccessible(true);
        
        $eval_prompt = $eval_method->invoke($this->ai_service, $this->test_deal);
        
        $this->assertStringContainsString('Paris, France', $eval_prompt);
        $this->assertStringContainsString('499', $eval_prompt);
        $this->assertStringContainsString('899', $eval_prompt);
        $this->assertStringContainsString('2025-06-01', $eval_prompt);
        $this->assertStringContainsString('JSON object', $eval_prompt);
        
        // Test content prompt
        $content_method = $reflection->getMethod('build_content_prompt');
        $content_method->setAccessible(true);
        
        $content_prompt = $content_method->invoke($this->ai_service, $this->test_deal);
        
        $this->assertStringContainsString('Paris, France', $content_prompt);
        $this->assertStringContainsString('499', $content_prompt);
        $this->assertStringContainsString('JSON', $content_prompt);
    }

    /**
     * Test response parsing
     */
    public function test_response_parsing() {
        // Use reflection to access private methods
        $reflection = new ReflectionClass($this->ai_service);
        
        // Test evaluation response parsing
        $eval_method = $reflection->getMethod('parse_evaluation_response');
        $eval_method->setAccessible(true);
        
        $mock_response = [
            'choices' => [
                [
                    'message' => [
                        'content' => '{"score": 85, "reasoning": "Great deal with excellent discount"}'
                    ]
                ]
            ]
        ];
        
        $result = $eval_method->invoke($this->ai_service, $mock_response);
        
        $this->assertEquals(85, $result['score']);
        $this->assertEquals('Great deal with excellent discount', $result['reasoning']);
        
        // Test content response parsing
        $content_method = $reflection->getMethod('parse_content_response');
        $content_method->setAccessible(true);
        
        $mock_content_response = [
            'choices' => [
                [
                    'message' => [
                        'content' => '{"title": "Amazing Paris Deal", "meta_description": "Great offer", "body": "<h1>Test</h1>"}'
                    ]
                ]
            ]
        ];
        
        $result = $content_method->invoke($this->ai_service, $mock_content_response);
        
        $this->assertEquals('Amazing Paris Deal', $result['title']);
        $this->assertEquals('Great offer', $result['meta_description']);
        $this->assertEquals('<h1>Test</h1>', $result['body']);
    }

    /**
     * Test response parsing with invalid data
     */
    public function test_response_parsing_with_invalid_data() {
        $reflection = new ReflectionClass($this->ai_service);
        
        // Test evaluation response with invalid JSON
        $eval_method = $reflection->getMethod('parse_evaluation_response');
        $eval_method->setAccessible(true);
        
        $mock_response = [
            'choices' => [
                [
                    'message' => [
                        'content' => 'invalid json response'
                    ]
                ]
            ]
        ];
        
        $result = $eval_method->invoke($this->ai_service, $mock_response);
        
        $this->assertEquals(50, $result['score']);
        $this->assertEquals('Failed to parse AI response', $result['reasoning']);
        
        // Test content response with invalid data
        $content_method = $reflection->getMethod('parse_content_response');
        $content_method->setAccessible(true);
        
        $mock_content_response = [
            'choices' => [
                [
                    'message' => [
                        'content' => 'invalid json'
                    ]
                ]
            ]
        ];
        
        $result = $content_method->invoke($this->ai_service, $mock_content_response);
        
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('meta_description', $result);
        $this->assertArrayHasKey('body', $result);
    }

    /**
     * Test score boundary validation
     */
    public function test_score_boundary_validation() {
        $reflection = new ReflectionClass($this->ai_service);
        $eval_method = $reflection->getMethod('parse_evaluation_response');
        $eval_method->setAccessible(true);
        
        // Test with score above 100
        $mock_response = [
            'choices' => [
                [
                    'message' => [
                        'content' => '{"score": 150, "reasoning": "Too high"}'
                    ]
                ]
            ]
        ];
        
        $result = $eval_method->invoke($this->ai_service, $mock_response);
        $this->assertEquals(100, $result['score']);
        
        // Test with negative score
        $mock_response['choices'][0]['message']['content'] = '{"score": -10, "reasoning": "Too low"}';
        $result = $eval_method->invoke($this->ai_service, $mock_response);
        $this->assertEquals(0, $result['score']);
    }
}

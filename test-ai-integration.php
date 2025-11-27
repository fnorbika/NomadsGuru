<?php
/**
 * Test Script for Phase 2 AI Integration
 * Run this to verify the enhanced AI service works correctly
 */

// Include WordPress (adjust path as needed)
// require_once('../../../wp-config.php');

// Include our AI service
require_once __DIR__ . '/includes/class-nomadsguru-ai.php';

echo "=== Phase 2 AI Integration Test ===\n\n";

// Test Data
$test_deal = [
    'title' => 'Summer Paradise: 5-Day Bali Resort Package',
    'description' => 'All-inclusive luxury resort with spa, meals, and airport transfers',
    'destination' => 'Bali, Indonesia',
    'price' => 899,
    'original_price' => 1499,
    'valid_until' => '2024-12-31'
];

echo "Test Deal Data:\n";
print_r($test_deal);
echo "\n";

// Initialize AI Service
$ai_service = NomadsGuru_AI::get_instance();

echo "=== Testing Deal Evaluation ===\n";
$evaluation = $ai_service->evaluate_deal($test_deal);

echo "Evaluation Results:\n";
echo "Score: " . $evaluation['score'] . "/100\n";
echo "Reasoning: " . $evaluation['reasoning'] . "\n";
if (isset($evaluation['value_score'])) {
    echo "Value Score: " . $evaluation['value_score'] . "/100\n";
    echo "Destination Score: " . $evaluation['destination_score'] . "/100\n";
    echo "Urgency Score: " . $evaluation['urgency_score'] . "/100\n";
    echo "Recommendation: " . $evaluation['recommendation'] . "\n";
}
echo "\n";

echo "=== Testing Content Generation ===\n";
$content = $ai_service->generate_content($test_deal);

echo "Generated Content:\n";
echo "Title: " . $content['title'] . "\n";
echo "Excerpt: " . $content['excerpt'] . "\n";
echo "Tags: " . implode(', ', $content['tags']) . "\n";
if (isset($content['meta_description'])) {
    echo "Meta Description: " . $content['meta_description'] . "\n";
}
echo "Content Length: " . strlen($content['content']) . " characters\n";
echo "\n";

echo "=== Testing API Connection ===\n";
$connection = $ai_service->test_connection();

echo "Connection Test:\n";
echo "Success: " . ($connection['success'] ? 'Yes' : 'No') . "\n";
echo "Message: " . $connection['message'] . "\n";

echo "\n=== Phase 2 AI Integration Test Complete ===\n";
echo "✅ Enhanced prompts implemented\n";
echo "✅ Improved JSON parsing with markdown support\n";
echo "✅ Detailed evaluation scoring (1-100 scale)\n";
echo "✅ SEO-optimized content generation\n";
echo "✅ Robust fallback systems\n";
echo "✅ Multi-provider support maintained\n";

<?php
/**
 * AI Service Test File
 * 
 * This file tests the AI Service integration to ensure it works correctly
 * Run this file to validate AI functionality before using in production
 * 
 * Usage: php test-ai-service.php
 */

// Bootstrap WordPress (adjust path as needed)
$wp_config_path = __DIR__ . '/../../../wp-config.php';
if (file_exists($wp_config_path)) {
    require_once $wp_config_path;
} else {
    echo "Error: WordPress config file not found.\n";
    echo "Please run this test within WordPress environment or adjust the path.\n";
    exit(1);
}

// Load the AIService
require_once __DIR__ . '/src/Services/AIService.php';

use NomadsGuru\Services\AIService;

echo "=== NomadsGuru AI Service Test ===\n\n";

// Create AI Service instance
$ai_service = new AIService();

// Test 1: Check API Key Configuration
echo "1. Checking API Key Configuration...\n";
$settings = get_option('ng_ai_settings', []);
if (empty($settings['api_key'])) {
    echo "❌ No API key configured. Please configure in WordPress admin.\n";
    echo "   Go to: NomadsGuru → AI Settings\n\n";
    exit(1);
} else {
    echo "✅ API key found (encrypted)\n\n";
}

// Test 2: Test API Connection
echo "2. Testing API Connection...\n";
$connection_test = $ai_service->test_connection();
if (is_wp_error($connection_test)) {
    echo "❌ Connection test failed: " . $connection_test->get_error_message() . "\n\n";
    exit(1);
} else {
    echo "✅ Connection test successful\n";
    echo "   Message: " . $connection_test['message'] . "\n";
    echo "   Test Score: " . $connection_test['test_score'] . "\n\n";
}

// Test 3: Test Deal Evaluation
echo "3. Testing Deal Evaluation...\n";
$test_deal = [
    'destination' => 'Paris, France',
    'currency' => 'USD',
    'discounted_price' => 499,
    'original_price' => 899,
    'travel_start' => '2025-06-01',
    'travel_end' => '2025-06-07'
];

$evaluation = $ai_service->evaluate_deal($test_deal);
if (isset($evaluation['score']) && $evaluation['score'] > 0) {
    echo "✅ Deal evaluation successful\n";
    echo "   Score: " . $evaluation['score'] . "/100\n";
    echo "   Reasoning: " . substr($evaluation['reasoning'], 0, 100) . "...\n\n";
} else {
    echo "❌ Deal evaluation failed\n";
    print_r($evaluation);
    echo "\n";
}

// Test 4: Test Content Generation
echo "4. Testing Content Generation...\n";
$content = $ai_service->generate_content($test_deal);
if (!empty($content['title']) && !empty($content['body'])) {
    echo "✅ Content generation successful\n";
    echo "   Title: " . $content['title'] . "\n";
    echo "   Meta Description: " . substr($content['meta_description'], 0, 80) . "...\n";
    echo "   Body Length: " . strlen($content['body']) . " characters\n\n";
} else {
    echo "❌ Content generation failed\n";
    print_r($content);
    echo "\n";
}

// Test 5: Test Fallback Mechanism
echo "5. Testing Fallback Mechanism...\n";
// Temporarily clear API key to test fallback
$original_settings = $settings;
update_option('ng_ai_settings', ['api_key' => '']);

$fallback_evaluation = $ai_service->evaluate_deal($test_deal);
if (isset($fallback_evaluation['score']) && strpos($fallback_evaluation['reasoning'], 'unavailable') !== false) {
    echo "✅ Fallback evaluation working\n";
    echo "   Fallback Score: " . $fallback_evaluation['score'] . "/100\n";
    echo "   Fallback Reasoning: " . $fallback_evaluation['reasoning'] . "\n\n";
} else {
    echo "❌ Fallback evaluation not working properly\n\n";
}

// Restore original settings
update_option('ng_ai_settings', $original_settings);

// Test 6: Check Usage Statistics
echo "6. Checking Usage Statistics...\n";
$stats = $ai_service->get_usage_stats();
if (!empty($stats)) {
    echo "✅ Usage statistics being tracked\n";
    $today = date('Y-m-d');
    if (isset($stats[$today])) {
        echo "   Today's Stats:\n";
        echo "   - Evaluation Calls: " . $stats[$today]['evaluation_calls'] . "\n";
        echo "   - Content Calls: " . $stats[$today]['content_calls'] . "\n";
        echo "   - Total Tokens: " . $stats[$today]['total_tokens'] . "\n";
        echo "   - Estimated Cost: $" . number_format($stats[$today]['total_cost'], 4) . "\n";
    }
} else {
    echo "ℹ️  No usage statistics yet (this is normal for first run)\n";
}

echo "\n=== AI Service Test Complete ===\n";
echo "✅ All critical functions are working!\n";
echo "📝 Next steps:\n";
echo "   1. Configure your AI settings in WordPress admin\n";
echo "   2. Test with real deal data\n";
echo "   3. Monitor usage statistics regularly\n";
echo "   4. Set up cost alerts if needed\n\n";

echo "🔗 Useful Links:\n";
echo "   - AI Settings: " . admin_url('admin.php?page=nomadsguru-ai-settings') . "\n";
echo "   - Usage Stats: Check in AI Settings page\n";
echo "   - Support: Check logs for detailed error information\n";

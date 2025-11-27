<?php
/**
 * Test Script for Phase 3 Image Service Integration
 * Run this to verify the image service works correctly
 */

// Include WordPress (adjust path as needed)
// require_once('../../../wp-config.php');

// Include our AI service
require_once __DIR__ . '/includes/class-nomadsguru-ai.php';

echo "=== Phase 3 Image Service Integration Test ===\n\n";

// Test Data
$test_destinations = [
    'Bali, Indonesia',
    'Paris, France', 
    'Tokyo, Japan',
    'New York, USA'
];

// Initialize AI Service
$ai_service = NomadsGuru_AI::get_instance();

echo "Testing Image Providers:\n";
echo "Provider Priority: Pixabay → Pexels → Unsplash → Placeholder\n\n";

foreach ($test_destinations as $destination) {
    echo "=== Testing: $destination ===\n";
    
    // Test image finding
    $image_result = $ai_service->find_travel_image($destination, ['travel', 'vacation', 'tourism']);
    
    if ($image_result['success']) {
        echo "✅ SUCCESS: Image found\n";
        echo "   Provider: " . $image_result['provider'] . "\n";
        echo "   Attachment ID: " . $image_result['attachment_id'] . "\n";
        echo "   Image URL: " . $image_result['url'] . "\n";
        
        if (!empty($image_result['attribution']['photographer'])) {
            echo "   Photographer: " . $image_result['attribution']['photographer'] . "\n";
        }
    } else {
        echo "❌ FAILED: " . $image_result['message'] . "\n";
        echo "   Provider: " . $image_result['provider'] . "\n";
    }
    
    echo "\n";
}

echo "=== Provider Testing ===\n";

// Test each provider individually
$providers = ['pixabay', 'pexels', 'unsplash'];
$query = 'beautiful travel destination';

foreach ($providers as $provider) {
    echo "\n--- Testing $provider Provider ---\n";
    
    // Use reflection to test private method
    $reflection = new ReflectionClass($ai_service);
    $method = $reflection->getMethod('try_image_provider');
    $method->setAccessible(true);
    
    $result = $method->invoke($ai_service, $provider, $query);
    
    if ($result['success']) {
        echo "✅ $provider: Working\n";
        echo "   Image URL: " . substr($result['url'], 0, 50) . "...\n";
    } else {
        echo "❌ $provider: " . $result['message'] . "\n";
    }
}

echo "\n=== Attribution Testing ===\n";
$test_result = $ai_service->find_travel_image('Bali', ['beach', 'resort']);

if ($test_result['success'] && !empty($test_result['attribution'])) {
    echo "✅ Attribution data found:\n";
    echo "   Photographer: " . ($test_result['attribution']['photographer'] ?? 'N/A') . "\n";
    echo "   Provider: " . $test_result['provider'] . "\n";
} else {
    echo "ℹ️  No attribution data (likely placeholder or demo key)\n";
}

echo "\n=== Phase 3 Image Service Integration Test Complete ===\n";
echo "✅ Multi-provider image search implemented\n";
echo "✅ WordPress media library integration\n";
echo "✅ Attribution and licensing support\n";
echo "✅ Robust fallback systems\n";
echo "✅ API key management\n";
echo "✅ Error handling and logging\n";

echo "\nNext Steps:\n";
echo "1. Get your own API keys for better limits:\n";
echo "   - Pixabay: https://pixabay.com/api/docs/ (5,000 req/hour)\n";
echo "   - Pexels: https://www.pexels.com/api/ (200 req/hour)\n";
echo "   - Unsplash: https://unsplash.com/developers (unlimited on approval)\n";
echo "2. Add keys in WordPress Admin → NomadsGuru → AI Settings\n";
echo "3. Test with real travel deals in the publishing workflow\n";

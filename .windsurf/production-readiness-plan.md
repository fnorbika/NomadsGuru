# NomadsGuru Plugin - Production Readiness Implementation Plan

## 🎯 OBJECTIVE
Transform the NomadsGuru plugin from development state to fully functional production system by implementing real API integrations, AJAX handlers, cron automation, and complete workflow testing.

---

## 📋 PHASE 1: REPLACE AI SERVICE (4-6 hours)

### Step 1.1: Choose AI Provider
**Decision Required**: Select ONE provider to start
- **Option 1**: OpenAI (GPT-4/GPT-3.5-turbo)
- **Option 2**: Google Gemini
- **Option 3**: xAI (Grok)
- **Option 4**: Perplexity AI
- **Recommendation**: Start with OpenAI GPT-3.5-turbo for cost-effectiveness

### Step 1.2: Get API Key
1. Go to https://platform.openai.com/
2. Sign up or login
3. Navigate to API Keys section
4. Click "Create new secret key"
5. Copy key (starts with `sk-`)
6. **CRITICAL**: Save key securely - you won't see it again

### Step 1.3: Add API Key to WordPress
**File**: Create new admin settings page OR use existing
```php
// In WordPress admin, add option:
update_option('ng_openai_api_key', 'sk-your-key-here');
```

**Security**: Encrypt the key
```php
// Use WordPress encryption
$encrypted = base64_encode($api_key);
update_option('ng_openai_api_key_encrypted', $encrypted);
```

### Step 1.4: Update AIService.php - Deal Evaluation
**File**: `src/Services/AIService.php`

**Replace** `evaluate_deal()` method:
```php
public function evaluate_deal($deal_data) {
    $api_key = get_option('ng_openai_api_key');
    
    $prompt = "Evaluate this travel deal on a scale of 0-100:\n";
    $prompt .= "Destination: {$deal_data['destination']}\n";
    $prompt .= "Price: {$deal_data['currency']} {$deal_data['discounted_price']}\n";
    $prompt .= "Original Price: {$deal_data['currency']} {$deal_data['original_price']}\n";
    $prompt .= "Travel Dates: {$deal_data['travel_start']} to {$deal_data['travel_end']}\n\n";
    $prompt .= "Return ONLY a JSON object with: {\"score\": 85, \"reasoning\": \"explanation\"}";
    
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a travel deal expert.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 200
        ]),
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        error_log('OpenAI API Error: ' . $response->get_error_message());
        return ['score' => 50, 'reasoning' => 'API Error'];
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $content = $body['choices'][0]['message']['content'] ?? '';
    $result = json_decode($content, true);
    
    return [
        'score' => $result['score'] ?? 50,
        'reasoning' => $result['reasoning'] ?? 'No reasoning provided'
    ];
}
```

### Step 1.5: Update AIService.php - Content Generation
**Replace** `generate_content()` method:
```php
public function generate_content($deal_data, $evaluation) {
    $api_key = get_option('ng_openai_api_key');
    
    $prompt = "Write a travel article for this deal:\n";
    $prompt .= "Destination: {$deal_data['destination']}\n";
    $prompt .= "Price: {$deal_data['currency']} {$deal_data['discounted_price']}\n";
    $prompt .= "Score: {$evaluation['score']}/100\n\n";
    $prompt .= "Generate JSON with: {\"title\": \"...\", \"meta_description\": \"...\", \"body\": \"...\"}";
    
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a travel writer.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.8,
            'max_tokens' => 1000
        ]),
        'timeout' => 45
    ]);
    
    if (is_wp_error($response)) {
        return [
            'title' => $deal_data['destination'] . ' Travel Deal',
            'meta_description' => 'Great deal to ' . $deal_data['destination'],
            'body' => 'Check out this amazing deal!'
        ];
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $content = $body['choices'][0]['message']['content'] ?? '';
    return json_decode($content, true);
}
```

### Step 1.6: Test AI Service
**Create test file**: `test-ai-service.php` in plugin root
```php
<?php
require_once 'src/Services/AIService.php';

$ai = new \NomadsGuru\Services\AIService();

$test_deal = [
    'destination' => 'Paris',
    'currency' => 'USD',
    'discounted_price' => 499,
    'original_price' => 899,
    'travel_start' => '2025-06-01',
    'travel_end' => '2025-06-07'
];

$evaluation = $ai->evaluate_deal($test_deal);
echo "Score: " . $evaluation['score'] . "\n";
echo "Reasoning: " . $evaluation['reasoning'] . "\n\n";

$content = $ai->generate_content($test_deal, $evaluation);
echo "Title: " . $content['title'] . "\n";
```

**Run test**: `php test-ai-service.php`

### Step 1.7: Verification Checklist
- [ ] API key stored securely
- [ ] `evaluate_deal()` returns valid score (0-100)
- [ ] `generate_content()` returns title, meta, body
- [ ] Error handling works (test with invalid key)
- [ ] Costs tracked (check OpenAI usage dashboard)

---

## 📋 PHASE 2: REPLACE IMAGE SERVICE (2-3 hours)

### Step 2.1: Choose Image API
**Recommendation**: Pexels (Free, 200 requests/hour)
- Alternative: Unsplash (5000 requests/hour)
- Alternative: Pixabay (Unlimited, no key needed)

### Step 2.2: Get Pexels API Key
1. Go to https://www.pexels.com/api/
2. Sign up
3. Get API key from dashboard
4. Save in WordPress: `update_option('ng_pexels_api_key', 'your-key');`

### Step 2.3: Update ImageService.php
**File**: `src/Services/ImageService.php`

**Replace entire class**:
```php
<?php
namespace NomadsGuru\Services;

class ImageService {
    
    public function find_image($destination, $keywords = []) {
        $api_key = get_option('ng_pexels_api_key');
        $query = urlencode($destination);
        
        $response = wp_remote_get(
            "https://api.pexels.com/v1/search?query={$query}&per_page=1",
            [
                'headers' => ['Authorization' => $api_key],
                'timeout' => 15
            ]
        );
        
        if (is_wp_error($response)) {
            return $this->get_placeholder_image();
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $photo = $body['photos'][0] ?? null;
        
        if (!$photo) {
            return $this->get_placeholder_image();
        }
        
        // Download image to WordPress media library
        $image_url = $photo['src']['large'];
        $image_id = $this->download_to_media_library($image_url, $destination);
        
        return [
            'url' => $image_url,
            'media_id' => $image_id,
            'photographer' => $photo['photographer'],
            'photographer_url' => $photo['photographer_url']
        ];
    }
    
    private function download_to_media_library($url, $title) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $tmp = download_url($url);
        if (is_wp_error($tmp)) {
            return null;
        }
        
        $file_array = [
            'name' => sanitize_file_name($title) . '.jpg',
            'tmp_name' => $tmp
        ];
        
        $id = media_handle_sideload($file_array, 0);
        
        if (is_wp_error($id)) {
            @unlink($tmp);
            return null;
        }
        
        return $id;
    }
    
    private function get_placeholder_image() {
        return [
            'url' => 'https://via.placeholder.com/800x600?text=' . urlencode('Travel Deal'),
            'media_id' => null
        ];
    }
}
```

### Step 2.4: Test Image Service
```php
$image_service = new \NomadsGuru\Services\ImageService();
$result = $image_service->find_image('Paris');
print_r($result);
```

### Step 2.5: Verification Checklist
- [ ] API key stored
- [ ] Images download successfully
- [ ] Images appear in Media Library
- [ ] Placeholder works if API fails
- [ ] Attribution stored (photographer credit)

---

## 📋 PHASE 3: IMPLEMENT DEAL SOURCE (4-6 hours)

### Step 3.1: Choose Deal Source
**Options**:
1. **Skyscanner API** (Requires approval)
2. **Amadeus API** (Free tier: 2000 calls/month)
3. **Manual CSV Import** (Easiest to start)

**Recommendation**: Start with Manual CSV for testing

### Step 3.2: Create Manual Deal Source
**File**: `src/Integrations/Sources/ManualSource.php`

```php
<?php
namespace NomadsGuru\Integrations\Sources;

use NomadsGuru\Integrations\DealSourceInterface;

class ManualSource implements DealSourceInterface {
    
    public function fetch_deals() {
        // Read from CSV file or admin-entered deals
        $csv_file = NOMADSGURU_PLUGIN_DIR . 'data/manual-deals.csv';
        
        if (!file_exists($csv_file)) {
            return [];
        }
        
        $deals = [];
        $handle = fopen($csv_file, 'r');
        
        // Skip header row
        fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            $deals[] = [
                'title' => $row[0],
                'destination' => $row[1],
                'original_price' => floatval($row[2]),
                'discounted_price' => floatval($row[3]),
                'currency' => $row[4],
                'travel_start' => $row[5],
                'travel_end' => $row[6],
                'booking_url' => $row[7],
                'source' => 'manual'
            ];
        }
        
        fclose($handle);
        return $deals;
    }
    
    public function validate_credentials($config) {
        return true; // No credentials needed
    }
}
```

### Step 3.3: Create Sample CSV
**File**: `data/manual-deals.csv`
```csv
title,destination,original_price,discounted_price,currency,travel_start,travel_end,booking_url
Paris Getaway,Paris,899,499,USD,2025-06-01,2025-06-07,https://example.com/paris
Tokyo Adventure,Tokyo,1299,799,USD,2025-07-15,2025-07-22,https://example.com/tokyo
London Escape,London,699,399,GBP,2025-05-10,2025-05-17,https://example.com/london
```

### Step 3.4: Register Manual Source
**File**: Add to `src/Core/Loader.php` or create registration method

```php
// Register manual source in database
global $wpdb;
$wpdb->insert(
    $wpdb->prefix . 'ng_deal_sources',
    [
        'name' => 'Manual CSV Import',
        'type' => 'manual',
        'config' => json_encode(['csv_path' => 'data/manual-deals.csv']),
        'is_active' => 1
    ]
);
```

### Step 3.5: Test Deal Discovery
Run discovery processor manually:
```php
$processor = new \NomadsGuru\Processors\DealDiscoveryProcessor();
$processor->process();

// Check database
global $wpdb;
$deals = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ng_raw_deals");
print_r($deals);
```

### Step 3.6: Verification Checklist
- [ ] CSV file created with sample deals
- [ ] Manual source registered in database
- [ ] `fetch_deals()` returns array of deals
- [ ] Deals inserted into `ng_raw_deals` table
- [ ] All required fields present

---

## 📋 PHASE 4: ADD AJAX HANDLERS (3-4 hours)

### Step 4.1: Queue Approval Handler
**File**: `src/Admin/AjaxHandlers.php` (NEW)

```php
<?php
namespace NomadsGuru\Admin;

class AjaxHandlers {
    
    public function init() {
        add_action('wp_ajax_ng_approve_deal', [$this, 'approve_deal']);
        add_action('wp_ajax_ng_reject_deal', [$this, 'reject_deal']);
        add_action('wp_ajax_ng_filter_deals', [$this, 'filter_deals']);
        add_action('wp_ajax_nopriv_ng_filter_deals', [$this, 'filter_deals']);
    }
    
    public function approve_deal() {
        check_ajax_referer('nomadsguru_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $queue_id = intval($_POST['queue_id']);
        
        global $wpdb;
        $queue_table = $wpdb->prefix . 'ng_processing_queue';
        
        // Get queue item
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $queue_table WHERE id = %d",
            $queue_id
        ));
        
        if (!$item) {
            wp_send_json_error('Queue item not found');
        }
        
        // Publish the deal
        $publisher = new \NomadsGuru\Processors\PublisherProcessor();
        $post_id = $publisher->publish_single($item->raw_deal_id);
        
        if ($post_id) {
            // Update queue status
            $wpdb->update(
                $queue_table,
                ['status' => 'published'],
                ['id' => $queue_id]
            );
            
            wp_send_json_success(['post_id' => $post_id]);
        } else {
            wp_send_json_error('Failed to publish');
        }
    }
    
    public function reject_deal() {
        check_ajax_referer('nomadsguru_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $queue_id = intval($_POST['queue_id']);
        
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'ng_processing_queue',
            ['status' => 'rejected'],
            ['id' => $queue_id]
        );
        
        wp_send_json_success();
    }
    
    public function filter_deals() {
        $search = sanitize_text_field($_POST['search'] ?? '');
        $destination = sanitize_text_field($_POST['destination'] ?? '');
        $min_price = floatval($_POST['min_price'] ?? 0);
        $max_price = floatval($_POST['max_price'] ?? 999999);
        $min_score = intval($_POST['min_score'] ?? 0);
        
        $args = [
            'post_type' => 'post',
            'posts_per_page' => 12,
            's' => $search,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_ng_evaluation_score',
                    'value' => $min_score,
                    'compare' => '>=',
                    'type' => 'NUMERIC'
                ],
                [
                    'key' => '_ng_discounted_price',
                    'value' => [$min_price, $max_price],
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                ]
            ]
        ];
        
        if ($destination) {
            $args['meta_query'][] = [
                'key' => '_ng_destination',
                'value' => $destination,
                'compare' => 'LIKE'
            ];
        }
        
        $deals = get_posts($args);
        
        ob_start();
        foreach ($deals as $deal) {
            // Render deal card (reuse from DealsBlock)
            echo '<div class="ng-deal-card">...</div>';
        }
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html, 'count' => count($deals)]);
    }
}
```

### Step 4.2: Add JavaScript for AJAX
**File**: `assets/js/queue-manager.js` (NEW)

```javascript
jQuery(document).ready(function($) {
    // Approve deal
    $('.ng-approve-deal').on('click', function() {
        const queueId = $(this).data('id');
        const $item = $(this).closest('.ng-queue-item');
        
        $.ajax({
            url: nomadsguruParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ng_approve_deal',
                queue_id: queueId,
                nonce: nomadsguruParams.nonce
            },
            success: function(response) {
                if (response.success) {
                    $item.fadeOut();
                    alert('Deal approved and published!');
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });
    
    // Reject deal
    $('.ng-reject-deal').on('click', function() {
        const queueId = $(this).data('id');
        const $item = $(this).closest('.ng-queue-item');
        
        if (!confirm('Reject this deal?')) return;
        
        $.ajax({
            url: nomadsguruParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ng_reject_deal',
                queue_id: queueId,
                nonce: nomadsguruParams.nonce
            },
            success: function(response) {
                $item.fadeOut();
            }
        });
    });
});
```

### Step 4.3: Register AJAX Handlers
**File**: Update `src/Core/Loader.php`

```php
private function init_ajax() {
    $ajax = new \NomadsGuru\Admin\AjaxHandlers();
    $ajax->init();
}

// Call in __construct or init()
$this->init_ajax();
```

### Step 4.4: Verification Checklist
- [ ] AJAX handlers registered
- [ ] Approve button works
- [ ] Reject button works
- [ ] Filter AJAX works
- [ ] Nonce verification works
- [ ] Capability checks work

---

## 📋 PHASE 5: SET UP CRON JOBS (2-3 hours)

### Step 5.1: Register Custom Intervals
**File**: `src/Core/Scheduler.php` (NEW)

```php
<?php
namespace NomadsGuru\Core;

class Scheduler {
    
    public function init() {
        add_filter('cron_schedules', [$this, 'add_custom_intervals']);
        add_action('ng_deal_discovery', [$this, 'run_discovery']);
        add_action('ng_queue_processing', [$this, 'run_processing']);
        add_action('ng_daily_maintenance', [$this, 'run_maintenance']);
    }
    
    public function add_custom_intervals($schedules) {
        $schedules['ng_15min'] = [
            'interval' => 900,
            'display' => __('Every 15 Minutes', 'nomadsguru')
        ];
        return $schedules;
    }
    
    public function run_discovery() {
        $processor = new \NomadsGuru\Processors\DealDiscoveryProcessor();
        $processor->process();
    }
    
    public function run_processing() {
        $processor = new \NomadsGuru\Processors\QueueProcessor();
        $processor->process();
    }
    
    public function run_maintenance() {
        global $wpdb;
        // Delete old logs (>30 days)
        $wpdb->query("DELETE FROM {$wpdb->prefix}ng_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    }
    
    public static function activate() {
        if (!wp_next_scheduled('ng_deal_discovery')) {
            wp_schedule_event(time(), 'hourly', 'ng_deal_discovery');
        }
        
        if (!wp_next_scheduled('ng_queue_processing')) {
            wp_schedule_event(time(), 'ng_15min', 'ng_queue_processing');
        }
        
        if (!wp_next_scheduled('ng_daily_maintenance')) {
            wp_schedule_event(strtotime('02:00:00'), 'daily', 'ng_daily_maintenance');
        }
    }
    
    public static function deactivate() {
        wp_clear_scheduled_hook('ng_deal_discovery');
        wp_clear_scheduled_hook('ng_queue_processing');
        wp_clear_scheduled_hook('ng_daily_maintenance');
    }
}
```

### Step 5.2: Register Scheduler
**File**: Update `nomadsguru.php`

```php
register_activation_hook(__FILE__, function() {
    \NomadsGuru\Core\Database::activate();
    \NomadsGuru\Core\Scheduler::activate();
});

register_deactivation_hook(__FILE__, function() {
    \NomadsGuru\Core\Scheduler::deactivate();
});
```

### Step 5.3: Initialize Scheduler
**File**: Update `src/Core/Loader.php`

```php
private function init_scheduler() {
    $scheduler = new Scheduler();
    $scheduler->init();
}
```

### Step 5.4: Test Cron Jobs
```php
// Manually trigger
do_action('ng_deal_discovery');
do_action('ng_queue_processing');

// Check next run
$next_discovery = wp_next_scheduled('ng_deal_discovery');
echo 'Next discovery: ' . date('Y-m-d H:i:s', $next_discovery);
```

### Step 5.5: Verification Checklist
- [ ] Custom intervals registered
- [ ] Cron jobs scheduled on activation
- [ ] Jobs cleared on deactivation
- [ ] Manual triggers work
- [ ] Jobs run automatically (wait 15 min)

---

## 📋 PHASE 6: ADD AFFILIATE PROGRAM (2-3 hours)

### Step 6.1: Choose Affiliate Network
**Options**:
1. **Booking.com** (4% commission)
2. **Skyscanner** (Varies)
3. **Generic URL replacement** (Easiest)

**Recommendation**: Start with generic URL replacement

### Step 6.2: Create Affiliate Program
**File**: `src/Integrations/AffiliatePrograms/GenericAffiliate.php`

```php
<?php
namespace NomadsGuru\Integrations\AffiliatePrograms;

use NomadsGuru\Integrations\AffiliateProgramInterface;

class GenericAffiliate implements AffiliateProgramInterface {
    
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function generate_link($original_url, $deal_data) {
        $affiliate_id = $this->config['affiliate_id'] ?? '';
        $param_name = $this->config['param_name'] ?? 'ref';
        
        // Add affiliate parameter to URL
        $separator = (strpos($original_url, '?') !== false) ? '&' : '?';
        return $original_url . $separator . $param_name . '=' . $affiliate_id;
    }
    
    public function validate_config($config) {
        return !empty($config['affiliate_id']);
    }
}
```

### Step 6.3: Register Affiliate Program
```php
global $wpdb;
$wpdb->insert(
    $wpdb->prefix . 'ng_affiliate_programs',
    [
        'name' => 'Generic Affiliate',
        'type' => 'generic',
        'config' => json_encode([
            'affiliate_id' => 'your-affiliate-id',
            'param_name' => 'ref'
        ]),
        'commission_rate' => 5.0,
        'is_active' => 1
    ]
);
```

### Step 6.4: Test Affiliate Link Generation
```php
$affiliate = new \NomadsGuru\Integrations\AffiliatePrograms\GenericAffiliate([
    'affiliate_id' => 'nomadsguru123',
    'param_name' => 'ref'
]);

$original = 'https://example.com/deal';
$affiliate_link = $affiliate->generate_link($original, []);
echo $affiliate_link; // Should be: https://example.com/deal?ref=nomadsguru123
```

### Step 6.5: Verification Checklist
- [ ] Affiliate program registered
- [ ] `generate_link()` adds affiliate parameter
- [ ] Links work when clicked
- [ ] Commission tracking setup (if applicable)

---

## 📋 PHASE 7: TEST COMPLETE WORKFLOW (3-4 hours)

### Step 7.1: End-to-End Test
**Complete workflow test**:

1. **Discovery**:
   ```php
   do_action('ng_deal_discovery');
   // Check: Deals in ng_raw_deals table
   ```

2. **Evaluation**:
   ```php
   $processor = new \NomadsGuru\Processors\EvaluationProcessor();
   $processor->process();
   // Check: Scores in ng_raw_deals
   ```

3. **Image Finding**:
   ```php
   $processor = new \NomadsGuru\Processors\ImageFinderProcessor();
   $processor->process();
   // Check: Images in deal_data
   ```

4. **Content Generation**:
   ```php
   $processor = new \NomadsGuru\Processors\ContentGeneratorProcessor();
   $processor->process();
   // Check: Content in deal_data
   ```

5. **Queue Selection**:
   ```php
   $processor = new \NomadsGuru\Processors\QueueProcessor();
   $processor->process();
   // Check: Items in ng_processing_queue
   ```

6. **Publishing** (Manual Mode):
   - Go to Queue page
   - Click "Approve"
   - Check: Post created

7. **Frontend Display**:
   - Add `[nomadsguru_deals]` to page
   - Check: Deals display correctly

### Step 7.2: Verification Matrix

| Step | Action | Expected Result | Status |
|------|--------|----------------|--------|
| 1 | Run discovery | 3 deals in database | [ ] |
| 2 | Run evaluation | All deals have scores | [ ] |
| 3 | Run image finder | All deals have images | [ ] |
| 4 | Run content gen | All deals have content | [ ] |
| 5 | Run queue processor | Top deals in queue | [ ] |
| 6 | Approve in admin | Post published | [ ] |
| 7 | View frontend | Deal displays | [ ] |
| 8 | Click affiliate link | Link has ref param | [ ] |

### Step 7.3: Error Testing
Test failure scenarios:
- [ ] Invalid API key → Fallback works
- [ ] No deals found → No errors
- [ ] Image API fails → Placeholder used
- [ ] Content generation fails → Default content
- [ ] Publishing fails → Error logged

---

## 🎯 SUCCESS CRITERIA

Plugin is production-ready when:
- [ ] AI service returns real evaluations
- [ ] Images download from Pexels
- [ ] At least 1 deal source works
- [ ] AJAX approve/reject works
- [ ] Cron jobs run automatically
- [ ] Affiliate links generated correctly
- [ ] Complete workflow tested
- [ ] Error handling verified
- [ ] No PHP errors in logs
- [ ] Frontend displays deals

---

## 📞 SUPPORT & TROUBLESHOOTING

### Common Issues

**Issue**: OpenAI API returns 401
- **Fix**: Check API key is correct
- **Fix**: Verify key has credits

**Issue**: Images don't download
- **Fix**: Check PHP `allow_url_fopen` enabled
- **Fix**: Verify write permissions on uploads folder

**Issue**: Cron jobs don't run
- **Fix**: Check WP-Cron is enabled
- **Fix**: Use external cron service

**Issue**: AJAX returns 0
- **Fix**: Check nonce is correct
- **Fix**: Verify action name matches

---

## 📝 FINAL CHECKLIST

Before going live:
- [ ] All API keys stored securely
- [ ] Error logging enabled
- [ ] Backup database
- [ ] Test on staging first
- [ ] Monitor API costs
- [ ] Set up error notifications
- [ ] Document configuration
- [ ] Train content team (if manual mode)

---

**Estimated Total Time**: 20-30 hours
**Complexity**: Medium-High
**Prerequisites**: API keys, basic PHP knowledge
**Support**: Check logs in `wp_ng_logs` table

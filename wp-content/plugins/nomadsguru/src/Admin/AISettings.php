<?php

namespace NomadsGuru\Admin;

class AISettings {
    /**
     * Option key for storing settings
     */
    const OPTION_KEY = 'ng_ai_settings';

    /**
     * Initialize the settings
     */
    public function init() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Register settings and fields
     */
    public function register_settings() {
        // Register a new setting for AI configuration
        register_setting(
            'nomadsguru_ai',
            self::OPTION_KEY,
            [
                'type' => 'object',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => [
                    'provider' => 'openai',
                    'api_key' => '',
                    'model' => 'gpt-3.5-turbo',
                    'temperature' => 0.7,
                    'max_tokens' => 500
                ]
            ]
        );

        // Add settings section
        add_settings_section(
            'ng_ai_section',
            __('AI Configuration', 'nomadsguru'),
            [$this, 'render_section_header'],
            'nomadsguru-ai-settings'
        );

        // Add provider field
        add_settings_field(
            'ai_provider',
            __('AI Provider', 'nomadsguru'),
            [$this, 'render_provider_field'],
            'nomadsguru-ai-settings',
            'ng_ai_section'
        );

        // Add API key field
        add_settings_field(
            'api_key',
            __('API Key', 'nomadsguru'),
            [$this, 'render_api_key_field'],
            'nomadsguru-ai-settings',
            'ng_ai_section'
        );

        // Add model field
        add_settings_field(
            'model',
            __('Model', 'nomadsguru'),
            [$this, 'render_model_field'],
            'nomadsguru-ai-settings',
            'ng_ai_section'
        );
    }

    /**
     * Render section header
     */
    public function render_section_header() {
        echo '<p>' . esc_html__('Configure AI settings for content generation and evaluation.', 'nomadsguru') . '</p>';
    }

    /**
     * Sanitize settings before saving
     */
    public function sanitize_settings($input) {
        $sanitized = [];
        
        // Sanitize provider
        $allowed_providers = ['openai', 'gemini', 'grok', 'perplexity'];
        $sanitized['provider'] = in_array($input['provider'] ?? '', $allowed_providers, true) 
            ? $input['provider'] 
            : 'openai';

        // Sanitize API key (store encrypted)
        if (!empty($input['api_key'])) {
            $sanitized['api_key'] = $this->encrypt_api_key($input['api_key']);
        }

        // Sanitize model
        $sanitized['model'] = sanitize_text_field($input['model'] ?? 'gpt-3.5-turbo');
        
        // Sanitize temperature
        $temp = floatval($input['temperature'] ?? 0.7);
        $sanitized['temperature'] = max(0, min(2, $temp));
        
        // Sanitize max tokens
        $tokens = intval($input['max_tokens'] ?? 500);
        $sanitized['max_tokens'] = max(100, min(4000, $tokens));

        return $sanitized;
    }

    /**
     * Encrypt API key before storing
     */
    private function encrypt_api_key($key) {
        if (empty($key)) {
            return '';
        }
        return base64_encode($key);
    }

    /**
     * Get decrypted API key
     */
    public static function get_api_key() {
        $settings = get_option(self::OPTION_KEY, []);
        return !empty($settings['api_key']) ? base64_decode($settings['api_key']) : '';
    }

    /**
     * Render provider field
     */
    public function render_provider_field() {
        $settings = get_option(self::OPTION_KEY, []);
        $current_provider = $settings['provider'] ?? 'openai';
        ?>
        <select name="ng_ai_settings[provider]" id="ai_provider">
            <option value="openai" <?php selected($current_provider, 'openai'); ?>>OpenAI (GPT-3.5/4)</option>
            <option value="gemini" <?php selected($current_provider, 'gemini'); ?>>Google Gemini</option>
            <option value="grok" <?php selected($current_provider, 'grok'); ?>>xAI Grok</option>
            <option value="perplexity" <?php selected($current_provider, 'perplexity'); ?>>Perplexity AI</option>
        </select>
        <?php
    }

    /**
     * Render API key field
     */
    public function render_api_key_field() {
        $settings = get_option(self::OPTION_KEY, []);
        $current_provider = $settings['provider'] ?? 'openai';
        $api_key = $settings['api_key'] ?? '';
        $decrypted_key = !empty($api_key) ? '••••••••' . substr(base64_decode($api_key), -4) : '';
        
        // Get provider-specific API key URL
        $api_links = [
            'openai' => '<a href="https://platform.openai.com/api-keys" target="_blank">OpenAI</a>',
            'gemini' => '<a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>',
            'grok' => '<a href="https://console.x.ai/api-keys" target="_blank">xAI</a>',
            'perplexity' => '<a href="https://www.perplexity.ai/settings/api" target="_blank">Perplexity</a>'
        ];
        
        $provider_link = $api_links[$current_provider] ?? $api_links['openai'];
        ?>
        <input type="password" 
               name="ng_ai_settings[api_key]" 
               id="api_key" 
               value="<?php echo esc_attr($decrypted_key); ?>" 
               class="regular-text" 
               placeholder="<?php esc_attr_e('Enter your API key', 'nomadsguru'); ?>"
               autocomplete="off"
        />
        <p class="description">
            <?php 
            printf(
                /* translators: %s: Link to provider API keys */
                esc_html__('Get your API key from %s', 'nomadsguru'),
                $provider_link
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render model field
     */
    public function render_model_field() {
        $settings = get_option(self::OPTION_KEY, []);
        $current_provider = $settings['provider'] ?? 'openai';
        $current_model = $settings['model'] ?? '';
        
        // Define models for each provider
        $models = [
            'openai' => [
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Fast & Cost-effective)',
                'gpt-4' => 'GPT-4 (Better quality, more expensive)',
                'gpt-4-turbo' => 'GPT-4 Turbo (Latest, balanced)',
            ],
            'gemini' => [
                'gemini-1.5-flash' => 'Gemini 1.5 Flash (Fast)',
                'gemini-1.5-pro' => 'Gemini 1.5 Pro (Advanced)',
                'gemini-pro' => 'Gemini Pro (Standard)',
            ],
            'grok' => [
                'grok-2' => 'Grok-2 (Latest)',
                'grok-2-mini' => 'Grok-2 Mini (Fast)',
            ],
            'perplexity' => [
                'llama-3.1-70b' => 'Llama 3.1 70B',
                'llama-3.1-8b' => 'Llama 3.1 8B (Fast)',
                'mixtral-8x7b' => 'Mixtral 8x7B',
            ]
        ];
        
        // Get models for current provider, default to OpenAI if not found
        $provider_models = $models[$current_provider] ?? $models['openai'];
        
        // Set default model if none selected
        if (empty($current_model) || !isset($provider_models[$current_model])) {
            $current_model = array_key_first($provider_models);
        }
        ?>
        <select name="ng_ai_settings[model]" id="ai_model">
            <?php foreach ($provider_models as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($current_model, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Choose the AI model to use for content generation.', 'nomadsguru'); ?>
        </p>
        <?php
    }

    /**
     * Render the settings page
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('nomadsguru_ai');
                do_settings_sections('nomadsguru-ai-settings');
                submit_button(__('Save Settings', 'nomadsguru'));
                ?>
            </form>
        </div>
        <?php
    }
}

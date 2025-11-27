jQuery(document).ready(function ($) {
    console.log('Admin.js loaded successfully'); // Debug
    
    // Check if nomadsguruParams is available
    if (typeof nomadsguruParams === 'undefined') {
        console.error('nomadsguruParams is not defined'); // Debug
    } else {
        console.log('nomadsguruParams found:', nomadsguruParams); // Debug
    }
    
    // Simple modal handlers
    $(document).on('click', '#ng-add-source-btn', function() {
        console.log('Add source button clicked'); // Debug
        $('#ng-modal-title').text('Add Deal Source');
        $('#ng-source-form')[0].reset();
        $('#ng-source-id').val('');
        $('#ng-source-modal').show();
        $('#website-url-group').show();
        $('#rss-feed-group').hide();
    });
    
    $(document).on('click', '.ng-close', function() {
        $('#ng-source-modal').hide();
    });
    
    // Handle source type change
    $(document).on('change', '#ng-source-type', function() {
        var sourceType = $(this).val();
        console.log('Source type changed to:', sourceType); // Debug
        
        if (sourceType === 'website') {
            $('#website-url-group').show();
            $('#rss-feed-group').hide();
            $('#ng-website-url').prop('required', true);
            $('#ng-rss-feed').prop('required', false);
        } else if (sourceType === 'rss') {
            $('#website-url-group').hide();
            $('#rss-feed-group').show();
            $('#ng-website-url').prop('required', false);
            $('#ng-rss-feed').prop('required', true);
        }
    });
    
    console.log('Event handlers attached'); // Debug
    
    // Re-initialize when tabs are switched
    $(document).on('click', '.nav-tab', function(e) {
        if ($(this).attr('href') && $(this).attr('href').indexOf('tab=ai') !== -1) {
            setTimeout(function() {
                // No validation to initialize
            }, 100);
        }
    });
    
    // AI Provider Change Handler
    function updateModelsForProvider(providerSelect) {
        var provider = providerSelect.val();
        var $modelSelect = $('#ai_model');
        var $apiKeyField = $('#api_key');
        var $apiKeyDescription = $apiKeyField.siblings('.description');
        
        console.log('Updating models for provider:', provider); // Debug
        
        // Define models for each provider
        var models = {
            openai: [
                { value: 'gpt-3.5-turbo', label: 'GPT-3.5 Turbo (Fast & Cost-effective)' },
                { value: 'gpt-4', label: 'GPT-4 (Better quality, more expensive)' },
                { value: 'gpt-4-turbo', label: 'GPT-4 Turbo (Latest, balanced)' }
            ],
            gemini: [
                { value: 'gemini-1.5-flash', label: 'Gemini 1.5 Flash (Fast)' },
                { value: 'gemini-1.5-pro', label: 'Gemini 1.5 Pro (Advanced)' },
                { value: 'gemini-pro', label: 'Gemini Pro (Standard)' }
            ],
            grok: [
                { value: 'grok-2', label: 'Grok-2 (Latest)' },
                { value: 'grok-2-mini', label: 'Grok-2 Mini (Fast)' }
            ],
            perplexity: [
                { value: 'llama-3.1-70b', label: 'Llama 3.1 70B' },
                { value: 'llama-3.1-8b', label: 'Llama 3.1 8B (Fast)' },
                { value: 'mixtral-8x7b', label: 'Mixtral 8x7B' }
            ]
        };
        
        // Define API key URLs for each provider
        var apiLinks = {
            openai: 'Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI</a>',
            gemini: 'Get your API key from <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>',
            grok: 'Get your API key from <a href="https://console.x.ai/api-keys" target="_blank">xAI</a>',
            perplexity: 'Get your API key from <a href="https://www.perplexity.ai/settings/api" target="_blank">Perplexity</a>'
        };
        
        // Get models for selected provider
        var providerModels = models[provider] || models.openai;
        console.log('Models for provider:', providerModels); // Debug
        
        // Store current selected value
        var currentModelValue = $modelSelect.val();
        console.log('Current model value:', currentModelValue); // Debug
        
        // Clear current model options
        $modelSelect.empty();
        
        // Add new model options
        providerModels.forEach(function(model) {
            var isSelected = model.value === currentModelValue || (providerModels.indexOf(model) === 0 && !currentModelValue);
            $modelSelect.append($('<option>', {
                value: model.value,
                text: model.label,
                selected: isSelected
            }));
        });
        
        // Update API key description
        var apiLink = apiLinks[provider] || apiLinks.openai;
        console.log('API link:', apiLink); // Debug
        $apiKeyDescription.html(apiLink);
        
        // Don't clear API key field when switching providers
        // The masked value will be preserved by the PHP sanitization
        
        // Trigger change event to ensure consistency
        $modelSelect.trigger('change');
    }
    
    // Bind change event using event delegation for dynamic content
    $(document).on('change', '#ai_provider', function(e) {
        e.preventDefault();
        console.log('Provider dropdown changed'); // Debug
        updateModelsForProvider($(this));
    });

    // Initialize on page load and when tabs are switched
    function initializeProviderHandler() {
        var $providerSelect = $('#ai_provider');
        var $modelSelect = $('#ai_model');
        var $apiKeyDescription = $('#api_key').siblings('.description');
        
        console.log('Initializing provider handler'); // Debug
        console.log('Provider select found:', $providerSelect.length > 0); // Debug
        console.log('Model select found:', $modelSelect.length > 0); // Debug
        console.log('API description found:', $apiKeyDescription.length > 0); // Debug
        
        if ($providerSelect.length > 0 && $modelSelect.length > 0 && $apiKeyDescription.length > 0) {
            // Trigger change event to set initial state
            updateModelsForProvider($providerSelect);
        }
    }
    
    // Initialize on page load with multiple attempts
    initializeProviderHandler();
    
    // Try again after a delay (in case elements aren't ready yet)
    setTimeout(function() {
        initializeProviderHandler();
    }, 500);
    
    // Also initialize when AI tab is clicked (for tab navigation)
    $(document).on('click', '.nav-tab', function(e) {
        // Check if this is the AI tab
        if ($(this).attr('href') && $(this).attr('href').indexOf('tab=ai') !== -1) {
            console.log('AI tab clicked'); // Debug
            // Wait a bit for the tab content to load
            setTimeout(function() {
                initializeProviderHandler();
            }, 100);
        }
    });
    
    // Also initialize on any tab change to be safe
    $(document).on('click', 'a[href*="tab="]', function() {
        setTimeout(function() {
            initializeProviderHandler();
        }, 100);
    });

    // Reset Plugin Data - Show Dialog
    $(document).on('click', '#reset-plugin-data', function(e) {
        e.preventDefault();
        showDialog();
    });

    // Function to show dialog
    function showDialog() {
        var $dialog = $('#reset-dialog');
        var $input = $('#confirm-delete-input');
        var $confirmBtn = $('#confirm-reset-button');
        var $result = $('#reset-result');
        
        // Reset form
        $input.val('').focus();
        $confirmBtn.prop('disabled', true);
        $result.hide().removeClass('success error').empty();
        
        // Show dialog using multiple methods for browser compatibility
        $dialog.css('display', 'block');
        $dialog.attr('aria-hidden', 'false');
        $dialog.addClass('ng-dialog-visible');
        
        // Focus management
        setTimeout(function() {
            $input.focus();
        }, 100);
    }

    // Function to hide dialog
    function hideDialog() {
        var $dialog = $('#reset-dialog');
        $dialog.css('display', 'none');
        $dialog.attr('aria-hidden', 'true');
        $dialog.removeClass('ng-dialog-visible');
        $('#confirm-delete-input').val('');
        $('#confirm-reset-button').prop('disabled', true);
    }

    // Close Reset Dialog
    $(document).on('click', '#close-reset-dialog, #cancel-reset-button', function(e) {
        e.preventDefault();
        hideDialog();
    });

    // Enable/Disable Confirm Button based on text input
    $(document).on('input', '#confirm-delete-input', function() {
        var value = $(this).val().trim();
        $('#confirm-reset-button').prop('disabled', value !== 'DELETE');
    });

    // Handle Enter key in input
    $(document).on('keypress', '#confirm-delete-input', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            var $confirmBtn = $('#confirm-reset-button');
            if (!$confirmBtn.prop('disabled')) {
                $confirmBtn.click();
            }
        }
    });

    // Handle Escape key to close dialog
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) { // Escape key
            if ($('#reset-dialog').is(':visible')) {
                hideDialog();
            }
        }
    });

    // Confirm Reset
    $(document).on('click', '#confirm-reset-button', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $spinner = $('#reset-spinner');
        var $result = $('#reset-result');
        var $dialog = $('#reset-dialog');
        
        // Double-check the confirmation text
        if ($('#confirm-delete-input').val().trim() !== 'DELETE') {
            return;
        }
        
        // Disable buttons and show spinner
        $button.prop('disabled', true);
        $('#cancel-reset-button').prop('disabled', true);
        $('#close-reset-dialog').prop('disabled', true);
        $spinner.css('display', 'inline-block');
        $result.hide().removeClass('success error').empty();

        // Make AJAX request
        $.ajax({
            url: nomadsguruParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'nomadsguru_reset_plugin_data',
                nonce: nomadsguruParams.nonce
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<p>' + (response.data.message || 'Plugin data has been reset successfully.') + '</p>')
                           .addClass('success')
                           .show();
                    
                    // Hide dialog after a short delay
                    setTimeout(function() {
                        hideDialog();
                        // Redirect to plugins page after reset
                        window.location.href = nomadsguruParams.pluginsUrl;
                    }, 2000);
                } else {
                    var errorMsg = response.data && response.data.message 
                        ? response.data.message 
                        : 'An error occurred while resetting the plugin data.';
                    $result.html('<p>' + errorMsg + '</p>')
                           .addClass('error')
                           .show();
                    
                    // Re-enable buttons on error
                    $button.prop('disabled', false);
                    $('#cancel-reset-button').prop('disabled', false);
                    $('#close-reset-dialog').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                var errorMsg = 'Failed to connect to the server. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg = xhr.responseJSON.data.message;
                }
                $result.html('<p>' + errorMsg + '</p>')
                       .addClass('error')
                       .show();
                
                // Re-enable buttons on error
                $button.prop('disabled', false);
                $('#cancel-reset-button').prop('disabled', false);
                $('#close-reset-dialog').prop('disabled', false);
            },
            complete: function() {
                $spinner.hide();
            }
        });
    });

    // Close dialog when clicking overlay (outside the dialog content)
    $(document).on('click', '#reset-dialog', function(e) {
        if (e.target === this) {
            hideDialog();
        }
    });

    // Source Modal
    const sourceModal = $('#ng-source-modal');
    const sourceForm = $('#ng-source-form');

    $('#ng-add-source-btn').on('click', function () {
        $('#ng-modal-title').text('Add Deal Source');
        sourceForm[0].reset();
        $('#ng-source-id').val('');
        sourceModal.show();
        // Show website URL group by default
        $('#website-url-group').show();
        $('#rss-feed-group').hide();
    });

    $('.ng-close').on('click', function () {
        sourceModal.hide();
    });

    // Handle source type change
    $(document).on('change', '#ng-source-type', function() {
        var sourceType = $(this).val();
        var $websiteGroup = $('#website-url-group');
        var $rssGroup = $('#rss-feed-group');
        
        if (sourceType === 'website') {
            $websiteGroup.show();
            $rssGroup.hide();
            $('#ng-website-url').prop('required', true);
            $('#ng-rss-feed').prop('required', false);
        } else if (sourceType === 'rss') {
            $websiteGroup.hide();
            $rssGroup.show();
            $('#ng-website-url').prop('required', false);
            $('#ng-rss-feed').prop('required', true);
        }
    });

    // Edit source
    $(document).on('click', '.ng-edit-source', function () {
        const sourceId = $(this).data('id');
        // AJAX to get source data and populate form
        $.ajax({
            url: nomadsguruParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ng_get_source',
                source_id: sourceId,
                nonce: nomadsguruParams.nonce
            },
            success: function(response) {
                if (response.success) {
                    const source = response.data;
                    $('#ng-modal-title').text('Edit Deal Source');
                    $('#ng-source-id').val(source.id);
                    $('#ng-source-type').val(source.source_type);
                    $('#ng-source-name').val(source.source_name);
                    $('#ng-source-interval').val(source.sync_interval_minutes || 60);
                    
                    // Show/hide appropriate fields based on source type
                    if (source.source_type === 'website') {
                        $('#website-url-group').show();
                        $('#rss-feed-group').hide();
                        $('#ng-website-url').val(source.website_url || '').prop('required', true);
                        $('#ng-rss-feed').prop('required', false);
                    } else if (source.source_type === 'rss') {
                        $('#website-url-group').hide();
                        $('#rss-feed-group').show();
                        $('#ng-rss-feed').val(source.rss_feed || '').prop('required', true);
                        $('#ng-website-url').prop('required', false);
                    }
                    
                    sourceModal.show();
                }
            }
        });
    });

    // Delete source
    $(document).on('click', '.ng-delete-source', function () {
        if (confirm('Are you sure you want to delete this source?')) {
            const sourceId = $(this).data('id');
            $.ajax({
                url: nomadsguruParams.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ng_delete_source',
                    source_id: sourceId,
                    nonce: nomadsguruParams.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
    });

    // Save source - use event delegation for better reliability
    $(document).on('submit', '#ng-source-form', function (e) {
        console.log('Form submit event triggered'); // Debug
        
        // Check if nomadsguruParams is available
        if (typeof nomadsguruParams === 'undefined') {
            console.error('nomadsguruParams is not defined'); // Debug
            alert('Configuration error: nomadsguruParams not found');
            return false;
        }
        
        console.log('nomadsguruParams:', nomadsguruParams); // Debug
        
        e.preventDefault();
        console.log('Form submitted, preventing default'); // Debug
        
        const formData = new FormData(this);
        formData.append('nonce', nomadsguruParams.nonce);
        
        console.log('Sending AJAX request'); // Debug
        
        $.ajax({
            url: nomadsguruParams.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('AJAX success:', response); // Debug
                if (response.success) {
                    sourceModal.hide();
                    location.reload();
                } else {
                    console.log('AJAX error:', response.data); // Debug
                    alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX failed:', xhr.responseText); // Debug
                alert('AJAX request failed. Please check console for details.');
            }
        });
        
        return false; // Extra safety
    });

    // Affiliate Modal
    const affiliateModal = $('#ng-affiliate-modal');
    const affiliateForm = $('#ng-affiliate-form');

    $('#ng-add-affiliate-btn').on('click', function () {
        $('#ng-affiliate-modal-title').text('Add Affiliate Program');
        affiliateForm[0].reset();
        $('#ng-affiliate-id').val('');
        affiliateModal.show();
    });

    // AI Settings Form Handler
    $(document).on('submit', 'form[action="options.php"]', function(e) {
        // Check if this is the AI settings form
        var $aiProvider = $(this).find('#ai_provider');
        if ($aiProvider.length > 0) {
            console.log('AI Settings form submitted'); // Debug
            console.log('Form data before submit:', $(this).serialize()); // Debug
            
            // No validation checks - allow form to submit normally
            
            // Show loading state
            var $submitButton = $(this).find('input[type="submit"]');
            var originalText = $submitButton.val();
            $submitButton.val('Saving...').prop('disabled', true);
            
            // Let the form submit normally to WordPress options.php
            // The form will handle the save process and show the success message
            setTimeout(function() {
                $submitButton.val(originalText).prop('disabled', false);
            }, 3000);
        }
    });
    
    // Test AI Connection Handler
    $(document).on('click', '#test_ai_connection', function(e) {
        e.preventDefault();
        console.log('Test AI Connection clicked'); // Debug
        
        var $button = $(this);
        var $result = $('#test_result');
        
        $button.prop('disabled', true);
        $result.html('<span class="spinner is-active"></span> Testing...');
        
        $.ajax({
            url: nomadsguruParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ng_test_ai_connection',
                nonce: nomadsguruParams.nonce
            },
            success: function(response) {
                console.log('Test connection response:', response); // Debug
                if (response.success) {
                    $result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                } else {
                    $result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                }
            },
            error: function(xhr, status, error) {
                console.log('Test connection error:', xhr.responseText); // Debug
                $result.html('<span style="color: red;">✗ Test failed</span>');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });

    // Delete Affiliate
    $('.ng-delete-affiliate').on('click', function () {
        if (!confirm('Are you sure you want to delete this affiliate program?')) {
            return;
        }

        const id = $(this).data('id');
        $.post(nomadsguruParams.ajaxUrl, {
            action: 'ng_delete_affiliate',
            id: id,
            nonce: nomadsguruParams.nonce
        }, function (response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error deleting affiliate program');
            }
        });
    });
});

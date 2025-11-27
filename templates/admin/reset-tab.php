<?php
/**
 * Reset tab template for NomadsGuru admin
 * 
 * @package NomadsGuru
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="nomadsguru-reset-section">
    <h2><?php esc_html_e( 'Reset Plugin Data', 'nomadsguru' ); ?></h2>
    
    <div class="nomadsguru-warning-box">
        <div class="nomadsguru-warning-icon">⚠️</div>
        <div class="nomadsguru-warning-content">
            <h3><?php esc_html_e( 'Warning: This action cannot be undone!', 'nomadsguru' ); ?></h3>
            <p><?php esc_html_e( 'Resetting the plugin data will permanently delete all of the following:', 'nomadsguru' ); ?></p>
            <ul>
                <li><?php esc_html_e( 'All deal sources and configurations', 'nomadsguru' ); ?></li>
                <li><?php esc_html_e( 'All discovered and processed deals', 'nomadsguru' ); ?></li>
                <li><?php esc_html_e( 'All affiliate program settings', 'nomadsguru' ); ?></li>
                <li><?php esc_html_e( 'All processing queue items', 'nomadsguru' ); ?></li>
                <li><?php esc_html_e( 'AI and publishing settings', 'nomadsguru' ); ?></li>
                <li><?php esc_html_e( 'Usage statistics and logs', 'nomadsguru' ); ?></li>
            </ul>
            <p><strong><?php esc_html_e( 'This will reset the plugin to its original state as if it was just installed.', 'nomadsguru' ); ?></strong></p>
        </div>
    </div>

    <div class="nomadsguru-reset-form">
        <h3><?php esc_html_e( 'Confirm Reset', 'nomadsguru' ); ?></h3>
        <p><?php esc_html_e( 'To proceed with the reset, please type "DELETE" in the confirmation field below:', 'nomadsguru' ); ?></p>
        
        <div class="nomadsguru-input-group">
            <label for="confirm-delete-input"><?php esc_html_e( 'Confirmation:', 'nomadsguru' ); ?></label>
            <input type="text" id="confirm-delete-input" placeholder="<?php esc_attr_e( 'Type DELETE to confirm', 'nomadsguru' ); ?>" />
        </div>
        
        <div class="nomadsguru-button-group">
            <button type="button" id="confirm-reset-button" class="button button-primary" disabled>
                <?php esc_html_e( 'Reset All Data', 'nomadsguru' ); ?>
            </button>
            <button type="button" id="cancel-reset-button" class="button">
                <?php esc_html_e( 'Cancel', 'nomadsguru' ); ?>
            </button>
        </div>
        
        <div id="reset-spinner" class="nomadsguru-spinner" style="display: none;">
            <span class="spinner is-active"></span>
            <?php esc_html_e( 'Resetting data...', 'nomadsguru' ); ?>
        </div>
        
        <div id="reset-result" class="nomadsguru-result"></div>
    </div>

    <div class="nomadsguru-alternatives">
        <h3><?php esc_html_e( 'Alternatives to Full Reset', 'nomadsguru' ); ?></h3>
        <p><?php esc_html_e( 'Consider these less destructive options:', 'nomadsguru' ); ?></p>
        <ul>
            <li>
                <strong><?php esc_html_e( 'Clear Queue Only:', 'nomadsguru' ); ?></strong>
                <?php esc_html_e( 'Remove pending items from the processing queue without affecting deals or settings.', 'nomadsguru' ); ?>
            </li>
            <li>
                <strong><?php esc_html_e( 'Deactivate Sources:', 'nomadsguru' ); ?></strong>
                <?php esc_html_e( 'Temporarily disable all sources to stop new deal discovery.', 'nomadsguru' ); ?>
            </li>
            <li>
                <strong><?php esc_html_e( 'Export Data First:', 'nomadsguru' ); ?></strong>
                <?php esc_html_e( 'Export your current data before resetting if you need a backup.', 'nomadsguru' ); ?>
            </li>
        </ul>
    </div>
</div>

<style>
.nomadsguru-reset-section {
    max-width: 800px;
}

.nomadsguru-warning-box {
    display: flex;
    align-items: flex-start;
    background: #fef8e7;
    border: 1px solid #dba617;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.nomadsguru-warning-icon {
    font-size: 24px;
    margin-right: 15px;
    flex-shrink: 0;
}

.nomadsguru-warning-content h3 {
    margin: 0 0 10px 0;
    color: #b22000;
}

.nomadsguru-warning-content ul {
    margin: 10px 0;
    padding-left: 20px;
}

.nomadsguru-warning-content li {
    margin-bottom: 5px;
}

.nomadsguru-reset-form {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.nomadsguru-input-group {
    margin: 20px 0;
}

.nomadsguru-input-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
}

.nomadsguru-input-group input {
    width: 100%;
    max-width: 300px;
    padding: 8px 12px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    font-size: 14px;
}

.nomadsguru-input-group input:focus {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
    outline: none;
}

.nomadsguru-button-group {
    margin: 20px 0;
}

.nomadsguru-button-group .button {
    margin-right: 10px;
}

.nomadsguru-spinner {
    margin: 20px 0;
    padding: 10px;
    background: #f0f0f1;
    border-radius: 4px;
    display: flex;
    align-items: center;
}

.nomadsguru-spinner .spinner {
    margin-right: 10px;
}

.nomadsguru-result {
    margin: 20px 0;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid;
}

.nomadsguru-result.success {
    background: #f0f6fc;
    border-color: #00a32a;
    color: #00a32a;
}

.nomadsguru-result.error {
    background: #fef7f7;
    border-color: #d63638;
    color: #d63638;
}

.nomadsguru-alternatives {
    background: #f8f9f9;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.nomadsguru-alternatives h3 {
    margin: 0 0 15px 0;
    color: #1d2327;
}

.nomadsguru-alternatives ul {
    margin: 15px 0;
    padding-left: 20px;
}

.nomadsguru-alternatives li {
    margin-bottom: 10px;
    line-height: 1.5;
}

.nomadsguru-alternatives strong {
    color: #1d2327;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Enable/disable reset button based on input
    $('#confirm-delete-input').on('input', function() {
        var value = $(this).val().trim();
        $('#confirm-reset-button').prop('disabled', value !== 'DELETE');
    });

    // Handle Enter key
    $('#confirm-delete-input').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            if (!$('#confirm-reset-button').prop('disabled')) {
                $('#confirm-reset-button').click();
            }
        }
    });

    // Cancel button
    $('#cancel-reset-button').on('click', function() {
        $('#confirm-delete-input').val('').focus();
        $('#confirm-reset-button').prop('disabled', true);
        $('#reset-result').hide();
    });

    // Reset button
    $('#confirm-reset-button').on('click', function() {
        if ($('#confirm-delete-input').val().trim() !== 'DELETE') {
            return;
        }

        var $button = $(this);
        var $spinner = $('#reset-spinner');
        var $result = $('#reset-result');

        // Disable buttons and show spinner
        $button.prop('disabled', true);
        $('#cancel-reset-button').prop('disabled', true);
        $spinner.show();
        $result.hide();

        // Make AJAX request
        $.ajax({
            url: nomadsguruParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'nomadsguru_reset_plugin_data',
                nonce: nomadsguruParams.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<p>' + response.data.message + '</p>')
                           .addClass('success')
                           .removeClass('error')
                           .show();
                    
                    // Redirect to plugins page after a short delay
                    setTimeout(function() {
                        window.location.href = nomadsguruParams.pluginsUrl;
                    }, 2000);
                } else {
                    var errorMsg = response.data && response.data.message 
                        ? response.data.message 
                        : 'An error occurred while resetting the plugin data.';
                    $result.html('<p>' + errorMsg + '</p>')
                           .addClass('error')
                           .removeClass('success')
                           .show();
                    
                    // Re-enable buttons on error
                    $button.prop('disabled', false);
                    $('#cancel-reset-button').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                var errorMsg = 'Failed to connect to the server. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg = xhr.responseJSON.data.message;
                }
                $result.html('<p>' + errorMsg + '</p>')
                       .addClass('error')
                       .removeClass('success')
                       .show();
                
                // Re-enable buttons on error
                $button.prop('disabled', false);
                $('#cancel-reset-button').prop('disabled', false);
            },
            complete: function() {
                $spinner.hide();
            }
        });
    });
});
</script>

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
    <h3><?php esc_html_e( 'Reset Plugin Data', 'nomadsguru' ); ?></h3>
    <p><?php esc_html_e( 'This will permanently delete all plugin data including deals, sources, and settings. This action cannot be undone.', 'nomadsguru' ); ?></p>
    
    <button type="button" class="button button-secondary" id="reset-plugin-btn">
        <?php esc_html_e( 'Reset All Data', 'nomadsguru' ); ?>
    </button>
</div>

<style>
.nomadsguru-reset-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.nomadsguru-reset-section h3 {
    margin-top: 0;
    color: #d63638;
}

.nomadsguru-reset-section p {
    margin-bottom: 15px;
}
</style>

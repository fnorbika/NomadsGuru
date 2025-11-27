<?php
/**
 * Queue template for NomadsGuru admin
 *
 * @package NomadsGuru
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Processing Queue', 'nomadsguru' ); ?></h1>
    
    <div class="nomadsguru-queue">
        <div class="nomadsguru-card">
            <p><?php esc_html_e( 'Processing queue will appear here once deals are being processed.', 'nomadsguru' ); ?></p>
        </div>
    </div>
</div>

<style>
.nomadsguru-queue {
    max-width: 1000px;
    margin: 20px 0;
}

.nomadsguru-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}
</style>

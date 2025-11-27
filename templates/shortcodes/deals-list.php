<?php
/**
 * Deals list shortcode template
 *
 * @package NomadsGuru
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Extract attributes
$atts = shortcode_atts( array(
    'limit' => 10,
    'category' => '',
    'source' => '',
), $atts, 'nomadsguru_deals' );

$limit = intval( $atts['limit'] );
$category = sanitize_text_field( $atts['category'] );
$source = sanitize_text_field( $atts['source'] );
?>

<div class="nomadsguru-deals-list">
    <div class="nomadsguru-loading">
        <?php esc_html_e( 'Loading deals...', 'nomadsguru' ); ?>
    </div>
</div>

<style>
.nomadsguru-deals-list {
    max-width: 100%;
    margin: 20px 0;
}

.nomadsguru-loading {
    text-align: center;
    padding: 40px;
    color: #666;
}

.deal-item {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.deal-title {
    font-size: 1.2em;
    font-weight: bold;
    margin-bottom: 10px;
}

.deal-description {
    margin-bottom: 15px;
    color: #333;
}

.deal-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9em;
    color: #666;
}

.deal-price {
    font-weight: bold;
    color: #0073aa;
}

.deal-source {
    background: #f0f0f0;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8em;
}

.nomadsguru-button {
    display: inline-block;
    background: #0073aa;
    color: #fff;
    padding: 8px 16px;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 500;
}

.nomadsguru-button:hover {
    background: #005a87;
}
</style>

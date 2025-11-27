<?php
/**
 * Deals shortcode template for NomadsGuru
 * 
 * @package NomadsGuru
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $deals ) ) : ?>
    <div class="nomadsguru-no-deals">
        <p><?php esc_html_e( 'No deals found matching your criteria.', 'nomadsguru' ); ?></p>
    </div>
<?php else : ?>
    <div class="nomadsguru-deals-container">
        <?php foreach ( $deals as $deal ) : ?>
            <div class="nomadsguru-deal-card">
                <?php if ( ! empty( $deal['image_url'] ) ) : ?>
                    <div class="nomadsguru-deal-image">
                        <img src="<?php echo esc_url( $deal['image_url'] ); ?>" 
                             alt="<?php echo esc_attr( $deal['title'] ); ?>"
                             loading="lazy" />
                    </div>
                <?php endif; ?>
                
                <div class="nomadsguru-deal-content">
                    <h3 class="nomadsguru-deal-title">
                        <a href="<?php echo NomadsGuru_Shortcodes::get_deal_permalink( $deal ); ?>" 
                           target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html( $deal['title'] ); ?>
                        </a>
                    </h3>
                    
                    <?php if ( ! empty( $deal['destination'] ) ) : ?>
                        <div class="nomadsguru-deal-destination">
                            <i class="dashicons dashicons-location"></i>
                            <?php echo esc_html( $deal['destination'] ); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="nomadsguru-deal-pricing">
                        <?php if ( ! empty( $deal['original_price'] ) && $deal['original_price'] > $deal['price'] ) : ?>
                            <span class="nomadsguru-deal-original-price">
                                <?php echo NomadsGuru_Shortcodes::format_price( $deal['original_price'] ); ?>
                            </span>
                        <?php endif; ?>
                        
                        <span class="nomadsguru-deal-current-price">
                            <?php echo NomadsGuru_Shortcodes::format_price( $deal['price'] ); ?>
                        </span>
                        
                        <?php if ( ! empty( $deal['discount_percentage'] ) && $deal['discount_percentage'] > 0 ) : ?>
                            <span class="nomadsguru-deal-discount">
                                -<?php echo NomadsGuru_Shortcodes::format_discount( $deal['discount_percentage'] ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ( ! empty( $deal['valid_until'] ) ) : ?>
                        <div class="nomadsguru-deal-validity">
                            <i class="dashicons dashicons-clock"></i>
                            <?php 
                            printf(
                                /* translators: %s: Valid until date */
                                esc_html__( 'Valid until %s', 'nomadsguru' ),
                                NomadsGuru_Shortcodes::format_date( $deal['valid_until'] )
                            );
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="nomadsguru-deal-score">
                        <span class="nomadsguru-score-label"><?php esc_html_e( 'AI Score:', 'nomadsguru' ); ?></span>
                        <span class="nomadsguru-score-value <?php echo $deal['ai_score'] >= 8 ? 'high' : ($deal['ai_score'] >= 6 ? 'medium' : 'low'); ?>">
                            <?php echo number_format( $deal['ai_score'], 1 ); ?>/10
                        </span>
                    </div>
                    
                    <?php if ( ! empty( $deal['description'] ) ) : ?>
                        <div class="nomadsguru-deal-description">
                            <?php echo wp_kses_post( wp_trim_words( $deal['description'], 20, '...' ) ); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="nomadsguru-deal-actions">
                        <a href="<?php echo NomadsGuru_Shortcodes::get_deal_permalink( $deal ); ?>" 
                           class="nomadsguru-button nomadsguru-button-primary" 
                           target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e( 'View Deal', 'nomadsguru' ); ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.nomadsguru-deals-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.nomadsguru-deal-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.nomadsguru-deal-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.nomadsguru-deal-image {
    height: 200px;
    overflow: hidden;
}

.nomadsguru-deal-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.nomadsguru-deal-card:hover .nomadsguru-deal-image img {
    transform: scale(1.05);
}

.nomadsguru-deal-content {
    padding: 20px;
}

.nomadsguru-deal-title {
    margin: 0 0 10px 0;
    font-size: 1.2em;
    line-height: 1.3;
}

.nomadsguru-deal-title a {
    color: #333;
    text-decoration: none;
}

.nomadsguru-deal-title a:hover {
    color: #0073aa;
}

.nomadsguru-deal-destination {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 10px;
}

.nomadsguru-deal-destination .dashicons {
    font-size: 16px;
    margin-right: 5px;
}

.nomadsguru-deal-pricing {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.nomadsguru-deal-original-price {
    text-decoration: line-through;
    color: #999;
    font-size: 0.9em;
}

.nomadsguru-deal-current-price {
    font-size: 1.4em;
    font-weight: bold;
    color: #0073aa;
}

.nomadsguru-deal-discount {
    background: #d4edda;
    color: #155724;
    padding: 2px 6px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: 600;
}

.nomadsguru-deal-validity {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 10px;
}

.nomadsguru-deal-validity .dashicons {
    font-size: 16px;
    margin-right: 5px;
}

.nomadsguru-deal-score {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.nomadsguru-score-label {
    font-size: 0.9em;
    color: #666;
}

.nomadsguru-score-value {
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.9em;
}

.nomadsguru-score-value.high {
    background: #d4edda;
    color: #155724;
}

.nomadsguru-score-value.medium {
    background: #fff3cd;
    color: #856404;
}

.nomadsguru-score-value.low {
    background: #f8d7da;
    color: #721c24;
}

.nomadsguru-deal-description {
    color: #666;
    font-size: 0.9em;
    line-height: 1.4;
    margin-bottom: 15px;
}

.nomadsguru-deal-actions {
    margin-top: 15px;
}

.nomadsguru-button {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    text-align: center;
    transition: background-color 0.2s ease;
}

.nomadsguru-button-primary {
    background: #0073aa;
    color: #fff;
}

.nomadsguru-button-primary:hover {
    background: #005a87;
    color: #fff;
}

.nomadsguru-no-deals {
    text-align: center;
    padding: 40px;
    background: #f8f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    color: #666;
}

@media (max-width: 768px) {
    .nomadsguru-deals-container {
        grid-template-columns: 1fr;
    }
    
    .nomadsguru-deal-pricing {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
}
</style>

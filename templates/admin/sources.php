<?php
/**
 * Sources template for NomadsGuru admin
 *
 * @package NomadsGuru
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Deal Sources', 'nomadsguru' ); ?></h1>
    
    <div class="nomadsguru-sources">
        <!-- Add New Source Button -->
        <div class="nomadsguru-card">
            <button type="button" class="button button-primary" id="add-source-btn">
                <?php esc_html_e( 'Add New Source', 'nomadsguru' ); ?>
            </button>
        </div>

        <!-- Sources List -->
        <div class="nomadsguru-card">
            <h3><?php esc_html_e( 'Active Sources', 'nomadsguru' ); ?></h3>
            <div id="sources-list">
                <p><?php esc_html_e( 'Loading sources...', 'nomadsguru' ); ?></p>
            </div>
        </div>

        <!-- Add/Edit Source Modal -->
        <div id="source-modal" class="nomadsguru-modal" style="display: none;">
            <div class="nomadsguru-modal-content">
                <div class="nomadsguru-modal-header">
                    <h3 id="modal-title"><?php esc_html_e( 'Add New Source', 'nomadsguru' ); ?></h3>
                    <span class="nomadsguru-modal-close">&times;</span>
                </div>
                <div class="nomadsguru-modal-body">
                    <form id="source-form">
                        <input type="hidden" id="ng-source-id" name="id" value="">
                        
                        <div class="form-field">
                            <label for="ng-source-type"><?php esc_html_e( 'Source Type', 'nomadsguru' ); ?></label>
                            <select id="ng-source-type" name="source_type" required>
                                <option value=""><?php esc_html_e( 'Select type...', 'nomadsguru' ); ?></option>
                                <option value="csv"><?php esc_html_e( 'CSV File', 'nomadsguru' ); ?></option>
                                <option value="rss"><?php esc_html_e( 'RSS Feed', 'nomadsguru' ); ?></option>
                                <option value="web_scraper"><?php esc_html_e( 'Web Scraper', 'nomadsguru' ); ?></option>
                                <option value="api"><?php esc_html_e( 'API Endpoint', 'nomadsguru' ); ?></option>
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="ng-source-name"><?php esc_html_e( 'Source Name', 'nomadsguru' ); ?></label>
                            <input type="text" id="ng-source-name" name="source_name" required>
                        </div>

                        <div class="form-field" id="website-url-field" style="display: none;">
                            <label for="ng-website-url"><?php esc_html_e( 'Website URL', 'nomadsguru' ); ?></label>
                            <input type="url" id="ng-website-url" name="website_url">
                        </div>

                        <div class="form-field" id="rss-feed-field" style="display: none;">
                            <label for="ng-rss-feed"><?php esc_html_e( 'RSS Feed URL', 'nomadsguru' ); ?></label>
                            <input type="url" id="ng-rss-feed" name="rss_feed">
                        </div>

                        <div class="form-field" id="api-endpoint-field" style="display: none;">
                            <label for="ng-api-endpoint"><?php esc_html_e( 'API Endpoint', 'nomadsguru' ); ?></label>
                            <input type="url" id="ng-api-endpoint" name="api_endpoint">
                        </div>

                        <div class="form-field">
                            <label for="ng-source-interval"><?php esc_html_e( 'Sync Interval (minutes)', 'nomadsguru' ); ?></label>
                            <input type="number" id="ng-source-interval" name="sync_interval_minutes" value="60" min="1">
                        </div>

                        <div class="form-field">
                            <label>
                                <input type="checkbox" id="ng-source-active" name="is_active" value="1" checked>
                                <?php esc_html_e( 'Active', 'nomadsguru' ); ?>
                            </label>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="button button-primary">
                                <?php esc_html_e( 'Save Source', 'nomadsguru' ); ?>
                            </button>
                            <button type="button" class="button" id="cancel-source-btn">
                                <?php esc_html_e( 'Cancel', 'nomadsguru' ); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.nomadsguru-sources {
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

.nomadsguru-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.nomadsguru-modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 0;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
}

.nomadsguru-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nomadsguru-modal-close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.nomadsguru-modal-body {
    padding: 20px;
}

.form-field {
    margin-bottom: 15px;
}

.form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-field input,
.form-field select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-actions {
    margin-top: 20px;
    text-align: right;
}

.form-actions button {
    margin-left: 10px;
}

.source-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 10px;
}

.source-info h4 {
    margin: 0 0 5px 0;
}

.source-info p {
    margin: 0;
    color: #666;
    font-size: 0.9em;
}

.source-actions {
    display: flex;
    gap: 10px;
}
</style>

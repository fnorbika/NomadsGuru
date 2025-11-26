<?php

namespace NomadsGuru\Core;

class Database {

	/**
	 * Create database tables
	 */
	public static function activate() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Table: Deal Sources
		$table_sources = $wpdb->prefix . 'ng_deal_sources';
		$sql_sources   = "CREATE TABLE $table_sources (
			id INT AUTO_INCREMENT PRIMARY KEY,
			source_type VARCHAR(50) NOT NULL,
			source_name VARCHAR(255) NOT NULL,
			website_url VARCHAR(500),
			rss_feed VARCHAR(500),
			is_active BOOLEAN DEFAULT 1,
			last_sync DATETIME,
			sync_interval_minutes INT DEFAULT 60,
			created_at DATETIME,
			updated_at DATETIME,
			UNIQUE KEY unique_source (source_type, source_name)
		) $charset_collate;";
		dbDelta( $sql_sources );

		// Table: Affiliate Programs
		$table_affiliates = $wpdb->prefix . 'ng_affiliate_programs';
		$sql_affiliates   = "CREATE TABLE $table_affiliates (
			id INT AUTO_INCREMENT PRIMARY KEY,
			program_name VARCHAR(255) NOT NULL,
			program_type ENUM('api', 'manual_url', 'cookie_based') DEFAULT 'manual_url',
			api_endpoint VARCHAR(500),
			credentials_encrypted LONGTEXT,
			url_pattern VARCHAR(1000),
			commission_rate DECIMAL(5,2),
			is_active BOOLEAN DEFAULT 1,
			created_at DATETIME,
			updated_at DATETIME,
			UNIQUE KEY unique_program (program_name)
		) $charset_collate;";
		dbDelta( $sql_affiliates );

		// Table: Raw Deals
		$table_deals = $wpdb->prefix . 'ng_raw_deals';
		$sql_deals   = "CREATE TABLE $table_deals (
			id INT AUTO_INCREMENT PRIMARY KEY,
			source_id INT NOT NULL,
			external_id VARCHAR(255),
			deal_data LONGTEXT,
			title VARCHAR(500),
			destination VARCHAR(255),
			original_price DECIMAL(10,2),
			discounted_price DECIMAL(10,2),
			currency VARCHAR(3),
			travel_dates_start DATE,
			travel_dates_end DATE,
			raw_link VARCHAR(1000),
			evaluation_score INT,
			evaluation_reason LONGTEXT,
			is_processed BOOLEAN DEFAULT 0,
			post_id INT,
			created_at DATETIME,
			expires_at DATETIME,
			UNIQUE KEY unique_deal (source_id, external_id),
			INDEX idx_score (evaluation_score DESC),
			INDEX idx_destination (destination),
			INDEX idx_created (created_at)
		) $charset_collate;";
		dbDelta( $sql_deals );

		// Table: Processing Queue
		$table_queue = $wpdb->prefix . 'ng_processing_queue';
		$sql_queue   = "CREATE TABLE $table_queue (
			id INT AUTO_INCREMENT PRIMARY KEY,
			raw_deal_id INT NOT NULL,
			status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
			retry_count INT DEFAULT 0,
			error_message LONGTEXT,
			created_at DATETIME,
			updated_at DATETIME,
			INDEX idx_status (status),
			INDEX idx_created (created_at)
		) $charset_collate;";
		dbDelta( $sql_queue );

		// Table: Publishing Configuration
		$table_config = $wpdb->prefix . 'ng_publishing_config';
		$sql_config   = "CREATE TABLE $table_config (
			id INT PRIMARY KEY DEFAULT 1,
			publishing_mode ENUM('automatic', 'manual') DEFAULT 'automatic',
			min_articles_per_batch INT DEFAULT 1,
			max_articles_per_batch INT DEFAULT 10,
			batch_schedule VARCHAR(50) DEFAULT 'daily',
			auto_publish_time TIME,
			email_notifications BOOLEAN DEFAULT 1,
			updated_at DATETIME
		) $charset_collate;";
		dbDelta( $sql_config );

		// Table: Logs
		$table_logs = $wpdb->prefix . 'ng_logs';
		$sql_logs   = "CREATE TABLE $table_logs (
			id INT AUTO_INCREMENT PRIMARY KEY,
			log_level VARCHAR(50),
			component VARCHAR(100),
			message LONGTEXT,
			context JSON,
			created_at DATETIME,
			INDEX idx_level (log_level),
			INDEX idx_component (component),
			INDEX idx_created (created_at)
		) $charset_collate;";
		dbDelta( $sql_logs );
	}
}

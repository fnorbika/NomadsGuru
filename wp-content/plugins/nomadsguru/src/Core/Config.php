<?php

namespace NomadsGuru\Core;

class Config {

	/**
	 * Default configuration
	 *
	 * @var array
	 */
	private static $defaults = array(
		'publishing_mode'        => 'automatic',
		'min_articles_per_batch' => 1,
		'max_articles_per_batch' => 10,
		'batch_schedule'         => 'daily',
		'auto_publish_time'      => '08:00:00',
		'email_notifications'    => 1,
	);

	/**
	 * Get a configuration value from options (General Settings)
	 *
	 * @param string $key Config key.
	 * @param mixed  $default Default value if not found.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$options = get_option( 'nomadsguru_settings', array() );
		return isset( $options[ $key ] ) ? $options[ $key ] : $default;
	}

	/**
	 * Get publishing configuration from custom table
	 *
	 * @return array
	 */
	public static function get_publishing_config() {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_publishing_config';
		
		// Try to get from cache first
		$cached = Cache::get( 'publishing_config' );
		if ( $cached ) {
			return $cached;
		}

		$config = $wpdb->get_row( "SELECT * FROM $table WHERE id = 1", ARRAY_A );

		if ( ! $config ) {
			return self::$defaults;
		}

		Cache::set( 'publishing_config', $config );

		return $config;
	}

	/**
	 * Update publishing configuration
	 * 
	 * @param array $data Data to update.
	 * @return bool|int
	 */
	public static function update_publishing_config( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_publishing_config';

		$defaults = self::$defaults;
		$data = array_merge( $defaults, $data ); // Ensure all keys exist if inserting

		// Check if exists
		$exists = $wpdb->get_var( "SELECT id FROM $table WHERE id = 1" );

		if ( $exists ) {
			$result = $wpdb->update( $table, $data, array( 'id' => 1 ) );
		} else {
			$data['id'] = 1;
			$result = $wpdb->insert( $table, $data );
		}

		Cache::delete( 'publishing_config' );

		return $result;
	}
}

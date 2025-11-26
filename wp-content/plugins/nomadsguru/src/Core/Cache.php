<?php

namespace NomadsGuru\Core;

class Cache {

	/**
	 * Set a value in cache
	 *
	 * @param string $key     Cache key.
	 * @param mixed  $value   Value to cache.
	 * @param int    $expiration Expiration in seconds. Default 1 hour.
	 * @return bool
	 */
	public static function set( $key, $value, $expiration = 3600 ) {
		return set_transient( 'ng_' . $key, $value, $expiration );
	}

	/**
	 * Get a value from cache
	 *
	 * @param string $key Cache key.
	 * @return mixed|false
	 */
	public static function get( $key ) {
		return get_transient( 'ng_' . $key );
	}

	/**
	 * Delete a value from cache
	 *
	 * @param string $key Cache key.
	 * @return bool
	 */
	public static function delete( $key ) {
		return delete_transient( 'ng_' . $key );
	}

	/**
	 * Clear all plugin transients (helper)
	 * Note: This is expensive and should be used with caution.
	 */
	public static function flush() {
		global $wpdb;
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_ng_%' OR option_name LIKE '_transient_timeout_ng_%'" );
	}
}

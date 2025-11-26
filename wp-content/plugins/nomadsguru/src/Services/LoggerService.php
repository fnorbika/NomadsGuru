<?php

namespace NomadsGuru\Services;

use NomadsGuru\Utils\DateHelper;

class LoggerService {

	/**
	 * Log a message
	 *
	 * @param string $level
	 * @param string $component
	 * @param string $message
	 * @param array  $context
	 */
	public static function log( $level, $component, $message, $context = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ng_logs';

		$wpdb->insert(
			$table,
			array(
				'log_level'  => $level,
				'component'  => $component,
				'message'    => $message,
				'context'    => json_encode( $context ),
				'created_at' => DateHelper::now(),
			)
		);
	}

	/**
	 * Log info
	 */
	public static function info( $component, $message, $context = array() ) {
		self::log( 'INFO', $component, $message, $context );
	}

	/**
	 * Log warning
	 */
	public static function warning( $component, $message, $context = array() ) {
		self::log( 'WARNING', $component, $message, $context );
	}

	/**
	 * Log error
	 */
	public static function error( $component, $message, $context = array() ) {
		self::log( 'ERROR', $component, $message, $context );
	}
}

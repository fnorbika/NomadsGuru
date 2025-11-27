<?php

namespace NomadsGuru\Utils;

class DateHelper {

	/**
	 * Get current datetime in MySQL format
	 *
	 * @return string
	 */
	public static function now() {
		return current_time( 'mysql' );
	}

	/**
	 * Format date
	 *
	 * @param string $date
	 * @param string $format
	 * @return string
	 */
	public static function format( $date, $format = 'Y-m-d' ) {
		return date( $format, strtotime( $date ) );
	}

	/**
	 * Add days to date
	 *
	 * @param string $date
	 * @param int    $days
	 * @return string
	 */
	public static function add_days( $date, $days ) {
		return date( 'Y-m-d', strtotime( $date . " +$days days" ) );
	}
}

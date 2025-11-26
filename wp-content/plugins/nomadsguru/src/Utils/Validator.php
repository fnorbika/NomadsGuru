<?php

namespace NomadsGuru\Utils;

class Validator {

	/**
	 * Validate email
	 *
	 * @param string $email
	 * @return bool
	 */
	public static function is_email( $email ) {
		return is_email( $email );
	}

	/**
	 * Validate URL
	 *
	 * @param string $url
	 * @return bool
	 */
	public static function is_url( $url ) {
		return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
	}

	/**
	 * Validate date format (Y-m-d)
	 *
	 * @param string $date
	 * @return bool
	 */
	public static function is_date( $date ) {
		$d = \DateTime::createFromFormat( 'Y-m-d', $date );
		return $d && $d->format( 'Y-m-d' ) === $date;
	}

	/**
	 * Validate non-empty string
	 *
	 * @param string $string
	 * @return bool
	 */
	public static function not_empty( $string ) {
		return ! empty( trim( $string ) );
	}
}

<?php

namespace NomadsGuru\Utils;

class Sanitizer {

	/**
	 * Sanitize a string
	 *
	 * @param string $string
	 * @return string
	 */
	public static function text( $string ) {
		return sanitize_text_field( $string );
	}

	/**
	 * Sanitize an email
	 *
	 * @param string $email
	 * @return string
	 */
	public static function email( $email ) {
		return sanitize_email( $email );
	}

	/**
	 * Sanitize a URL
	 *
	 * @param string $url
	 * @return string
	 */
	public static function url( $url ) {
		return esc_url_raw( $url );
	}

	/**
	 * Sanitize an array recursively
	 *
	 * @param array $array
	 * @return array
	 */
	public static function array_recursive( $array ) {
		if ( ! is_array( $array ) ) {
			return self::text( $array );
		}

		foreach ( $array as $key => $value ) {
			$array[ $key ] = is_array( $value ) ? self::array_recursive( $value ) : self::text( $value );
		}

		return $array;
	}
}

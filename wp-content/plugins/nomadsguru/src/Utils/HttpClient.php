<?php

namespace NomadsGuru\Utils;

class HttpClient {

	/**
	 * Perform a GET request
	 *
	 * @param string $url
	 * @param array  $args
	 * @return array|\WP_Error
	 */
	public static function get( $url, $args = array() ) {
		$defaults = array(
			'timeout' => 15,
		);
		$args = wp_parse_args( $args, $defaults );
		return wp_remote_get( $url, $args );
	}

	/**
	 * Perform a POST request
	 *
	 * @param string $url
	 * @param array  $body
	 * @param array  $args
	 * @return array|\WP_Error
	 */
	public static function post( $url, $body, $args = array() ) {
		$defaults = array(
			'timeout' => 15,
			'body'    => $body,
		);
		$args = wp_parse_args( $args, $defaults );
		return wp_remote_post( $url, $args );
	}

	/**
	 * Get body from response
	 *
	 * @param array|\WP_Error $response
	 * @return string|null
	 */
	public static function get_body( $response ) {
		if ( is_wp_error( $response ) ) {
			return null;
		}
		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Get JSON body from response
	 *
	 * @param array|\WP_Error $response
	 * @return mixed|null
	 */
	public static function get_json( $response ) {
		$body = self::get_body( $response );
		if ( ! $body ) {
			return null;
		}
		return json_decode( $body, true );
	}
}

<?php

namespace NomadsGuru\REST;

class DealsController {

	/**
	 * Register routes
	 */
	public function register_routes() {
		register_rest_route( 'nomadsguru/v1', '/deals', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_deals' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'nomadsguru/v1', '/deals/(?P<id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_deal' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Get deals
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_deals( $request ) {
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => $request->get_param( 'per_page' ) ?: 10,
			'meta_key'       => '_ng_evaluation_score',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
		);

		$posts = get_posts( $args );
		$deals = array();

		foreach ( $posts as $post ) {
			$deals[] = $this->format_deal( $post );
		}

		return new \WP_REST_Response( $deals, 200 );
	}

	/**
	 * Get single deal
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_deal( $request ) {
		$post = get_post( $request['id'] );

		if ( ! $post ) {
			return new \WP_REST_Response( array( 'error' => 'Deal not found' ), 404 );
		}

		return new \WP_REST_Response( $this->format_deal( $post ), 200 );
	}

	/**
	 * Format deal for API response
	 *
	 * @param \WP_Post $post
	 * @return array
	 */
	private function format_deal( $post ) {
		return array(
			'id'                => $post->ID,
			'title'             => $post->post_title,
			'content'           => $post->post_content,
			'excerpt'           => $post->post_excerpt,
			'destination'       => get_post_meta( $post->ID, '_ng_destination', true ),
			'original_price'    => get_post_meta( $post->ID, '_ng_original_price', true ),
			'discounted_price'  => get_post_meta( $post->ID, '_ng_discounted_price', true ),
			'currency'          => get_post_meta( $post->ID, '_ng_currency', true ),
			'score'             => get_post_meta( $post->ID, '_ng_evaluation_score', true ),
			'affiliate_link'    => get_post_meta( $post->ID, '_ng_affiliate_link', true ),
			'featured_image'    => get_post_meta( $post->ID, '_ng_featured_image_url', true ),
			'published_date'    => $post->post_date,
		);
	}
}

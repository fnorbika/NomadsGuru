<?php

namespace NomadsGuru\Services;

class AIService {

	/**
	 * Evaluate a deal based on criteria
	 *
	 * @param array $deal_data
	 * @return array Score and reasoning
	 */
	public function evaluate_deal( $deal_data ) {
		// MOCK IMPLEMENTATION
		// In a real scenario, this would call OpenAI/Gemini API
		
		$score = 0;
		$reason = [];

		// 1. Discount Percentage (30%)
		$discount = 0;
		if ( ! empty( $deal_data['original_price'] ) && ! empty( $deal_data['discounted_price'] ) ) {
			$discount = ( ( $deal_data['original_price'] - $deal_data['discounted_price'] ) / $deal_data['original_price'] ) * 100;
		}
		
		if ( $discount > 50 ) {
			$score += 30;
			$reason[] = "Excellent discount of " . round( $discount ) . "%";
		} elseif ( $discount > 30 ) {
			$score += 20;
			$reason[] = "Good discount of " . round( $discount ) . "%";
		} else {
			$score += 10;
			$reason[] = "Moderate discount of " . round( $discount ) . "%";
		}

		// 2. Value for Money (30%) - Mock random for now
		$value_score = rand( 15, 30 );
		$score += $value_score;
		$reason[] = "Value for money score: $value_score/30";

		// 3. Destination Attractiveness (20%) - Mock
		$dest_score = rand( 10, 20 );
		$score += $dest_score;
		$reason[] = "Destination attractiveness: $dest_score/20";

		// 4. Timing/Seasonality (20%) - Mock
		$time_score = rand( 10, 20 );
		$score += $time_score;
		$reason[] = "Timing score: $time_score/20";

		return array(
			'score'  => $score,
			'reason' => implode( "; ", $reason ),
		);
	}

	/**
	 * Generate content for a deal
	 *
	 * @param array $deal_data
	 * @return array Title, description, body
	 */
	public function generate_content( $deal_data ) {
		// MOCK IMPLEMENTATION
		
		$destination = isset( $deal_data['destination'] ) ? $deal_data['destination'] : 'Unknown Destination';
		$price = isset( $deal_data['discounted_price'] ) ? $deal_data['discounted_price'] : 'N/A';

		return array(
			'title' => "Amazing Deal: Fly to $destination for only $$price!",
			'meta_description' => "Don't miss this incredible offer to visit $destination. Book now and save big on your next adventure.",
			'body' => "
				<h2>Discover $destination</h2>
				<p>Experience the beauty of $destination with this exclusive deal. Whether you are looking for adventure or relaxation, this trip has it all.</p>
				<h3>Highlights</h3>
				<ul>
					<li>Round trip flights included</li>
					<li>4-star accommodation</li>
					<li>Guided tours available</li>
				</ul>
				<h3>Booking Tips</h3>
				<p>Book early to secure this price. Prices are subject to availability.</p>
			",
		);
	}
}

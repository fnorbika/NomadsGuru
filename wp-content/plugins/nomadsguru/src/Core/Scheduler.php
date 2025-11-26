<?php

namespace NomadsGuru\Core;

class Scheduler {

	/**
	 * Initialize
	 */
	public function init() {
		add_action( 'init', array( $this, 'schedule_events' ) );
		add_action( 'nomadsguru_poll_sources', array( $this, 'poll_sources' ) );
	}

	/**
	 * Schedule cron events
	 */
	public function schedule_events() {
		if ( ! wp_next_scheduled( 'nomadsguru_poll_sources' ) ) {
			wp_schedule_event( time(), 'hourly', 'nomadsguru_poll_sources' );
		}
	}

	/**
	 * Poll sources for new deals
	 */
	public function poll_sources() {
		// Logic to iterate through active sources and fetch deals
		// Then save to database
		error_log( 'NomadsGuru: Polling sources...' );
	}
}

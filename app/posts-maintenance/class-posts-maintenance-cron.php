<?php
/**
 * Posts Maintenance Cron functionality.
 *
 * @package WPMUDEV\PluginTest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPMUDEV_Posts_Maintenance_Cron
 *
 * Handles scheduled posts maintenance.
 */
class WPMUDEV_Posts_Maintenance_Cron {

	/**
	 * Initialize cron functionality.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp', array( $this, 'schedule_event' ) );
		add_action( 'wpmudev_daily_posts_scan', array( $this, 'run_daily_scan' ) );
	}

	/**
	 * Schedule the daily scan event.
	 *
	 * @return void
	 */
	public function schedule_event() {
		if ( ! wp_next_scheduled( 'wpmudev_daily_posts_scan' ) ) {
			wp_schedule_event( time(), 'daily', 'wpmudev_daily_posts_scan' );
		}
	}

	/**
	 * Run the daily posts scan.
	 *
	 * @return void
	 */
	public function run_daily_scan() {
		$maintenance = new WPMUDEV_Posts_Maintenance();
		// Log the scan execution for debugging purposes.
		$maintenance->scan_posts();
	}
}
}

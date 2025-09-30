<?php
/**
 * Cron for Posts Maintenance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPMUDEV_Posts_Maintenance_Cron {

    public function init() {
        add_action( 'wpmudev_daily_scan', [ $this, 'run_daily_scan' ] );

        if ( ! wp_next_scheduled( 'wpmudev_daily_scan' ) ) {
            wp_schedule_event( time(), 'daily', 'wpmudev_daily_scan' );
        }
    }

    public function run_daily_scan() {
        $count = WPMUDEV_Posts_Maintenance::scan_posts();
        error_log( "WPMUDEV daily posts scan updated {$count} posts." );
    }
}

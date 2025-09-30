<?php
/**
 * WP-CLI integration for Posts Maintenance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    class WPMUDEV_Posts_Maintenance_CLI extends WP_CLI_Command {

        /**
         * Scan posts and update last scan timestamp.
         *
         * ## OPTIONS
         *
         * [--post_type=<type>]
         * : Limit scan to a specific post type (default: all public).
         *
         * ## EXAMPLES
         *
         *     wp wpmudev posts scan
         *     wp wpmudev posts scan --post_type=page
         *
         * @when after_wp_load
         */
        public function scan( $args, $assoc_args ) {
            $post_types = [];

            if ( ! empty( $assoc_args['post_type'] ) ) {
                $post_types = explode( ',', $assoc_args['post_type'] );
            }

            $count = WPMUDEV_Posts_Maintenance::scan_posts( $post_types );
            WP_CLI::success( "Scan complete. Updated {$count} posts." );
        }
    }

    WP_CLI::add_command( 'wpmudev posts', 'WPMUDEV_Posts_Maintenance_CLI' );
}

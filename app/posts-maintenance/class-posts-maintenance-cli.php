<?php
/**
 * Posts Maintenance CLI command.
 *
 * @package WPMUDEV\PluginTest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPMUDEV_Posts_Maintenance_CLI
 *
 * WP-CLI command for posts maintenance.
 */
class WPMUDEV_Posts_Maintenance_CLI {

	/**
	 * Scan posts via WP-CLI.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function scan( $args, $assoc_args ) {
		WP_CLI::log( 'Starting posts scan...' );

		$post_types = isset( $assoc_args['post_types'] ) ? explode( ',', $assoc_args['post_types'] ) : array( 'post', 'page' );
		$status     = isset( $assoc_args['status'] ) ? $assoc_args['status'] : 'publish';

		$maintenance = new WPMUDEV_Posts_Maintenance();
		$result      = $maintenance->scan_posts(
			array(
				'post_type'   => $post_types,
				'post_status' => $status,
			)
		);

		WP_CLI::success( 'Scan completed. Updated ' . $result['count'] . ' posts.' );
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'posts-maintenance', 'WPMUDEV_Posts_Maintenance_CLI' );
}

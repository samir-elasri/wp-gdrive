<?php
/**
 * Posts Maintenance functionality.
 *
 * @package WPMUDEV\PluginTest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPMUDEV_Posts_Maintenance
 *
 * Handles posts maintenance operations.
 */
class WPMUDEV_Posts_Maintenance {

	/**
	 * Plugin instance.
	 *
	 * @var WPMUDEV_Posts_Maintenance
	 */
	private static $instance;

	/**
	 * Initialize the class.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'wp_ajax_scan_posts', array( $this, 'ajax_scan_posts' ) );
	}

	/**
	 * Register the admin page.
	 *
	 * @return void
	 */
	public function register_admin_page() {
		add_menu_page(
			__( 'Posts Maintenance', 'wpmudev-plugin-test' ),
			__( 'Posts Maintenance', 'wpmudev-plugin-test' ),
			'manage_options',
			'posts-maintenance',
			array( $this, 'render_page' ),
			'dashicons-admin-tools'
		);
	}

	/**
	 * Render the admin page.
	 *
	 * @return void
	 */
	public function render_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Posts Maintenance', 'wpmudev-plugin-test' ); ?></h1>
			<p><?php esc_html_e( 'Scan all public posts and pages to update last scan timestamp.', 'wpmudev-plugin-test' ); ?></p>
			
			<button id="scan-posts-btn" class="button button-primary">
				<?php esc_html_e( 'Scan Posts', 'wpmudev-plugin-test' ); ?>
			</button>
			
			<div id="scan-progress" style="display:none;">
				<p><?php esc_html_e( 'Scanning...', 'wpmudev-plugin-test' ); ?></p>
			</div>
			
			<script>
			document.getElementById('scan-posts-btn').onclick = function() {
				// Add AJAX functionality.
			};
			</script>
			
			<input type="hidden" id="scan-nonce" value="<?php echo esc_attr( wp_create_nonce( 'scan_posts_nonce' ) ); ?>" />
		</div>
		<?php
	}

	/**
	 * Handle AJAX scan posts request.
	 *
	 * @return void
	 */
	public function ajax_scan_posts() {
		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'scan_posts_nonce' ) ) {
			wp_die(
				esc_html__(
					'Security check failed.',
					'wpmudev-plugin-test'
				)
			);
		}

		$result = $this->scan_posts();
		wp_send_json_success( $result );
	}

	/**
	 * Scan posts and update meta.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function scan_posts( $args = array() ) {
		$defaults = array(
			'post_type'   => array( 'post', 'page' ),
			'post_status' => 'publish',
		);

		$query_args = array_merge( $defaults, $args );

		$posts = get_posts( $query_args );
		$count = 0;

		foreach ( $posts as $post ) {
			update_post_meta( $post->ID, 'wpmudev_test_last_scan', current_time( 'mysql' ) );
			++$count;
		}

		return array( 'count' => $count );
	}
}

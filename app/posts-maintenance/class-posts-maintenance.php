<?php
/**
 * Posts Maintenance Admin Page
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPMUDEV_Posts_Maintenance {

    private $page_slug = 'wpmudev-posts-maintenance';

    public function init() {
        add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
        add_action( 'wp_ajax_wpmudev_scan_posts', [ $this, 'ajax_scan_posts' ] );
    }

    public function register_admin_page() {
        add_menu_page(
            __( 'Posts Maintenance', 'wpmudev-plugin-test' ),
            __( 'Posts Maintenance', 'wpmudev-plugin-test' ),
            'manage_options',
            $this->page_slug,
            [ $this, 'render_page' ],
            'dashicons-hammer'
        );
    }

    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Posts Maintenance', 'wpmudev-plugin-test' ); ?></h1>
            <p><?php esc_html_e( 'Scan all public posts and pages to update last scan timestamp.', 'wpmudev-plugin-test' ); ?></p>

            <button id="wpmudev-scan-posts" class="button button-primary">
                <?php esc_html_e( 'Scan Posts', 'wpmudev-plugin-test' ); ?>
            </button>

            <div id="wpmudev-scan-result" style="margin-top:1em;"></div>

            <script type="text/javascript">
                jQuery(function($){
                    $('#wpmudev-scan-posts').on('click', function(e){
                        e.preventDefault();
                        $('#wpmudev-scan-result').text('<?php echo esc_js( __( 'Scanning...', 'wpmudev-plugin-test' ) ); ?>');

                        $.post(ajaxurl, {
                            action: 'wpmudev_scan_posts',
                            _ajax_nonce: '<?php echo wp_create_nonce( 'wpmudev_scan_posts' ); ?>'
                        }, function(res){
                            $('#wpmudev-scan-result').text(res.data.message);
                        });
                    });
                });
            </script>
        </div>
        <?php
    }

    public function ajax_scan_posts() {
        check_ajax_referer( 'wpmudev_scan_posts' );

        $count = self::scan_posts();

        wp_send_json_success( [
            'message' => sprintf( __( 'Scan complete. Updated %d posts.', 'wpmudev-plugin-test' ), $count )
        ] );
    }

    public static function scan_posts( $post_types = [] ) {
        if ( empty( $post_types ) ) {
            $post_types = get_post_types( [ 'public' => true ], 'names' );
        }

        $args = [
            'post_type'      => $post_types,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];

        $query = new WP_Query( $args );
        $count = 0;

        foreach ( $query->posts as $post_id ) {
            update_post_meta( $post_id, 'wpmudev_test_last_scan', current_time( 'mysql' ) );
            $count++;
        }

        return $count;
    }
}

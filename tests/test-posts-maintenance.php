<?php
<?php if (!class_exists("WP_UnitTestCase")) { class WP_UnitTestCase extends \PHPUnit\Framework\TestCase { public function set_up() {} } }
/**
 * Tests for Posts Maintenance feature.
 *
 * @group posts-maintenance
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * If you prefer WP_UnitTestCase, you can extend it directly:
 * class Tests_Posts_Maintenance extends WP_UnitTestCase { ... }
 * Using polyfills TestCase is fine too; we'll include WP factories by bootstrapping WP.
 */
class Tests_Posts_Maintenance extends WP_UnitTestCase {

    public function set_up() {
        parent::set_up();
        // Ensure the classes exist.
        $this->assertTrue( class_exists( 'WPMUDEV_Posts_Maintenance' ), 'WPMUDEV_Posts_Maintenance not loaded' );
        $this->assertTrue( class_exists( 'WPMUDEV_Posts_Maintenance_Cron' ), 'WPMUDEV_Posts_Maintenance_Cron not loaded' );

        // Clear any lingering meta from previous runs.
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = 'wpmudev_test_last_scan'" );
    }

    public function test_scan_posts_updates_last_scan_meta_for_posts_and_pages() {
        // Create sample posts & pages.
        $posts = self::factory()->post->create_many( 3, ['post_type' => 'post'] );
        $pages = self::factory()->post->create_many( 2, ['post_type' => 'page'] );

        // Sanity.
        foreach ( array_merge( $posts, $pages ) as $pid ) {
            $this->assertSame( '', get_post_meta( $pid, 'wpmudev_test_last_scan', true ) );
        }

        // Run the scan (default: all public post types).
        $count = WPMUDEV_Posts_Maintenance::scan_posts();

        // Assert counts & meta.
        $this->assertSame( 5, $count, 'Unexpected number of updated posts' );

        foreach ( array_merge( $posts, $pages ) as $pid ) {
            $val = get_post_meta( $pid, 'wpmudev_test_last_scan', true );
            $this->assertNotEmpty( $val, 'Meta not set' );
            // Basic timestamp sanity: current_time('mysql') format contains '-' and ':'
            $this->assertMatchesRegularExpression( '/\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}/', $val );
        }
    }

    public function test_scan_posts_accepts_post_type_filter() {
        $posts = self::factory()->post->create_many( 2, ['post_type' => 'post'] );
        $pages = self::factory()->post->create_many( 2, ['post_type' => 'page'] );

        // Only scan pages.
        $count = WPMUDEV_Posts_Maintenance::scan_posts( ['page'] );
        $this->assertSame( 2, $count );

        foreach ( $pages as $pid ) {
            $this->assertNotEmpty( get_post_meta( $pid, 'wpmudev_test_last_scan', true ) );
        }
        foreach ( $posts as $pid ) {
            $this->assertSame( '', get_post_meta( $pid, 'wpmudev_test_last_scan', true ), 'Posts should not be updated when filtering to pages' );
        }
    }

    public function test_scan_posts_handles_no_posts_gracefully() {
        $count = WPMUDEV_Posts_Maintenance::scan_posts();
        $this->assertSame( 0, $count );
    }

    public function test_daily_cron_hook_is_registered_and_runs() {
        // Ensure the cron class is init'd (plugins_loaded in our loader sets it).
        $this->assertNotFalse( has_action( 'wpmudev_daily_scan' ) );

        // Create a post to see if the cron handler updates it.
        $pid = self::factory()->post->create( ['post_type' => 'post'] );

        // Fire the cron hook handler directly (simulate daily run).
        do_action( 'wpmudev_daily_scan' );

        $this->assertNotEmpty( get_post_meta( $pid, 'wpmudev_test_last_scan', true ) );
    }

    /**
     * Optional: CLI smoke test (skips if WP_CLI not defined during test run).
     */
    public function test_cli_command_class_exists_and_scan_invokes_logic() {
        if ( ! defined( 'WP_CLI' ) ) {
            $this->markTestSkipped( 'WP_CLI not available in unit test env.' );
        }
        $this->assertTrue( class_exists( 'WPMUDEV_Posts_Maintenance_CLI' ) );

        // Create some posts, then call the scan() method directly.
        $ids = self::factory()->post->create_many( 2, ['post_type' => 'post'] );
        $cli  = new WPMUDEV_Posts_Maintenance_CLI();
        $cli->scan( [], [] ); // no post_type filter

        foreach ( $ids as $pid ) {
            $this->assertNotEmpty( get_post_meta( $pid, 'wpmudev_test_last_scan', true ) );
        }
    }
}

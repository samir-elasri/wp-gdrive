<?php
/**
 * PHPUnit bootstrap for wpmudev-plugin-test.
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?\n";
    echo "Skipping WordPress tests - no test environment\n"; return;
}

/**
 * Manually load the plugin.
 */
function _manually_load_plugin() {
    // Adjust if your main file lives elsewhere.
    require dirname( __DIR__ ) . '/wpmudev-plugin-test.php';

    // Ensure Posts Maintenance classes are loaded by the plugin file (as we instructed earlier).
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';


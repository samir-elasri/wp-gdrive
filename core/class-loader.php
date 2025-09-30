<?php
/**
 * Loader class file.
 *
 * @package WPMUDEV\PluginTest
 */

namespace WPMUDEV\PluginTest;

// Abort if called directly.
defined( 'WPINC' ) || die;

/**
 * Class Loader
 *
 * Handles the loading and initialization of plugin components.
 *
 * @since 1.0.0
 */
class Loader extends Singleton {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( ! $this->can_boot() ) {
			return;
		}

		$this->init();
	}

	/**
	 * Initialize the loader.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		$this->load_admin_pages();
		$this->load_endpoints();
	}

	/**
	 * Load admin pages.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_admin_pages() {
		App\Admin_Pages\Google_Drive::instance()->init();
	}

	/**
	 * Load endpoints.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_endpoints() {
		Endpoints\V1\Drive_API::instance()->init();
	}

	/**
	 * Main condition that checks if plugin parts should continue loading.
	 *
	 * @return bool
	 */
	private function can_boot() {
		/**
		 * Checks
		 *  - PHP version
		 *  - WP Version
		 * If not then return.
		 */
		global $wp_version;

		return (
			version_compare( PHP_VERSION, $this->php_version, '>' ) &&
			version_compare( $wp_version, $this->wp_version, '>' )
		);
	}
}
	/**
	 * Register all the actions and filters.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	private function init() {
		App\Admin_Pages\Google_Drive::instance()->init();
		Endpoints\V1\Drive_API::instance()->init();
	}
}

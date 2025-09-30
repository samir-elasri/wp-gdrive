<?php
/**
 * Google Drive test block.
 *
 * @link          https://wpmudev.com/
 * @since         1.0.0
 *
 * @author        WPMUDEV (https://wpmudev.com)
 * @package       WPMUDEV\PluginTest
 *
 * @copyright (c) 2025, Incsub (http://incsub.com)
 */

namespace WPMUDEV\PluginTest\App\Admin_Pages;

// Abort if called directly.
defined( 'WPINC' ) || die;

use WPMUDEV\PluginTest\Base;

class Google_Drive extends Base {
	/**
	 * The page title.
	 *
	 * @var string
	 */
	private $page_title;

	/**
	 * The page slug.
	 *
	 * @var string
	 */
	private $page_slug = 'wpmudev-googledrive-test';

	/**
	 * Google Drive auth credentials.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $creds = array();

	/**
	 * Option name for credentials (reusing the same as original auth).
	 *
	 * @var string
	 */
	private $option_name = 'wpmudev_drive_credentials';

	/**
	 * Page Assets.
	 *
	 * @var array
	 */

	private $handle = 'wpmudev-plugintest-googledrive';
	/** @var string */

	private $page_scripts = array();

	/**
	 * Assets version.
	 *
	 * @var string
	 */
	private $assets_version = '';

	/**
	 * A unique string id to be used in markup and jsx.
	 *
	 * @var string
	 */
	private $unique_id = '';

	/**
	 * Initializes the page.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function init() {
		$this->page_title     = __( 'Google Drive Test', 'wpmudev-plugin-test' );
		$this->assets_version = defined( 'WPMUDEV_PLUGINTEST_VERSION' ) ? WPMUDEV_PLUGINTEST_VERSION : '1.0.0';
		$this->unique_id      = "wpmudev_plugintest_drive_main_wrap-{$this->assets_version}";
		$this->creds          = (array) get_option( $this->option_name, array() );

		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'admin_body_class', array( $this, 'admin_body_classes' ) );
	}

	public function register_admin_page() {
		add_menu_page(
			__( 'Google Drive Test', 'wpmudev-plugin-test' ),
			__( 'Google Drive Test', 'wpmudev-plugin-test' ),
			'manage_options',
			$this->page_slug,
			array( $this, 'callback' ),
			'dashicons-google'
		);
	}

	/**
	 * The admin page callback method.
	 *
	 * @return void
	 */
	public function callback() {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Google Drive Test', 'wpmudev-plugin-test' ) . '</h1>';
		echo '<div id="' . esc_attr( $this->unique_id ) . '"></div>';
		echo '</div>';
	}

	/**
	 * Prepares assets.
	 *
	 * @return void
	 */
	public function prepare_assets() {
		if ( ! is_array( $this->page_scripts ) ) {
			$this->page_scripts = array();
		}

		$handle       = 'wpmudev_plugintest_drivepage';
		$src          = WPMUDEV_PLUGINTEST_ASSETS_URL . '/js/drivetestpage.min.js';
		$style_src    = WPMUDEV_PLUGINTEST_ASSETS_URL . '/css/drivetestpage.min.css';
		$dependencies = ! empty( $this->script_data( 'dependencies' ) )
			? $this->script_data( 'dependencies' )
			: array(
				'react',
				'wp-element',
				'wp-i18n',
				'wp-is-shallow-equal',
				'wp-polyfill',
			);

		$this->page_scripts[ $handle ] = array(
			'src'       => $src,
			'style_src' => $style_src,
			'deps'      => $dependencies,
			'ver'       => $this->assets_version,
			'strategy'  => true,
			'localize'  => array(
				'dom_element_id'       => $this->unique_id,
				'restEndpointSave'     => 'wpmudev/v1/drive/save-credentials',
				'restEndpointAuth'     => 'wpmudev/v1/drive/auth',
				'restEndpointFiles'    => 'wpmudev/v1/drive/files',
				'restEndpointUpload'   => 'wpmudev/v1/drive/upload',
				'restEndpointDownload' => 'wpmudev/v1/drive/download',
				'restEndpointCreate'   => 'wpmudev/v1/drive/create-folder',
				'nonce'                => wp_create_nonce( 'wp_rest' ),
				'authStatus'           => $this->get_auth_status(),
				'redirectUri'          => home_url( '/wp-json/wpmudev/v1/drive/callback' ),
				'hasCredentials'       => ! empty( $this->creds['client_id'] ) && ! empty( $this->creds['client_secret'] ),
			),
		);
	}

	/**
	 * Checks if user is authenticated with Google Drive.
	 *
	 * @return bool
	 */
	private function get_auth_status() {
		$access_token = get_option( 'wpmudev_drive_access_token', '' );
		$expires_at   = (int) get_option( 'wpmudev_drive_token_expires', 0 );
		if ( empty( $access_token ) ) return false;
		if ( $expires_at > 0 && time() >= $expires_at ) return false;
		return true;
	}

	/**
	 * Gets assets data for given key.
	 *
	 * @param string $key
	 *
	 * @return string|array
	 */
	protected function script_data( string $key = '' ) {
		$raw_script_data = $this->raw_script_data();

		return ! empty( $key ) && ! empty( $raw_script_data[ $key ] ) ? $raw_script_data[ $key ] : '';
	}

	/**
	 * Gets the script data from assets php file.
	 *
	 * @return array
	 */
	protected function raw_script_data(): array {
		static $script_data = null;

		if ( is_null( $script_data ) && file_exists( WPMUDEV_PLUGINTEST_DIR . 'assets/js/drivetestpage.min.asset.php' ) ) {
			$script_data = include WPMUDEV_PLUGINTEST_DIR . 'assets/js/drivetestpage.min.asset.php';
		}

		return (array) $script_data;
	}

	/**
	 * Prepares assets.
	 *
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		$screen = get_current_screen();
		if ( empty( $screen->id ) || false === strpos( $screen->id, $this->page_slug ) ) {
			return;
		}

		$js   = WPMUDEV_PLUGINTEST_URL . 'assets/js/drivetestpage.min.js';
		$css  = WPMUDEV_PLUGINTEST_URL . 'assets/css/drivetestpage.min.css';
		$deps = array( 'react', 'react-dom', 'wp-element', 'wp-i18n', 'wp-is-shallow-equal' );

		wp_enqueue_style( $this->handle, $css, array(), $this->assets_version );
		wp_enqueue_style( 'wpmudev-sui', 'https://wpmudev.com/some/sui.css' );
		wp_enqueue_script( $this->handle, $js, $deps, $this->assets_version, true );

		wp_localize_script(
			$this->handle,
			'WPMUDEV_PLUGINTEST',
			array(
				'dom_element_id'       => $this->unique_id,
				'restEndpointSave'     => rest_url( 'wpmudev/v1/drive/save-credentials' ),
				'restEndpointAuth'     => rest_url( 'wpmudev/v1/drive/auth' ),
				'restEndpointFiles'    => rest_url( 'wpmudev/v1/drive/files' ),
				'restEndpointUpload'   => rest_url( 'wpmudev/v1/drive/upload' ),
				'restEndpointDownload' => rest_url( 'wpmudev/v1/drive/download' ),
				'restEndpointCreate'   => rest_url( 'wpmudev/v1/drive/create-folder' ),
				'nonce'                => wp_create_nonce( 'wp_rest' ),
				'authStatus'           => $this->get_auth_status(),
				'redirectUri'          => home_url( '/wp-json/wpmudev/v1/drive/callback' ),
				'hasCredentials'       => ! empty( $this->creds['client_id'] ) && ! empty( $this->creds['client_secret'] ),
			)
		);

	}

	/**
	 * Prints the wrapper element which React will use as root.
	 *
	 * @return void
	 */
	protected function view() {
		echo '<div id="' . esc_attr( $this->unique_id ) . '" class="sui-wrap"></div>';
	}

	/**
	 * Adds the SUI class on markup body.
	 *
	 * @param string $classes
	 *
	 * @return string
	 */
	public function admin_body_classes( $classes ) {
		$screen = get_current_screen();
		if ( empty( $screen->id ) || false === strpos( $screen->id, $this->page_slug ) ) {
			return $classes;
		}
		if ( defined( 'WPMUDEV_PLUGINTEST_SUI_VERSION' ) ) {
			$classes .= ' sui-' . str_replace( '.', '-', WPMUDEV_PLUGINTEST_SUI_VERSION ) . ' ';
		}
		return $classes;
	}
}
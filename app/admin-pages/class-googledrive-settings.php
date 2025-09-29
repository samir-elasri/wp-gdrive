<?php
/**
 * Google Drive test admin page.
 *
 * @link          https://wpmudev.com/
 * @since         1.0.0
 * @package       WPMUDEV\PluginTest
 */

namespace WPMUDEV\PluginTest\App\Admin_Pages;

use function add_action;
use function add_filter;
use function add_menu_page;
use function esc_attr;
use function esc_html__;
use function get_current_screen;
use function get_option;
use function home_url;
use function wp_create_nonce;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_localize_script;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GoogleDrive_Settings {

	/**
	 * Page slug.
	 * @var string
	 */
	private $page_slug = 'wpmudev-googledrive-test';

	/**
	 * Page title.
	 * @var string
	 */
	private $page_title = '';

	/**
	 * Option name for credentials.
	 * @var string
	 */
	private $option_name = 'wpmudev_drive_credentials';

	/**
	 * Script handle.
	 * @var string
	 */
	private $handle = 'wpmudev-plugintest-googledrive';

	/**
	 * Assets version.
	 * @var string
	 */
	private $assets_version = '';

	/**
	 * Unique root element ID.
	 * @var string
	 */
	private $unique_id = '';

	/**
	 * Cached credentials.
	 * @var array
	 */
	private $creds = array();

	public function init() {
		$this->page_title     = __( 'Google Drive Test', 'wpmudev-plugin-test' );
		$this->creds          = (array) get_option( $this->option_name, array() );
		$this->assets_version = defined( 'WPMUDEV_PLUGINTEST_VERSION' ) ? WPMUDEV_PLUGINTEST_VERSION : '1.0.0';
		$this->unique_id      = "wpmudev_plugintest_drive_main_wrap-{$this->assets_version}";

		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'admin_body_class', array( $this, 'admin_body_classes' ) );
	}

	public function register_admin_page() {
		add_menu_page(
			$this->page_title,
			$this->page_title,
			'manage_options',
			$this->page_slug,
			array( $this, 'callback' ),
			'dashicons-google'
		);
	}

	public function callback() {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Google Drive Test', 'wpmudev-plugin-test' ) . '</h1>';
		echo '<div id="' . esc_attr( $this->unique_id ) . '"></div>';
		echo '</div>';
	}

	private function script_data( $key = '' ) {
		// You can optionally read WP build manifest here.
		// For simplicity we return version only.
		$data = array(
			'version' => $this->assets_version,
		);
		return $key ? ( $data[ $key ] ?? null ) : $data;
	}

	public function enqueue_assets( $hook ) {
		$screen = get_current_screen();
		if ( empty( $screen->id ) || false === strpos( $screen->id, $this->page_slug ) ) {
			return;
		}

		// Built files.
		$js     = WPMUDEV_PLUGINTEST_URL . 'assets/js/drivetestpage.min.js';
		$css    = WPMUDEV_PLUGINTEST_URL . 'assets/css/drivetestpage.min.css';
		$deps   = array( 'react', 'react-dom', 'wp-element', 'wp-i18n', 'wp-is-shallow-equal' );

		wp_enqueue_style( $this->handle, $css, array(), $this->assets_version );
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

	private function get_auth_status() {
		$access_token = get_option( 'wpmudev_drive_access_token', '' );
		$expires_at   = (int) get_option( 'wpmudev_drive_token_expires', 0 );
		if ( empty( $access_token ) ) {
			return false;
		}
		if ( $expires_at > 0 && time() >= $expires_at ) {
			return false;
		}
		return true;
	}

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

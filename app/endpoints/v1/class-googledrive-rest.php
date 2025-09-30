<?php
/**
 * Google Drive API endpoints using Google Client Library.
 *
 * @link          https://wpmudev.com/
 * @since         1.0.0
 *
 * @author        WPMUDEV (https://wpmudev.com)
 * @package       WPMUDEV\PluginTest
 *
 * @copyright (c) 2025, Incsub (http://incsub.com)
 */

namespace WPMUDEV\PluginTest\Endpoints\V1;

// Abort if called directly.
defined( 'WPINC' ) || die;

use WPMUDEV\PluginTest\Base;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;

class Drive_API extends Base {

	/**
	 * Google Client instance.
	 *
	 * @var Google_Client
	 */
	private $client;

	/**
	 * Google Drive service.
	 *
	 * @var Google_Service_Drive
	 */
	private $drive_service;

	/**
	 * OAuth redirect URI.
	 *
	 * @var string
	 */
	private $redirect_uri;

	/**
	 * Google Drive API scopes.
	 *
	 * @var array
	 */
	private $scopes = array(
		Google_Service_Drive::DRIVE_FILE,
		Google_Service_Drive::DRIVE_READONLY,
	);

	/** @var string */
	private $creds_option = 'wpmudev_drive_credentials';

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		$this->redirect_uri = home_url( '/wp-json/wpmudev/v1/drive/callback' );
		$this->setup_google_client();
	}

	/**
	 * Setup Google Client.
	 */
	private function setup_google_client() : void {
		$this->client = new \Google_Client();
		$this->client->setApplicationName( 'WPMU DEV Plugin Test' );

		$creds = (array) get_option( $this->creds_option, array() );
		if ( ! empty( $creds['client_id'] ) && ! empty( $creds['client_secret'] ) ) {
			$this->client->setClientId( $creds['client_id'] );
			$this->client->setClientSecret( $creds['client_secret'] );
		}

		$this->client->setRedirectUri( home_url( '/wp-json/wpmudev/v1/drive/callback' ) );
		$this->client->setAccessType( 'offline' );
		$this->client->setPrompt( 'consent' );
		$this->client->setScopes( array(
			'https://www.googleapis.com/auth/drive.file',
			'https://www.googleapis.com/auth/drive.metadata.readonly',
		) );

		$token = get_option( 'wpmudev_drive_access_token', '' );
		if ( ! empty( $token ) && is_array( $token ) ) {
			$this->client->setAccessToken( $token );
		}
		$this->drive_service = new \Google_Service_Drive( $this->client );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		// [ADD guard closure inside register_routes()]
		$guard = function () {
			if ( ! current_user_can( 'manage_options' ) ) {
				return new \WP_Error( 'forbidden', __( 'Insufficient permissions.', 'wpmudev-plugin-test' ), array( 'status' => 403 ) );
			}
			$nonce = $_SERVER['HTTP_X_WP_NONCE'] ?? '';
			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				return new \WP_Error( 'rest_cookie_invalid_nonce', __( 'Invalid REST nonce.', 'wpmudev-plugin-test' ), array( 'status' => 401 ) );
			}
			return true;
		};

		// [ADD route registrations]
		register_rest_route( 'wpmudev/v1/drive', '/save-credentials', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'save_credentials' ),
			'permission_callback' => $guard,
		) );

		register_rest_route( 'wpmudev/v1/drive', '/auth', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'start_auth' ),
			'permission_callback' => $guard,
		) );

		register_rest_route( 'wpmudev/v1/drive', '/callback', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'handle_callback' ),
			'permission_callback' => '__return_true', // must be public for Google redirect
		) );

		register_rest_route( 'wpmudev/v1/drive', '/files', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'list_files' ),
			'permission_callback' => $guard,
		) );

		register_rest_route( 'wpmudev/v1/drive', '/upload', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'upload_file' ),
			'permission_callback' => $guard,
		) );

		register_rest_route( 'wpmudev/v1/drive', '/download', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'download_file' ),
			'permission_callback' => $guard,
		) );

		register_rest_route( 'wpmudev/v1/drive', '/create-folder', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'create_folder' ),
			'permission_callback' => $guard,
		) );
	}

	/**
	 * Save Google OAuth credentials.
	 */
	public function save_credentials( \WP_REST_Request $request ) {
		$client_id     = sanitize_text_field( (string) ( $request->get_param( 'client_id' ) ?? '' ) );
		$client_secret = sanitize_text_field( (string) ( $request->get_param( 'client_secret' ) ?? '' ) );

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return new \WP_Error( 'invalid_credentials', __( 'Client ID and Client Secret are required.', 'wpmudev-plugin-test' ), array( 'status' => 400 ) );
		}

		update_option( $this->creds_option, array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
		) );

		// Invalidate tokens on change.
		delete_option( 'wpmudev_drive_access_token' );
		delete_option( 'wpmudev_drive_refresh_token' );
		delete_option( 'wpmudev_drive_token_expires' );

		$this->setup_google_client();

		return new \WP_REST_Response( array( 'success' => true ) );
	}

	/**
	 * Start Google OAuth flow.
	 */
	public function start_auth() {
		$auth_url = $this->client->createAuthUrl();
		return new \WP_REST_Response( array( 'authUrl' => $auth_url ) );
	}

	/**
	 * Handle OAuth callback.
	 */
	public function handle_callback( \WP_REST_Request $request ) {
		$code  = sanitize_text_field( (string) ( $request->get_param( 'code' ) ?? '' ) );
		if ( empty( $code ) ) {
			return new \WP_REST_Response( __( 'Authorization code not received.', 'wpmudev-plugin-test' ) );
		}

		try {
			$token = $this->client->fetchAccessTokenWithAuthCode( $code );
			if ( isset( $token['error'] ) ) {
				wp_die( esc_html( $token['error_description'] ?? $token['error'] ) );
			}

			update_option( 'wpmudev_drive_access_token', $token );
			if ( ! empty( $token['refresh_token'] ) ) {
				update_option( 'wpmudev_drive_refresh_token', $token['refresh_token'] );
			}
			$expires_at = ! empty( $token['expires_in'] ) ? time() + (int) $token['expires_in'] : ( time() + 3600 );
			update_option( 'wpmudev_drive_token_expires', $expires_at );

			echo '<script>window.close && window.close();</script>';
			echo esc_html__( 'Authentication complete. You can close this window.', 'wpmudev-plugin-test' );
			exit;
		} catch ( \Exception $e ) {
			wp_die( 'Failed to get access token: ' . esc_html( $e->getMessage() ) );
		}
	}

	/**
	 * Ensure we have a valid access token.
	 */
	private function ensure_valid_token() : bool {
		if ( ! $this->client ) return false;

		$access_token = get_option( 'wpmudev_drive_access_token', array() );
		if ( empty( $access_token ) || ! is_array( $access_token ) ) return false;

		$this->client->setAccessToken( $access_token );

		if ( $this->client->isAccessTokenExpired() ) {
			$refresh_token = get_option( 'wpmudev_drive_refresh_token', '' );
			if ( empty( $refresh_token ) ) return false;

			try {
				$new_token = $this->client->fetchAccessTokenWithRefreshToken( $refresh_token );
				if ( isset( $new_token['error'] ) ) return false;

				$merged = $this->client->getAccessToken();
				update_option( 'wpmudev_drive_access_token', $merged );
				$expires_at = ! empty( $merged['expires_in'] ) ? time() + (int) $merged['expires_in'] : ( time() + 3600 );
				update_option( 'wpmudev_drive_token_expires', $expires_at );
			} catch ( \Exception $e ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * List files in Google Drive.
	 */
	public function list_files( \WP_REST_Request $request ) {
		if ( ! $this->ensure_valid_token() ) {
		return new \WP_Error( 'no_access_token', __( 'Not authenticated with Google Drive.', 'wpmudev-plugin-test' ), array( 'status' => 401 ) );
		}

		try {
			$page_size = (int) ( $request->get_param( 'page_size' ) ?? 20 );
			if ( $page_size < 1 || $page_size > 100 ) $page_size = 20;
			$query = (string) ( $request->get_param( 'q' ) ?? 'trashed=false' );

			$results = $this->drive_service->files->listFiles( array(
				'pageSize' => $page_size,
				'q'        => $query,
				'fields'   => 'files(id,name,mimeType,size,modifiedTime,webViewLink)',
			) );

			$files = $results->getFiles() ?: array();
			$out = array();
			foreach ( $files as $f ) {
				$out[] = array(
					'id'           => $f->getId(),
					'name'         => $f->getName(),
					'mimeType'     => $f->getMimeType(),
					'size'         => $f->getSize(),
					'modifiedTime' => $f->getModifiedTime(),
					'webViewLink'  => $f->getWebViewLink(),
				);
			}

			return new \WP_REST_Response( array( 'success' => true, 'files' => $out ) );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'list_failed', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Upload file to Google Drive.
	 */
	public function upload_file( \WP_REST_Request $request ) {
		if ( ! $this->ensure_valid_token() ) {
			return new \WP_Error( 'no_access_token', __( 'Not authenticated with Google Drive.', 'wpmudev-plugin-test' ), array( 'status' => 401 ) );
		}
		if ( empty( $_FILES['file'] ) || ! is_uploaded_file( $_FILES['file']['tmp_name'] ) ) {
			return new \WP_Error( 'no_file', __( 'No file uploaded.', 'wpmudev-plugin-test' ), array( 'status' => 400 ) );
		}

		$tmp  = $_FILES['file']['tmp_name'];
		$name = sanitize_file_name( wp_unslash( $_FILES['file']['name'] ) );
		$type = sanitize_mime_type( (string) ( $_FILES['file']['type'] ?? 'application/octet-stream' ) );

		try {
			$file_meta = new \Google_Service_Drive_DriveFile( array( 'name' => $name ) );
			$res = $this->drive_service->files->create(
				$file_meta,
				array(
					'data'       => file_get_contents( $tmp ),
					'mimeType'   => $type,
					'uploadType' => 'multipart',
					'fields'     => 'id,name,mimeType,webViewLink',
				)
			);

			return new \WP_REST_Response( array(
				'success' => true,
				'file'    => array(
					'id'          => $res->getId(),
					'name'        => $res->getName(),
					'mimeType'    => $res->getMimeType(),
					'webViewLink' => $res->getWebViewLink(),
				),
			) );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'upload_failed', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Download file from Google Drive.
	 */
	public function download_file( \WP_REST_Request $request ) {
		if ( ! $this->ensure_valid_token() ) {
			return new \WP_Error( 'no_access_token', __( 'Not authenticated with Google Drive.', 'wpmudev-plugin-test' ), array( 'status' => 401 ) );
		}
		$file_id = sanitize_text_field( (string) ( $request->get_param( 'file_id' ) ?? '' ) );
		if ( empty( $file_id ) ) {
			return new \WP_Error( 'missing_file_id', __( 'File ID is required.', 'wpmudev-plugin-test' ), array( 'status' => 400 ) );
		}

		try {
			$meta = $this->drive_service->files->get( $file_id, array( 'fields' => 'id,name,mimeType,size' ) );
			$response = $this->drive_service->files->get( $file_id, array( 'alt' => 'media' ) );
			$content  = $response->getBody()->getContents();

			return new \WP_REST_Response( array(
				'success'  => true,
				'content'  => base64_encode( $content ),
				'filename' => $meta->getName(),
				'mimeType' => $meta->getMimeType(),
			) );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'download_failed', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Create folder in Google Drive.
	 */
	public function create_folder( \WP_REST_Request $request ) {
		if ( ! $this->ensure_valid_token() ) {
			return new \WP_Error( 'no_access_token', __( 'Not authenticated with Google Drive.', 'wpmudev-plugin-test' ), array( 'status' => 401 ) );
		}
		$name = sanitize_text_field( (string) ( $request->get_param( 'name' ) ?? '' ) );
		if ( '' === $name ) {
			return new \WP_Error( 'missing_name', __( 'Folder name is required.', 'wpmudev-plugin-test' ), array( 'status' => 400 ) );
		}

		try {
			$meta = new \Google_Service_Drive_DriveFile( array(
				'name'     => $name,
				'mimeType' => 'application/vnd.google-apps.folder',
			) );
			$res = $this->drive_service->files->create( $meta, array( 'fields' => 'id,name,mimeType,webViewLink' ) );

			return new \WP_REST_Response( array(
				'success' => true,
				'folder'  => array(
					'id'          => $res->getId(),
					'name'        => $res->getName(),
					'mimeType'    => $res->getMimeType(),
					'webViewLink' => $res->getWebViewLink(),
				),
			) );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'create_failed', $e->getMessage(), array( 'status' => 500 ) );
		}
	}
}
<?php
/**
 * UI — serves the Frontman client at /wp-admin/frontman.
 *
 * @package Frontman
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontman_UI {
	private Frontman_Settings $settings;

	public function __construct( Frontman_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Register the admin page.
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
	}

	/**
	 * Add Frontman page under the admin menu.
	 */
	public function add_admin_page(): void {
		add_menu_page(
			__( 'Frontman', 'frontman' ),
			__( 'Frontman', 'frontman' ),
			'manage_options',
			'frontman',
			[ $this, 'render_page' ],
			'dashicons-edit-large',
			3,
		);
	}

	/**
	 * Render the Frontman client page.
	 */
	public function render_page(): void {
		$client_url     = 'https://app.frontman.sh/frontman.es.js';
		$client_css_url = 'https://app.frontman.sh/frontman.css';

		// The REST API base URL for our routes.
		$api_base = esc_url( rest_url( 'frontman/v1' ) );
		$nonce    = wp_create_nonce( 'wp_rest' );

		?>
		<!DOCTYPE html>
		<html lang="en" class="dark">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php esc_html_e( 'Frontman', 'frontman' ); ?></title>
			<link rel="stylesheet" href="<?php echo esc_url( $client_css_url ); ?>">
			<style>
				html, body, #root {
					height: 100%;
					margin: 0;
					padding: 0;
				}
				/* Hide WP admin chrome when in Frontman */
				#wpcontent { padding-left: 0 !important; }
				#wpbody-content { padding-bottom: 0 !important; }
				#wpfooter { display: none !important; }
			</style>
		</head>
		<body>
			<div id="root"></div>
			<script>
				// Provide the relay URL pointing to our REST API.
				window.__FRONTMAN_CONFIG__ = {
					relayUrl: <?php echo wp_json_encode( $api_base ); ?>,
					nonce: <?php echo wp_json_encode( $nonce ); ?>,
					framework: 'wordpress',
				};
			</script>
			<script type="module" src="<?php echo esc_url( $client_url ); ?>"></script>
		</body>
		</html>
		<?php
	}
}

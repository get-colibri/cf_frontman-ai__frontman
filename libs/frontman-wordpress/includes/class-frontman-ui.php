<?php
/**
 * UI — serves the Frontman client at /frontman.
 *
 * This page is served directly by the router's parse_request interception,
 * not via wp-admin. The client JS fetches /frontman/tools and
 * /frontman/tools/call at the same origin — identical to Vite/Astro/Next.js.
 *
 * Auth is already verified by the router before render_page() is called.
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
	 * Register admin menu items.
	 *
	 * We keep a menu entry so admins can find Frontman in the sidebar,
	 * but it links to /frontman (the direct path) rather than a wp-admin page.
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_admin_menu_link' ] );
	}

	/**
	 * Add a menu link that points to /frontman (external to wp-admin).
	 */
	public function add_admin_menu_link(): void {
		// Register a top-level menu page so Settings can be a submenu.
		add_menu_page(
			__( 'Frontman', 'frontman' ),
			__( 'Frontman', 'frontman' ),
			'manage_options',
			'frontman',
			'__return_null', // Callback unused — we redirect below.
			'dashicons-edit-large',
			3,
		);

		// Redirect the wp-admin menu click to /frontman.
		add_action( 'load-toplevel_page_frontman', function (): void {
			wp_safe_redirect( home_url( '/frontman' ) );
			exit;
		} );
	}

	/**
	 * Render the full Frontman client page.
	 *
	 * Called directly by the router — this outputs a complete HTML document
	 * (no wp-admin chrome). The client is loaded from the production CDN.
	 */
	public function render_page(): void {
		$client_url     = 'https://app.frontman.sh/frontman.es.js';
		$client_css_url = 'https://app.frontman.sh/frontman.css';

		// Inline runtime config — same shape as FrontmanCore__UIShell produces
		// for Vite/Astro/Next.js. The client reads window.__frontmanRuntime.
		$runtime_config = wp_json_encode( [
			'framework' => 'wordpress',
		] );

		status_header( 200 );
		header( 'Content-Type: text/html; charset=utf-8' );
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
	</style>
</head>
<body>
	<div id="root"></div>
	<script>window.__frontmanRuntime=<?php echo $runtime_config; ?></script>
	<script type="module" src="<?php echo esc_url( $client_url ); ?>"></script>
</body>
</html>
		<?php
	}
}

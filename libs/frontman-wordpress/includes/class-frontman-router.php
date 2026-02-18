<?php
/**
 * Router — intercepts /frontman/* requests at the WordPress level.
 *
 * Uses parse_request to catch requests before WordPress tries to resolve
 * them as posts/pages. This means the client can call the same paths as
 * all other Frontman adapters (Vite, Astro, Next.js):
 *
 *   GET  /frontman          → Serve the UI
 *   GET  /frontman/tools    → Merged tool list (standalone + WP)
 *   POST /frontman/tools/call → Dispatch tool call (SSE)
 *   POST /frontman/resolve-source-location → Proxy to standalone
 *
 * Every route is guarded by Frontman_Auth::check() — only logged-in
 * administrators can access any Frontman endpoint.
 *
 * @package Frontman
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontman_Router {
	private Frontman_Tools    $tools;
	private Frontman_Proxy    $proxy;
	private Frontman_Settings $settings;
	private Frontman_UI       $ui;

	public function __construct(
		Frontman_Tools $tools,
		Frontman_Proxy $proxy,
		Frontman_Settings $settings,
		Frontman_UI $ui,
	) {
		$this->tools    = $tools;
		$this->proxy    = $proxy;
		$this->settings = $settings;
		$this->ui       = $ui;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'parse_request', [ $this, 'intercept' ], 1 );
	}

	/**
	 * Intercept /frontman/* requests before WordPress resolves them.
	 */
	public function intercept( \WP $wp ): void {
		// Get the raw request path, stripping the site subdirectory if present.
		$request_uri = $this->get_request_path();

		// Match /frontman or /frontman/... paths.
		if ( ! preg_match( '#^/frontman(?:/(.*))?$#', $request_uri, $matches ) ) {
			return;
		}

		$sub_path = $matches[1] ?? '';
		$method   = strtoupper( $_SERVER['REQUEST_METHOD'] ?? 'GET' );

		// Auth check — every Frontman route requires an admin session.
		$auth = Frontman_Auth::check();
		if ( is_wp_error( $auth ) ) {
			$is_api = ( $sub_path !== '' );
			Frontman_Auth::send_error( $auth, $is_api );
		}

		// Route the request.
		switch ( true ) {
			// GET /frontman — serve the UI.
			case $method === 'GET' && $sub_path === '':
				$this->ui->render_page();
				exit;

			// GET /frontman/tools — merged tool list.
			case $method === 'GET' && $sub_path === 'tools':
				$this->handle_get_tools();
				exit;

			// POST /frontman/tools/call — dispatch tool call (SSE).
			case $method === 'POST' && $sub_path === 'tools/call':
				$this->handle_tool_call();
				exit;

			// POST /frontman/resolve-source-location — proxy to standalone.
			case $method === 'POST' && $sub_path === 'resolve-source-location':
				$this->handle_resolve_source_location();
				exit;

			// OPTIONS — handle CORS preflight (same-origin, but be explicit).
			case $method === 'OPTIONS':
				status_header( 204 );
				exit;

			default:
				status_header( 404 );
				header( 'Content-Type: application/json; charset=utf-8' );
				echo wp_json_encode( [ 'error' => 'Not found' ] );
				exit;
		}
	}

	/**
	 * Get the request path relative to the site root.
	 *
	 * Handles WordPress installed in a subdirectory (e.g. /blog/frontman).
	 */
	private function get_request_path(): string {
		$request_uri = $_SERVER['REQUEST_URI'] ?? '/';

		// Strip query string.
		$path = strtok( $request_uri, '?' );

		// Strip the site's home path prefix if WP is in a subdirectory.
		$home_path = parse_url( home_url(), PHP_URL_PATH ) ?: '';
		if ( $home_path !== '' && $home_path !== '/' ) {
			$home_path = rtrim( $home_path, '/' );
			if ( str_starts_with( $path, $home_path ) ) {
				$path = substr( $path, strlen( $home_path ) );
			}
		}

		// Ensure leading slash.
		if ( $path === '' || $path[0] !== '/' ) {
			$path = '/' . $path;
		}

		return $path;
	}

	/**
	 * Read JSON body from the request.
	 */
	private function read_json_body(): array {
		$raw  = file_get_contents( 'php://input' );
		$data = json_decode( $raw, true );
		return is_array( $data ) ? $data : [];
	}

	/**
	 * GET /frontman/tools — merge standalone + WP tool definitions.
	 */
	private function handle_get_tools(): void {
		$wp_tools = $this->tools->all_definitions();

		// Fetch standalone tools.
		$standalone       = $this->proxy->fetch_tools();
		$standalone_tools = $standalone['tools'] ?? [];

		// Merge: standalone tools first, then WP tools.
		$all_tools = array_merge( $standalone_tools, $wp_tools );

		status_header( 200 );
		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode( [
			'tools'           => $all_tools,
			'serverInfo'      => [
				'name'    => 'frontman-wordpress',
				'version' => FRONTMAN_VERSION,
			],
			'protocolVersion' => '1.0',
		] );
	}

	/**
	 * POST /frontman/tools/call — route by tool name.
	 *
	 * wp_* tools are handled locally; file tools are proxied to standalone.
	 */
	private function handle_tool_call(): void {
		$body  = $this->read_json_body();
		$name  = $body['name'] ?? '';
		$input = $body['arguments'] ?? $body['input'] ?? [];

		if ( empty( $name ) ) {
			header( 'Content-Type: text/event-stream' );
			header( 'Cache-Control: no-cache' );
			echo "event: error\ndata: " . wp_json_encode( [
				'content' => [ [ 'type' => 'text', 'text' => 'Missing tool name' ] ],
				'isError' => true,
			] ) . "\n\n";
			return;
		}

		// WP tools — handle locally.
		if ( $this->tools->is_wp_tool( $name ) ) {
			header( 'Content-Type: text/event-stream' );
			header( 'Cache-Control: no-cache' );
			header( 'X-Accel-Buffering: no' );

			try {
				$result = $this->tools->call( $name, $input );
				echo "event: result\ndata: " . wp_json_encode( $result ) . "\n\n";
			} catch ( \Throwable $e ) {
				echo "event: error\ndata: " . wp_json_encode( [
					'content' => [ [ 'type' => 'text', 'text' => $e->getMessage() ] ],
					'isError' => true,
				] ) . "\n\n";
			}
			return;
		}

		// File tools — proxy to standalone.
		$this->proxy->stream_tool_call( $name, $input );
	}

	/**
	 * POST /frontman/resolve-source-location — proxy to standalone.
	 */
	private function handle_resolve_source_location(): void {
		$body   = $this->read_json_body();
		$result = $this->proxy->forward( 'POST', 'resolve-source-location', $body );

		status_header( $result['code'] );
		header( 'Content-Type: application/json; charset=utf-8' );
		echo $result['body'];
	}
}

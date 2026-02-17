<?php
/**
 * REST API router — the single relay endpoint for all Frontman traffic.
 *
 * Browser talks to WordPress, never to standalone directly.
 * Routes tool calls by name: wp_* tools handled locally in PHP,
 * file tools proxied to standalone via SSE streaming.
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

	public function __construct(
		Frontman_Tools $tools,
		Frontman_Proxy $proxy,
		Frontman_Settings $settings,
	) {
		$this->tools    = $tools;
		$this->proxy    = $proxy;
		$this->settings = $settings;
	}

	/**
	 * Register REST API routes.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register all REST routes under /frontman/v1/.
	 */
	public function register_routes(): void {
		$namespace = 'frontman/v1';

		// GET /frontman/v1/tools — merged tool list.
		register_rest_route( $namespace, '/tools', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'handle_get_tools' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		// POST /frontman/v1/tools/call — dispatch tool call.
		register_rest_route( $namespace, '/tools/call', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_tool_call' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		// POST /frontman/v1/resolve-source-location — proxy to standalone.
		register_rest_route( $namespace, '/resolve-source-location', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_resolve_source_location' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );
	}

	/**
	 * Permission check — only admins can use Frontman.
	 */
	public function check_permissions(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET /tools — merge standalone + WP tool definitions.
	 */
	public function handle_get_tools( \WP_REST_Request $request ): \WP_REST_Response {
		$wp_tools = $this->tools->all_definitions();

		// Fetch standalone tools.
		$standalone = $this->proxy->fetch_tools();
		$standalone_tools = $standalone['tools'] ?? [];
		$server_info      = $standalone['serverInfo'] ?? [
			'name'    => 'frontman-wordpress',
			'version' => FRONTMAN_VERSION,
		];

		// Merge: standalone tools first, then WP tools.
		$all_tools = array_merge( $standalone_tools, $wp_tools );

		return new \WP_REST_Response( [
			'tools'           => $all_tools,
			'serverInfo'      => [
				'name'    => 'frontman-wordpress',
				'version' => FRONTMAN_VERSION,
			],
			'protocolVersion' => '1.0',
		], 200 );
	}

	/**
	 * POST /tools/call — route by tool name.
	 *
	 * wp_* tools are handled locally; file tools are proxied to standalone.
	 */
	public function handle_tool_call( \WP_REST_Request $request ): void {
		$body = $request->get_json_params();
		$name  = $body['name'] ?? '';
		$input = $body['arguments'] ?? $body['input'] ?? [];

		if ( empty( $name ) ) {
			header( 'Content-Type: text/event-stream' );
			echo "event: error\ndata: " . wp_json_encode( [
				'content' => [ [ 'type' => 'text', 'text' => 'Missing tool name' ] ],
				'isError' => true,
			] ) . "\n\n";
			exit;
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
			exit;
		}

		// File tools — proxy to standalone.
		$this->proxy->stream_tool_call( $name, $input );
		exit;
	}

	/**
	 * POST /resolve-source-location — proxy to standalone.
	 */
	public function handle_resolve_source_location( \WP_REST_Request $request ): \WP_REST_Response {
		$body   = $request->get_json_params();
		$result = $this->proxy->forward( 'POST', 'resolve-source-location', $body );

		$data = json_decode( $result['body'], true );
		return new \WP_REST_Response( $data ?? [ 'error' => 'Proxy failed' ], $result['code'] );
	}
}

<?php
/**
 * Proxy — forwards file-tool calls to the standalone Bun server via SSE streaming.
 *
 * Uses fpassthru() to relay SSE bytes directly from standalone → browser
 * without buffering the entire response in PHP memory.
 *
 * @package Frontman
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontman_Proxy {
	private Frontman_Settings $settings;

	public function __construct( Frontman_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Get the standalone server base URL.
	 */
	private function standalone_url(): string {
		$host = $this->settings->get( 'standalone_host', '127.0.0.1' );
		$port = (int) $this->settings->get( 'standalone_port', 4321 );
		return "http://{$host}:{$port}";
	}

	/**
	 * Fetch the tool list from the standalone server.
	 *
	 * @return array{tools: array, serverInfo: array}|null Null on failure.
	 */
	public function fetch_tools(): ?array {
		$url = $this->standalone_url() . '/frontman/tools';

		$response = wp_remote_get( $url, [
			'timeout' => 5,
			'headers' => [ 'Accept' => 'application/json' ],
		] );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || ! isset( $data['tools'] ) ) {
			return null;
		}

		return $data;
	}

	/**
	 * Proxy a tool call to the standalone server, streaming SSE back to the client.
	 *
	 * @param string $name  Tool name.
	 * @param array  $input Tool input arguments.
	 */
	public function stream_tool_call( string $name, array $input ): void {
		$url = $this->standalone_url() . '/frontman/tools/call';

		$payload = wp_json_encode( [
			'name'      => $name,
			'arguments' => $input,
		] );

		// Use cURL directly for streaming — wp_remote_post buffers everything.
		$ch = curl_init( $url );
		curl_setopt_array( $ch, [
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $payload,
			CURLOPT_HTTPHEADER     => [
				'Content-Type: application/json',
				'Accept: text/event-stream',
			],
			CURLOPT_RETURNTRANSFER => false,
			CURLOPT_TIMEOUT        => 120,
			CURLOPT_WRITEFUNCTION  => function ( $ch, $data ) {
				echo $data;
				if ( ob_get_level() ) {
					ob_flush();
				}
				flush();
				return strlen( $data );
			},
		] );

		// Set SSE headers before streaming.
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );
		header( 'X-Accel-Buffering: no' );

		// Disable output buffering.
		while ( ob_get_level() ) {
			ob_end_flush();
		}

		curl_exec( $ch );

		$error = curl_error( $ch );
		$code  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		if ( $error ) {
			echo "event: error\ndata: " . wp_json_encode( [
				'content' => [ [ 'type' => 'text', 'text' => "Proxy error: {$error}" ] ],
				'isError' => true,
			] ) . "\n\n";
		}
	}

	/**
	 * Proxy a non-streaming request to the standalone server.
	 *
	 * @param string $method HTTP method.
	 * @param string $path   Path relative to /frontman/.
	 * @param array  $body   Request body (for POST).
	 * @return array{code: int, body: string, headers: array}
	 */
	public function forward( string $method, string $path, array $body = [] ): array {
		$url = $this->standalone_url() . '/frontman/' . ltrim( $path, '/' );

		$args = [
			'method'  => strtoupper( $method ),
			'timeout' => 30,
			'headers' => [ 'Content-Type' => 'application/json' ],
		];

		if ( ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return [
				'code'    => 502,
				'body'    => wp_json_encode( [ 'error' => $response->get_error_message() ] ),
				'headers' => [],
			];
		}

		return [
			'code'    => wp_remote_retrieve_response_code( $response ),
			'body'    => wp_remote_retrieve_body( $response ),
			'headers' => wp_remote_retrieve_headers( $response )->getAll(),
		];
	}
}

<?php
/**
 * WordPress Options tools — read and modify site options.
 *
 * Tools: wp_get_option, wp_update_option, wp_list_options
 *
 * @package Frontman
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontman_Tool_Options {
	/**
	 * Options that are safe to read/modify.
	 * We deliberately exclude sensitive options like auth keys, salts, etc.
	 */
	private const ALLOWED_OPTIONS = [
		'blogname',
		'blogdescription',
		'siteurl',
		'home',
		'admin_email',
		'posts_per_page',
		'date_format',
		'time_format',
		'timezone_string',
		'gmt_offset',
		'permalink_structure',
		'default_category',
		'default_post_format',
		'show_on_front',
		'page_on_front',
		'page_for_posts',
		'blog_public',
		'default_comment_status',
		'thread_comments',
		'thread_comments_depth',
		'comments_per_page',
		'stylesheet',
		'template',
		'sidebars_widgets',
		'widget_text',
		'widget_categories',
		'widget_archives',
		'widget_meta',
		'widget_search',
		'widget_recent-posts',
		'widget_recent-comments',
	];

	/**
	 * Register all options tools.
	 */
	public function register( Frontman_Tools $tools ): void {
		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_get_option',
			description: 'Reads a WordPress option value by name. Only allows reading from a safe allowlist of options.',
			input_schema: [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'name' => [
						'type'        => 'string',
						'description' => 'The option name to read (e.g. "blogname", "permalink_structure", "posts_per_page").',
					],
				],
				'required' => [ 'name' ],
			],
			handler: [ $this, 'get_option' ],
		) );

		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_update_option',
			description: 'Updates a WordPress option value. Only allows modifying a safe allowlist of options.',
			input_schema: [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'name'  => [
						'type'        => 'string',
						'description' => 'The option name to update.',
					],
					'value' => [
						'type'        => 'string',
						'description' => 'The new value for the option. Pass numbers and booleans as strings (e.g. "10", "true").',
					],
				],
				'required' => [ 'name', 'value' ],
			],
			handler: [ $this, 'update_option' ],
		) );

		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_list_options',
			description: 'Lists all WordPress options that can be read or modified via wp_get_option/wp_update_option, with their current values.',
			input_schema: [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => new \stdClass(),
			],
			handler: [ $this, 'list_options' ],
		) );
	}

	/**
	 * Check if an option is in the allowlist.
	 */
	private function is_allowed( string $name ): bool {
		return in_array( $name, self::ALLOWED_OPTIONS, true );
	}

	/**
	 * wp_get_option handler.
	 */
	public function get_option( array $input ): array {
		$name = sanitize_key( $input['name'] ?? '' );

		if ( ! $this->is_allowed( $name ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Option not allowed: {$name}" ] ],
				'isError' => true,
			];
		}

		$value = get_option( $name );

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( [
				'name'  => $name,
				'value' => $value,
			] ) ] ],
		];
	}

	/**
	 * wp_update_option handler.
	 */
	public function update_option( array $input ): array {
		$name = sanitize_key( $input['name'] ?? '' );

		if ( ! $this->is_allowed( $name ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Option not allowed: {$name}" ] ],
				'isError' => true,
			];
		}

		$value = $input['value'];

		if ( is_string( $value ) ) {
			$value = sanitize_text_field( $value );
		}

		$updated = update_option( $name, $value );

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( [
				'updated' => $updated,
				'name'    => $name,
				'value'   => get_option( $name ),
			] ) ] ],
		];
	}

	/**
	 * wp_list_options handler.
	 */
	public function list_options( array $input ): array {
		$result = [];

		foreach ( self::ALLOWED_OPTIONS as $name ) {
			$value = get_option( $name );
			// Skip complex/serialized values for readability.
			if ( is_array( $value ) || is_object( $value ) ) {
				$value = '(complex value - use wp_get_option to read)';
			}
			$result[] = [
				'name'  => $name,
				'value' => $value,
			];
		}

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( $result ) ] ],
		];
	}
}

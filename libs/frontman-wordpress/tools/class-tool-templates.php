<?php
/**
 * WordPress Template tools — site info and template listing.
 *
 * Tools: wp_get_site_info, wp_list_templates
 *
 * @package Frontman
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontman_Tool_Templates {
	/**
	 * Register all template tools.
	 */
	public function register( Frontman_Tools $tools ): void {
		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_get_site_info',
			description: 'Returns comprehensive site information including WordPress version, active theme, active plugins, registered post types, and taxonomies.',
			input_schema: [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => new \stdClass(),
			],
			handler: [ $this, 'get_site_info' ],
		) );

		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_list_templates',
			description: 'Lists available block templates or template parts in the active theme.',
			input_schema: [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'type' => [
						'type'        => 'string',
						'description' => 'The template type to list.',
						'enum'        => [ 'wp_template', 'wp_template_part' ],
						'default'     => 'wp_template',
					],
				],
			],
			handler: [ $this, 'list_templates' ],
		) );
	}

	/**
	 * wp_get_site_info handler.
	 */
	public function get_site_info( array $input ): array {
		$theme   = wp_get_theme();
		$plugins = get_option( 'active_plugins', [] );

		// Get plugin names from slugs.
		$plugin_info = [];
		foreach ( $plugins as $plugin_file ) {
			$data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, false );
			$plugin_info[] = [
				'name'    => $data['Name'] ?? $plugin_file,
				'version' => $data['Version'] ?? 'unknown',
			];
		}

		// Get post types.
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		$pt_list    = [];
		foreach ( $post_types as $pt ) {
			$pt_list[] = [
				'name'  => $pt->name,
				'label' => $pt->label,
				'count' => (int) wp_count_posts( $pt->name )->publish,
			];
		}

		// Get taxonomies.
		$taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
		$tax_list   = [];
		foreach ( $taxonomies as $tax ) {
			$tax_list[] = [
				'name'  => $tax->name,
				'label' => $tax->label,
			];
		}

		$result = [
			'site_name'    => get_bloginfo( 'name' ),
			'site_url'     => get_site_url(),
			'home_url'     => get_home_url(),
			'wp_version'   => get_bloginfo( 'version' ),
			'php_version'  => phpversion(),
			'theme'        => [
				'name'       => $theme->get( 'Name' ),
				'version'    => $theme->get( 'Version' ),
				'is_block'   => $theme->is_block_theme(),
				'template'   => $theme->get_template(),
				'stylesheet' => $theme->get_stylesheet(),
			],
			'plugins'      => $plugin_info,
			'post_types'   => $pt_list,
			'taxonomies'   => $tax_list,
			'is_multisite' => is_multisite(),
			'language'     => get_bloginfo( 'language' ),
		];

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( $result ) ] ],
		];
	}

	/**
	 * wp_list_templates handler.
	 */
	public function list_templates( array $input ): array {
		$type = sanitize_key( $input['type'] ?? 'wp_template' );

		if ( ! in_array( $type, [ 'wp_template', 'wp_template_part' ], true ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Invalid template type: {$type}" ] ],
				'isError' => true,
			];
		}

		$templates = get_block_templates( [], $type );
		$result    = [];

		foreach ( $templates as $template ) {
			$result[] = [
				'id'          => $template->id,
				'slug'        => $template->slug,
				'title'       => $template->title ?? $template->slug,
				'description' => $template->description ?? '',
				'type'        => $template->type,
				'source'      => $template->source,
				'has_content' => ! empty( $template->content ),
			];
		}

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( [
				'type'      => $type,
				'count'     => count( $result ),
				'templates' => $result,
			] ) ] ],
		];
	}
}

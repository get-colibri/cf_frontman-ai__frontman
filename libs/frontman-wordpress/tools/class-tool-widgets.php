<?php
/**
 * WordPress Widget tools — list widget areas and update widgets.
 *
 * Tools: wp_list_widget_areas, wp_update_widget
 *
 * @package Frontman
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontman_Tool_Widgets {
	/**
	 * Register all widget tools.
	 */
	public function register( Frontman_Tools $tools ): void {
		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_list_widget_areas',
			description: 'Lists all registered widget areas (sidebars) and their active widget IDs.',
			input_schema: [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => new \stdClass(),
			],
			handler: [ $this, 'list_widget_areas' ],
		) );

		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_update_widget',
			description: 'Updates a widget\'s settings in a sidebar. Merges the provided settings with existing ones.',
			input_schema: [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'sidebar_id' => [
						'type'        => 'string',
						'description' => 'The sidebar/widget area ID (from wp_list_widget_areas).',
					],
					'widget_id'  => [
						'type'        => 'string',
						'description' => 'The widget instance ID (e.g. "text-2", "categories-3").',
					],
					'settings'   => [
						'type'        => 'object',
						'description' => 'Key-value pairs of widget settings to update.',
					],
				],
				'required' => [ 'sidebar_id', 'widget_id', 'settings' ],
			],
			handler: [ $this, 'update_widget' ],
		) );
	}

	/**
	 * wp_list_widget_areas handler.
	 */
	public function list_widget_areas( array $input ): array {
		global $wp_registered_sidebars;

		$sidebars_widgets = wp_get_sidebars_widgets();
		$result           = [];

		foreach ( $wp_registered_sidebars as $id => $sidebar ) {
			$widgets = $sidebars_widgets[ $id ] ?? [];

			$result[] = [
				'id'           => $id,
				'name'         => $sidebar['name'],
				'description'  => $sidebar['description'] ?? '',
				'widget_count' => count( $widgets ),
				'widgets'      => $widgets,
			];
		}

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( $result ) ] ],
		];
	}

	/**
	 * wp_update_widget handler.
	 */
	public function update_widget( array $input ): array {
		$sidebar_id = sanitize_key( $input['sidebar_id'] ?? '' );
		$widget_id  = sanitize_text_field( $input['widget_id'] ?? '' );
		$settings   = $input['settings'] ?? [];

		if ( empty( $sidebar_id ) || empty( $widget_id ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => 'Missing sidebar_id or widget_id' ] ],
				'isError' => true,
			];
		}

		// Parse widget base and number from ID (e.g. "text-2" → base="text", number=2).
		if ( ! preg_match( '/^(.+)-(\d+)$/', $widget_id, $matches ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Invalid widget ID format: {$widget_id}" ] ],
				'isError' => true,
			];
		}

		$widget_base   = $matches[1];
		$widget_number = (int) $matches[2];

		// Get current widget settings.
		$all_settings = get_option( "widget_{$widget_base}", [] );

		if ( ! isset( $all_settings[ $widget_number ] ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Widget instance not found: {$widget_id}" ] ],
				'isError' => true,
			];
		}

		// Merge new settings.
		$all_settings[ $widget_number ] = array_merge(
			$all_settings[ $widget_number ],
			array_map( 'sanitize_text_field', $settings ),
		);

		update_option( "widget_{$widget_base}", $all_settings );

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( [
				'updated'   => true,
				'widget_id' => $widget_id,
				'settings'  => $all_settings[ $widget_number ],
			] ) ] ],
		];
	}
}

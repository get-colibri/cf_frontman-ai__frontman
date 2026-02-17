<?php
/**
 * WordPress Menu tools — list and modify navigation menus.
 *
 * Tools: wp_list_menus, wp_read_menu, wp_update_menu_item
 *
 * @package Frontman
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontman_Tool_Menus {
	/**
	 * Register all menu tools.
	 */
	public function register( Frontman_Tools $tools ): void {
		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_list_menus',
			description: "Lists all registered navigation menus and their items.\n\nNo parameters required.",
			input_schema: [
				'type'       => 'object',
				'properties' => new \stdClass(),
			],
			handler: [ $this, 'list_menus' ],
		) );

		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_read_menu',
			description: "Reads a single navigation menu with all its items.\n\nParameters:\n- id (required): The menu term ID.",
			input_schema: [
				'type'       => 'object',
				'properties' => [
					'id' => [ 'type' => 'integer' ],
				],
				'required' => [ 'id' ],
			],
			handler: [ $this, 'read_menu' ],
		) );

		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_update_menu_item',
			description: "Updates a menu item's properties.\n\nParameters:\n- menu_item_id (required): The menu item (post) ID.\n- title (optional): New menu item title.\n- url (optional): New URL for custom link items.\n- position (optional): New menu order position.",
			input_schema: [
				'type'       => 'object',
				'properties' => [
					'menu_item_id' => [ 'type' => 'integer' ],
					'title'        => [ 'type' => 'string' ],
					'url'          => [ 'type' => 'string' ],
					'position'     => [ 'type' => 'integer' ],
				],
				'required' => [ 'menu_item_id' ],
			],
			handler: [ $this, 'update_menu_item' ],
		) );
	}

	/**
	 * Serialize a menu item for output.
	 */
	private function serialize_menu_item( \WP_Post $item ): array {
		return [
			'id'        => $item->ID,
			'title'     => $item->title,
			'url'       => $item->url,
			'type'      => $item->type,
			'object'    => $item->object,
			'object_id' => (int) $item->object_id,
			'parent'    => (int) $item->menu_item_parent,
			'position'  => (int) $item->menu_order,
		];
	}

	/**
	 * wp_list_menus handler.
	 */
	public function list_menus( array $input ): array {
		$menus  = wp_get_nav_menus();
		$result = [];

		foreach ( $menus as $menu ) {
			$items      = wp_get_nav_menu_items( $menu->term_id ) ?: [];
			$locations  = get_nav_menu_locations();
			$assigned   = array_keys( array_filter( $locations, fn( $id ) => $id === $menu->term_id ) );

			$result[] = [
				'id'        => $menu->term_id,
				'name'      => $menu->name,
				'slug'      => $menu->slug,
				'count'     => count( $items ),
				'locations' => $assigned,
			];
		}

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( $result ) ] ],
		];
	}

	/**
	 * wp_read_menu handler.
	 */
	public function read_menu( array $input ): array {
		$id   = absint( $input['id'] ?? 0 );
		$menu = wp_get_nav_menu_object( $id );

		if ( ! $menu ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Menu not found: {$id}" ] ],
				'isError' => true,
			];
		}

		$items = wp_get_nav_menu_items( $menu->term_id ) ?: [];

		$result = [
			'id'    => $menu->term_id,
			'name'  => $menu->name,
			'slug'  => $menu->slug,
			'items' => array_map( [ $this, 'serialize_menu_item' ], $items ),
		];

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( $result ) ] ],
		];
	}

	/**
	 * wp_update_menu_item handler.
	 */
	public function update_menu_item( array $input ): array {
		$menu_item_id = absint( $input['menu_item_id'] ?? 0 );
		$item         = get_post( $menu_item_id );

		if ( ! $item || $item->post_type !== 'nav_menu_item' ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Menu item not found: {$menu_item_id}" ] ],
				'isError' => true,
			];
		}

		$menu_data = [];

		if ( isset( $input['title'] ) ) {
			$menu_data['menu-item-title'] = sanitize_text_field( $input['title'] );
		}
		if ( isset( $input['url'] ) ) {
			$menu_data['menu-item-url'] = esc_url_raw( $input['url'] );
		}
		if ( isset( $input['position'] ) ) {
			$menu_data['menu-item-position'] = absint( $input['position'] );
		}

		// Get the menu this item belongs to.
		$menus = wp_get_object_terms( $menu_item_id, 'nav_menu' );
		if ( empty( $menus ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Menu item {$menu_item_id} is not assigned to any menu" ] ],
				'isError' => true,
			];
		}

		$result = wp_update_nav_menu_item( $menus[0]->term_id, $menu_item_id, $menu_data );

		if ( is_wp_error( $result ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => $result->get_error_message() ] ],
				'isError' => true,
			];
		}

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( [
				'updated'      => true,
				'menu_item_id' => $result,
			] ) ] ],
		];
	}
}

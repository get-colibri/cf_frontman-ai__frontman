<?php
/**
 * WordPress Block tools — read and manipulate Gutenberg blocks within posts.
 *
 * Tools: wp_list_blocks, wp_read_block, wp_update_block, wp_insert_block
 *
 * @package Frontman
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontman_Tool_Blocks {
	/**
	 * Register all block tools.
	 */
	public function register( Frontman_Tools $tools ): void {
		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_list_blocks',
			description: "Lists all blocks in a post's content.\n\nParameters:\n- post_id (required): The post ID to list blocks from.\n\nReturns an array of blocks with their name, attributes, and index.",
			input_schema: [
				'type'       => 'object',
				'properties' => [
					'post_id' => [ 'type' => 'integer' ],
				],
				'required' => [ 'post_id' ],
			],
			handler: [ $this, 'list_blocks' ],
		) );

		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_read_block',
			description: "Reads a single block from a post by index.\n\nParameters:\n- post_id (required): The post ID.\n- index (required): Zero-based block index.",
			input_schema: [
				'type'       => 'object',
				'properties' => [
					'post_id' => [ 'type' => 'integer' ],
					'index'   => [ 'type' => 'integer' ],
				],
				'required' => [ 'post_id', 'index' ],
			],
			handler: [ $this, 'read_block' ],
		) );

		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_update_block',
			description: "Replaces a block at a given index in a post.\n\nParameters:\n- post_id (required): The post ID.\n- index (required): Zero-based block index to replace.\n- block_markup (required): The new block markup (HTML with block comments).",
			input_schema: [
				'type'       => 'object',
				'properties' => [
					'post_id'      => [ 'type' => 'integer' ],
					'index'        => [ 'type' => 'integer' ],
					'block_markup' => [ 'type' => 'string' ],
				],
				'required' => [ 'post_id', 'index', 'block_markup' ],
			],
			handler: [ $this, 'update_block' ],
		) );

		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_insert_block',
			description: "Inserts a new block into a post at a given position.\n\nParameters:\n- post_id (required): The post ID.\n- index (optional): Zero-based position to insert at. Appends to end if omitted.\n- block_markup (required): The block markup (HTML with block comments).",
			input_schema: [
				'type'       => 'object',
				'properties' => [
					'post_id'      => [ 'type' => 'integer' ],
					'index'        => [ 'type' => 'integer' ],
					'block_markup' => [ 'type' => 'string' ],
				],
				'required' => [ 'post_id', 'block_markup' ],
			],
			handler: [ $this, 'insert_block' ],
		) );
	}

	/**
	 * Parse blocks from a post.
	 */
	private function get_blocks( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return [];
		}
		// Filter out empty/whitespace-only blocks.
		return array_values( array_filter(
			parse_blocks( $post->post_content ),
			fn( $block ) => ! empty( $block['blockName'] ),
		) );
	}

	/**
	 * Serialize blocks back to post content string.
	 */
	private function serialize_blocks( array $blocks ): string {
		return implode( "\n\n", array_map( 'serialize_block', $blocks ) );
	}

	/**
	 * Summarize a block for listing.
	 */
	private function summarize_block( array $block, int $index ): array {
		return [
			'index'      => $index,
			'name'       => $block['blockName'],
			'attributes' => $block['attrs'] ?? [],
			'innerText'  => wp_strip_all_tags( implode( '', $block['innerHTML'] ?? [] ) ),
		];
	}

	/**
	 * wp_list_blocks handler.
	 */
	public function list_blocks( array $input ): array {
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Post not found: {$post_id}" ] ],
				'isError' => true,
			];
		}

		$blocks = $this->get_blocks( $post_id );
		$result = array_map( [ $this, 'summarize_block' ], $blocks, array_keys( $blocks ) );

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( [
				'post_id'     => $post_id,
				'block_count' => count( $blocks ),
				'blocks'      => $result,
			] ) ] ],
		];
	}

	/**
	 * wp_read_block handler.
	 */
	public function read_block( array $input ): array {
		$post_id = absint( $input['post_id'] ?? 0 );
		$index   = absint( $input['index'] ?? 0 );
		$blocks  = $this->get_blocks( $post_id );

		if ( empty( $blocks ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Post not found or has no blocks: {$post_id}" ] ],
				'isError' => true,
			];
		}

		if ( $index >= count( $blocks ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Block index {$index} out of range (post has " . count( $blocks ) . ' blocks)' ] ],
				'isError' => true,
			];
		}

		$block = $blocks[ $index ];
		$result = [
			'index'        => $index,
			'name'         => $block['blockName'],
			'attributes'   => $block['attrs'] ?? [],
			'innerHTML'    => $block['innerHTML'] ?? '',
			'innerContent' => $block['innerContent'] ?? [],
			'markup'       => serialize_block( $block ),
		];

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( $result ) ] ],
		];
	}

	/**
	 * wp_update_block handler.
	 */
	public function update_block( array $input ): array {
		$post_id      = absint( $input['post_id'] ?? 0 );
		$index        = absint( $input['index'] ?? 0 );
		$block_markup = $input['block_markup'] ?? '';

		$blocks = $this->get_blocks( $post_id );

		if ( empty( $blocks ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Post not found or has no blocks: {$post_id}" ] ],
				'isError' => true,
			];
		}

		if ( $index >= count( $blocks ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Block index {$index} out of range" ] ],
				'isError' => true,
			];
		}

		$new_blocks = parse_blocks( $block_markup );
		$new_block  = array_values( array_filter( $new_blocks, fn( $b ) => ! empty( $b['blockName'] ) ) );

		if ( empty( $new_block ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => 'Invalid block markup' ] ],
				'isError' => true,
			];
		}

		$blocks[ $index ] = $new_block[0];
		$content = $this->serialize_blocks( $blocks );

		$result = wp_update_post( [ 'ID' => $post_id, 'post_content' => $content ], true );

		if ( is_wp_error( $result ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => $result->get_error_message() ] ],
				'isError' => true,
			];
		}

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( [
				'updated'     => true,
				'post_id'     => $post_id,
				'index'       => $index,
				'block_count' => count( $blocks ),
			] ) ] ],
		];
	}

	/**
	 * wp_insert_block handler.
	 */
	public function insert_block( array $input ): array {
		$post_id      = absint( $input['post_id'] ?? 0 );
		$block_markup = $input['block_markup'] ?? '';
		$post         = get_post( $post_id );

		if ( ! $post ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Post not found: {$post_id}" ] ],
				'isError' => true,
			];
		}

		$blocks = $this->get_blocks( $post_id );

		$new_blocks = parse_blocks( $block_markup );
		$new_block  = array_values( array_filter( $new_blocks, fn( $b ) => ! empty( $b['blockName'] ) ) );

		if ( empty( $new_block ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => 'Invalid block markup' ] ],
				'isError' => true,
			];
		}

		$index = $input['index'] ?? count( $blocks );
		$index = min( max( 0, $index ), count( $blocks ) );

		array_splice( $blocks, $index, 0, $new_block );
		$content = $this->serialize_blocks( $blocks );

		$result = wp_update_post( [ 'ID' => $post_id, 'post_content' => $content ], true );

		if ( is_wp_error( $result ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => $result->get_error_message() ] ],
				'isError' => true,
			];
		}

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( [
				'inserted'    => true,
				'post_id'     => $post_id,
				'index'       => $index,
				'block_count' => count( $blocks ),
			] ) ] ],
		];
	}
}

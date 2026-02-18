<?php
/**
 * WordPress Post tools — CRUD operations on posts/pages/CPTs.
 *
 * Tools: wp_list_posts, wp_read_post, wp_create_post, wp_update_post, wp_delete_post
 *
 * @package Frontman
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontman_Tool_Posts {
	/**
	 * Register all post tools.
	 */
	public function register( Frontman_Tools $tools ): void {
		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_list_posts',
			description: 'Lists posts, pages, or custom post types with pagination and filtering.',
			input_schema: [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'post_type' => [
						'type'        => 'string',
						'description' => 'Post type slug. Use "page" for pages, or any registered CPT slug.',
						'default'     => 'post',
					],
					'status'    => [
						'type'        => 'string',
						'description' => 'Filter by post status.',
						'enum'        => [ 'publish', 'draft', 'pending', 'private', 'trash', 'any' ],
						'default'     => 'publish',
					],
					'per_page'  => [
						'type'        => 'integer',
						'description' => 'Number of results per page (max 100).',
						'default'     => 20,
					],
					'page'      => [
						'type'        => 'integer',
						'description' => 'Page number for pagination.',
						'default'     => 1,
					],
					'search'    => [
						'type'        => 'string',
						'description' => 'Search query string to filter posts by keyword.',
					],
					'orderby'   => [
						'type'        => 'string',
						'description' => 'Field to sort results by.',
						'enum'        => [ 'date', 'title', 'modified', 'ID' ],
						'default'     => 'date',
					],
					'order'     => [
						'type'        => 'string',
						'description' => 'Sort direction.',
						'enum'        => [ 'ASC', 'DESC' ],
						'default'     => 'DESC',
					],
				],
			],
			handler: [ $this, 'list_posts' ],
		) );

		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_read_post',
			description: 'Reads a single post or page by ID, including its full content, metadata, and block markup.',
			input_schema: [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'id' => [
						'type'        => 'integer',
						'description' => 'The post ID to read.',
					],
				],
				'required' => [ 'id' ],
			],
			handler: [ $this, 'read_post' ],
		) );

		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_create_post',
			description: 'Creates a new post or page. Returns the new post ID and permalink.',
			input_schema: [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'title'     => [
						'type'        => 'string',
						'description' => 'The post title.',
					],
					'content'   => [
						'type'        => 'string',
						'description' => 'The post content as HTML or Gutenberg block markup.',
					],
					'post_type' => [
						'type'        => 'string',
						'description' => 'Post type slug.',
						'default'     => 'post',
					],
					'status'    => [
						'type'        => 'string',
						'description' => 'Initial post status.',
						'enum'        => [ 'draft', 'publish', 'pending', 'private' ],
						'default'     => 'draft',
					],
				],
				'required' => [ 'title', 'content' ],
			],
			handler: [ $this, 'create_post' ],
		) );

		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_update_post',
			description: 'Updates an existing post or page. Only the fields you provide will be changed.',
			input_schema: [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'id'      => [
						'type'        => 'integer',
						'description' => 'The post ID to update.',
					],
					'title'   => [
						'type'        => 'string',
						'description' => 'New post title.',
					],
					'content' => [
						'type'        => 'string',
						'description' => 'New post content as HTML or block markup.',
					],
					'status'  => [
						'type'        => 'string',
						'description' => 'New post status.',
						'enum'        => [ 'draft', 'publish', 'pending', 'private', 'trash' ],
					],
					'excerpt' => [
						'type'        => 'string',
						'description' => 'New post excerpt.',
					],
				],
				'required' => [ 'id' ],
			],
			handler: [ $this, 'update_post' ],
		) );

		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_delete_post',
			description: 'Deletes a post or page. By default moves to trash; set force=true to permanently delete.',
			input_schema: [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'id'    => [
						'type'        => 'integer',
						'description' => 'The post ID to delete.',
					],
					'force' => [
						'type'        => 'boolean',
						'description' => 'If true, permanently delete instead of moving to trash.',
						'default'     => false,
					],
				],
				'required' => [ 'id' ],
			],
			handler: [ $this, 'delete_post' ],
		) );
	}

	/**
	 * wp_list_posts handler.
	 */
	public function list_posts( array $input ): array {
		$args = [
			'post_type'      => sanitize_key( $input['post_type'] ?? 'post' ),
			'post_status'    => sanitize_key( $input['status'] ?? 'publish' ),
			'posts_per_page' => min( absint( $input['per_page'] ?? 20 ), 100 ),
			'paged'          => max( absint( $input['page'] ?? 1 ), 1 ),
			'orderby'        => sanitize_key( $input['orderby'] ?? 'date' ),
			'order'          => strtoupper( sanitize_key( $input['order'] ?? 'DESC' ) ),
		];

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = sanitize_text_field( $input['search'] );
		}

		$query = new \WP_Query( $args );
		$posts = [];

		foreach ( $query->posts as $post ) {
			$posts[] = [
				'id'       => $post->ID,
				'title'    => $post->post_title,
				'status'   => $post->post_status,
				'type'     => $post->post_type,
				'date'     => $post->post_date,
				'modified' => $post->post_modified,
				'excerpt'  => wp_trim_words( $post->post_content, 30 ),
			];
		}

		$result = [
			'posts'       => $posts,
			'total'       => $query->found_posts,
			'total_pages' => $query->max_num_pages,
			'page'        => $args['paged'],
		];

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( $result ) ] ],
		];
	}

	/**
	 * wp_read_post handler.
	 */
	public function read_post( array $input ): array {
		$id   = absint( $input['id'] ?? 0 );
		$post = get_post( $id );

		if ( ! $post ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Post not found: {$id}" ] ],
				'isError' => true,
			];
		}

		$result = [
			'id'        => $post->ID,
			'title'     => $post->post_title,
			'content'   => $post->post_content,
			'excerpt'   => $post->post_excerpt,
			'status'    => $post->post_status,
			'type'      => $post->post_type,
			'date'      => $post->post_date,
			'modified'  => $post->post_modified,
			'author'    => (int) $post->post_author,
			'slug'      => $post->post_name,
			'permalink' => get_permalink( $post ),
		];

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( $result ) ] ],
		];
	}

	/**
	 * wp_create_post handler.
	 */
	public function create_post( array $input ): array {
		$post_data = [
			'post_title'   => sanitize_text_field( $input['title'] ),
			'post_content' => wp_kses_post( $input['content'] ),
			'post_type'    => sanitize_key( $input['post_type'] ?? 'post' ),
			'post_status'  => sanitize_key( $input['status'] ?? 'draft' ),
		];

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => $post_id->get_error_message() ] ],
				'isError' => true,
			];
		}

		$result = [
			'id'        => $post_id,
			'title'     => $post_data['post_title'],
			'status'    => $post_data['post_status'],
			'type'      => $post_data['post_type'],
			'permalink' => get_permalink( $post_id ),
		];

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( $result ) ] ],
		];
	}

	/**
	 * wp_update_post handler.
	 */
	public function update_post( array $input ): array {
		$id   = absint( $input['id'] ?? 0 );
		$post = get_post( $id );

		if ( ! $post ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Post not found: {$id}" ] ],
				'isError' => true,
			];
		}

		$post_data = [ 'ID' => $id ];

		if ( isset( $input['title'] ) ) {
			$post_data['post_title'] = sanitize_text_field( $input['title'] );
		}
		if ( isset( $input['content'] ) ) {
			$post_data['post_content'] = wp_kses_post( $input['content'] );
		}
		if ( isset( $input['status'] ) ) {
			$post_data['post_status'] = sanitize_key( $input['status'] );
		}
		if ( isset( $input['excerpt'] ) ) {
			$post_data['post_excerpt'] = sanitize_textarea_field( $input['excerpt'] );
		}

		$result = wp_update_post( $post_data, true );

		if ( is_wp_error( $result ) ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => $result->get_error_message() ] ],
				'isError' => true,
			];
		}

		$updated_post = get_post( $id );
		$out = [
			'id'        => $updated_post->ID,
			'title'     => $updated_post->post_title,
			'status'    => $updated_post->post_status,
			'modified'  => $updated_post->post_modified,
			'permalink' => get_permalink( $updated_post ),
		];

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( $out ) ] ],
		];
	}

	/**
	 * wp_delete_post handler.
	 */
	public function delete_post( array $input ): array {
		$id    = absint( $input['id'] ?? 0 );
		$force = (bool) ( $input['force'] ?? false );
		$post  = get_post( $id );

		if ( ! $post ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Post not found: {$id}" ] ],
				'isError' => true,
			];
		}

		$result = wp_delete_post( $id, $force );

		if ( ! $result ) {
			return [
				'content' => [ [ 'type' => 'text', 'text' => "Failed to delete post: {$id}" ] ],
				'isError' => true,
			];
		}

		return [
			'content' => [ [ 'type' => 'text', 'text' => wp_json_encode( [
				'deleted' => true,
				'id'      => $id,
				'title'   => $post->post_title,
				'trashed' => ! $force,
			] ) ] ],
		];
	}
}

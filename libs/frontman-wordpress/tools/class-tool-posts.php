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
			description: "Lists posts, pages, or custom post types.\n\nParameters:\n- post_type (optional): Post type slug (default: \"post\"). Use \"page\" for pages, or any CPT slug.\n- status (optional): Post status filter (default: \"publish\"). Options: publish, draft, pending, private, trash, any.\n- per_page (optional): Number of results (default: 20, max: 100).\n- page (optional): Page number for pagination (default: 1).\n- search (optional): Search query string.\n- orderby (optional): Sort field (default: \"date\"). Options: date, title, modified, ID.\n- order (optional): Sort direction (default: \"DESC\"). Options: ASC, DESC.",
			input_schema: [
				'type'       => 'object',
				'properties' => [
					'post_type' => [ 'type' => 'string' ],
					'status'    => [ 'type' => 'string' ],
					'per_page'  => [ 'type' => 'integer' ],
					'page'      => [ 'type' => 'integer' ],
					'search'    => [ 'type' => 'string' ],
					'orderby'   => [ 'type' => 'string' ],
					'order'     => [ 'type' => 'string' ],
				],
			],
			handler: [ $this, 'list_posts' ],
		) );

		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_read_post',
			description: "Reads a single post/page by ID, including its content, metadata, and block markup.\n\nParameters:\n- id (required): The post ID.",
			input_schema: [
				'type'       => 'object',
				'properties' => [
					'id' => [ 'type' => 'integer' ],
				],
				'required' => [ 'id' ],
			],
			handler: [ $this, 'read_post' ],
		) );

		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_create_post',
			description: "Creates a new post or page.\n\nParameters:\n- title (required): Post title.\n- content (required): Post content (HTML or block markup).\n- post_type (optional): Post type (default: \"post\").\n- status (optional): Post status (default: \"draft\"). Options: draft, publish, pending, private.",
			input_schema: [
				'type'       => 'object',
				'properties' => [
					'title'     => [ 'type' => 'string' ],
					'content'   => [ 'type' => 'string' ],
					'post_type' => [ 'type' => 'string' ],
					'status'    => [ 'type' => 'string' ],
				],
				'required' => [ 'title', 'content' ],
			],
			handler: [ $this, 'create_post' ],
		) );

		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_update_post',
			description: "Updates an existing post or page.\n\nParameters:\n- id (required): The post ID to update.\n- title (optional): New title.\n- content (optional): New content (HTML or block markup).\n- status (optional): New status.\n- excerpt (optional): New excerpt.",
			input_schema: [
				'type'       => 'object',
				'properties' => [
					'id'      => [ 'type' => 'integer' ],
					'title'   => [ 'type' => 'string' ],
					'content' => [ 'type' => 'string' ],
					'status'  => [ 'type' => 'string' ],
					'excerpt' => [ 'type' => 'string' ],
				],
				'required' => [ 'id' ],
			],
			handler: [ $this, 'update_post' ],
		) );

		$tools->add( new Frontman_Tool_Definition(
			name: 'wp_delete_post',
			description: "Deletes a post or page.\n\nParameters:\n- id (required): The post ID to delete.\n- force (optional): Skip trash and permanently delete (default: false).",
			input_schema: [
				'type'       => 'object',
				'properties' => [
					'id'    => [ 'type' => 'integer' ],
					'force' => [ 'type' => 'boolean' ],
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

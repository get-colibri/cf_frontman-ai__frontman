<?php
/**
 * Tool registry — holds WP tool definitions and dispatches calls.
 *
 * @package Frontman
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Represents a single tool definition.
 */
class Frontman_Tool_Definition {
	public string $name;
	public string $description;
	public array  $input_schema;
	public bool   $visible_to_agent;
	/** @var callable(array): array */
	public $handler;

	/**
	 * @param string   $name             Tool name (e.g. "wp_list_posts").
	 * @param string   $description      Human-readable description.
	 * @param array    $input_schema     JSON Schema for input (as PHP array).
	 * @param callable $handler          fn(array $input): array — returns MCP content array.
	 * @param bool     $visible_to_agent Whether the agent can see this tool.
	 */
	public function __construct(
		string $name,
		string $description,
		array $input_schema,
		callable $handler,
		bool $visible_to_agent = true,
	) {
		$this->name             = $name;
		$this->description      = $description;
		$this->input_schema     = $input_schema;
		$this->handler          = $handler;
		$this->visible_to_agent = $visible_to_agent;
	}

	/**
	 * Serialize to relay protocol format.
	 */
	public function to_array(): array {
		return [
			'name'           => $this->name,
			'description'    => $this->description,
			'inputSchema'    => $this->input_schema,
			'visibleToAgent' => $this->visible_to_agent,
		];
	}
}

/**
 * Singleton tool registry.
 */
class Frontman_Tools {
	/** @var Frontman_Tool_Definition[] */
	private array $tools = [];

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register a tool definition.
	 */
	public function add( Frontman_Tool_Definition $tool ): void {
		$this->tools[ $tool->name ] = $tool;
	}

	/**
	 * Look up a tool by name.
	 */
	public function get( string $name ): ?Frontman_Tool_Definition {
		return $this->tools[ $name ] ?? null;
	}

	/**
	 * Return all tool definitions as serializable arrays.
	 *
	 * @return array[]
	 */
	public function all_definitions(): array {
		return array_values(
			array_map(
				fn( Frontman_Tool_Definition $t ) => $t->to_array(),
				$this->tools,
			)
		);
	}

	/**
	 * Execute a tool by name.
	 *
	 * @param string $name  Tool name.
	 * @param array  $input Tool input arguments.
	 * @return array MCP content array.
	 * @throws \RuntimeException If tool not found.
	 */
	public function call( string $name, array $input ): array {
		$tool = $this->get( $name );
		if ( ! $tool ) {
			throw new \RuntimeException(
				sprintf(
					/* translators: %s: tool name */
					__( 'Unknown tool: %s', 'frontman' ),
					$name,
				)
			);
		}
		return ( $tool->handler )( $input );
	}

	/**
	 * Check if a tool name is a WP tool (handled locally).
	 */
	public function is_wp_tool( string $name ): bool {
		return isset( $this->tools[ $name ] );
	}
}

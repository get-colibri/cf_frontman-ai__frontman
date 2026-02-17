<?php
/**
 * Settings — admin settings page for Frontman configuration.
 *
 * @package Frontman
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontman_Settings {
	private const OPTION_KEY = 'frontman_settings';

	private const DEFAULTS = [
		'standalone_host' => '127.0.0.1',
		'standalone_port' => 4321,
	];

	/**
	 * Register settings hooks.
	 */
	public function register(): void {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
	}

	/**
	 * Register WordPress settings.
	 */
	public function register_settings(): void {
		register_setting( 'frontman_settings_group', self::OPTION_KEY, [
			'type'              => 'array',
			'sanitize_callback' => [ $this, 'sanitize' ],
			'default'           => self::DEFAULTS,
		] );

		add_settings_section(
			'frontman_standalone_section',
			__( 'Standalone Server', 'frontman' ),
			fn() => printf(
				'<p>%s</p>',
				esc_html__( 'Configure the connection to the Frontman standalone tool server.', 'frontman' ),
			),
			'frontman-settings',
		);

		add_settings_field(
			'standalone_host',
			__( 'Host', 'frontman' ),
			[ $this, 'render_text_field' ],
			'frontman-settings',
			'frontman_standalone_section',
			[ 'key' => 'standalone_host', 'placeholder' => '127.0.0.1' ],
		);

		add_settings_field(
			'standalone_port',
			__( 'Port', 'frontman' ),
			[ $this, 'render_text_field' ],
			'frontman-settings',
			'frontman_standalone_section',
			[ 'key' => 'standalone_port', 'placeholder' => '4321', 'type' => 'number' ],
		);
	}

	/**
	 * Add sub-menu settings page under the Frontman menu.
	 */
	public function add_settings_page(): void {
		add_submenu_page(
			'frontman',
			__( 'Frontman Settings', 'frontman' ),
			__( 'Settings', 'frontman' ),
			'manage_options',
			'frontman-settings',
			[ $this, 'render_page' ],
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Frontman Settings', 'frontman' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'frontman_settings_group' );
				do_settings_sections( 'frontman-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a text input field.
	 */
	public function render_text_field( array $args ): void {
		$key         = $args['key'];
		$value       = $this->get( $key );
		$placeholder = $args['placeholder'] ?? '';
		$type        = $args['type'] ?? 'text';
		$name        = self::OPTION_KEY . "[{$key}]";

		printf(
			'<input type="%s" name="%s" value="%s" placeholder="%s" class="regular-text">',
			esc_attr( $type ),
			esc_attr( $name ),
			esc_attr( $value ),
			esc_attr( $placeholder ),
		);
	}

	/**
	 * Sanitize settings on save.
	 */
	public function sanitize( array $input ): array {
		return [
			'standalone_host' => sanitize_text_field( $input['standalone_host'] ?? self::DEFAULTS['standalone_host'] ),
			'standalone_port' => absint( $input['standalone_port'] ?? self::DEFAULTS['standalone_port'] ),
		];
	}

	/**
	 * Get a setting value.
	 */
	public function get( string $key, mixed $default = null ): mixed {
		$settings = get_option( self::OPTION_KEY, self::DEFAULTS );
		return $settings[ $key ] ?? $default ?? self::DEFAULTS[ $key ] ?? null;
	}
}

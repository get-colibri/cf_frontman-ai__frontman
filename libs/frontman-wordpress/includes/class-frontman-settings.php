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
		'standalone_host'  => 'localhost',
		'standalone_port'  => 19478,
		'dev_mode'         => false,
		'dev_client_port'  => 5173,
		'frontman_host'    => 'frontman.local:4000',
	];

	/**
	 * Register settings fields (admin_init only).
	 *
	 * The admin menu entry is registered separately via register_menu()
	 * to ensure the parent "Frontman" menu page exists first.
	 */
	public function register(): void {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register the submenu page under admin_menu.
	 *
	 * Must be called AFTER the parent "frontman" menu page is registered
	 * by Frontman_UI::register(), otherwise WordPress can't resolve the
	 * parent slug and the page returns "not allowed".
	 */
	public function register_menu(): void {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ], 20 );
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
			[ 'key' => 'standalone_host', 'placeholder' => 'localhost' ],
		);

		add_settings_field(
			'standalone_port',
			__( 'Port', 'frontman' ),
			[ $this, 'render_text_field' ],
			'frontman-settings',
			'frontman_standalone_section',
			[ 'key' => 'standalone_port', 'placeholder' => '19478', 'type' => 'number' ],
		);

		// --- Development section ---
		add_settings_section(
			'frontman_dev_section',
			__( 'Development', 'frontman' ),
			fn() => printf(
				'<p>%s</p>',
				esc_html__( 'Enable dev mode to load the Frontman client from a local Vite dev server instead of the production CDN.', 'frontman' ),
			),
			'frontman-settings',
		);

		add_settings_field(
			'dev_mode',
			__( 'Dev Mode', 'frontman' ),
			[ $this, 'render_checkbox_field' ],
			'frontman-settings',
			'frontman_dev_section',
			[ 'key' => 'dev_mode', 'label' => __( 'Load client from local Vite dev server', 'frontman' ) ],
		);

		add_settings_field(
			'dev_client_port',
			__( 'Client Dev Port', 'frontman' ),
			[ $this, 'render_text_field' ],
			'frontman-settings',
			'frontman_dev_section',
			[ 'key' => 'dev_client_port', 'placeholder' => '5173', 'type' => 'number' ],
		);

		add_settings_field(
			'frontman_host',
			__( 'Frontman Server Host', 'frontman' ),
			[ $this, 'render_text_field' ],
			'frontman-settings',
			'frontman_dev_section',
			[ 'key' => 'frontman_host', 'placeholder' => 'frontman.local:4000' ],
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
	 * Render a checkbox input field.
	 */
	public function render_checkbox_field( array $args ): void {
		$key   = $args['key'];
		$value = (bool) $this->get( $key );
		$label = $args['label'] ?? '';
		$name  = self::OPTION_KEY . "[{$key}]";

		printf(
			'<label><input type="checkbox" name="%s" value="1" %s /> %s</label>',
			esc_attr( $name ),
			checked( $value, true, false ),
			esc_html( $label ),
		);
	}

	/**
	 * Sanitize settings on save.
	 */
	public function sanitize( array $input ): array {
		return [
			'standalone_host'  => sanitize_text_field( $input['standalone_host'] ?? self::DEFAULTS['standalone_host'] ),
			'standalone_port'  => absint( $input['standalone_port'] ?? self::DEFAULTS['standalone_port'] ),
			'dev_mode'         => ! empty( $input['dev_mode'] ),
			'dev_client_port'  => absint( $input['dev_client_port'] ?? self::DEFAULTS['dev_client_port'] ),
			'frontman_host'    => sanitize_text_field( $input['frontman_host'] ?? self::DEFAULTS['frontman_host'] ),
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

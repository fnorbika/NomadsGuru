<?php
/**
 * Handles admin menu, settings page, and settings API registration for
 * WP Autoplugin Content Curator.
 *
 * @package WACC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WACC_Admin class.
 * Manages the administrative functionalities of the plugin.
 */
class WACC_Admin {

	/**
	 * Constructor.
	 * Initializes the class.
	 */
	public function __construct() {
		// No actions hooked in constructor, handled by run() method.
	}

	/**
	 * Runs the admin plugin logic.
	 * Hooks into WordPress admin actions.
	 */
	public function run() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Adds the plugin's administration menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			esc_html__( 'WP Autoplugin Content Curator Settings', 'wp-autoplugin-content-curator' ),
			esc_html__( 'Autoplugin Curator', 'wp-autoplugin-content-curator' ),
			'manage_options',
			'wacc-settings',
			array( $this, 'settings_page_callback' ),
			'dashicons-admin-generic',
			80
		);

		add_submenu_page(
			'wacc-settings', // Parent slug.
			esc_html__( 'Curated Articles', 'wp-autoplugin-content-curator' ),
			esc_html__( 'Curated Articles', 'wp-autoplugin-content-curator' ),
			'manage_options',
			'wacc-curated-articles',
			array( $this, 'curated_articles_page_callback' )
		);
	}

	/**
	 * Renders the plugin's settings page.
	 */
	public function settings_page_callback() {
		// Include the settings page template.
		require_once WACC_PLUGIN_DIR . 'admin/settings-page.php';
	}

	/**
	 * Renders the curated articles list page.
	 */
	public function curated_articles_page_callback() {
		// Include the curated articles list template.
		require_once WACC_PLUGIN_DIR . 'admin/curated-articles-list.php';
	}

	/**
	 * Registers the plugin's settings using the WordPress Settings API.
	 */
	public function register_settings() {
		// Register the settings group.
		register_setting(
			'wacc_settings_group', // Option group.
			'wacc_customer_keywords', // Option name.
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_textarea_input' ),
				'default'           => '',
				'show_in_rest'      => false,
			)
		);

		register_setting(
			'wacc_settings_group', // Option group.
			'wacc_target_websites', // Option name.
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_textarea_input' ),
				'default'           => '',
				'show_in_rest'      => false,
			)
		);

		register_setting(
			'wacc_settings_group', // Option group.
			'wacc_gemini_api_key', // Option name.
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
				'show_in_rest'      => false,
			)
		);

		register_setting(
			'wacc_settings_group', // Option group.
			'wacc_ai_model_identifier', // Option name.
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'gemini-apu-custom', // Default to the custom model ID.
				'show_in_rest'      => false,
			)
		);

		register_setting(
			'wacc_settings_group', // Option group.
			'wacc_post_status', // Option name.
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_post_status' ),
				'default'           => 'draft',
				'show_in_rest'      => false,
			)
		);

		// Add a settings section for General Settings.
		add_settings_section(
			'wacc_general_settings_section', // ID.
			esc_html__( 'General Settings', 'wp-autoplugin-content-curator' ), // Title.
			array( $this, 'general_settings_section_callback' ), // Callback.
			'wacc-settings' // Page.
		);

		// Add settings fields to General Settings section.
		add_settings_field(
			'wacc_customer_keywords_field', // ID.
			esc_html__( 'Customer Keywords', 'wp-autoplugin-content-curator' ), // Title.
			array( $this, 'customer_keywords_callback' ), // Callback.
			'wacc-settings', // Page.
			'wacc_general_settings_section', // Section.
			array(
				'label_for' => 'wacc_customer_keywords',
				'description' => esc_html__( 'Enter keywords to monitor, one per line. Articles containing these keywords will be processed. Max 1000.', 'wp-autoplugin-content-curator' ),
			)
		);

		add_settings_field(
			'wacc_target_websites_field', // ID.
			esc_html__( 'Target Websites', 'wp-autoplugin-content-curator' ), // Title.
			array( $this, 'target_websites_callback' ), // Callback.
			'wacc-settings', // Page.
			'wacc_general_settings_section', // Section.
			array(
				'label_for' => 'wacc_target_websites',
				'description' => esc_html__( 'Enter website URLs to monitor, one per line. Max 1000.', 'wp-autoplugin-content-curator' ),
			)
		);

		add_settings_field(
			'wacc_post_status_field', // ID.
			esc_html__( 'Default Post Status', 'wp-autoplugin-content-curator' ), // Title.
			array( $this, 'post_status_callback' ), // Callback.
			'wacc-settings', // Page.
			'wacc_general_settings_section', // Section.
			array(
				'label_for' => 'wacc_post_status',
				'description' => esc_html__( 'Select the default status for newly created posts.', 'wp-autoplugin-content-curator' ),
			)
		);

		// Add a settings section for AI Settings.
		add_settings_section(
			'wacc_ai_settings_section', // ID.
			esc_html__( 'AI Settings', 'wp-autoplugin-content-curator' ), // Title.
			array( $this, 'ai_settings_section_callback' ), // Callback.
			'wacc-settings' // Page.
		);

		// Add settings fields to AI Settings section.
		add_settings_field(
			'wacc_gemini_api_key_field', // ID.
			esc_html__( 'Gemini API Key', 'wp-autoplugin-content-curator' ), // Title.
			array( $this, 'gemini_api_key_callback' ), // Callback.
			'wacc-settings', // Page.
			'wacc_ai_settings_section', // Section.
			array(
				'label_for' => 'wacc_gemini_api_key',
				'description' => esc_html__( 'Enter your custom Gemini API key.', 'wp-autoplugin-content-curator' ),
			)
		);

		add_settings_field(
			'wacc_ai_model_identifier_field', // ID.
			esc_html__( 'AI Model Identifier', 'wp-autoplugin-content-curator' ), // Title.
			array( $this, 'ai_model_identifier_callback' ), // Callback.
			'wacc-settings', // Page.
			'wacc_ai_settings_section', // Section.
			array(
				'label_for' => 'wacc_ai_model_identifier',
				'description' => esc_html__( 'The specific identifier for your custom Gemini APU model (e.g., "gemini-apu-custom"). This is what the plugin will use to call the API.', 'wp-autoplugin-content-curator' ),
			)
		);
	}

	/**
	 * Callback for the general settings section.
	 *
	 * @param array $args Arguments passed to the section.
	 */
	public function general_settings_section_callback( $args ) {
		echo '<p>' . esc_html__( 'Configure general settings for content curation.', 'wp-autoplugin-content-curator' ) . '</p>';
	}

	/**
	 * Callback for the AI settings section.
	 *
	 * @param array $args Arguments passed to the section.
	 */
	public function ai_settings_section_callback( $args ) {
		echo '<p>' . esc_html__( 'Configure AI integration settings.', 'wp-autoplugin-content-curator' ) . '</p>';
	}

	/**
	 * Callback for the Customer Keywords field.
	 */
	public function customer_keywords_callback( $args ) {
		$keywords = get_option( 'wacc_customer_keywords', '' );
		?>
		<textarea id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( $args['label_for'] ); ?>" rows="10" cols="50" class="large-text code"><?php echo esc_textarea( $keywords ); ?></textarea>
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<div id="wacc-keywords-error" class="wacc-validation-error"></div>
		<?php
	}

	/**
	 * Callback for the Target Websites field.
	 */
	public function target_websites_callback( $args ) {
		$websites = get_option( 'wacc_target_websites', '' );
		?>
		<textarea id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( $args['label_for'] ); ?>" rows="10" cols="50" class="large-text code"><?php echo esc_textarea( $websites ); ?></textarea>
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<div id="wacc-websites-error" class="wacc-validation-error"></div>
		<?php
	}

	/**
	 * Callback for the Gemini API Key field.
	 */
	public function gemini_api_key_callback( $args ) {
		$api_key = get_option( 'wacc_gemini_api_key', '' );
		?>
		<input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( $args['label_for'] ); ?>" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text">
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php
	}

	/**
	 * Callback for the AI Model Identifier field.
	 */
	public function ai_model_identifier_callback( $args ) {
		$model_id = get_option( 'wacc_ai_model_identifier', 'gemini-apu-custom' );
		?>
		<input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( $args['label_for'] ); ?>" value="<?php echo esc_attr( $model_id ); ?>" class="regular-text">
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php
	}

	/**
	 * Callback for the Post Status dropdown.
	 */
	public function post_status_callback( $args ) {
		$current_status = get_option( 'wacc_post_status', 'draft' );
		$post_statuses  = get_post_statuses(); // Get all registered post statuses.
		?>
		<select id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( $args['label_for'] ); ?>">
			<?php
			foreach ( $post_statuses as $status_slug => $status_name ) {
				// Exclude internal statuses like 'auto-draft', 'inherit', 'trash'.
				if ( in_array( $status_slug, array( 'auto-draft', 'inherit', 'trash' ), true ) ) {
					continue;
				}
				printf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $status_slug ),
					selected( $current_status, $status_slug, false ),
					esc_html( $status_name )
				);
			}
			?>
		</select>
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php
	}

	/**
	 * Sanitizes textarea input, preserving newlines.
	 *
	 * @param string $input The raw input from the textarea.
	 * @return string Sanitized output.
	 */
	public function sanitize_textarea_input( $input ) {
		// Given the context (keywords, URLs), we want plain text.
		$sanitized_lines = array_map( 'trim', explode( "\n", $input ) );
		$sanitized_lines = array_map( 'sanitize_text_field', $sanitized_lines ); // Sanitize each line.
		$sanitized_lines = array_filter( $sanitized_lines ); // Remove empty lines.
		return implode( "\n", $sanitized_lines );
	}

	/**
	 * Sanitizes the post status input.
	 *
	 * @param string $input The raw input for post status.
	 * @return string Validated post status, defaults to 'draft' if invalid.
	 */
	public function sanitize_post_status( $input ) {
		$valid_statuses = array_keys( get_post_statuses() );
		// Filter out internal statuses that are not suitable for direct user selection.
		$valid_statuses = array_diff( $valid_statuses, array( 'auto-draft', 'inherit', 'trash' ) );

		if ( in_array( $input, $valid_statuses, true ) ) {
			return $input;
		}
		return 'draft'; // Default to 'draft' if an invalid status is provided.
	}

	/**
	 * Enqueues admin-specific scripts and styles.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load assets on our plugin's admin pages.
		if ( 'toplevel_page_wacc-settings' !== $hook && 'autoplugin-curator_page_wacc-curated-articles' !== $hook ) {
			return;
		}

		// Enqueue admin CSS.
		wp_enqueue_style(
			'wacc-admin-styles',
			WACC_PLUGIN_URL . 'admin/css/wacc-admin.css',
			array(),
			WACC_VERSION
		);

		// Enqueue admin JS.
		wp_enqueue_script(
			'wacc-admin-scripts',
			WACC_PLUGIN_URL . 'admin/js/wacc-admin.js',
			array( 'jquery' ),
			WACC_VERSION,
			true // Load in footer.
		);

		// Localize script for passing data from PHP to JavaScript.
		wp_localize_script(
			'wacc-admin-scripts',
			'waccAdmin',
			array(
				'settingsPageUrl' => admin_url( 'admin.php?page=wacc-settings' ),
				'i18n'            => array(
					'invalidUrl'      => esc_html__( 'Please enter a valid URL (e.g., https://example.com).', 'wp-autoplugin-content-curator' ),
					'emptyField'      => esc_html__( 'This field cannot be empty.', 'wp-autoplugin-content-curator' ),
					'maxKeywords'     => esc_html__( 'You can enter a maximum of 1000 keywords.', 'wp-autoplugin-content-curator' ),
					'maxWebsites'     => esc_html__( 'You can enter a maximum of 1000 target websites.', 'wp-autoplugin-content-curator' ),
				),
			)
		);
	}
}
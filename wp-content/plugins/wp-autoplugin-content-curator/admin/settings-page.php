<?php
/**
 * Template for the plugin's settings page.
 *
 * @package WACC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'WP Autoplugin Content Curator Settings', 'wp-autoplugin-content-curator' ); ?></h1>

	<?php settings_errors(); // Display WordPress general settings errors/notices. ?>

	<h2 class="nav-tab-wrapper wacc-nav-tab-wrapper">
		<a href="#wacc_general_settings_section" class="nav-tab nav-tab-active" id="wacc_general_settings_section-tab"><?php esc_html_e( 'General Settings', 'wp-autoplugin-content-curator' ); ?></a>
		<a href="#wacc_ai_settings_section" class="nav-tab" id="wacc_ai_settings_section-tab"><?php esc_html_e( 'AI Settings', 'wp-autoplugin-content-curator' ); ?></a>
	</h2>

	<form method="post" action="options.php" id="wacc-settings-form">
		<?php
		// Output security fields for the registered setting "wacc_settings_group".
		settings_fields( 'wacc_settings_group' );

		// Output all setting sections and their fields for the 'wacc-settings' page.
		// The sections will be rendered with their respective IDs (e.g., wacc_general_settings_section).
		do_settings_sections( 'wacc-settings' );

		// Output save button.
		submit_button( esc_html__( 'Save Changes', 'wp-autoplugin-content-curator' ) );
		?>
	</form>
</div>
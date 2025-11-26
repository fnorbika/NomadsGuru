<?php
/**
 * Template for the curated articles list page.
 *
 * @package WACC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Instantiate the list table.
$curated_articles_list_table = new WACC_Curated_Articles_List_Table();
$curated_articles_list_table->prepare_items();
?>
<div class="wrap wacc-admin-page">
	<h1><?php esc_html_e( 'Curated Articles', 'wp-autoplugin-content-curator' ); ?></h1>

	<form id="wacc-curated-articles-filter" method="get">
		<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
		<?php
		$curated_articles_list_table->display();
		?>
	</form>
</div>
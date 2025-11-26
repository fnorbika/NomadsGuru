<?php
/**
 * WP_List_Table implementation for displaying curated articles.
 *
 * @package WACC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure WP_List_Table is loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * WACC_Curated_Articles_List_Table class.
 * Displays a list of posts created by the plugin.
 */
class WACC_Curated_Articles_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 *
	 * @param array $args An associative array of arguments.
	 */
	public function __construct( $args = array() ) {
		parent::__construct(
			array_merge(
				$args,
				array(
					'singular' => esc_html__( 'Curated Article', 'wp-autoplugin-content-curator' ),
					'plural'   => esc_html__( 'Curated Articles', 'wp-autoplugin-content-curator' ),
					'ajax'     => false,
				)
			)
		);
	}

	/**
	 * Get a list of columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'            => '<input type="checkbox" />', // Checkbox for bulk actions.
			'title'         => esc_html__( 'Title', 'wp-autoplugin-content-curator' ),
			'status'        => esc_html__( 'Status', 'wp-autoplugin-content-curator' ),
			'original_url'  => esc_html__( 'Original URL', 'wp-autoplugin-content-curator' ),
			'ai_model'      => esc_html__( 'AI Model', 'wp-autoplugin-content-curator' ),
			'date'          => esc_html__( 'Date', 'wp-autoplugin-content-curator' ),
		);
		return $columns;
	}

	/**
	 * Get a list of sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'title'  => array( 'title', false ),
			'status' => array( 'status', false ),
			'date'   => array( 'date', true ), // 'date' is the column ID, true means it's sorted by default.
		);
		return $sortable_columns;
	}

	/**
	 * Get a list of possible bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions = array(
			'delete' => esc_html__( 'Delete', 'wp-autoplugin-content-curator' ),
		);
		return $actions;
	}

	/**
	 * Handles data for the 'cb' column (checkbox).
	 *
	 * @param WP_Post $post The current post object.
	 * @return string
	 */
	protected function column_cb( $post ) {
		return sprintf(
			'<input type="checkbox" name="post[]" value="%d" />',
			$post->ID
		);
	}

	/**
	 * Handles data for the 'title' column.
	 *
	 * @param WP_Post $post The current post object.
	 * @return string
	 */
	protected function column_title( $post ) {
		$edit_link = get_edit_post_link( $post->ID );
		$title     = get_the_title( $post );

		$actions = array(
			'edit'   => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $edit_link ),
				esc_html__( 'Edit', 'wp-autoplugin-content-curator' )
			),
			'view'   => sprintf(
				'<a href="%s" rel="bookmark">%s</a>',
				esc_url( get_permalink( $post->ID ) ),
				esc_html__( 'View', 'wp-autoplugin-content-curator' )
			),
			'delete' => sprintf(
				'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
				get_delete_post_link( $post->ID ),
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'Delete "%s"', 'wp-autoplugin-content-curator' ), $title ) ), // Changed “%s” to "%s"
				esc_html__( 'Delete', 'wp-autoplugin-content-curator' )
			),
		);

		return sprintf(
			'<strong><a class="row-title" href="%s" aria-label="%s">%s</a></strong>%s',
			esc_url( $edit_link ),
			/* translators: %s: Post title. */
			esc_attr( sprintf( __( '"%s" (Edit)', 'wp-autoplugin-content-curator' ), $title ) ), // Changed “%s” to "%s"
			esc_html( $title ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Handles data for the 'status' column.
	 *
	 * @param WP_Post $post The current post object.
	 * @return string
	 */
	protected function column_status( $post ) {
		$status_obj = get_post_status_object( $post->post_status );
		return $status_obj ? esc_html( $status_obj->label ) : esc_html( $post->post_status );
	}

	/**
	 * Handles data for the 'original_url' column.
	 *
	 * @param WP_Post $post The current post object.
	 * @return string
	 */
	protected function column_original_url( $post ) {
		$original_url = get_post_meta( $post->ID, '_wacc_original_url', true );
		if ( $original_url ) {
			return sprintf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( $original_url ), esc_html( wp_parse_url( $original_url, PHP_URL_HOST ) ) );
		}
		return esc_html__( 'N/A', 'wp-autoplugin-content-curator' );
	}

	/**
	 * Handles data for the 'ai_model' column.
	 *
	 * @param WP_Post $post The current post object.
	 * @return string
	 */
	protected function column_ai_model( $post ) {
		$ai_model = get_post_meta( $post->ID, '_wacc_ai_model', true );
		return $ai_model ? esc_html( $ai_model ) : esc_html__( 'N/A', 'wp-autoplugin-content-curator' );
	}

	/**
	 * Handles data for the 'date' column.
	 *
	 * @param WP_Post $post The current post object.
	 * @return string
	 */
	protected function column_date( $post ) {
		return sprintf(
			'<abbr title="%s">%s</abbr>',
			esc_attr( get_the_time( 'Y/m/d H:i:s', $post ) ),
			esc_html( get_the_time( get_option( 'date_format' ), $post ) )
		);
	}

	/**
	 * Handles data for any custom column.
	 *
	 * @param WP_Post $post        The current post object.
	 * @param string  $column_name The name of the column to display.
	 * @return string
	 */
	protected function column_default( $post, $column_name ) {
		// Fallback for columns not explicitly handled.
		return '';
	}

	/**
	 * Prepares the list of items for displaying.
	 */
	public function prepare_items() {
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'wacc_curated_articles_per_page', 20 );
		$current_page = $this->get_pagenum();

		$args = array(
			'post_type'      => 'post',
			'post_status'    => array_keys( get_post_statuses() ), // Include all statuses.
			'meta_query'     => array(
				array(
					'key'     => '_wacc_original_url',
					'compare' => 'EXISTS',
				),
			),
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
			'orderby'        => isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'date',
			'order'          => isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC',
		);

		// Handle sorting by meta keys if needed in the future.
		if ( 'original_url' === $args['orderby'] ) {
			$args['orderby']  = 'meta_value';
			$args['meta_key'] = '_wacc_original_url';
		} elseif ( 'ai_model' === $args['orderby'] ) {
			$args['orderby']  = 'meta_value';
			$args['meta_key'] = '_wacc_ai_model';
		}

		$query = new WP_Query( $args );

		$this->items = $query->posts;

		$this->set_pagination_args(
			array(
				'total_items' => $query->found_posts,
				'per_page'    => $per_page,
				'total_pages' => $query->max_num_pages,
			)
		);
	}

	/**
	 * Processes bulk actions.
	 */
	protected function process_bulk_action() {
		if ( 'delete' === $this->current_action() ) {
			if ( ! current_user_can( 'delete_posts' ) ) {
				wp_die( esc_html__( 'You are not allowed to delete posts.', 'wp-autoplugin-content-curator' ) );
			}

			check_admin_referer( 'bulk-' . $this->_args['plural'] );

			$post_ids = isset( $_REQUEST['post'] ) ? array_map( 'absint', (array) $_REQUEST['post'] ) : array();

			if ( ! empty( $post_ids ) ) {
				foreach ( $post_ids as $post_id ) {
					// Ensure it's a post created by our plugin before deleting.
					if ( get_post_meta( $post_id, '_wacc_original_url', true ) ) {
						wp_delete_post( $post_id, true ); // true for force delete.
					}
				}
				// Redirect to avoid resubmission and clear query args.
				wp_safe_redirect( remove_query_arg( array( 'action', 'action2', 'post', '_wpnonce' ) ) );
				exit;
			}
		}
	}
}
<?php
/**
 * Plugin Name: Bulk Product Remover
 * Description: A powerful tool to bulk remove WooCommerce products via CSV upload. Easily manage your product catalog by removing multiple products at once.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: kamal15
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bulk-product-remover
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 8.0.0
 *
 * @package BulkProductRemover
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
class BPRbyKML_BulkProductRemover {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'bprbykml_add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'bprbykml_enqueue_admin_assets' ) );
		add_action( 'wp_ajax_bulk_remove_delete', array( $this, 'bprbykml_bulk_remove_delete_func' ) );
		add_action( 'init', array( $this, 'bprbykml_load_textdomain' ) );
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.0.0
	 */
	public function bprbykml_load_textdomain() {
		load_plugin_textdomain(
			'bulk-product-remover',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Add admin menu.
	 *
	 * @since 1.0.0
	 */
	public function bprbykml_add_admin_menu() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		add_submenu_page(
			'edit.php?post_type=product',
			__( 'Bulk Product Remover', 'bulk-product-remover' ),
			__( 'Bulk Remover', 'bulk-product-remover' ),
			'manage_woocommerce',
			'bulk-product-remover',
			array( $this, 'bprbykml_render_admin_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Current admin page.
	 */
	public function bprbykml_enqueue_admin_assets( $hook ) {
		if ( 'product_page_bulk-product-remover' !== $hook ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		wp_enqueue_style(
			'bulk-product-remover-style',
			plugin_dir_url( __FILE__ ) . 'assets/css/admin-style.css',
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/admin-style.css' )
		);

		wp_enqueue_script(
			'bulk-product-remover-script',
			plugin_dir_url( __FILE__ ) . 'assets/js/admin-script.js',
			array( 'jquery' ),
			filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/admin-script.js' ),
			true
		);

		wp_localize_script(
			'bulk-product-remover-script',
			'bulkProductRemover',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'bulk_product_remover_nonce' ),
				'i18n'    => array(
					'confirmDelete' => __( 'Are you sure you want to delete these products?', 'bulk-product-remover' ),
					'errorMessage'  => __( 'An error occurred while processing the request.', 'bulk-product-remover' ),
				),
			)
		);
	}

	/**
	 * Render admin page.
	 *
	 * @since 1.0.0
	 */
	public function bprbykml_render_admin_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Bulk Product Remover', 'bulk-product-remover' ); ?></h1>
			
			<?php if ( isset( $_FILES['csv_file'] ) && check_admin_referer( 'bulk_product_remover_action', 'bulk_product_remover_nonce' ) ) : ?>
				<?php $this->bprbykml_handle_csv_upload(); ?>
			<?php else : ?>
				<form method="post" enctype="multipart/form-data" id="bulk_removal_csv_form">
					<?php wp_nonce_field( 'bulk_product_remover_action', 'bulk_product_remover_nonce' ); ?>
					<div class="form-field">
						<label for="bulk_removal_csv_file">
							<strong><?php esc_html_e( 'Upload CSV file with product IDs:', 'bulk-product-remover' ); ?></strong>
						</label>
						<input type="file" id="bulk_removal_csv_file" name="csv_file" accept=".csv" required />
						<p class="description">
							<?php esc_html_e( 'CSV file should contain product IDs in the first column.', 'bulk-product-remover' ); ?>
						</p>
					</div>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handle CSV file upload.
	 *
	 * @since 1.0.0
	 */
	private function bprbykml_handle_csv_upload() {
		// Verify nonce
		if (!isset($_POST['bulk_product_remover_nonce']) || 
			!wp_verify_nonce(sanitize_key($_POST['bulk_product_remover_nonce']), 'bulk_product_remover_action')) {
			wp_die(esc_html__('Security check failed', 'bulk-product-remover'));
		}

		// Check if file was uploaded
		if (!isset($_FILES['csv_file']) || !isset($_FILES['csv_file']['error']) || UPLOAD_ERR_OK !== $_FILES['csv_file']['error']) {
			echo '<div class="notice notice-error"><p>' . esc_html__('Invalid CSV file format', 'bulk-product-remover') . '</p></div>';
			return;
		}

		// Validate all required file data exists
		$required_keys = array('name', 'type', 'tmp_name', 'error', 'size');
		foreach ($required_keys as $key) {
			if (!isset($_FILES['csv_file'][$key])) {
				echo '<div class="notice notice-error"><p>' . esc_html__('Invalid file data', 'bulk-product-remover') . '</p></div>';
				return;
			}
		}

		// Sanitize and validate file data
		$file = array(
			'name'     => isset($_FILES['csv_file']['name']) ? sanitize_file_name($_FILES['csv_file']['name']) : '',
			'type'     => isset($_FILES['csv_file']['type']) ? sanitize_mime_type($_FILES['csv_file']['type']) : '',
			'tmp_name' => isset($_FILES['csv_file']['tmp_name']) ? sanitize_text_field($_FILES['csv_file']['tmp_name']) : '',
			'error'    => isset($_FILES['csv_file']['error']) ? absint($_FILES['csv_file']['error']) : 0,
			'size'     => isset($_FILES['csv_file']['size']) ? absint($_FILES['csv_file']['size']) : 0,
		);

		// Validate file data
		if (empty($file['name']) || empty($file['type']) || empty($file['tmp_name'])) {
			echo '<div class="notice notice-error"><p>' . esc_html__('Invalid file data', 'bulk-product-remover') . '</p></div>';
			return;
		}

		// Verify file type
		$allowed_types = array('text/csv', 'application/vnd.ms-excel');
		if (!in_array($file['type'], $allowed_types, true)) {
			echo '<div class="notice notice-error"><p>' . esc_html__('Invalid file type. Please upload a CSV file.', 'bulk-product-remover') . '</p></div>';
			return;
		}

		// Verify file size (max 5MB)
		$max_size = 5 * 1024 * 1024; // 5MB in bytes
		if ($file['size'] > $max_size) {
			echo '<div class="notice notice-error"><p>' . esc_html__('File size exceeds the maximum limit of 5MB.', 'bulk-product-remover') . '</p></div>';
			return;
		}

		// Use WordPress file handling
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		$upload = wp_handle_upload($file, array('test_form' => false));

		if (isset($upload['error'])) {
			echo '<div class="notice notice-error"><p>' . esc_html($upload['error']) . '</p></div>';
			return;
		}

		if (!isset($upload['file'])) {
			echo '<div class="notice notice-error"><p>' . esc_html__('Error processing file upload', 'bulk-product-remover') . '</p></div>';
			return;
		}

		// Read and process the CSV file
		$products = array_map('str_getcsv', file($upload['file']));
		
		// Clean up the uploaded file
		wp_delete_file($upload['file']);

		if (empty($products)) {
			echo '<div class="notice notice-error"><p>' . esc_html__('No products found in CSV file', 'bulk-product-remover') . '</p></div>';
			return;
		}

		?>
		<div class="product-list-container">
			<button id="start_delete_btn" class="button button-primary">
				<?php esc_html_e( 'Delete Products', 'bulk-product-remover' ); ?>
			</button>
			<button id="cancel_delete_btn" class="button">
				<?php esc_html_e( 'Cancel', 'bulk-product-remover' ); ?>
			</button>
			
			<ul class="remove_product_ids">
				<?php 
				if ( $products ) {
					foreach ( $products as $key => $value ) {
						if ( isset( $value[0] ) && 'id' !== strtolower( $value[0] ) ) {
							$product_id = absint( $value[0] );
							echo '<li class="hidden" id="' . esc_attr( $product_id ) . '">' . esc_html( $product_id ) . '</li>';
						}
					}
				}
				?>
			</ul>
			<div id="progress-container" style="display: none;">
				<div class="progress-bar-container">
					<div id="progress-bar" class="progress-bar"></div>
				</div>
				<div id="progress-text">
					<span id="deleted_products_cnt">0</span> / <span id="total_products">0</span> products deleted
				</div>
			</div>

			<div id="success-message" style="display: none;">
				<h2>
					<?php esc_html_e( 'All products deleted successfully!', 'bulk-product-remover' ); ?>
					<a href="#" id="download_as_csv" class="button button-primary">
						<?php esc_html_e( 'Download Report', 'bulk-product-remover' ); ?>
					</a>
				</h2>
			</div>

			<div id="product-deleted-result"></div>
		</div>
		<?php
	}

	/**
	 * Handle bulk delete AJAX request.
	 *
	 * @since 1.0.0
	 */
	public function bprbykml_bulk_remove_delete_func() {
		check_ajax_referer( 'bulk_product_remover_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$product_ids = isset( $_POST['productIDs'] ) ? array_map( 'absint', $_POST['productIDs'] ) : array();
		
		if ( empty( $product_ids ) ) {
			wp_send_json_error( 'No product IDs provided' );
		}

		$deleted_products = array();
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$product_name = $product->get_name();
				$product_sku  = $product->get_sku();
				$product_url  = get_permalink( $product_id );
				
				if ( wp_delete_post( $product_id, true ) ) {
					$deleted_products[] = sprintf(
						'%d, %s, %s, %s',
						$product_id,
						esc_html( $product_name ),
						esc_url( $product_url ),
						esc_html( $product_sku )
					);
				}
			}
		}

		$message = sprintf(
			/* translators: %d: The number of products that were successfully deleted */
			_n(
				'Successfully deleted %d product',
				'Successfully deleted %d products',
				count( $deleted_products ),
				'bulk-product-remover'
			),
			count( $deleted_products )
		);

		wp_send_json_success(
			array(
				'message'          => $message,
				'deleted_products' => $deleted_products,
			)
		);
	}
}

// Initialize the plugin.
new BPRbyKML_BulkProductRemover(); 
=== Bulk Product Remover ===
Contributors: kamal15
Tags: woocommerce, bulk-delete, products, admin tools, csv
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Efficiently remove multiple WooCommerce products at once using CSV file upload, streamlining your product management workflow.

== Description ==

**Bulk Product Remover** is a powerful and user-friendly WordPress plugin that allows you to efficiently remove multiple WooCommerce products at once using a simple CSV file upload. It's designed to save time when managing large product catalogs by providing a quick and secure way to remove products in bulk.

== Installation ==

1. Upload the `bulk-product-remover` folder to your `/wp-content/plugins/` directory or install the plugin via the WordPress plugin directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Once activated, go to the **Products** section and you'll see a new submenu called **Bulk Remover**.
4. Upload your CSV file containing product IDs and click "Delete Products" to remove them.

== How to Use ==

1. After activation, navigate to **Products** > **Bulk Remover** in your WordPress admin menu.
2. Prepare a CSV file with product IDs in the first column.
3. Upload the CSV file using the file input field.
4. Click the **Delete Products** button to start the deletion process.
5. Monitor the progress bar to track the deletion status.
6. Once complete, you can download a report of deleted products.

Note: Product ID must match to delete the products else you will get bank csv file.

== Features ==

- **CSV File Upload**: Easily upload a CSV file containing product IDs for bulk deletion.
- **Progress Tracking**: Real-time progress bar showing deletion status.
- **Detailed Reports**: Download a CSV report of deleted products with their details.
- **Security**: Built-in security measures including nonce verification and file validation.
- **User Permissions**: Only users with WooCommerce management capabilities can use the plugin.
- **Error Handling**: Comprehensive error handling for file uploads and product deletion.
- **Multilingual Support**: Easily translate the plugin for various languages.

== Frequently Asked Questions ==

= What format should my CSV file be in? =
Your CSV file should contain product IDs in the first column. The first row can be a header row with "id" as the column name.

= Is there a limit to how many products I can delete at once? =
The plugin can handle large numbers of products, but it's recommended to process them in batches of 100-200 for optimal performance.

= What happens if a product ID in the CSV doesn't exist? =
The plugin will skip non-existent product IDs and continue with the deletion process for valid products.

= Can I get a report of which products were deleted? =
Yes, after the deletion process is complete, you can download a CSV report containing details of all deleted products.

= Is the plugin secure? =
Yes, the plugin includes multiple security measures:
- Nonce verification for all actions
- File type validation
- File size limits
- User capability checks
- Input sanitization and validation

== Changelog ==

= 1.0.0 =
* Initial release with core features:
  - CSV file upload for product deletion
  - Progress tracking
  - Deletion reports
  - Security measures

== Screenshots ==
1. Plugin activation screen.
2. Bulk removal form with file upload.
3. Progress tracking interface.
4. Deletion report download option.

== License ==

This plugin is released under the GPL-2.0 license. You are free to modify and redistribute it under the terms of this license.

== Contact ==
For support or inquiries, email: virdikamal909@gmail.com 
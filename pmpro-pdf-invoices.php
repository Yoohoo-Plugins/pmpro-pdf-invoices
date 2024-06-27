<?php
/**
 * Plugin Name: Paid Memberships Pro - PDF Invoices
 * Description: Generates PDF Invoices for Paid Memberships Pro Orders.
 * Plugin URI: https://yoohooplugins.com/plugins/paid-memberships-pro-pdf-invoices/
 * Author: Yoohoo Plugins
 * Author URI: https://yoohooplugins.com
 * Version: 1.22.1
 * License: GPL2 or later
 * Tested up to: 6.2
 * Requires PHP: 7.2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pmpro-pdf-invoices
 * Domain Path: /languages
 * Network: false
 *
 * Paid Memberships Pro - PDF Invoices is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Paid Memberships Pro - PDF Invoices is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Paid Memberships Pro - PDF Invoices. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

defined( 'ABSPATH' ) or exit;

/**
 * Include update class for automatic updates.
 */
if ( ! defined( 'YOOHOO_STORE' ) ) {
	define( 'YOOHOO_STORE', 'https://yoohooplugins.com/edd-sl-api/' );
}
define( 'PMPRO_PDF_PLUGIN_ID', 2117 );
define( 'PMPRO_PDF_VERSION', '1.22.1' );
define( 'PMPRO_PDF_DIR', dirname( __file__ ) );

define( 'PMPRO_PDF_LOGO_URL', 'PMPRO_PDF_LOGO_URL');
define( 'PMPRO_PDF_REWRITE_TOKEN', 'PMPRO_PDF_REWRITE_TOKEN');
define( 'PMPRO_PDF_ADMIN_EMAILS', 'PMPRO_PDF_ADMIN_EMAILS');

// Include the template editor page/functions
include PMPRO_PDF_DIR . '/includes/template-editor.php';

// Include license settings page.
include PMPRO_PDF_DIR . '/includes/general-settings.php';

function pmpropdf_init() {

	// Load text domain
	// Load plugin text domain
	load_plugin_textdomain( 'pmpro-pdf-invoices', false, dirname( plugin_basename( __FILE__ ) ) . '/languages'  );


	if ( isset( $_REQUEST['pmpropdf'] ) ) {
		// Include other files.
		include PMPRO_PDF_DIR . '/includes/download-pdf.php';
	}

	/** Check if the strict redirect is in place*/
	if(pmpropdf_check_rewrite_active()){
		//Silence in this case
	}

	pmpropdf_check_should_zip();
}
add_action( 'init', 'pmpropdf_init' );


function pmpropdf_settings_page() {
	add_options_page( 'Paid Memberships Pro PDF Invoice License Settings', 'PMPro PDF Invoice', 'manage_options', 'pmpro_pdf_invoices_license_key', 'pmpro_pdf_invoice_settings_page' );
}
add_action( 'admin_menu', 'pmpropdf_settings_page' );

/**
 * Class to handle automatic updates.
 */
if ( ! class_exists( 'PMPro_PDF_Invoice_Updater' ) ) {
	include( PMPRO_PDF_DIR . '/includes/class.pmpro-pdf-invoice-updater.php' );
}

$license_key = trim( get_option( 'pmpro_pdf_invoice_license_key' ) );

$edd_updater = new PMPro_PDF_Invoice_Updater( YOOHOO_STORE, __FILE__, array(
		'version' => PMPRO_PDF_VERSION,
		'license' => $license_key,
		'item_id' => PMPRO_PDF_PLUGIN_ID,
		'author' => 'Yoohoo Plugins',
		'url' => home_url()
	)
);

use Dompdf\Dompdf;
include( PMPRO_PDF_DIR . '/includes/dompdf/autoload.inc.php' );

/**
 * Hook into the PMPro Email Attachements hook
 * Get the last order for the user
 * Store the PDF into the uploads directory
 * Return new attachments array to the PMPro email attachment hook
*/
function pmpropdf_attach_pdf_email( $attachments, $email ) {
	$admin_emails = get_option(PMPRO_PDF_ADMIN_EMAILS, false);

	// Let's not send it to admins
	if( strpos( $email->template, "admin" ) !== false && empty($admin_emails)){
		return $attachments;
	}

	$email_templates = apply_filters( 'pmpropdf_pdf_included_email_templates', array( 'invoice', 'billable_invoice', 'check_pending', 'check_pending_reminder' ) );
	
	// If the email template isn't a checkout email or in the list of email templates, bail.
	if ( strpos( $email->template, "checkout_" ) === false && ! in_array( $email->template, $email_templates ) ) {
		return $attachments;
	}

	// Let developers decide if attach the pdf
	if ( ! apply_filters( 'pmpropdf_can_attach_pdf_email', true, $email ) ) {
		return $attachments;
	}

	// Make sure there is an order code available, otherwise get it from the user.
	if ( empty( $email->data['order_code'] ) ) {
		$user = get_user_by( "email", $email->data['user_email'] );
		$last_order = pmpropdf_get_last_order( $user->ID );
	} else {
		$order_code = $email->data['order_code'];
		$last_order = pmpropdf_get_order_by_code($order_code);
	}

	// Bail if order is empty / doesn't exist.
	// We do this early to avoid initializing the DomPDF library if it is unneeded
	if ( empty( $last_order[0] ) ) {
	 	return $attachments;
	}

	$order_data = $last_order[0];

	$path = pmpropdf_generate_pdf($order_data);
	if($path !== false){
		$attachments[] = $path;
	}

	return $attachments;

}
add_filter( 'pmpro_email_attachments', 'pmpropdf_attach_pdf_email', 10, 2 );


/**
 * Generate PDF invoice when an order is added.
 * @since 1.5
 */
function pmpropdf_added_order( $order ) {
	// Let developers decide if generate the pdf
	if ( apply_filters( 'pmpropdf_can_generate_pdf_on_added_order', true, $order ) ) {
		$last_order = pmpropdf_get_order_by_code( $order->code );

		// Bail if order is empty / doesn't exist.
		// We do this early to avoid initializing the DomPDF library if it is unneeded
		if ( empty( $last_order[0] ) ) {
			return;
		}

		$order_data = $last_order[0];
		pmpropdf_generate_pdf( $order_data );
	}
}
add_action( 'pmpro_added_order', 'pmpropdf_added_order' );

/**
 * Handles storage of PDF Invoice
 * Modular design allows it to be used in the primary pmpro_email_attachments_hook
 * As well as the batch processing tool
*/
function pmpropdf_generate_pdf($order_data, $return_dom_pdf = false){

	// Stop PDF from generating in certain cases.
	if ( ! apply_filters( 'pmpropdf_should_generate_pdf', true, $order_data ) ) {
		return;
	}

	$user = get_user_by('ID', $order_data->user_id);
	$order = new MemberOrder( $order_data->code );

	$dompdf = new Dompdf( apply_filters( 'pmpropdf_dompdf_args', array( 'enable_remote' => true ) ) );
	$body = pmpropdf_get_order_template_html();

	// Build the string for billing data.
	if ( ! empty( $order->billing->name ) ) {
		$billing_details = "<p><strong>" . __( 'Billing Details', 'pmpro-pdf-invoices' ) . "</strong></p>";
		$billing_details .= "<p>" . $order->billing->name . "<br>";
		$billing_details .= $order->billing->street . "<br>";
		$billing_details .= $order->billing->zip . " " . $order->billing->city . " (" . $order->billing->state . "), " . $order->billing->country . "<br>";
		$billing_details .= $order->billing->phone . "</p>";
	} else {
		$billing_details = '';
	}

	if ( !empty($_GET['sub_action'] && $_GET['sub_action'] == 'view_sample') ) {
		$date = date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) );
	} else {
		$date = date_i18n( get_option( 'date_format' ), $order->getTimestamp() );
	}	
	$gateway = pmpro_gateways();

	$payment_method = !empty( $order_data->gateway ) ? apply_filters( 'pmpro_pdf_gateway_string', $gateway[$order_data->gateway] ) : __( 'N/A', 'pmpro-pdf-invoices');

	$order_level_name = '';
	if(function_exists('pmpro_getLevel')){
		$order_level = pmpro_getLevel($order_data->membership_id);
		if(!empty($order_level) && !empty($order_level->name)){
			$order_level_name = $order_level->name;
		}
	}

	$logo_url = get_option(PMPRO_PDF_LOGO_URL, '');
	$logo_image = !empty($logo_url) ? "<img style='max-width:300px;' src='$logo_url' />" : '';

	// Items to replace.
	$replacements = array(
		'{{invoice_code}}' => $order_data->code ?: '',
		'{{user_email}}' => $user->data->user_email ?: '',
		'{{membership_level}}' => $order_level_name ?: '',
		'{{membership_description}}' => $order_level->description ?: '',
		'{{membership_level_confirmation_message}}' => $order_level->confirmation ?: '',
		'{{billing_address}}' => $billing_details ?: '',
		'{{payment_method}}' => $payment_method ?: '',
		'{{total}}' => pmpro_formatPrice($order_data->total) ?: '',
		'{{site}}' => get_bloginfo( 'sitename' ) ?: '',
		'{{site_url}}' => esc_url( get_site_url() ) ?: '',
		'{{subtotal}}' => pmpro_formatPrice( $order_data->subtotal ) ?: '',
		'{{tax}}' => pmpro_formatPrice($order_data->tax) ?: '',
		'{{ID}}' => $order_data->membership_id ?: '',
		'{{invoice_date}}' => $date ?: '',
		'{{logo_image}}' => $logo_image ?: '',
		'{{admin_email}}' => get_bloginfo( 'admin_email' )
	);

	//Additional replacements - Developer hook to add custom variable parse
	//Should use key-value pair array (assoc)
	$replacements = apply_filters('pmpro_pdf_invoice_custom_variables', $replacements, $user, $order_data );

	// Setup PDF Structure
	$body = str_replace(
		array_keys( $replacements ),
		array_values( $replacements ),
		$body
	);

	if( preg_match_all( '/(?<={{)(.*?)(?=}})/m', $body, $matches ) ) {

		foreach( $matches as $match_group ) {

			foreach( $match_group as $mg ) {

				$cleaned_up = strip_tags( $mg );

				$meta = get_user_meta( $order_data->user_id, $cleaned_up, true );

				$body = str_replace( '{{'.$cleaned_up.'}}', $meta, $body );
				
			}
			
		}

	}

	$dompdf->loadHtml( $body );

	$dompdf = apply_filters( 'pmpropdf_dompdf_before_render', $dompdf );

	$dompdf->render();

	// This allows calling functions to get access to the dompdf instance, instead of storing
	if($return_dom_pdf){
		return $dompdf;
	}

	$output = $dompdf->output();
	// Let's write this file to a directory now.

	$invoice_dir = pmpropdf_get_invoice_directory_or_url();
	$invoice_name = pmpropdf_generate_invoice_name($order_data->code);
	$path = $invoice_dir . $invoice_name;
	try{
		file_put_contents( $path, $output );
	} catch (Exception $ex){
		return false;
	}
		
	do_action( 'pmpropdf_generated_pdf_invoice', $order_data->id, $path );

	return $path;
}

/**
 * Generate and download a sample PDF without needing to checkout.
 * Uses sample data.
 * @since 1.10
 */
function pmpropdf_generate_sample_pdf(){
	if ( class_exists( 'MemberOrder' ) ) {
		/* Create a dummy order */
		$order = new MemberOrder();
		$order->get_test_order();

		/* Fill the missing details in the order, that are needed for PDF */
		$order->code = $order->getRandomCode();
		$order->total = $order->InitialPayment;
		$order->subtotal = $order->total;
		$order->tax = 0;

		/* Cross populate the billing object */
		if ( ! empty( $order->billing ) && is_object( $order->billing ) ) {
			foreach( $order->billing as $key => $value ) {
				$dynamiKey = "billing_{$key}";
				$order->{$dynamiKey} = $value;
			}
		}
		$dompdf = pmpropdf_generate_pdf( $order, true );

		$dompdf->stream( 'invoice_sample.pdf' );
		exit();
	}
}


/**
 * Generate the invoice name based on the invoice code.
 * @since 1.22
 */
function pmpropdf_admin_column_header( $columns ) {
	// Only load this for orders.
	if ( ! current_user_can( 'pmpro_orders' ) ) {
		return $columns;
	}

	$columns['pmpro_pdf'] = __( 'Invoice PDF', 'pmpro-pdf-invoices' );
	return $columns;
}
add_filter( 'pmpro_manage_orderslist_columns', 'pmpropdf_admin_column_header' );


function my_pmpro_pdf_column_stuff( $column, $order_id) {
	if ( $column != 'pmpro_pdf' ) {
		return;
	}

	$order = new MemberOrder( $order_id );

	if ( ! current_user_can( 'pmpro_orders' ) ) {
		return;
	}

	if ( file_exists( pmpropdf_get_invoice_directory_or_url() . pmpropdf_generate_invoice_name($order->code) ) ){
		echo '<a href="' . esc_url( admin_url( '?pmpropdf=' . $order->code ) ). '" target="_blank">' . esc_html__( 'Download PDF', 'pmpro-pdf-invoices' ) .'</a>';
	} else {
		echo '<a href="javascript:void(0)" id="pmpro-pdf-generate_' . esc_attr( $order->code ) . '" class="pmpro-pdf-generate" order_code="' . esc_attr( $order->code ) . '">' . esc_html__( 'Generate PDF', 'pmpro-pdf-invoices' ) . '</a>';
	}

}
add_action( 'pmpro_manage_orderlist_custom_column', 'my_pmpro_pdf_column_stuff', 10, 2 );

/**
 * Helper function to get member order when class not available.
 * Revisit this at a later stage.
 */
function pmpropdf_get_last_order( $user_id ) {
	global $wpdb;

	$user_id = intval( $user_id );

	$order = $wpdb->get_results("SELECT * FROM $wpdb->pmpro_membership_orders WHERE user_id = " . esc_sql( $user_id ) . " AND status NOT IN('cancelled') ORDER BY timestamp DESC LIMIT 1");

	return $order;
}

/**
 * Get specific order by its order code
 * Proxy of: pmpropdf_get_last_order
 */
function pmpropdf_get_order_by_code( $order_code ) {
	global $wpdb;
	$order = $wpdb->get_results("SELECT * FROM $wpdb->pmpro_membership_orders WHERE code = '" . esc_sql( $order_code ) . "' LIMIT 1");

	return $order;
}

/**
 * Returns the invoice storage directory
 * Creates it if it does no exist
*/
function pmpropdf_get_invoice_directory_or_url($url = false){
	$upload_dir = wp_upload_dir();
	$invoice_dir = ($url ? $upload_dir['baseurl'] : $upload_dir['basedir'] ) . '/pmpro-invoices/';

	if($url == false){
		if ( !file_exists( $invoice_dir ) ) {
			mkdir( $invoice_dir, 0777, true );
		}
	}

	return $invoice_dir;
}

/**
 * Generates an invoice name from an order code
*/
function pmpropdf_generate_invoice_name($order_code){
	$invoice_prefix = apply_filters( 'pmpro_pdf_invoice_prefix', 'INV' );
	$invoice_name = $invoice_prefix . $order_code . ".pdf";
	
	return apply_filters( 'pmpro_pdf_invoice_name', $invoice_name, $order_code );
}

/**
 * Get batch of orders
 * Return the ordders for loop processing
*/
function pmpropdf_get_order_batch($batch_size = 100, $batch_no = 0){
	global $wpdb;

	$offset = $batch_no * $batch_size;
	$batch_sql = "SELECT * FROM $wpdb->pmpro_membership_orders ORDER BY timestamp ASC LIMIT $batch_size OFFSET $offset";
	$batch = $wpdb->get_results($batch_sql);

	return $batch;
}

/**
 * Process a batch of orders
 * Check if the current order has a PDF generated
 * Generate one if this is not the case
 * Skip it if we have this invoice already created
*/
function pmpropdf_process_batch($batch_size = 100, $batch_no = 0){
	$output_array = array(
		'skipped' => 0,
		'created' => 0,
		'batch_no' => $batch_no,
		'batch_count' => 0
	);
	try{
		$invoice_dir = pmpropdf_get_invoice_directory_or_url();

		$batch = pmpropdf_get_order_batch($batch_size, $batch_no);
		foreach ($batch as $order_data) {
			$invoice_name = pmpropdf_generate_invoice_name($order_data->code);

			if(file_exists($invoice_dir . $invoice_name)){
				$output_array['skipped'] += 1;
			} else {
				$path = pmpropdf_generate_pdf($order_data);
				$output_array['created'] += 1;
			}
		}

		$output_array['batch_count'] = count($batch);
	} catch (Exception $ex){
		$output_array['error'] = "An unexpected error occurred, we were not able to complete PDF generation";
	} catch (Error $err){
		$output_array['error'] = "An unexpected error occurred, we were not able to complete PDF generation";
	}

	return $output_array;
}

/**
 * AJAX Loop
*/
function pmpropdf_batch_processor() {
	if(defined('DOING_AJAX') && DOING_AJAX){
		$batch_size = !empty($_POST['batch_size']) ? intval($_POST['batch_size']) : 100;
		$batch_no = !empty($_POST['batch_no']) ? intval($_POST['batch_no']) : 0;
		$batch_output = pmpropdf_process_batch($batch_size, $batch_no);

		echo json_encode($batch_output);
	}
	die();
}
add_action( 'wp_ajax_pmpropdf_batch_processor', 'pmpropdf_batch_processor' );


/**
 * Download PDF invoice.
 * @since 1.2
 */
function pmpropdf_download_invoice( $order_code ) {

	if( file_exists( pmpropdf_get_invoice_directory_or_url() . pmpropdf_generate_invoice_name( $order_code ) ) ) {
		$invoice_name = pmpropdf_generate_invoice_name( $order_code );
		$download_url = esc_url( pmpropdf_get_invoice_directory_or_url( true ) . $invoice_name );
		$access_key = pmpropdf_get_rewrite_token();

		$download_url .= "?access=$access_key";

		header('Content-type: application/pdf');
		header('Content-Disposition: attachment; filename="'.$invoice_name.'"');
		readfile($download_url);

		/**
		 * This is removed to support the force htaccess redirect
		 * Auto download is now automatically handled in the htaccess file
		 *
		 * -------------
		 * header( 'Content-Description: File Transfer' );
		 * header( 'Content-Type: application/octet-stream' );
		 * header( 'Content-Disposition: attachment; filename="'.basename( $invoice_name ).'"' );
		 * header( 'Expires: 0' );
		 * header( 'Cache-Control: must-revalidate' );
		 * header( 'Pragma: public' );
		 * header( 'Content-Length: ' . filesize( $download_url ) );
		 * flush(); // Flush system output buffer
		 * readfile( $download_url );
		 * -------------
		*/

		exit;
	  }

}

/**
 * Check if the rewrite .htaccess file is in place
 * If not, create it.
*/
function pmpropdf_check_rewrite_active(){
	$invoice_dir = pmpropdf_get_invoice_directory_or_url();
	$htaccess_location = $invoice_dir . ".htaccess";
	if(!file_exists($htaccess_location)){
		try{
			$access_key = pmpropdf_get_rewrite_token();
			$server_ip = $_SERVER['REMOTE_ADDR'];
			$htaccess = fopen($htaccess_location, "w");

			$content = "<IfModule mod_rewrite.c>\n";
 			$content .= "RewriteEngine On\n";
 			$content .= "RewriteCond %{QUERY_STRING} !access=$access_key\n";
			$content .= "RewriteRule (.*) - [F]\n";
			$content .= "</IfModule>\n\n";

			$content .= "<FilesMatch \"\.(pdf)$\">\n";
			$content .= "ForceType application/octet-stream\n";
			$content .= "Header set Content-Disposition attachment\n";
			$content .= "</FilesMatch>";

			fwrite($htaccess, $content);
			fclose($htaccess);

			return true;
		} catch (Exception $e){
			return false;
		}
	}
	return true;
}

/**
 * Unlinks/deletes the current rewrite .htaccess files
 * This will then be regenerated in the next loop
*/
function pmpropdf_remove_rewrite_for_regen(){
	$invoice_dir = pmpropdf_get_invoice_directory_or_url();
	$htaccess_location = $invoice_dir . ".htaccess";
	if(file_exists($htaccess_location)){
		unlink($htaccess_location);
	}
}

/**
 * Get the rewrite access token
*/
function pmpropdf_get_rewrite_token(){
	$access_token = get_option(PMPRO_PDF_REWRITE_TOKEN);
	if($access_token === false){
		$access_token = bin2hex(openssl_random_pseudo_bytes(16));
		update_option(PMPRO_PDF_REWRITE_TOKEN, $access_token);
	}

	return $access_token;
}

/**
 * Shortcode handler for the invoice list based on current user
 */
function pmpropdf_download_list_shortcode_handler(){
	global $wpdb, $current_user;

	// This is if it's shown on a page that doesn't require login.
	if ( empty( $current_user ) && apply_filters( 'pmpropdf_show_logged_out_shortcode_message', false ) ) {
		return esc_html__( 'Please login to view your invoices.', 'pmpro-pdf-invoices' );
	}

	$limit = apply_filters( 'pmpropdf_invoice_table_limit', 15 );

	$invoices = $wpdb->get_results("
		SELECT *, UNIX_TIMESTAMP(timestamp) as timestamp
		FROM $wpdb->pmpro_membership_orders
		WHERE user_id = '$current_user->ID'
		AND status NOT
		IN('review', 'token', 'error')
		ORDER BY timestamp DESC LIMIT " . $limit
	);

	if(!empty($invoices)){
		foreach ($invoices as $key => $invoice) {
			$invoice_id = $invoice->id;
			$invoice = new MemberOrder;
			$invoice->getMemberOrderByID($invoice_id);
			$invoice->getMembershipLevel();

			$membership_level = $invoice->membership_level->name;

			if ( file_exists( pmpropdf_get_invoice_directory_or_url() . pmpropdf_generate_invoice_name($invoice->code) ) ){
				$content .= '<tr>';
				$content .=		'<td>' . date_i18n(get_option("date_format"), $invoice->timestamp) . '</td>';
				$content .=		'<td>' . $membership_level . '</td>';
				$content .=		'<td>' . pmpro_formatPrice($invoice->total) . '</td>';
				$content .= 	'<td><a href="' . esc_url( admin_url( '?pmpropdf=' . $invoice->code ) ). '">' . pmpropdf_generate_invoice_name( $invoice->code ) .'</a></td>';
				$content .= '</tr>';
			}
		}
	}

	if(!empty($content)){
		$table_content = "<h3>" . __("PDF Invoices", 'pmpro-pdf-invoices' ) . "</h3>";
		$table_content .= "<table width='100%'' cellpadding='0' cellspacing='0' border='0'>";
		$table_content .= 	"<thead>";
		$table_content .= 		"<tr>";
		$table_content .= 			"<th>" . __("Date", 'paid-memberships-pro' ) . "</th>";
		$table_content .= 			"<th>" . __("Level", 'paid-memberships-pro' ) . "</th>";
		$table_content .= 			"<th>" . __("Amount", 'paid-memberships-pro' ) . "</th>";
		$table_content .= 			"<th>" . __("Download", 'paid-memberships-pro' ) . "</th>";
		$table_content .= 		"</tr>";
		$table_content .= 	"</thead>";
		$table_content .= 	"<tbody>";
		$table_content .= 		$content;
		$table_content .= 	"</tbody>";
		$table_content .= "</table>";

		return $table_content;

	} else {
		$content = "<h3>" . __("PDF Invoices", 'pmpro-pdf-invoices' ) . "</h3>";
		$content .= "<div><em>" . __("No PDF invoices found...", 'pmpro-pdf-invoices' ) . "</em></div>";
	}

	return $content;
	}
add_shortcode('pmpropdf_download_list', 'pmpropdf_download_list_shortcode_handler');

/**
 * Shortcode handler for the download all as ZIP file
*/
function pmpropdf_download_all_zip_shortcode_handler( $atts ){
	$title = __("Download all PDF's as ZIP", 'pmpro-pdf-invoices');
	if(!empty($atts['title'])){
		$title = sanitize_text_field($atts['title']);
	}

	if(class_exists('ZipArchive') && is_user_logged_in() ) {
		global $wpdb, $current_user;

		$invoices = $wpdb->get_results("
			SELECT *, UNIX_TIMESTAMP(timestamp) as timestamp
			FROM $wpdb->pmpro_membership_orders
			WHERE user_id = '$current_user->ID'
			AND status NOT
			IN('review', 'token', 'error')
			ORDER BY timestamp DESC"
		);

		if(!empty($invoices) && count($invoices) > 0){
			return "<a href='?pmpro_pdf_invoices_action=download_zip' target='_BLANK'>$title</a>";
		}
	}
	return '';

}
add_shortcode('pmpropdf_download_all_zip', 'pmpropdf_download_all_zip_shortcode_handler');

/**
 * Checks if we received a request to perform a zip of the current users documents
 *
 * If so, this will go ahead and generate that plus download it
*/
function pmpropdf_check_should_zip(){
	if(!empty($_REQUEST['pmpro_pdf_invoices_action']) && $_REQUEST['pmpro_pdf_invoices_action'] === 'download_zip'){
		if(class_exists('ZipArchive')){
			if(class_exists('ZipArchive') && function_exists('pmpro_hasMembershipLevel') && pmpro_hasMembershipLevel()){
				global $wpdb, $current_user;

				$invoices = $wpdb->get_results("
					SELECT *, UNIX_TIMESTAMP(timestamp) as timestamp
					FROM $wpdb->pmpro_membership_orders
					WHERE user_id = '$current_user->ID'
					AND status NOT
					IN('review', 'token', 'error')
					ORDER BY timestamp DESC"
				);

				if(!empty($invoices) && count($invoices) > 0){
					$files = array();
					foreach ($invoices as $key => $invoice) {
						if ( file_exists( pmpropdf_get_invoice_directory_or_url() . pmpropdf_generate_invoice_name($invoice->code) ) ){
							$files[] = pmpropdf_get_invoice_directory_or_url() . pmpropdf_generate_invoice_name($invoice->code);
						}
					}


					$archive_name = 'invoices_' . time() . '.zip';
					$archive = new ZipArchive;
					if($archive->open($archive_name, ZipArchive::CREATE) === TRUE){
						foreach ($files as $file) {
							$archive->addFromString(basename($file), file_get_contents($file));
						}
						$archive->close();

						/** Send the headers and file data to the browser */
						header('Content-Type: application/zip');
						header('Content-disposition: attachment; filename='.$archive_name);
						header('Content-Length: ' . filesize($archive_name));
						readfile($archive_name);

						@unlink($archive_name);
					}
				}
			}
		}
	} else if (!empty($_GET['page']) && !empty($_GET['sub_action'])){
		if($_GET['page'] === 'pmpro_pdf_invoices_license_key' && $_GET['sub_action'] === 'download_zip_archive'){
			/* This is an admin download, processes here for the sake of header output */
			if(current_user_can('administrator') && class_exists('ZipArchive')){
				$invoice_dir = pmpropdf_get_invoice_directory_or_url();
				if(file_exists($invoice_dir)){
					$files = scandir($invoice_dir); 
					$pdfs = array();
					foreach ($files as $file) {
						if(strpos($file, '.pdf') !== FALSE){
							$pdfs[] = pmpropdf_get_invoice_directory_or_url() . $file;
						}
					}
					
					if(!empty($pdfs)){
						$archive_name = 'invoices_archive_' . time() . '.zip';
						$archive = new ZipArchive;
						if($archive->open($archive_name, ZipArchive::CREATE) === TRUE){
							foreach ($pdfs as $path) {
								$archive->addFromString(basename($path), file_get_contents($path));
							}
							$archive->close();

							/** Send the headers and file data to the browser */
							header('Content-Type: application/zip');
							header('Content-disposition: attachment; filename='.$archive_name);
							header('Content-Length: ' . filesize($archive_name));
							readfile($archive_name);

							@unlink($archive_name);
						}
					}
				}
			}
		}
	}
}


function pmpropdf_footer_note ($footnote){
	if(!empty($_GET['page']) && strpos($_GET['page'], 'pmpro_pdf_invoices') !== FALSE){
		$footnote .= "<em> || PMPro PDF Invoices (v" . PMPRO_PDF_VERSION . ") by <a href='https://yoohooplugins.com/' target='_blank'>Yoohoo Plugins</a>.</em>";
	}
	return $footnote;
}
 
add_filter('admin_footer_text', 'pmpropdf_footer_note', 10, 1);


function pmpropdf_nginx_notice () {

	if ( empty( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], 'pmpro' ) === false ) {
		return;
	}

	$user_id = get_current_user_id();

	if( current_user_can( 'manage_options' ) && 
		intval( get_user_meta( $user_id, 'pmpropdf_nginx_dismissed', true ) ) == false &&
		( !empty( $_SERVER['SERVER_SOFTWARE'] ) && strpos( $_SERVER['SERVER_SOFTWARE'], 'nginx' ) !== false ) 
	){

		$upload_dir = wp_upload_dir();

		$baseurl = str_replace( site_url(), '', $upload_dir['baseurl'] );

		$invoice_dir = $baseurl . '/pmpro-invoices/';

		$access_key = pmpropdf_get_rewrite_token();
	
		?>
		<div class="updated">
			<h2><?php _e('Paid Memberships Pro - PDF Invoices - Nginx Detected', 'pmpro-pdf-invoices'); ?></h2>
			<p><?php _e('We detected that your installation is running on Nginx. To protect generated invoices that are stored on your web server, the following Nginx rule should be added to your Nginx WordPress installation config file.', 'pmpro-pdf-invoices' ); ?></p>
			<p><code>
				location <?php echo $invoice_dir; ?> {
					if ($query_string  !~ "access=<?php echo $access_key; ?>"){
						return 403;
				  	}
				}			
			</code></p>
			<p><a class='button button-primary' id="pmpropdf_nginx_prompt" href="<?php echo admin_url( '?pmpropdf_nginx=dismiss' ); ?>"><?php _e("I've Added The Nginx Rule", "pmpro-pdf-invoices"); ?></a></p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'pmpropdf_nginx_notice' );

function pmpropdf_dismiss_nginx_notice(){

	if( !empty( $_REQUEST['pmpropdf_nginx'] ) && $_REQUEST['pmpropdf_nginx'] == 'dismiss' ){
		if( current_user_can( 'manage_options' ) ){
			$user_id = get_current_user_id();
			update_user_meta( $user_id, 'pmpropdf_nginx_dismissed', 1 );
		}
	}

}
add_action( 'admin_init', 'pmpropdf_dismiss_nginx_notice' );

/**
 * Get the template content
 *
 * Helper function to standardize body content retrieval, also should help with any automated migration
 *
 * @since 1.9
*/
function pmpropdf_get_order_template_html(){
	pmpropdf_migrate_custom_template(); 
	
	$path = pmpropdf_get_order_template_path();
	if(!empty($path) && file_exists($path)){
		return file_get_contents($path);		
	}
	return "";
}

/**
 * Get the template path
 *
 * Helper function to standardize path generation and testing for the template file
 *
 * @since 1.9
*/
function pmpropdf_get_order_template_path(){
	$path = PMPRO_PDF_DIR . '/templates/order.html';
	$upload_dir = wp_upload_dir();
	if(!empty($upload_dir) && !empty($upload_dir['basedir'])){
		$template_dir = $upload_dir['basedir'] . '/pmpro-invoice-templates/order.html';
		if(file_exists($template_dir)){
			$path = $template_dir;
		}
	}
	return apply_filters("pmpro_order_template_path", $path);
}

/**
 * Helper for migrating existing custom templates to the uploads directory
 *
 * This automated process should help prevent any template data loss
 *
 * @since 1.9
*/
function pmpropdf_migrate_custom_template(){
	$legacy_path = get_stylesheet_directory() . "/pmpro-pdf-invoices/order.html";
	if(file_exists($legacy_path)){
		$legacy_content = file_get_contents($legacy_path);

		$upload_dir = wp_upload_dir();
		$template_dir = $upload_dir['basedir'] . '/pmpro-invoice-templates/';

		if(!file_exists( $template_dir )){
			mkdir( $template_dir, 0777, true );
		}

		if(!file_exists($template_dir . 'order.html')){
			try{
				file_put_contents($template_dir . 'order.html', $legacy_content);
			} catch (Exception $ex){
				//Silence
			}
		}
		
		@unlink($legacy_path);
	} 
}

/**
 * Regenerate PDF invoice when an order is updated
 *
 * @since 1.9
 */
function pmpropdf_updated_order( $order ) {
	// Let developers decide if generate the pdf
	if ( apply_filters( 'pmpropdf_can_regenerate_pdf_on_added_order', true, $order ) ) {
		$invoice_dir = pmpropdf_get_invoice_directory_or_url();
		$invoice_name = pmpropdf_generate_invoice_name($order->code);

		if(file_exists($invoice_dir . $invoice_name)){
			unlink($invoice_dir . $invoice_name);
		}

		$path = pmpropdf_generate_pdf($order);
	}
}
add_action( 'pmpro_updated_order', 'pmpropdf_updated_order', 99, 1);

/**
 * Function to enqueue scripts and styles on PMPro pages.
 * @since 1.2
 */
function pmpropdf_enqueue_scripts_styles() {
	// Only enqueue on PMPro prefixed admin pages.
	if ( ! isset( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], 'pmpro' ) !== 0 ) {
		return;
	}

	// Enqueue scripts.
	wp_register_script( 'pmpro-pdf-admin', plugins_url( '/includes/js/admin.js', __FILE__ ), array( 'jquery' ), PMPRO_PDF_VERSION );

	wp_localize_script( 'pmpro-pdf-admin', 'pmpro_pdf_admin', array( 
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'admin_url' => esc_url( admin_url( '?pmpropdf=') ),
		'download_text' => __( 'Download PDF', 'pmpro-pdf-invoices' ),
		'loading_gif' => plugins_url( '/includes/images/pmpropdf-loading.gif', __FILE__ ),
		'nonce' => wp_create_nonce( 'pmpro-pdf-invoices-single' ),
		)  
	);

	wp_enqueue_script( 'pmpro-pdf-admin' );
	wp_enqueue_style( 'pmpro-pdf-admin-css', plugins_url( '/includes/css/admin.css', __FILE__ ), array(), PMPRO_PDF_VERSION );
}
add_action( 'admin_enqueue_scripts', 'pmpropdf_enqueue_scripts_styles' );

/**
 * Ajax generate single PDF Invoice single
 * @since 1.2
 */
function pmpropdf_ajax_generate_pdf_invoice() {
	$order_code = sanitize_text_field( $_REQUEST['order_code'] );
	$last_order = pmpropdf_get_order_by_code($order_code);

	// check if nonce is valid
	if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'pmpro-pdf-invoices-single' ) ) {
		wp_die( __( 'Nonce is invalid', 'pmpro-pdf-invoices' ) );
	}

	// Bail if order is empty / doesn't exist.
	// We do this early to avoid initializing the DomPDF library if it is unneeded
	if ( empty( $last_order[0] ) ) {
	 	return $attachments;
	}

	$order_data = $last_order[0];
	pmpropdf_generate_pdf($order_data);
	die();
}
add_action( 'wp_ajax_pmpropdf_ajax_generate_pdf_invoice', 'pmpropdf_ajax_generate_pdf_invoice' );


// License Checks Below
/**
 * Show a notice for no license keys in the plugin settings
 *
 * @param [type] $plugin_file
 * @param [type] $plugin_data
 * @param [type] $status
 * @return void
 */
function pmpropdf_after_plugin_row( $plugin_file, $plugin_data, $status ) {
	
	// If there's already an update just bail, don't show the bump.
	if ( ! empty( $plugin_data ) && ! empty( $plugin_data['new_version'] ) && $plugin_data['new_version'] ) {
		return;
	}

	$license_key = trim( get_option( 'pmpro_pdf_invoice_license_key' ) );
	$license_valid = false;

	if ( ! empty( $license_key ) ) {
		// License could be valid, let's try check the status.
		$license_status = get_option( 'pmpro_pdf_invoice_license_status', true );

		if ( $license_status !== 'valid' ) {
			$license_valid = false;
		} else {
			$license_valid = true;
		}

	} else {
		$license_valid = false;
	}

	// If the license isn't valid.
	if ( ! $license_valid && current_user_can( 'update_plugins' ) ) {
	?>
		<tr class="plugin-update-tr active" id="pmpropdf-plugin-update" style="border-top:none">
			<td class="plugin-update colspanchange" colspan="4">
				<div class="update-message notice inline notice-warning notice-alt">
					<p><?php 
					echo sprintf( __( '%s your copy of PMPro PDF Invoices to receive access to automatic upgrades and support. Need a license key? %s', 'pmpro-pdf-invoices' ), '<a href="' . admin_url( 'options-general.php?page=pmpro_pdf_invoices_license_key#tab_0' ) . '"> ' . __( 'Register', 'pmpro-pdf-invoices' ) . '</a>', '<a href="https://yoohooplugins.com/plugins/paid-memberships-pro-pdf-invoices/" target="_blank" rel="nofollow">' . __( 'Purchase one now.', 'pmpro-pdf-invoices' ) . '</a>' ); 
					?></p>
				</div>
			</td>
		</tr>
	<script type='text/javascript'> 
		jQuery('#pmpropdf-plugin-update').prev('tr').addClass('update'); 
	</script>
	<?php
	}
}
add_action( 'after_plugin_row_pmpro-pdf-invoices/pmpro-pdf-invoices.php', 'pmpropdf_after_plugin_row', 10, 3 );

/** Helper function to see if PDF license key is active */
function pmpropdf_is_license_active(){
	$license_key = trim( get_option( 'pmpro_pdf_invoice_license_key' ) );
	$license_valid = false;

	// license cache
	$license_valid = get_transient( 'pmpro_pdf_invoice_license_valid' );

	// Return this if there is a transient already.
	if ( $license_valid ) {
		return $license_valid;
	}

	// Check if it's still valid or not.
	if ( ! empty( $license_key ) ) {
		$license_valid = pmpropdf_check_license_valid_api( $license_key );
	} else {
		$license_valid = false;
	}

	// Cache the license status for 1 week and check again to save our own resources.
	set_transient( 'pmpro_pdf_invoice_license_valid', $license_valid, 7 * DAY_IN_SECONDS );

	return $license_valid;
}

function pmpropdf_show_no_license_warning() {

	// Show on all PMPro pages.
	if ( empty( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], 'pmpro' ) === false ) {
		return;
	}

	$license_valid = pmpropdf_is_license_active();

	if ( ! $license_valid ) {
		?>
		<div class="notice pmpropdf-notice-error">
			<p><?php 
			echo sprintf( __( '%s your copy of PMPro PDF Invoices to receive access to automatic upgrades and support. Need a license key? %s', 'pmpro-pdf-invoices' ), '<a href="' . admin_url( 'options-general.php?page=pmpro_pdf_invoices_license_key#tab_0' ) . '"> ' . __( 'Register', 'pmpro-pdf-invoices' ) . '</a>', '<a href="https://yoohooplugins.com/plugins/paid-memberships-pro-pdf-invoices/" target="_blank" rel="nofollow">' . __( 'Purchase one now.', 'pmpro-pdf-invoices' ) . '</a>' ); 
			?></p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'pmpropdf_show_no_license_warning' );

/**
 * Helper function to ping our license server to make sure the license key is still valid or not.
 *
 * @param string $license_key
 * @return bool $valid Whether or not the license checker is valid (License server).
 */
function pmpropdf_check_license_valid_api( $license_key ) {
	$api_params = array(
			'edd_action' => 'check_license',
			'license'    => $license_key,
			'item_id'    => PMPRO_PDF_PLUGIN_ID, // The ID of the item in EDD
			'url'        => home_url()
		);

	// Call the custom API.
	$response = wp_remote_post( YOOHOO_STORE, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

	// Make sure the response came back okay.
	if ( is_wp_error( $response ) ) {
		return false;
	}

	// Decode the license data.
	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	if ( $license_data->license == 'valid' ) {
		update_option( 'pmpro_pdf_invoice_license_status', 'valid' );
		return true;
	} else {
		update_option( 'pmpro_pdf_invoice_license_status', false );
		return false;
	}
}
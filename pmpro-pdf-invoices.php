<?php
/**
 * Plugin Name: Paid Memberships Pro - PDF Invoices
 * Description: Generates PDF Invoices for Paid Memberships Pro Orders.
 * Plugin URI: https://yoohooplugins.com/plugins/pmpro-pdf-invoices/
 * Author: Yoohoo Plugins
 * Author URI: https://yoohooplugins.com
 * Version: 1.0
 * License: GPL2 or later
 * Tested up to: 5.0
 * Requires PHP: 5.6
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pmpro-pdf-invoices
 * Domain Path: languages
 * Network: false
 *
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
define( 'YOOHOO_STORE', 'https://yoohooplugins.com/edd-sl-api/' );
define( 'YH_PLUGIN_ID', 2117 );
define( 'PMPRO_PDF_VERSION', '1.0' );
define( 'PMPRO_PDF_DIR', dirname( __file__ ) );

// Include license settings page.
include PMPRO_PDF_DIR . '/includes/license-settings.php';

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
		'item_id' => YH_PLUGIN_ID,
		'author' => 'Yoohoo Plugins',
		'url' => home_url()
	)
);

use Dompdf\Dompdf;
include( PMPRO_PDF_DIR . '/includes/dompdf/autoload.inc.php' );

// Generate PDF invoice and email it to user.
function pmpropdf_attach_pdf_email( $attachments, $email ) {

	// let's not send it to admins and only with checkout emails.
	if ( strpos( $email->template, "checkout_" ) !== false && strpos( $email->template, "admin" ) !== false ) {
		return $attachments;
	}

	$user = get_user_by( "email", $email->data['user_email'] );

	$dompdf = new Dompdf( array( 'enable_remote' => true ) );


	$custom_dir = get_stylesheet_directory() . "/pmpro-pdf-invoices/order.html";

	if ( file_exists( $custom_dir ) ) {
		$body = file_get_contents( $custom_dir );
	} else {
		$body = file_get_contents( PMPRO_PDF_DIR . '/templates/order.html' );

	}
	
	// items to replace.
	$replace = array( "{{invoice_code}}", "{{user_email}}", '{{membership_level}}', '{{billing_address}}', "{{payment_method}}", "{{total}}", "{{site}}", "{{site_url}}", "{{subtotal}}", "{{tax}}", "{{ID}}", "{{invoice_date}}" );


	// get values from order.
	$last_order = pmpropdf_get_last_order( $user->ID );
	
	// bail if order is empty / doesn't exist.
	if ( empty( $last_order[0] ) ) {
	 	return $attachments;
	}

	$order_data = $last_order[0];

	// Build the string for billing data.
	if ( ! empty( $order_data->billing_name ) ) {
		$billing_details = "<p><strong>" . __( 'Billing Details', 'pmpro-pdf-invoices' ) . "</strong></p>";
		$billing_details .= "<p>" . $order_data->billing_name . "<br/>";
		$billing_details .=  $order_data->billing_street . "<br/>";
		$billing_details .= $order_data->billing_city . "<br/>";
		$billing_details .= $order_data->billing_state . "<br/>";
		$billing_details .= $order_data->billing_country . "<br/>";
		$billing_details .= $order_data->billing_phone . "</p>";
	} else {
		$billing_details = '';
	}

	$date = new DateTime( $order_data->timestamp );
	$date = $date->format( "Y-m-d" );

	$payment_method = !empty( $order_data->gateway ) ? $order_data->gateway : __( 'N/A', 'pmpro-pdf-invoices');

	$values = array( $order_data->code, $email->data['user_email'], $email->data['membership_level_name'], $billing_details, $payment_method, pmpro_formatPrice($order_data->total), get_bloginfo( 'sitename' ), esc_url( get_site_url() ), pmpro_formatPrice( $order_data->subtotal ), pmpro_formatPrice($order_data->tax), $order_data->membership_id, $date );


	$body = str_replace( $replace, $values, $body );
	$dompdf->loadHtml( $body );
	$dompdf->render();
	$output = $dompdf->output();

	// let's write this file to a directory now.
	$upload_dir = wp_upload_dir();
	$invoice_dir = $upload_dir['basedir'] . '/pmpro-invoices/';

	if ( !file_exists( $invoice_dir ) ) {
		mkdir( $invoice_dir, 0777, true );
	}

	$path = $invoice_dir . "INV" . $order_data->code . ".pdf";

	file_put_contents( $path, $output );

	$attachments[] = $path;

	return $attachments;

}
add_filter( 'pmpro_email_attachments', 'pmpropdf_attach_pdf_email', 10, 2 );


// look at changing this soon.
function pmpropdf_admin_column_header( $order_id ) {

	echo '<td>' . __( 'PDF', 'pmpro-pdf-invoices' ) . '</td>';
}
add_action( 'pmpro_orders_extra_cols_header', 'pmpropdf_admin_column_header' );

function pmpropdf_admin_column_body( $order ) {

	$upload_dir = wp_upload_dir();
	$download_url = $upload_dir['baseurl'] . '/pmpro-invoices/INV' . $order->code . ".pdf";

	if ( file_exists( $upload_dir['basedir'] . '/pmpro-invoices/INV' . $order->code . ".pdf" ) ){
	echo '<td><a href="' . esc_url( $download_url ). '" target="_blank">' . __( 'Download PDF', 'pmpro-pdf-invoices' ) .'</a></td>';	
	} else {
		echo '<td> - </td>';
	}
	
}
add_action( 'pmpro_orders_extra_cols_body', 'pmpropdf_admin_column_body' );

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


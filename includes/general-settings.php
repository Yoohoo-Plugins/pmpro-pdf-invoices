<?php

/**
 * Settings page for License key.
 */

function pmpro_pdf_invoice_settings_page() {

	if(isset($_GET['sub_action']) && $_GET['sub_action'] === 'template_editor'){
		pmpro_pdf_template_editor_page();
		return false;
	}

	if(isset($_GET['sub_action']) && $_GET['sub_action'] === 'reset_template'){
		$upload_dir = wp_upload_dir();
		if(!empty($upload_dir) && !empty($upload_dir['basedir'])){
			$template_dir = $upload_dir['basedir'] . '/pmpro-invoice-templates/order.html';
			if(file_exists($template_dir)){

				unlink($template_dir);
				pmpro_pdf_admin_notice( __( 'Template file reset.', 'pmpro-pdf-invoices' ) , 'success is-dismissible' );
			}
		}
	}

	if(isset($_GET['sub_action']) && $_GET['sub_action'] === 'regen_rewrites'){
		pmpropdf_remove_rewrite_for_regen();
		pmpro_pdf_admin_notice( __( 'Regenerated rewrite file.', 'pmpro-pdf-invoices' ), 'success is-dismissible' );
	}

	if(isset($_GET['sub_action']) && $_GET['sub_action'] === 'set_template'){
		$template_selected = !empty($_GET['template']) ? $_GET['template'] : false;
		if ( ! empty( $template_selected ) ) {
	        try{
	        	$template_body = file_get_contents( PMPRO_PDF_DIR . '/templates/' . $template_selected . '.html' );

	            $upload_dir = wp_upload_dir();
	            $template_dir = $upload_dir['basedir'] . '/pmpro-invoice-templates/';

	            if(!file_exists( $template_dir )){
	              mkdir( $template_dir, 0777, true );
	            }

	            $custom_dir = $template_dir . "order.html";

	            file_put_contents($custom_dir, pmpro_pdf_temlate_editor_get_forced_css() .  $template_body);

	            ?>
	            <div class="notice notice-success">
	                <p><?php _e( 'Template Saved!', 'pmpro-pdf-invoices' ); ?></p>
	            </div>
	            <?php
	        } catch(Exception $ex){
	            ?>
	            <div class="update-nag">
	                <p><?php _e( 'Could not save Template', 'pmpro-pdf-invoices' ); ?></p>
	            </div>
	            <?php
	        }
		}
	}


	wp_enqueue_style('pmrpopdf-settings-styles', plugin_dir_url(__FILE__) . '/css/settings-styles.css');

	wp_enqueue_media();
	wp_enqueue_script('pmrpropdf-settins-scripts', plugin_dir_url(__FILE__) . '/js/settings-scripts.js' , array('jquery'));
	wp_localize_script( 'pmrpropdf-settins-scripts', 'pmpropdf_js', array(
		'ajax_url' => admin_url( 'admin-ajax.php' )
	));

	$license = get_option( 'pmpro_pdf_invoice_license_key' );
	$status  = get_option( 'pmpro_pdf_invoice_license_status' );
	$expires = get_option( 'pmpro_pdf_invoice_license_expires' );

	$expired = false;

	if ( empty( 'license' ) ) {
		pmpro_pdf_admin_notice( __( 'If you are running PMPro PDF Invoices on a live site, we recommend an annual support license. <a href="https://yoohooplugins.com/plugins/zapier-integration/" target="_blank" rel="noopener">More Info</a>', 'pmpro-pdf-invoices' ), 'warning' );
	}
	// get the date and show a notice.
	if ( ! empty( $expires ) ) {
		$expired = pmpro_pdf_license_expires( $expires );
		if ( $expired ) {
			pmpro_pdf_admin_notice( __( 'Your license key has expired. We recommend in renewing your annual support license to continue to get automatic updates and premium support. <a href="https://yoohooplugins.com/plugins/zapier-integration/" target="_blank" rel="noopener">More Info</a>', 'pmpro-pdf-invoices' ), 'warning' );
			$expires = __( 'Your license key has expired.', 'pmpro-pdf-invoices' );
		} else {
			$expired = false;
		}
	}

	// Check on Submit and update license server.
	if ( isset( $_REQUEST['submit'] ) ) {
		if ( isset( $_REQUEST['pmpro_pdf_invoice_license_key' ] ) && !empty( $_REQUEST['pmpro_pdf_invoice_license_key'] ) ) {
			update_option( 'pmpro_pdf_invoice_license_key', $_REQUEST['pmpro_pdf_invoice_license_key'] );
			$license = $_REQUEST['pmpro_pdf_invoice_license_key'];
			pmpro_pdf_admin_notice( __( 'Saved successfully.', 'pmpro-pdf-invoices' ), 'success is-dismissible' );
		}else{
			delete_option( 'pmpro_pdf_invoice_license_key' );
			$license = '';
		}
	}
	// Activate license.
	if( isset( $_POST['activate_license'] ) ) {
		// run a quick security check
	 	if( ! check_admin_referer( 'pmpro_pdf_license_nonce', 'pmpro_pdf_license_nonce' ) ) {
			return; // get out if we didn't click the Activate button
	 	}
		// retrieve the license from the database
		$license = trim( get_option( 'pmpro_pdf_invoice_license_key' ) );
		// data to send in our API request
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_id'    => PMPRO_PDF_PLUGIN_ID, // The ID of the item in EDD
			'url'        => home_url()
		);
		// Call the custom API.
		$response = wp_remote_post( YOOHOO_STORE, array( 'timeout' => 15, 'sslverify' => true, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$message =  ( is_wp_error( $response ) && ! empty( $response->get_error_message() ) ) ? $response->get_error_message() : __( 'An error occurred, please try again.', 'pmpro-pdf-invoices' );
			pmpro_pdf_admin_notice( $message, 'error is-dismissible' );
		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		}

		$status = sanitize_text_field( $license_data->license );
		$expires = ! empty( $license_data->expires ) ? sanitize_text_field( $license_data->expires ) : '';

		update_option( 'pmpro_pdf_invoice_license_status', $status );
		update_option( 'pmpro_pdf_invoice_license_expires', $expires );


		if( $license_data->success != false ) {
			pmpro_pdf_admin_notice( __( 'License successfully activated.', 'pmpro-pdf-invoices' ), 'success is-dismissible' );
		} else {
			pmpro_pdf_admin_notice( __( 'Unable to activate license, please ensure your license is valid.', 'pmpro-pdf-invoices' ), 'error is-dismissible' );
		}
	}
	// Deactivate license.
	if ( isset( $_POST['deactivate_license'] ) ) {
		if( ! check_admin_referer( 'pmpro_pdf_license_nonce', 'pmpro_pdf_license_nonce' ) ) {
			return; // get out if we didn't click the Activate button
	 	}

	$api_params = array(
		'edd_action' => 'deactivate_license',
		'license' => $license,
		'item_id' => PMPRO_PDF_PLUGIN_ID, // the name of our product in EDD
		'url' => home_url()
	);
	// Send the remote request
	$response = wp_remote_post( YOOHOO_STORE, array( 'body' => $api_params, 'timeout' => 15, 'sslverify' => true ) );
	// if there's no erros in the post, just delete the option.
	if ( ! is_wp_error( $response ) ) {
		delete_option( 'pmpro_pdf_invoice_license_status' );
		delete_option( 'pmpro_pdf_invoice_license_expires' );
		$status = false;
		pmpro_pdf_admin_notice( __( 'Deactivated license successfully.', 'pmpro-pdf-invoices' ), 'success is-dismissible' );
	}


}

//General Settings Save

if(isset($_POST['pmpropdf_save_settings'])){
	$logo_url = !empty($_POST['logo_url']) ? strip_tags($_POST['logo_url']) : '';
	update_option(PMPRO_PDF_LOGO_URL, $logo_url);
	update_option(PMPRO_PDF_ADMIN_EMAILS, (!empty($_POST['admin_emails']) ? true : false));
}

if(isset($_GET['sub_action']) && $_GET['sub_action'] === 'insert_account_shortcode'){
	if(function_exists('pmpro_getOption')){
		$account_page_id = pmpro_getOption("account_page_id");
		if($account_page_id !== NULL){
			$current_post = get_post(intval($account_page_id));
			$current_content = $current_post->post_content;
			$update_post = array(
		      'ID'           => intval($account_page_id),
		      'post_content' => $current_content . '<br><br>[pmpropdf_download_list] [pmpropdf_download_all_zip]',
		  	);

  			wp_update_post($update_post);

  			pmpro_pdf_admin_notice( __( 'Shortcode automatically added to Account Page', 'pmpro-pdf-invoices' ), 'success is-dismissible' );
  		}
  	}
}

$logo_url = get_option(PMPRO_PDF_LOGO_URL, '');
$admin_emails = get_option(PMPRO_PDF_ADMIN_EMAILS, false);

//Generate a license tab badge class
$license_tab_badge = '';
if (false !== $status && $status == 'valid') {
	if ($expired) {
		$license_tab_badge = 'pmpropdf_tab_badge expired';
	}
} else {
	$license_tab_badge = 'pmpropdf_tab_badge unregistered';
}
?>
	<div class="wrap">
		<h2><?php _e('PMPro PDF Invoices Options'); ?></h2>

		<div class='pmpropdf_option_tabs'>
			<div class='pmpropdf_tab active' data-tab='1'><?php esc_html_e( 'Tools', 'pmpro-pdf-invoices' ); ?></div>
			<div class='pmpropdf_tab' data-tab='2'><?php esc_html_e( 'Settings', 'pmpro-pdf-invoices' ); ?></div>
			<div class='pmpropdf_tab' data-tab='4'><?php esc_html_e( 'Shortcode', 'pmpro-pdf-invoices' ); ?></div>
			<div class='pmpropdf_tab' data-tab='3'><?php esc_html_e( 'Info', 'pmpro-pdf-invoices' ); ?></div>
			<div class='pmpropdf_tab <?php echo $license_tab_badge; ?>' data-tab='0'><?php esc_html_e( 'License', 'pmpro-pdf-invoices' ); ?></div>
		</div>

		<div class='wp-editor-container pmpropdf_option_section' data-tab='0'>
			<form method="post" action="">

				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e('License Key'); ?>
							</th>
							<td>
								<input id="pmpro_pdf_invoice_license_key" name="pmpro_pdf_invoice_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
								<label class="description" for="pmpro_pdf_invoice_license_key"> <small><em><?php _e('Enter your license key.'); ?></em></small></label><br/>
							</td>
						</tr>

						<tr>
							<th scope="row" valign="top">
								<?php _e( 'License Status', 'pmpro-pdf-invoices' ); ?>
							</th>
							<td>
								<?php
								if ( false !== $status && $status == 'valid' ) {
									if ( ! $expired ) {
										?>
											<span class='rewrite_badge active'><strong><?php _e( 'Active', 'pmpro-pdf-invoices' ); ?></strong></span>
										<?php
									} else {
										?>
											<span class='rewrite_badge inactive'><strong><?php _e( 'Expired', 'pmpro-pdf-invoices' ); ?></strong></span>
										<?php
									}	

									if ( ! $expired && ! empty ( $expires ) ) {
										esc_html_e( sprintf( 'Expires on %s', $expires ), 'pmpro-pdf-invoices' );
									}
								} else {
									?>
										<span class='rewrite_badge unknown'><strong><?php _e( 'Unregistered', 'pmpro-pdf-invoices' ); ?></strong></span>
									<?php
								}
								?>
							</td>
						</tr>
						<?php if( ! empty( $license ) || false != $license ) { ?>
							<tr valign="top">
								<th scope="row" valign="top">
									<?php _e('Activate License'); ?>
								</th>
								<td>
									<?php if ( $status !== false && $status == 'valid' ) { ?>
										<?php wp_nonce_field( 'pmpro_pdf_license_nonce', 'pmpro_pdf_license_nonce' ); ?>
										<input type="submit" class="button-secondary" style="color:red;" name="deactivate_license" value="<?php _e('Deactivate License'); ?>"/><br/><br/>
										<?php } else {
										wp_nonce_field( 'pmpro_pdf_license_nonce', 'pmpro_pdf_license_nonce' ); ?>
										<input type="submit" class="button-secondary" name="activate_license" value="<?php _e( 'Activate License', 'pmpro-pdf-invoices' ); ?>" <?php if ( isset( $expired ) && $expired ) { echo 'disabled'; } ?>>
									<?php } ?>
								</td>
							</tr>
						<?php } ?>						

					</tbody>
				</table>
				<?php submit_button(); ?>

				</form>
			</div>

			<div class='wp-editor-container pmpropdf_option_section visible' data-tab='1'>
				<strong><?php esc_html_e( 'Template Editor', 'pmpro-pdf-invoices' ); ?></strong>
				<br>

				<?php
				$template_notice = __( 'It appears you do not have a custom template set up.', 'pmpro-pdf-invoices' );
				$template_button = __( 'Create Template', 'pmpro-pdf-invoices' );

				$upload_dir = wp_upload_dir();
				if(!empty($upload_dir) && !empty($upload_dir['basedir'])){
					$custom_dir = $upload_dir['basedir'] . '/pmpro-invoice-templates/order.html';
					if(file_exists($custom_dir)){
    					$template_notice = __( 'You are using a custom template.', 'pmpro-pdf-invoices' );
    					$template_button = __( 'Edit Template', 'pmpro-pdf-invoices' );
    				}
    			}
    			?>
				<small><?php echo esc_html( $template_notice ); ?></small>

				<br><br>
				<a class='button' href='?page=pmpro_pdf_invoices_license_key&sub_action=template_editor'><?php echo esc_html( $template_button ); ?></a>

				<?php if(file_exists($custom_dir)){ ?>
					<a class='button reset_template_btn' href='?page=pmpro_pdf_invoices_license_key&sub_action=reset_template'><?php esc_html_e( 'Reset Template', 'pmpro-pdf-invoices' ); ?></a>
				<?php } ?>

				<a class='button select_template_btn' href='#' title="Select a template from our library"><?php esc_html_e( 'Select Template', 'pmpro-pdf-invoices' ); ?></a> 

				<?php $pdf_sample_nonce = wp_create_nonce( 'pmpropdf_view_sample' ); ?>
				<a class='button view_sample_btn' href="?page=pmpro_pdf_invoices_license_key&sub_action=view_sample&_wpnonce=<?php echo $pdf_sample_nonce; ?>" target="_blank"><?php esc_html_e( 'Download PDF Sample', 'pmpro-pdf-invoices' ); ?></a>

				<br><br>
				<small><em><?php _e( "<strong>Tip:</strong> Not sure where to start? use one of our included templates by clicking on 'Select Template'", 'pmpro-pdf-invoices' ); ?></em></small>

				<br><br>
				<strong><?php esc_html_e( 'Generate Missing Invoices', 'pmpro-pdf-invoices' ); ?></strong>
				<div class='missing_invoice_log'>
					<div class='item'><?php esc_html_e( 'No output data yet...', 'pmpro-pdf-invoices' ); ?></div>
				</div>
				<small><em><?php esc_html_e( 'Please leave this window open while processing', 'pmpro-pdf-invoices' ); ?></em></small>
				<br><br>
				<button class='button generate_missing_logs'><?php esc_html_e( 'Generate', 'pmpro-pdf-invoices' ); ?></button>
				<?php

				$user_id = get_current_user_id();
						if( ( !empty( $_SERVER['SERVER_SOFTWARE'] ) && strpos( $_SERVER['SERVER_SOFTWARE'], 'nginx' ) !== false ) ){

							$upload_dir = wp_upload_dir();

							$baseurl = str_replace( site_url(), '', $upload_dir['baseurl'] );

							$invoice_dir = $baseurl . '/pmpro-invoices/';

							$access_key = pmpropdf_get_rewrite_token();
						
							?>
							<br/><br/>
							<div class="pmpro-pdf-invoices-nginx-notice">
								<strong><?php _e('Nginx Detected', 'pmpro-pdf-invoices'); ?></strong>
								<p><?php _e('We detected that your installation is running on Nginx. To protect generated invoices that are stored on your web server, the following Nginx rule should be added to your Nginx WordPress installation config file.', 'pmpro-pdf-invoices' ); ?></p>
								<p><code>
									location <?php echo $invoice_dir; ?> {
										if ($query_string  !~ "access=<?php echo $access_key; ?>"){
											return 403;
									  	}
									}			
								</code></p>
							</div>
					<?php } ?>

				<br><br>
				<strong><?php esc_html_e( 'Archives', 'pmpro-pdf-invoices' ); ?></strong>
				
				<br><br>
				<a class='button download_zip_btn' href='?page=pmpro_pdf_invoices_license_key&sub_action=download_zip_archive'><?php esc_html_e( 'ZIP & Download', 'pmpro-pdf-invoices' ); ?></a>
				
				<br><br>
				<small><em><?php esc_html_e( 'Click the button above to download all stored invoices as a ZIP file. Alternatively individual files can be downloaded from the orders page', 'pmpro-pdf-invoices' ); ?></em></small>
			</div>

			<div class='wp-editor-container pmpropdf_option_section' data-tab='2'>
				<form method="POST" style='width: 45%; display: inline-block; vertical-align: top'>
					<strong><?php esc_html_e( 'Invoice Logo', 'pmpro-pdf-invoices' ); ?></strong>
					<br>
					<div class='logo_holder'>
						<?php if(!empty($logo_url)) {
							?>
								<img src="<?php echo strip_tags($logo_url); ?>" alt="" style="max-width:150px;"/>
							<?php
						} else {
							?>
								<em><?php esc_html_e( 'No Logo Selected', 'pmpro-pdf-invoices' ); ?></em>
							<?php
						} ?>
					</div>
					<button class='button pmpropdf_logo_upload'><?php esc_html_e( 'Select Image', 'pmpro-pdf-invoices' ); ?></button>
					<?php if(!empty($logo_url)){
						?>
							<button class='button pmpropdf_logo_remove'><?php esc_html_e( 'Remove', 'pmpro-pdf-invoices' ); ?></button>
						<?php
					} ?>

					<br><br>

					<input id='logo_url' name='logo_url' type='hidden' value='<?php echo $logo_url; ?>' />

					<strong><?php esc_html_e( 'Emails', 'pmpro-pdf-invoices' ); ?></strong>
					<br>
					<label style="padding-top: 10px; display: block"><input type="checkbox" name="admin_emails" <?php echo !empty($admin_emails) ? 'checked' : ''; ?>> <small><?php esc_html_e( "Attach PDF's to admin checkout emails", 'pmpro-pdf-invoices' ); ?></small></label>

					<br><br>
					<input type='submit' class='button button-primary' name='pmpropdf_save_settings' value='Save Settings'>
				</form>
			</div>

			<div class='wp-editor-container pmpropdf_option_section' data-tab='3'>
				<strong><?php esc_html_e( 'ZipArchive Module', 'pmpro-pdf-invoices' ); ?></strong><br>
				<?php
				if(class_exists('ZipArchive')){
					echo "<em class='rewrite_badge active'>Active</em> Invoices can be archived and downloaded as a ZIP file.";
				} else {
					echo "<em class='rewrite_badge inactive'>Inactive</em> ZipArchive module not available, ZIP functionality disabled.";
				}
				?>
				<br><br>
				<strong><?php esc_html_e( 'Invoice Rewrite Status', 'pmpro-pdf-invoices' ); ?></strong><br>
				<?php
				if(pmpropdf_check_rewrite_active()){
					echo "<em class='rewrite_badge active'>" . __( 'Active', 'pmpro-pdf-invoices' ) . "</em> " . __( 'Invoices cannot be accessed directly.', 'pmpro-pdf-invoices' );
				} else {
					echo "<em class='rewrite_badge inactive'>" . __( 'Inactive', 'pmpro-pdf-invoices' ) . "</em> " . __( 'Invoice are not secured and can be accessed directly.', 'pmpro-pdf-invoices' );
				}
				?>
				<br><br>
				<strong><?php esc_html_e( 'Regenerate Invoice Rewrites', 'pmpro-pdf-invoices' ); ?></strong><br>
				<small><?php esc_html_e( 'Use this tool to regenerate the rewrite file', 'pmpro-pdf-invoices' ); ?></small>
				<br><br>
				<a class='button' href='?page=pmpro_pdf_invoices_license_key&sub_action=regen_rewrites'><?php esc_html_e( 'Regenerate Rewrite File', 'pmpro-pdf-invoices' ); ?></a> <br>
				<br>
			</div>

			<div class='wp-editor-container pmpropdf_option_section' data-tab='4'>
				<strong><?php esc_html_e( 'PDF Invoice List', 'pmpro-pdf-invoices' ); ?></strong><br>
				<br>
				<code>[pmpropdf_download_all_zip]</code>
				<em><?php esc_html_e( 'This can be placed in a page to allow users to download all their invoices as a ZIP file', 'pmpro-pdf-invoices' ); ?></em>
				<?php if(!class_exists('ZipArchive')) { ?>
					<br><br>
					<code style='color:red'><?php esc_html_e( 'ZipArchive module not enabled within your server environment. This shortcode will be disabled', 'pmpro-pdf-invoices' ); ?></code>
				<?php } ?>
				<br><br>
				<code>[pmpropdf_download_list]</code>
				<em><?php esc_html_e( 'This can be placed in a page to allow users to download their PDF Invoices', 'pmpro-pdf-invoices' ); ?></em>

				<?php
				if(function_exists('pmpro_getOption')){
					$account_page_id = pmpro_getOption("account_page_id");
					if($account_page_id !== NULL){
						?>
						<br><br>
						<a class='button' href='?page=pmpro_pdf_invoices_license_key&sub_action=insert_account_shortcode'><?php esc_html_e( 'Add to Account Page', 'pmpro-pdf-invoices' ); ?></a>
						<br><br><em><?php esc_html_e( 'Let us automatically add these shortcodes to your PMPro Account Page', 'pmpro-pdf-invoices' ); ?></em>
						<?php
					}
				}
				?>
			</div>
	</div>

	<div class='pmprofpdf_template_selector' style='display: none;'>
		<div class='inner_panel'>
			<div class='heading'>
				<h4><?php esc_html_e( 'Select Template', 'pmpro-pdf-invoices' ); ?></h4>
				<div class='close_btn'><?php esc_html_e( 'Close', 'pmpro-pdf-invoices' ); ?></div>
			</div>
			<div class='content'>
				<small><?php esc_html_e( 'Click on a tile below to apply template to your invoices', 'pmpro-pdf-invoices' ); ?></small>
				<br><br>
				<div class='template_tile' data-template='blank'>
					<img src='<?php echo plugin_dir_url(__FILE__) . '/images/blank_template.jpg'; ?>' />
					<div class='hover'>
						<?php esc_html_e( 'Blank Template', 'pmpro-pdf-invoices' ); ?>
					</div>
				</div>
				<div class='template_tile' data-template='order'>
					<img src='<?php echo plugin_dir_url(__FILE__) . '/images/default_template.jpg'; ?>' />
					<div class='hover'>
						<?php esc_html_e( 'Default Template', 'pmpro-pdf-invoices' ); ?>
					</div>
				</div>
				<div class='template_tile' data-template='corporate'>
					<img src='<?php echo plugin_dir_url(__FILE__) . '/images/corp_template.jpg'; ?>' />
					<div class='hover'>
						<?php esc_html_e( 'Corporate Template', 'pmpro-pdf-invoices' ); ?>
					</div>
				</div>
				<div class='template_tile' data-template='green'>
					<img src='<?php echo plugin_dir_url(__FILE__) . '/images/green_template.jpg'; ?>' />
					<div class='hover'>
						<?php esc_html_e( 'Green', 'pmpro-pdf-invoices' ); ?>
					</div>
				</div>
				<div class='template_tile' data-template='split'>
					<img src='<?php echo plugin_dir_url(__FILE__) . '/images/split_template.jpg'; ?>' />
					<div class='hover'>
						<?php esc_html_e( 'Split', 'pmpro-pdf-invoices' ); ?>
					</div>
				</div>
				<br><br>
			</div>
			<div class='foot'>
				<small><?php _e( '<strong>Note: </strong>Selecting a bundled theme will override any custom templates you may have setup', 'pmpro-pdf-invoices' ); ?></small>
			</div>
		</div>
	</div>

<?php

}

/**
 * Show an admin notice helper function.
 * @since 1.8
 */
function pmpro_pdf_admin_notice( $message, $status ) {
	   ?>
    <div class="notice notice-<?php echo $status; ?>">
        <p><?php _e( $message ); ?></p>
    </div>
    <?php
}

function pmpro_pdf_license_expires( $expiry_date ) {
	$today = date( 'Y-m-d H:i:s' );

	if ( $expiry_date < $today ) {
		$r = true;
	} else {
		$r = false;
	}
	return $r;
}

/**
 * Call the generate sample PDF method to get a sample PDF.
 * @since 1.10
 */
function pmpro_pdf_admin_view_sample_pdf() {
	if ( ! empty( $_GET['page'] ) && ! empty( $_GET['sub_action'] ) ) {
		if ( $_GET['page'] === 'pmpro_pdf_invoices_license_key' && $_GET['sub_action'] === 'view_sample' ){
			$pmpropdf_view_sample_nonce = $_REQUEST['_wpnonce'];
			if ( ! wp_verify_nonce( $pmpropdf_view_sample_nonce, 'pmpropdf_view_sample' ) ) {
				die( 'Failed security check for sample PDF' );
			} else {
				pmpropdf_generate_sample_pdf();
			}
		}
	}
}
add_action( 'admin_init', 'pmpro_pdf_admin_view_sample_pdf' );
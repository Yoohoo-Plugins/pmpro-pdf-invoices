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
		$custom_dir = get_stylesheet_directory() . "/pmpro-pdf-invoices/order.html";
    	if(file_exists($custom_dir)){
			unlink($custom_dir);
			pmpro_pdf_admin_notice( 'Template file reset.', 'success is-dismissible' );
		}
	}

	if(isset($_GET['sub_action']) && $_GET['sub_action'] === 'regen_rewrites'){
		pmpropdf_remove_rewrite_for_regen();
		pmpro_pdf_admin_notice( 'Regenerated rewrite file.', 'success is-dismissible' );
	}

	if(isset($_GET['sub_action']) && $_GET['sub_action'] === 'set_template'){
		$template_selected = !empty($_GET['template']) ? $_GET['template'] : false;
		if($template_selected === 'order' || $template_selected === 'corporate'){
	        try{
	        	$template_body = file_get_contents( PMPRO_PDF_DIR . '/templates/' . $template_selected . '.html' );

	            if(!file_exists(get_stylesheet_directory() . '/pmpro-pdf-invoices')){
	                mkdir(get_stylesheet_directory() . '/pmpro-pdf-invoices', 0777, true);
	            }

	            $custom_dir = get_stylesheet_directory() . "/pmpro-pdf-invoices/order.html";

	            file_put_contents($custom_dir, pmpro_pdf_temlate_editor_get_forced_css() .  $template_body);

	            ?>
	            <div class="notice notice-success">
	                <p><?php _e('Template Saved!'); ?></p>
	            </div>
	            <?php
	        } catch(Exception $ex){
	            ?>
	            <div class="update-nag">
	                <p><?php _e('Could not save Template'); ?></p>
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
		pmpro_pdf_admin_notice( 'If you are running PMPro PDF Invoices on a live site, we recommend an annual support license. <a href="https://yoohooplugins.com/plugins/zapier-integration/" target="_blank" rel="noopener">More Info</a>', 'warning' );
	}
	// get the date and show a notice.
	if ( ! empty( $expires ) ) {
		$expired = pmpro_pdf_license_expires( $expires );
		if ( $expired ) {
			pmpro_pdf_admin_notice( 'Your license key has expired. We recommend in renewing your annual support license to continue to get automatic updates and premium support. <a href="https://yoohooplugins.com/plugins/zapier-integration/" target="_blank" rel="noopener">More Info</a>', 'warning' );
			$expires = "Your license key has expired.";
		} else {
			$expired = false;
		}
	}

	// Check on Submit and update license server.
	if ( isset( $_REQUEST['submit'] ) ) {
		if ( isset( $_REQUEST['pmpro_pdf_invoice_license_key' ] ) && !empty( $_REQUEST['pmpro_pdf_invoice_license_key'] ) ) {
			update_option( 'pmpro_pdf_invoice_license_key', $_REQUEST['pmpro_pdf_invoice_license_key'] );
			$license = $_REQUEST['pmpro_pdf_invoice_license_key'];
			pmpro_pdf_admin_notice( 'Saved successfully.', 'success is-dismissible' );
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
			'item_id'    => YH_PLUGIN_ID, // The ID of the item in EDD
			'url'        => home_url()
		);
		// Call the custom API.
		$response = wp_remote_post( YOOHOO_STORE, array( 'timeout' => 15, 'sslverify' => true, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$message =  ( is_wp_error( $response ) && ! empty( $response->get_error_message() ) ) ? $response->get_error_message() : __( 'An error occurred, please try again.' );
			pmpro_pdf_admin_notice( $message, 'error is-dismissible' );
		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		}

		$status = $license_data->license;
		$expires = ! empty( $license_data->expires ) ? $license_expires : '';

		update_option( 'pmpro_pdf_invoice_license_status', $status );
		update_option( 'pmpro_pdf_invoice_license_expires', $expires );


		if( $license_data->success != false ) {
			pmpro_pdf_admin_notice( 'License successfully activated.', 'success is-dismissible' );
		} else {
			pmpro_pdf_admin_notice( 'Unable to activate license, please ensure your license is valid.', 'error is-dismissible' );
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
		'item_id' => YH_PLUGIN_ID, // the name of our product in EDD
		'url' => home_url()
	);
	// Send the remote request
	$response = wp_remote_post( YOOHOO_STORE, array( 'body' => $api_params, 'timeout' => 15, 'sslverify' => true ) );
	// if there's no erros in the post, just delete the option.
	if ( ! is_wp_error( $response ) ) {
		delete_option( 'pmpro_pdf_invoice_license_status' );
		delete_option( 'pmpro_pdf_invoice_license_expires' );
		$status = false;
		pmpro_pdf_admin_notice( 'Deactivated license successfully.', 'success is-dismissible' );
	}


}

//General Settings Save

if(isset($_POST['pmpropdf_save_settings'])){
	$logo_url = !empty($_POST['logo_url']) ? strip_tags($_POST['logo_url']) : '';
	update_option(PMPRO_PDF_LOGO_URL, $logo_url);
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

  			pmpro_pdf_admin_notice( 'Shortcode automatically added to Account Page', 'success is-dismissible' );
  		}
  	}
}

$logo_url = get_option(PMPRO_PDF_LOGO_URL, '');

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
			<div class='pmpropdf_tab active' data-tab='1'>Tools</div>
			<div class='pmpropdf_tab' data-tab='2'>Settings</div>
			<div class='pmpropdf_tab' data-tab='4'>Shortcode</div>
			<div class='pmpropdf_tab' data-tab='3'>Info</div>
			<div class='pmpropdf_tab <?php echo $license_tab_badge; ?>' data-tab='0'>License</div>
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
								<?php _e( 'License Status' ); ?>
							</th>
							<td>
								<?php
								if ( false !== $status && $status == 'valid' ) {
									if ( ! $expired ) {
										?>
											<span class='rewrite_badge active'><strong><?php _e( 'Active' ); ?></strong></span>
										<?php
									} else {
										?>
											<span class='rewrite_badge inactive'><strong><?php _e( 'Expired' ); ?></strong></span>
										<?php
									}

									if ( ! $expired ) {
										_e( sprintf( 'Expires on %s', $expires ) );
									}
								} else {
									?>
										<span class='rewrite_badge unknown'><strong><?php _e( 'Unregistered' ); ?></strong></span>
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
										<input type="submit" class="button-secondary" name="activate_license" value="<?php _e('Activate License'); ?>" <?php if ( isset( $expired ) && $expired ) { echo 'disabled'; } ?>>
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
				<strong>Template Editor</strong>
				<br>

				<?php
				$template_notice = 'It appears you do not have a custom template set up.';
				$template_button = 'Create Template';
				$custom_dir = get_stylesheet_directory() . "/pmpro-pdf-invoices/order.html";
    			if(file_exists($custom_dir)){
    				$template_notice = 'You are using a custom template.';
    				$template_button = 'Edit Template';
    			}
    			?>
				<small><?php echo $template_notice; ?></small>

				<br><br>
				<a class='button' href='?page=pmpro_pdf_invoices_license_key&sub_action=template_editor'><?php echo $template_button; ?></a>

				<?php if(file_exists($custom_dir)){ ?>
					<a class='button reset_template_btn' href='?page=pmpro_pdf_invoices_license_key&sub_action=reset_template'>Reset Template</a>
				<?php } ?>

				<a class='button select_template_btn' href='#' title="Select a template from our library">Select Template</a>

				<br><br>
				<strong>Generate Missing Invoices</strong>
				<div class='missing_invoice_log'>
					<div class='item'>No output data yet...</div>
				</div>
				<small><em>Please leave this window open while processing</em></small>
				<br><br>
				<button class='button generate_missing_logs'>Generate</button>
			</div>

			<div class='wp-editor-container pmpropdf_option_section' data-tab='2'>
				<form method="POST" style='width: 45%; display: inline-block; vertical-align: top'>
					<strong>Invoice Logo</strong>
					<br>
					<div class='logo_holder'>
						<?php if(!empty($logo_url)) {
							?>
								<img src="<?php echo strip_tags($logo_url); ?>" alt="" style="max-width:150px;"/>
							<?php
						} else {
							?>
								<em>No Logo Selected</em>
							<?php
						} ?>
					</div>
					<button class='button pmpropdf_logo_upload'>Select Image</button>
					<?php if(!empty($logo_url)){
						?>
							<button class='button pmpropdf_logo_remove'>Remove</button>
						<?php
					} ?>

					<br><br>

					<input id='logo_url' name='logo_url' type='hidden' value='<?php echo $logo_url; ?>' />

					<input type='submit' class='button button-primary' name='pmpropdf_save_settings' value='Save Settings'>
				</form>
			</div>

			<div class='wp-editor-container pmpropdf_option_section' data-tab='3'>
				<strong>ZipArchive Module</strong><br>
				<?php
				if(class_exists('ZipArchive')){
					echo "<em class='rewrite_badge active'>Active</em> Invoices can be archived and downloaded as a ZIP file.";
				} else {
					echo "<em class='rewrite_badge inactive'>Inactive</em> ZipArchive module not available, ZIP functionality disabled.";
				}
				?>
				<br><br>
				<strong>Invoice Rewrite Status</strong><br>
				<?php
				if(pmpropdf_check_rewrite_active()){
					echo "<em class='rewrite_badge active'>Active</em> Invoices cannot be accessed directly.";
				} else {
					echo "<em class='rewrite_badge inactive'>Inactive</em> Invoice are not secured and can be accessed directly.";
				}
				?>
				<br><br>
				<strong>Regenerate Invoice Rewrites</strong><br>
				<small>Use this tool to regenerate the rewrite file</small>
				<br><br>
				<a class='button' href='?page=pmpro_pdf_invoices_license_key&sub_action=regen_rewrites'>Regenerate Rewrite File</a> <br>
				<br>
			</div>

			<div class='wp-editor-container pmpropdf_option_section' data-tab='4'>
				<strong>PDF Invoice List</strong><br>
				<br>
				<code>[pmpropdf_download_all_zip]</code>
				<em>This can be placed in a page to allow users to download all their invoices as a ZIP file</em>
				<?php if(!class_exists('ZipArchive')) { ?>
					<br><br>
					<code style='color:red'>ZipArchive module not enabled within your server environment. This shortcode will be disabled</code>
				<?php } ?>
				<br><br>
				<code>[pmpropdf_download_list]</code>
				<em>This can be placed in a page to allow users to download their PDF Invoices</em>

				<?php
				if(function_exists('pmpro_getOption')){
					$account_page_id = pmpro_getOption("account_page_id");
					if($account_page_id !== NULL){
						?>
						<br><br>
						<a class='button' href='?page=pmpro_pdf_invoices_license_key&sub_action=insert_account_shortcode'>Add to Account Page</a>
						<br><br><em>Let us automatically add these shortcodes to your PMPro Account Page</em>
						<?php
					}
				}
				?>
			</div>
	</div>

	<div class='pmprofpdf_template_selector' style='display: none;'>
		<div class='inner_panel'>
			<div class='heading'>
				<h4>Select Template</h4>
				<div class='close_btn'>Close</div>
			</div>
			<div class='content'>
				<small>Click on a tile below to apply template to your invoices</small>
				<br><br>
				<div class='template_tile' data-template='order'>
					<img src='<?php echo plugin_dir_url(__FILE__) . '/images/default_template.jpg'; ?>' />
					<div class='hover'>
						Default Template
					</div>
				</div>
				<div class='template_tile' data-template='corporate'>
					<img src='<?php echo plugin_dir_url(__FILE__) . '/images/corp_template.jpg'; ?>' />
					<div class='hover'>
						Corporate Template
					</div>
				</div>
				<br><br>
			</div>
			<div class='foot'>
				<small><strong>Note: </strong>Selecting a bundled theme will override any custom templates you may have setup</small>
			</div>
		</div>
	</div>

<?php

}
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
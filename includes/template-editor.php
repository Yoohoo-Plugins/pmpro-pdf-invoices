<?php
/**
 * Handles custom template system
*/

function pmpro_pdf_template_editor_page(){
    global $pmpro_pdf_notice_shown;
    $pmpro_pdf_notice_shown = false;

    pmpro_pdf_template_editor_check_save();
    pmpro_pdf_temaplte_editor_enqueues();
    pmpro_pdf_template_editor_page_html();
}

function pmpro_pdf_temaplte_editor_enqueues(){
    wp_enqueue_style('pmpropdf-template-editor-fullscreen-css', plugin_dir_url(__FILE__) . '/css/fullscreen-editor.css');
    wp_enqueue_style('pmpropdf-template-editor-grape-css', plugin_dir_url(__FILE__) . '/grapejs/grapejs.min.css');

    wp_enqueue_script('pmpropdf-template-editor-grape-js', plugin_dir_url(__FILE__) . '/grapejs/grapejs.min.js' , array());
    wp_enqueue_script('pmpropdf-template-editor-grape-js-preset', plugin_dir_url(__FILE__) . '/grapejs/grapejs-preset-base.min.js' , array('pmpropdf-template-editor-grape-js'));
    wp_enqueue_script('pmpropdf-template-editor-grape-js-init', plugin_dir_url(__FILE__) . '/grapejs/grapejs-pmpro-pdf-editor-init.js' , array('pmpropdf-template-editor-grape-js', 'pmpropdf-template-editor-grape-js-preset', 'jquery'));

    $custom_replacements = apply_filters('pmpro_pdf_invoice_custom_variable_hook', array());
    if(count($custom_replacements) > 0){
      wp_localize_script('pmpropdf-template-editor-grape-js-init', 'pmpro_custom_shortcodes', $custom_replacements);
    }
}

function pmpro_pdf_template_editor_page_html(){
    global $pmpro_pdf_notice_shown;
    $template_body = pmpropdf_get_order_template_html();

    ?>
    <div class="pmpro-pdf-leave-notice">
      <div class="pmrpro-pdf-leave-notice-inner">
        <h4><?php _e('Unsaved Changes'); ?></h4>
        <p>Some changes to your template have not been saved, would you like to save now?</p>
        <div class='button_wrap'>
          <button class="button cancel-btn">Leave without saving</button>
          <button class="button button-primary accept-btn">Save Changes</button> 
        </div>
      </div>
    </div>

    <div class="wrap pmpro-pdf-editor-wrap <?php echo !empty($pmpro_pdf_notice_shown) ? 'notice_present' : ''; ?>">
        <h2><?php _e('PMPro PDF Template Editor'); ?></h2>

        <div class="pmpro-pdf-control-bar-top">
            <a class='button' href='?page=pmpro_pdf_invoices_license_key'>Close Editor</a> <button class='button button-primary save_template_btn'>Save Template</button>
        </div>

        <div id="gjs">
            <?php echo $template_body; ?>
        </div>

        <div class="pmpro-pdf-control-bar-bottom">
            <a class='button' href='?page=pmpro_pdf_invoices_license_key'>Close Editor</a> <button class='button button-primary save_template_btn'>Save Template</button>
        </div>

        <form method='POST' style='display: none;' id='save_html_form'>
            <input type="text" name='redirect_on_save' id='redirect_on_save' value=''>
            <textarea name='template_content' id='template_content'></textarea>
            <textarea name='template_addition_styles' id='template_addition_styles'></textarea>
        </form>

        
    </div>


    <style>
    .gjs-am-file-uploader {
        display: none !important;
    }

    .gjs-am-assets-cont {
        width: 100% !important;
    }

    .gjs-four-color{
        color: #1dd0a2 !important;
    }
    </style>
    <?php
}

function pmpro_pdf_template_editor_check_save(){
    global $pmpro_pdf_notice_shown;
    if(isset($_POST['template_content'])){
        $html_content = str_replace('\\', '', $_POST['template_content']);
        $css_content =  strip_tags($_POST['template_addition_styles']);
        $html_content = pmpro_pdf_cleanup_editor_html($html_content);

        $html_content .= "<style>$css_content</style>";

        try{
            $upload_dir = wp_upload_dir();
            $template_dir = $upload_dir['basedir'] . '/pmpro-invoice-templates/';

            if(!file_exists( $template_dir )){
              mkdir( $template_dir, 0777, true );
            }

            $custom_dir = $template_dir . 'order.html';
            file_put_contents($custom_dir, pmpro_pdf_temlate_editor_get_forced_css() .  $html_content);

            $pmpro_pdf_notice_shown = true;
            ?>
            <div class="notice notice-success pmpro_pdf_notice">
                <p><?php _e('Template Saved!'); ?></p>
            </div>
            <?php
            if(!empty($_POST['redirect_on_save'])){
              $redirectUrl = trim(strip_tags($_POST['redirect_on_save']));
              //We have a redirect passed in 
              ?>
              <div class="notice notice-warning pmpro_pdf_notice">
                  <p><?php _e('Please wait while we redirect you...'); ?></p>
              </div>
              <script>
                window.location.href = "<?php echo $redirectUrl; ?>";
              </script>
              <?php
            }

        } catch(Exception $ex){
            $pmpro_pdf_notice_shown = true;
            ?>
            <div class="update-nag pmpro_pdf_notice">
                <p><?php _e('Could not save Template'); ?></p>
            </div>
            <?php
        }
    }
}

function pmpro_pdf_cleanup_editor_html($content){
  /*** TODO: Add more filtering here as needed */
  $content = pmpro_pdf_remove_empty_rows($content);
  $content = pmpro_pdf_add_newline_formatting($content);
  return $content;
}

/** 
 * This can be removed, simply reformats the raw order.html doc to make it easier to read
*/
function pmpro_pdf_add_newline_formatting($content){
  $tackon_list = array(
    '<br/>', '<br>', '</div>', '</tr>', '</td>', '</table>'
  );

  foreach ($tackon_list as $i => $tackon) {
    $content = str_replace($tackon, $tackon."\n", $content);
  }

  return $content;
}

/**
 * This function was added as it causes DomPDF to have a fit and not render tables in their correct placement
*/
function pmpro_pdf_remove_empty_rows($content){
  $content = preg_replace("(<tr[^/>]+>[ \n\r\t]*<\/tr>)", '', $content);
  return $content;
}

function pmpro_pdf_temlate_editor_get_forced_css(){
    return "<style>h1, p, table {
              font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
          }

          #invoice, table{
              width:700px;
          }

          #invoice{
            text-align:center;
          }

          #heading{
              font-size:18px;
              color:#555;
              background:#EEE;
          }

          #heading th{
              padding:5px;
              border-radius:2px;
          }

          #heading tr{
              margin-bottom:10px;
          }

          .alignright{
              text-align: right;
          }

          .alignleft{
              text-align:left;
          }

          table {
              border-collapse: collapse;
          }

          .row{
              border-bottom:1px solid #eee;
          }

          .row td, .row th{
              padding:12px;
          }</style>";
}
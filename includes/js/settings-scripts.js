jQuery(function(){
    pmpropdf_js.batch_process = {
        total_count : 0,
        total_created : 0,
        total_skipped : 0
    };

    jQuery(document).ready(function(){
        var activeTab = window.location.hash;
        activeTab = activeTab.trim();

        console.log(activeTab);
        
        if(activeTab !== "" && activeTab.indexOf('#tab_') !== -1){
            activeTab = activeTab.replace('#tab_', '');
            jQuery('.pmpropdf_tab[data-tab="' + activeTab + '"]').click();
        }
    });

    jQuery('.pmpropdf_tab').on('click', function(){
        jQuery('.pmpropdf_tab').removeClass('active');
        jQuery(this).addClass('active');

        var tab_i = jQuery(this).attr('data-tab');

        jQuery('.pmpropdf_option_section').removeClass('visible');
        jQuery('.pmpropdf_option_section[data-tab=' + tab_i + ']').addClass('visible');
        window.location.hash = 'tab_' + tab_i;
    });

    jQuery('.generate_missing_logs').on('click', function(e){
        e.preventDefault();
        jQuery('.missing_invoice_log').html('<div class="item">Generating Missing Invoices...</div>');
        pmpropdf_ajax_batch_loop(100, 0);
    });

    jQuery('.pmpropdf_logo_upload').on('click', function(e){
        e.preventDefault();
        pmpropdf_logo_uploader();
    });

    jQuery('.pmpropdf_logo_remove').on('click', function(e){
        e.preventDefault();
        jQuery('.logo_holder').html('');
        jQuery('#logo_url').val('');
    });

    jQuery('.reset_template_btn').on('click', function(e){
        e.preventDefault();
        var url = jQuery(this).attr('href');

        var continuePrompt = confirm("This cannot be undone, current template will be deleted.\n\nAre you sure you would like to continue? ");

        if (continuePrompt == true) {
            window.location = url;
        }
    });

    jQuery('.select_template_btn').on('click', function(e){
        jQuery('.pmprofpdf_template_selector').show();
    });

    jQuery('.pmprofpdf_template_selector .close_btn').on('click', function(e){
        jQuery('.pmprofpdf_template_selector').hide();
    });

    jQuery('.template_tile').on('click', function(e){
        var template = jQuery(this).attr('data-template');
        window.location = '?page=pmpro_pdf_invoices_license_key&sub_action=set_template&template=' + template;
    });
});

function pmpropdf_ajax_batch_loop(batch_size, batch_no){
    jQuery.ajax({
        url : pmpropdf_js.ajax_url,
        type : 'post',
        data : {
            action : 'pmpropdf_batch_processor',
            batch_size : batch_size,
            batch_no : batch_no
        },
        success : function( response ) {
            response = JSON.parse(response);

            pmpropdf_js.batch_process.total_count += typeof response.batch_count !== 'undefined' ? response.batch_count : 0;
            pmpropdf_js.batch_process.total_created += typeof response.created !== 'undefined' ? response.created : 0;
            pmpropdf_js.batch_process.total_skipped += typeof response.skipped !== 'undefined' ? response.skipped : 0;

            pmpropdf_update_batch_stats();

            if(typeof response.batch_no !== 'undefined' && typeof response.batch_count !== 'undefined'){
                if(response.batch_count >= batch_size){
                    //Iterate another loop
                    jQuery('.missing_invoice_log').append('<div class="item">Processing...</div>');
                    pmpropdf_ajax_batch_loop(batch_size, response.batch_no+1);
                } else {
                    //Show complete message
                    if(pmpropdf_js.batch_process.total_created == 0){
                        jQuery('.missing_invoice_log').append('<div class="item">No missing invoices!</div>');
                    } else {
                        jQuery('.missing_invoice_log').append('<div class="item">Processing Complete!</div>');
                    }
                }
            }

        }
    });
}

function pmpropdf_update_batch_stats(){
    jQuery('.missing_invoice_log').html(
        '<div class="item">' +
            'Processed: ' + pmpropdf_js.batch_process.total_count +
            '<br>Created: ' + pmpropdf_js.batch_process.total_created +
            '<br>Skipped: ' + pmpropdf_js.batch_process.total_skipped +
        '</div><br>'
    );
}

function pmpropdf_logo_uploader(){
    var file_frame, image_data;
    if(undefined !== file_frame) {
        file_frame.open();
        return;
    }

    file_frame = wp.media.frames.file_frame = wp.media({
        title: 'Select Logo for use in Invoice',
        button: {
           text: 'Set Logo'
        },
        multiple: false,
    });


    file_frame.on( 'select', function() {
      var attachment = file_frame.state().get('selection').first().toJSON();

      jQuery('.logo_holder').html('<img src="'+attachment.url+'" alt="" style="max-width:150px;"/>');
      jQuery('#logo_url').val(attachment.url);
    });

    // Now display the actual file_frame
    file_frame.open();
}
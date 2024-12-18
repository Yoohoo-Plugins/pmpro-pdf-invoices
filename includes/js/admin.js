/** Trigger to create download PDF admin AJAX in some cases */
jQuery(document).ready(function ($) {
    // Add a trigger to the download PDF button
    jQuery('.pmpro-pdf-generate').click(function (e) {
        var data = {
            action: 'pmpropdf_ajax_generate_pdf_invoice',
            order_code: jQuery(this).attr('order_code'),
            download_link: pmpro_pdf_admin.admin_url,
            download_text: pmpro_pdf_admin.download_text,
            loading_gif: pmpro_pdf_admin.loading_gif,
            nonce: pmpro_pdf_admin.nonce
        };

        // Do loading on click.
        jQuery(this).html('<img src="' + data.loading_gif + '" alt="loading gif" width="20px"/>');

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            timeout: 5000,
            dataType: 'html',
            data: data,
            error: function (xml) {
            },
            success: function (responseHTML) {
                if (responseHTML == 'error') {
                    jQuery(this).html('Error generating PDF. Please refresh and try again.');
                } else {
                    jQuery('#pmpro-pdf-generate_' + data.order_code).replaceWith('<a href="' + data.download_link + data.order_code + '" target="_blank">' + data.download_text + '</a>');
                }
            }
        });
    });

}); // End of document ready.
/** Trigger to create download PDF admin AJAX in some cases */
jQuery(document).ready(function ($) {
    // Add a trigger to the download PDF button
    $('.pmpro-pdf-generate').click(function (e) {
        var data = {
            action: 'pmpropdf_ajax_generate_pdf_invoice',
        };

        console.log(data.action);
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            timeout: 2000,
            dataType: 'html',
            data: data,
            error: function (xml) {
                alert('Error generating PDF Invoice');
                // console.log(xml);
            },
            success: function (responseHTML) {
                if (responseHTML == 'error') {
                    // alert('Error generating PDF Invoice.');
                } else {
                    // jQuery('span.pmpro_affiliate_paid_status').html(data.paid_status);
                }
            }
        });
    });

}); // End of document ready.
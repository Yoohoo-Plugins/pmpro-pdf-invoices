document.addEventListener("DOMContentLoaded", function(event) {
  window.pmpro_pdf_editor = grapesjs.init({
    container : '#gjs',
    plugins: ['gjs-blocks-basic'],
    height: (window.outerHeight - 200) + 'px',
    showDevices: 0,
    fromElement: true,
    storageManager : {
      autosave : 0,
      storeHtml : 0,
      storeCss : 0,
      autoload : 0,
      storeStyles: 0,
      storeComponents: 0
    },
    assetManager: {
      upload: false,
    },
    protectedCss: `h1, p, table {
                      font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
                  }

                  #invoice{
                      width:700px;
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
                  }`,
  });

  const blockManager = window.pmpro_pdf_editor.BlockManager;

  blockManager.add('pmpropdf-break-block', {
    label: 'Break Tag',
    content: '<br/>',
    category: 'Basic',
    attributes: {
      title: 'Add a break tag',
      class: "fa fa-code"
    }
  });


  blockManager.add('pmpropdf-table-block', {
    label: 'Table 2/2',
    content: '<table><tr><td>Row 1 - Cell 1</td><td>Row 1 - Cell 2</td></tr><tr><td>Row 2 - Cell 1</td><td>Row 2 - Cell 2</td></tr></table>',
    category: 'Tables',
    attributes: {
      title: 'Add a simple table with 2 rows and 2 columns',
      class: "fa fa-table"
    }
  });

  blockManager.add('pmpropdf-table-empty-block', {
    label: 'Table Empty',
    content: '<table></table>',
    category: 'Tables',
    attributes: {
      title: 'Add a simple table with no rows or columns. To be edited with code editor.',
      class: "fa fa-table"
    }
  });

  blockManager.add('pmpropdf-tag-logo-block', {
    label: 'Logo',
    content: {
        type: "text",
        content: '{{logo_image}}',
        style: {
            padding: "10px"
        },
        activeOnRender: 1
    },
    category: 'Invoice Shortcode',
    attributes: {
      title: 'Add the PMPRO PDF Invoice Logo Shortcode to your template',
      class: "gjs-fonts gjs-f-image"
    }
  });

  blockManager.add('pmpropdf-tag-site-block', {
    label: 'Site Name',
    content: {
        type: "text",
        content: '{{site}}',
        style: {
            padding: "10px"
        },
        activeOnRender: 1
    },
    category: 'Invoice Shortcode',
    attributes: {
      title: 'Add the Site name Shortcode to your template',
      class: "gjs-fonts gjs-f-text"
    }
  });

  blockManager.add('pmpropdf-tag-code-block', {
    label: 'Invoice Code',
    content: {
        type: "text",
        content: '{{invoice_code}}',
        style: {
            padding: "10px"
        },
        activeOnRender: 1
    },
    category: 'Invoice Shortcode',
    attributes: {
      title: 'Add the PMPRO PDF Invoice Code Shortcode to your template',
      class: "fa fa-barcode"
    }
  });

  blockManager.add('pmpropdf-tag-date-block', {
    label: 'Date',
    content: {
        type: "text",
        content: '{{invoice_date}}',
        style: {
            padding: "10px"
        },
        activeOnRender: 1
    },
    category: 'Invoice Shortcode',
    attributes: {
      title: 'Add the PMPRO PDF Invoice Date Shortcode to your template',
      class: "fa fa-calendar"
    }
  });

  blockManager.add('pmpropdf-tag-payment-block', {
    label: 'Payment Method',
    content: {
        type: "text",
        content: '{{payment_method}}',
        style: {
            padding: "10px"
        },
        activeOnRender: 1
    },
    category: 'Invoice Shortcode',
    attributes: {
      title: 'Add the PMPRO PDF Invoice Payment Method Shortcode to your template',
      class: "fa fa-credit-card"
    }
  });

  blockManager.add('pmpropdf-tag-billing-block', {
    label: 'Billing Address',
    content: {
        type: "text",
        content: '{{billing_address}}',
        style: {
            padding: "10px"
        },
        activeOnRender: 1
    },
    category: 'Invoice Shortcode',
    attributes: {
      title: 'Add the PMPRO PDF Invoice Billing Address Shortcode to your template',
      class: "fa fa-building"
    }
  });

  blockManager.add('pmpropdf-tag-membershup-block', {
    label: 'Membership Level',
    content: {
        type: "text",
        content: '{{membership_level}}',
        style: {
            padding: "10px"
        },
        activeOnRender: 1
    },
    category: 'Invoice Shortcode',
    attributes: {
      title: 'Add the PMPRO PDF Invoice Membership Level Shortcode to your template',
      class: "fa fa-user"
    }
  });

  blockManager.add('pmpropdf-tag-subtotal-block', {
    label: 'Subtotal',
    content: {
        type: "text",
        content: '{{subtotal}}',
        style: {
            padding: "10px"
        },
        activeOnRender: 1
    },
    category: 'Invoice Shortcode',
    attributes: {
      title: 'Add the PMPRO PDF Invoice Subtotal Shortcode to your template',
      class: "fa fa-money"
    }
  });

  blockManager.add('pmpropdf-tag-total-block', {
    label: 'Total',
    content: {
        type: "text",
        content: '{{total}}',
        style: {
            padding: "10px"
        },
        activeOnRender: 1
    },
    category: 'Invoice Shortcode',
    attributes: {
      title: 'Add the PMPRO PDF Invoice Total Shortcode to your template',
      class: "fa fa-money"
    }
  });

  blockManager.add('pmpropdf-tag-tax-block', {
    label: 'Tax',
    content: {
        type: "text",
        content: '{{tax}}',
        style: {
            padding: "10px"
        },
        activeOnRender: 1
    },
    category: 'Invoice Shortcode',
    attributes: {
      title: 'Add the PMPRO PDF Invoice Tax Shortcode to your template',
      class: "fa fa-percent"
    }
  });

  if(typeof pmpro_custom_shortcodes !== 'undefined' && typeof pmpro_custom_shortcodes === 'object'){
    for(var key in pmpro_custom_shortcodes){

      var nice_key = key.replace('{{', '');
      nice_key = nice_key.replace('}}', '');

      blockManager.add('pmpropdf-custom-' + nice_key, {
        label: nice_key,
        content: {
            type: "text",
            content: key,
            style: {
                padding: "10px"
            },
            activeOnRender: 1
        },
        category: 'Custom Shortcodes',
        attributes: {
          class: "fa fa-asterisk"
        }
      });

    }
  }

});

jQuery(function($){
  $('.save_template_btn').on('click', function(){
    if(typeof window.pmpro_pdf_editor !== 'undefined'){
      var current_html = window.pmpro_pdf_editor.getHtml();
      jQuery('#template_content').val(current_html);
      jQuery('#save_html_form').submit();
    }
  });
});
<?php

    // Bail if the user isn't logged-in at all.
    if ( ! is_user_logged_in() ) {
        die( __( 'Whoops! You need to be logged-in to get this data.', 'pmpro-pdf-invoices' ) );
    }

    // Get the order number.
    $order_code = esc_attr( $_GET['pmpropdf'] );

    $order_data = pmpropdf_get_order_by_code( $order_code );
    // See if order exists and user has the right permissions.
    if ( ! empty( $order_data ) ) {
        global $current_user;

        if ( $current_user->ID === intval( $order_data[0]->user_id ) || current_user_can( 'manage_options' ) ){
            //Continue to download the file.
          pmpropdf_download_invoice( $order_code );
        }
    } else {
        die( __( 'Invoice doesnt exist', 'pmpro-pdf-invoices' ) );
    }
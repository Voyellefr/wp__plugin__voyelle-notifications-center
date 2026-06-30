<?php
/**
 * Uninstall script : Delete all notifications , meta fields, options, etc
 * @author Floflo
 * @since 0.9 
 */

// If uninstall is not called from WordPress, exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

function voy_notif_delete_notifications() {
    //Delete notifications
    $notifications = new WP_Query( array( 'post_type' => 'voy_notification', 'posts_per_page' => -1 ) );
    while ( $notifications->have_posts() ) { $notifications->the_post();
        wp_delete_post( get_the_ID(), true );
    }
}
        
//Multisite install
if( is_multisite() ) {
    $sites = get_sites();
    foreach ( $sites as $site ) {
        switch_to_blog( (int) $site->blog_id );
        delete_option( 'voynotif_current_template' );
        delete_option( 'voynotif_email_logo' );
        delete_option( 'voynotif_email_title_color' );
        delete_option( 'voynotif_email_button_color' );
        delete_option( 'voynotif_email_backgroundcontent_color' );
        delete_option( 'voynotif_email_background_color' );
        delete_option( 'voynotif_email_footer_message' );
        delete_option( 'voynotif_sender_name' );
        delete_option( 'voynotif_sender_email' );                
        voy_notif_delete_notifications();
        restore_current_blog();
    }

//Single site install    
} else {
    delete_option( 'voynotif_current_template' );
    delete_option( 'voynotif_email_logo' );
    delete_option( 'voynotif_email_title_color' );
    delete_option( 'voynotif_email_button_color' );
    delete_option( 'voynotif_email_backgroundcontent_color' );
    delete_option( 'voynotif_email_background_color' );
    delete_option( 'voynotif_email_footer_message' );
    delete_option( 'voynotif_sender_name' );
    delete_option( 'voynotif_sender_email' );
    voy_notif_delete_notifications();
}
        
    




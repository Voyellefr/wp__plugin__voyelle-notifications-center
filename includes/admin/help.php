<?php


class VOYNOTIF_admin_help {

    /**
     * Constructor
     * 
     * @author Floflo
     * @since 1.0
     * @update 2016-11-26
     */
    function __construct() {
        add_action( 'admin_menu', array($this, 'create_submenu') ); //Hook for submenu creation   
    }
    
    
    /**
     * Submenu creation
     * 
     * @author Floflo
     * @since 1.0
     * @update 2016-11-26
     */
    function create_submenu() {
        global $submenu;
        $link_to_add = 'http://www.notificationscenter.com/documentation';
        // change edit.php to the top level menu you want to add it to 
        $submenu['edit.php?post_type=voy_notification'][] = array( __('Help', 'notifications-center'), 'edit_posts', $link_to_add);   
    } 
 
       
}

new VOYNOTIF_admin_help();




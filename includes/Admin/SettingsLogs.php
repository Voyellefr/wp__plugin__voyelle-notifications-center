<?php

namespace Voyelle\NotificationsCenter\Admin;

use Voyelle\NotificationsCenter\Core\Logs;
use Voyelle\NotificationsCenter\Framework\SettingsScreen;


class SettingsLogs extends SettingsScreen {

    /**
     * 
     */
    function __construct() {
        
        add_action('voynotif/save_field', array( $this, 'add_table' ), 10, 4);
        
        if( get_option( VOYNOTIF_FIELD_PREFIXE . 'activate_logs' ) == true ) {
            $this->id = 'logs';
            $this->title = __('Logs', 'notifications-center');
            $this->order = 200;
            $this->callback = array($this, 'html');
            parent::__construct();
        }
    }

    
    /*
     * -------------------------------------------------------------------------
     * GENERIC METHODS
     * ------------------------------------------------------------------------- 
     */
    
    function add_table( $field_id, $value, $result, $context ) {
        
        //Check if option is checked & if table hasn't alreaby been created
        if( $result != true || $field_id != 'voynotif_activate_logs' || $value != true || $context != 'option' ) return;
        
        global $wpdb;
        $table_name = Logs::get_table_name(); 
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
                notification_id mediumint(9) NOT NULL,
		date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		type varchar(250),
		recipient varchar(250),
		subject varchar(250),
                title varchar(250),
                status mediumint(9),
                context text,
                token varchar(250),
                opens text,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
        
    }
    
    function html() {        
        include_once( __DIR__ . '/views/settings-logs.php' );
    }

  

}

//Activation
new SettingsLogs();



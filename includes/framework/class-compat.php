<?php

class VOYNOTIF_compat {

    function __construct() {
        $this->missing_plugins = array();
        $this->load();   
    }
    
    /**
     * 
     */
    function load() {
        if( $this->check_dependencies() ) {
            $this->init();
        }
    }
    
    
    /**
     * Chekc if Needed dependencies are active. If not, plugin will not load
     * 
     * @author Floflo
     * @since 1.3.0
     * 
     * @return boolean
     */
    function check_dependencies() {
        if( empty( $this->dependencies ) || !is_array( $this->dependencies ) ) {
            return true;
        }
        
        $auth = true;        
        foreach( $this->dependencies as $plugin_name => $plugin_data ) {           
            if( !$this->is_plugin_dependencie_ok($plugin_name, $plugin_data) ) {
                $auth = false;
                $this->missing_plugins[] = $plugin_name;
            }                    
        }
       
        if( $auth == true ) {
            return true;
        }
        
        return false;
        
    }
    
    
    /**
     * 
     * @param type $plugin_name
     * @param type $plugin_data
     * @return boolean
     */
    function is_plugin_dependencie_ok($plugin_name, $plugin_data) {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if( !is_plugin_active($plugin_data['file']) ) {
            return false;
        }
        
        if( !isset( $plugin_data['version'] ) || empty($plugin_data['version']) ) {
            return true;
        }

        $wp_plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_data['file'] );
        if( version_compare( $wp_plugin_data['Version'], $plugin_data['version'] ) >= 0 ) {
            return true;
        }
        
        return false;
    }
    
}


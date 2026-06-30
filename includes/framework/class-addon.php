<?php

class VOYNOTIF_addon {
    
    
    /**
     * Constructor
     */
    function __construct() {
        $this->missing_plugins = array();
        $this->init();      
    }
    
    
    /**
     * 
     */
    function init() {
        if( $this->check_dependencies() ) {
            $this->add_filter( 'voynotif/settings/screens', array( $this, 'register_settings_screen' ) );
        } else {
            add_action( 'admin_notices', array( $this, 'warning_notices' ) );           
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
    
    
    /**
     * 
     */
    function warning_notices() {
        $needed_plugin_message = '';
        foreach( $this->missing_plugins as $missing_plugin ) {
            $data = $this->dependencies[$missing_plugin];
            $needed_plugin_message .= '<strong>'.$missing_plugin.' (version '.$data['version'].')</strong>';
        }
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php printf(esc_html__( '%1$s  needs %2$s to work.', 'notifications-center-profilebuilder' ), $this->name, $needed_plugin_message ); ?></p>
        </div>
        <?php         
    }
 
    /**
     * Check if called function is present in child class before adding filter
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
     * 
     * @param string $tag filter tag
     * @param string $function_to_add function name present in child class
     * @param int $priority filter priority
     * @param mixed $accepted_args
     */
    function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        if( is_callable($function_to_add) ) {
                add_filter($tag, $function_to_add, $priority, $accepted_args);
        }
    } 


    /**
     * Check if called function is present in child class before adding action
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
     * 
     * @param string $tag action tag
     * @param string $function_to_add function name present in child class
     * @param int $priority filter priority
     * @param mixed $accepted_args
     */
    function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        if( is_callable($function_to_add) ) {
                add_action($tag, $function_to_add, $priority, $accepted_args);
        }
    }      
    
}


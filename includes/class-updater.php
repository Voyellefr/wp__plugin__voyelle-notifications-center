<?php

class VOYNOTIF_updater {

    /**
     * @var version Current Notifications Center, registered in database
     */
    var $current_version;
    
    /**
     * @var version New Notifications center version from plugin main file. Should be different from $current version after plugin update & before notification update script 
     */
    var $new_version;

    
    /**
     * Constructor
     * 
     * @author Floflo
     * @since 1.1.0
     * @update 2017-01-17
     */
    function __construct() {
        
        $this->current_version = '';
        
        $update_options = get_option( VOYNOTIF_FIELD_PREFIXE . 'update_options' );
        if( !empty( $update_options ) ) {
            $this->current_version = $update_options['current_version'];
        }       
        $this->new_version = VOYNOTIF_VERSION;
        
    }
    
    
    /**
     * Init updater for previous plugins version
     * 
     * @author Floflo
     * @since 1.1.0
     * @update 2017-01-17
     */
    function init() {
        
        $update_options = get_option( VOYNOTIF_FIELD_PREFIXE . 'update_options' );
        if( empty( $update_options ) ) {
            
            $update_options = array(
                'current_version' => $this->new_version,
            );           
            update_option( VOYNOTIF_FIELD_PREFIXE . 'update_options', $update_options );
            $this->current_version = $update_options['current_version'];
            
        }      
        
    }
 
    
    /**
     * Update current version to database. Runned at the end of update() method
     * 
     * @author Floflo
     * @since 1.1.0
     * @update 2017-01-17
     */
    private function update_current_version() {
        $update_options = array(
            'current_version' => $this->new_version,
        ); 
        update_option( VOYNOTIF_FIELD_PREFIXE . 'update_options', $update_options );
    }
    
    
    /**
     * Perform update. Triggered if plugin version is diffrent from database version
     * 
     * @author Floflo
     * @since 1.1.0
     * @update 2017-01-17
     * 
     * @return boolean Return false if any error occured, else returns true
     */
    function update() {
        
        //List versions
        $versions = array(
            '1.1.0' => array($this, 'update_to_110' ),
        );
        
        foreach( $versions as $version => $callback ) {
            
            if (version_compare( $this->current_version, $version ) >= 0 ) continue; //If current version is higher from update, don't do anything
            
            $result = call_user_func( $callback );           
            if( $result != true ) {
                return false;
            } 
            
        }
        
        $this->update_current_version();
        return true;
        
    }
    
    
    /**
     * Update script to 1.1.0
     * 
     * @author Floflo
     * @since 1.1.0
     * @update 2017-01-17
     * 
     * @return boolean Returns true
     */
    private function update_to_110() {
        
        //Transfer blocking ability to settings
        $types = voynotif_get_notifications_types();
        if( !empty( $types ) ) {
            foreach( $types as $type_id => $type_data ) {
                if( $type_data['overrides_wp'] != true ) continue;
                $notifications = voynotif_get_notifications( $type_id );
                if( ! empty( $notifications ) ) {
                    
                    if( $type_id == 'core_update'  ) {
                        update_option( VOYNOTIF_FIELD_PREFIXE . 'block_core_update_success_status', 1 );
                        update_option( VOYNOTIF_FIELD_PREFIXE . 'block_core_update_fail_status', 1 );
                        update_option( VOYNOTIF_FIELD_PREFIXE . 'block_core_update_critical_status', 1 );
                        update_option( VOYNOTIF_FIELD_PREFIXE . 'block_core_update_manual_status', 1 );
                    } else {
                        update_option( VOYNOTIF_FIELD_PREFIXE . 'block_' . $type_id . '_status', 1 );
                    }
                    
                    
                }
            }
        }
        return true;
        
    }
    
}
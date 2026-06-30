<?php
/**
 * NOTIFICATION Core Update
 */
class VOYNOTIF_notification_type_core_update extends VOYNOTIF_notification_type {
    
    
    /**
     * Constructor
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
     */
    function __construct() {
        
        $this->name = 'core_update';
        $this->label = __( 'Wordpress Core Update', 'notifications-center' );
        $this->tags = array(
            'system'
        );
        $this->overrides_wp = true;
        
        parent::__construct();
    }
    
    
    /**
     * Declare all needed hook to perform the current notification
     * Called in parent class just after __construct method
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
     */
    function init() {
        add_filter( 'auto_core_update_send_email', array( $this, 'core_update' ), 100, 4 );        
    }
    
    
    /**
     * Register setting fields, displayed in the setting metabox if current notification type is selected
     * Called in voynotif/notification/fields/settings/type={current_type} filter
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
     * 
     * @param array $fields array of registered fields
     * @return array array of registered fields
     */
    function setting_fields($fields) {
        $fields[] = 
            array(
                'id' => 'voynotif_core_update_type',
                'label' => __( 'Update result', 'notifications-center' ),
                'type' => 'select',
                'choices' => array(
                    'success' => __( 'Wordpress succesfuly updated', 'notifications-center' ),
                    'fail' => __( 'Fail', 'notifications-center' ),
                    'critical' => __( 'Critical', 'notifications-center' ),
                    'manual' => __( 'New update available', 'notifications-center' ),
                ),
            );
        return $fields;
    }

    
    /**
     * 
     * @param array $masks
     * @return array
     */
    function custom_masks($masks) {
        
        //unset $masks['id'];
        $masks['core_update_type'] = array(
            'title' => __( 'Update type', 'notifications-center'),
            'description' => '',
            'tag' => 'system',
            'dummy_data' => 'Success',
            'value' => '',
        );
        $masks['core_update_version'] = array(
            'title' => __( 'Update version', 'notifications-center'),
            'description' => '',
            'tag' => 'system',
            'dummy_data' => '',
            'value' => '',
        );
        $masks['core_update_newslink'] = array(
            'title' => __( 'Update news link', 'notifications-center'),
            'description' => '',
            'tag' => 'system',
            'dummy_data' => '',
            'value' => '',
        );
        return $masks;
    }
    
    
    /*
     * -------------------------------------------------------------------------
     * ADD ALL NEEDED CUSTOM METHODS UNDER THIS LINE
     * ------------------------------------------------------------------------- 
     */
        

    /**
     * Overload Wordpress core update defaut message
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-07-09
     * 
     * @param bool $send
     * @param string $type
     * @param object $core_update
     * @param mixed $result
     * @return boolean
     */
    function core_update( $send, $type, $core_update, $result ) {
            
        //Get notifications
        $notifications = voynotif_get_notifications( $this->name );

        //Return true if no matching notifications, to let wordpress use defaut message
        if( ! empty( $notifications ) ) {
            foreach( $notifications as $notification ) {

                if( $type != $notification->get_field('core_update_type') ) {
                    continue;
                }

                //Add context info to perform masks
                $notification->set_context_info( array(
                    'core_update_type' => $type,
                    'core_update_version' => $core_update->current,
                    'core_update_newslink' => admin_url() . '/about.php',
                ) );

                //Build CTA info
                /*$cta = array(
                    'label' => __( 'What\'s new', 'notifications-center' ),
                    'url'   =>  admin_url() . '/about.php',
                );
                $notification->set_button_info($cta); */                  

                //Send notification
                $notification->send_notification();   

            }
        }
        
        //Return false to block WP default message
        if( get_option( VOYNOTIF_FIELD_PREFIXE . 'block_' . $this->name . '_' . $type . '_status' ) == 1 ) {
            return false;
        }
        
        //default return
        return $send;
    }
        
}
new VOYNOTIF_notification_type_core_update();


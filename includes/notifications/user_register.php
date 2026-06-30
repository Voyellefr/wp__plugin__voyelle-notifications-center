<?php

class VOYNOTIF_notification_type_user_register extends VOYNOTIF_notification_type {
    
    function __construct() {
        
        $this->name = 'user_register';
        $this->label = __( 'User register', 'notifications-center' );
        $this->tags = array(
            'user',
            'system'
        );
        
        parent::__construct();
    }
    
    function init() {
        add_action( 'register_new_user', array( $this, 'user_register' ), 9 );
        add_action( 'edit_user_created_user', array( $this, 'user_register' ), 9, 2 );
    }
    
    
    /**
     * Register setting fields, displayed in the setting metabox if current notification type is selected
     * Called in voynotif/notification/fields/settings/type={current_type} filter
     * 
     * @author Floflo
     * @since 0.9
     * @update 2013-07-13
     * 
     * @param array $fields array of registered fields
     * @return array array of registered fields
     */
    function setting_fields($fields) {
        $fields[] = 
            array(
                'id' => 'voynotif_settings_userrole',
                'label' => __( 'User role', 'notifications-center' ),
                'description' => __( 'For which user role would you like to send the notification', 'notifications-center' ),
                'type' => 'selectmultiple',
                'choices' => parent::_get_user_roles(),
                'params' => array(
                    'select2' => true,
                    'placeholder' => __( 'Select user role(s)', 'notifications-center' ),
                )
            );
        return $fields;
    }    

    
    /**
     * Add recipients choices (or unset existing ones) in the recipient select field
     * 
     * @author Floflo
     * @since 1.1.0
     * @update 2016-07-12
     * 
     * @param array $types array of default choices (emails, roles, users)
     * @return arrray array of choices
     */
    function set_recipient_types($types) {        
        $types['requested_user'] = __( 'New registered user', 'notifications-center' );
        return $types;
    }
    
    
    /**
     * Register custom masks
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-07-12
     * 
     * @param array $masks
     * @return string
     */
    function custom_masks($masks) {
        
        //unset $masks['id'];
        $masks['password_reset_link'] = array(
            'title' => __( 'Password reset link', 'notifications-center' ),
            'description' => '',
            'tag' => 'system',
        );
        return $masks;
    }    
    
    
    /*
     * -------------------------------------------------------------------------
     * ADD ALL NEEDED CUSTOM METHODS UNDER THIS LINE
     * ------------------------------------------------------------------------- 
     */
    
    function user_register( $user_id, $notify = 'both' ) {

        //Get notifications
        $notifications = voynotif_get_notifications( $this->name );

        //If Notifications
        if( ! empty( $notifications ) ) {
            
            //For each notification
            foreach( $notifications as $notification ) {
               
                $user = get_userdata($user_id);
                $allowed_roles = $notification->get_field('userrole');
                if( ! empty( $allowed_roles ) ) {
                    $allow = false;
                    foreach ($user->roles as $role_name) {
                        if( in_array( $role_name, $allowed_roles ) ) {
                            $allow = true;
                        }
                    }                   
                    if( $allow != true ) {
                        continue;
                    }
                }
    
                $notification->set_context_info( array(
                    'user_id' => $user->ID,
                    'password_reset_link' => 'testlink'
                ) );
                
                //May Set user email as recipient
                if( $notification->recipient_type == 'requested_user' )
                    $notification->add_recipient($user->user_email);               

                //Send notification
                $notification->send_notification();                
                
            }
        }
        
        //If we need to block default wordpress notifications when new user registered
        if( get_option( VOYNOTIF_FIELD_PREFIXE . 'block_' . $this->name . '_user_status' ) == 1 || get_option( VOYNOTIF_FIELD_PREFIXE . 'block_' . $this->name . '_admin_status' ) == 1 ) {
            
            //First we remove the default actions
            remove_action( 'register_new_user',      'wp_send_new_user_notifications' );
            remove_action( 'edit_user_created_user', 'wp_send_new_user_notifications' );    
            
            //If we need to block only admin notification, we set a new call for wordpress wp_new_user_notification function with 'user' value
            if( get_option( VOYNOTIF_FIELD_PREFIXE . 'block_' . $this->name . '_user_status' ) != 1 ) {                
                wp_new_user_notification( $user_id, null, 'user' );
            }
            
            //If we need to block only user notification, we set a new call for wordpress wp_new_user_notification function with 'admin' value
            if( get_option( VOYNOTIF_FIELD_PREFIXE . 'block_' . $this->name . '_admin_status' ) != 1 ) {                
                wp_new_user_notification( $user_id, null, 'admin' );
            }
            
        }

    }
    
        
}
new VOYNOTIF_notification_type_user_register();



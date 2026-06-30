<?php
/**
 * NOTIFICATION User password changed
 */
class VOYNOTIF_notification_type_user_password_changed extends VOYNOTIF_notification_type {
    
    
    /**
     * Constructor
     * 
     * @author Floflo
     * @since 1.1.0
     * @update 2016-12-28
     */
    function __construct() {
        
        $this->name = 'user_password_changed';
        $this->label = __( 'User password changed', 'notifications-center' );
        $this->tags = array(
            'user',
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
     * @since 1.1.0
     * @update 2016-12-28
     */
    function init() {
        add_action( 'after_password_reset', array( $this, 'password_changed' ), 1, 1 );        
    }
    
    
    /**
     * Add recipients choices (or unset existing ones) in the recipient select field
     * 
     * @author Floflo
     * @since 1.1.0
     * @update 2016-12-28
     * 
     * @param array $types array of default choices (emails, roles, users)
     * @return arrray array of choices
     */
    function set_recipient_types($types) {
        
        //Unset default recipient types
        //unset($types['emails']);
        //unset($types['roles']);
        //unset($types['users']);
        
        //Add custom recipient types
        $types['requested_user'] = __( 'User who requested', 'notifications-center' );
        
        //return types
        return $types;
    }

    
    /**
     * Register custom masks
     * 
     * @author Floflo
     * @since 1.1.0
     * @update 2016-12-28
     * 
     * @param array $masks
     * @return string
     */
    /*function custom_masks($masks) {
        
        //unset $masks['id'];
        $masks['password_reset_link'] = array(
            'title' => __( 'Password reset link', 'notifications-center' ),
            'description' => '',
            'tag' => 'system',
            'dummy_data' => 'John',
            'value' => '',
        );
        return $masks;
    }*/
    
    
    /*
     * -------------------------------------------------------------------------
     * ADD ALL NEEDED CUSTOM METHODS UNDER THIS LINE
     * ------------------------------------------------------------------------- 
     */
    
    
    /**
     * Add filter to wordpress default message. If no matching notification, do nothing. Else block defaut message and use custom notification instead
     * 
     * @author Floflo
     * @since 1.1.0
     * @update 2016-12-28
     */
    function password_changed( $user ) {
            
        //Get notifications
        $notifications = voynotif_get_notifications( $this->name );

        //If Notifications
        if( ! empty( $notifications ) ) {
            
            //For each notification
            foreach( $notifications as $notification ) {
    
                $notification->set_context_info( array(
                    'user_id' => $user->ID,
                ) );

                //May Set user email as recipient
                if( $notification->recipient_type == 'requested_user' )
                    $notification->add_recipient($user->user_email);               

                //Send notification
                $notification->send_notification();   

            }
        
        }       

        //Return false to block WP default message
        if( get_option( VOYNOTIF_FIELD_PREFIXE . 'block_' . $this->name . '_status' ) == 1 ) {
            remove_action( 'after_password_reset', 'wp_password_change_notification' );
        }      
        
        //Default return
        return $message;
    }
        
}
new VOYNOTIF_notification_type_user_password_changed();



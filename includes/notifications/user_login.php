<?php
/**
 * NOTIFICATION Pending Content
 */
class VOYNOTIF_notification_type_user_login extends VOYNOTIF_notification_type {
    
    
    /**
     * Constructor
     * 
     * @author Floflo
     * @since 0.9.1
     * @update 2016-07-28
     */
    function __construct() {
        
        $this->name = 'user_login';
        $this->label = __( 'User Log in', 'notifications-center' );
        $this->tags = array(
            'user',
            'system'
        );
        
        parent::__construct();
    }
    
    
    /**
     * Declare all needed hook to perform the current notification
     * Called in parent class just after __construct method
     * 
     * @author Floflo
     * @since 0.9.1
     * @update 2016-07-28
     */
    function init() {
        add_action( 'wp_login', array( $this, 'login' ), 10, 2 );        
    }
    
    
    /**
     * Add recipients choices (or unset existing ones) in the recipient select field
     * 
     * @author Floflo
     * @since 0.9.1
     * @update 2016-07-28
     * 
     * @param array $types array of default choices (emails, roles, users)
     * @return arrray array of choices
     */
    function set_recipient_types($types) {
        
        //Add custom recipient types
        $types['requested_user'] = __( 'User who logged in', 'notifications-center' );
        
        //return types
        return $types;
    }
    
    
    /*
     * -------------------------------------------------------------------------
     * ADD ALL NEEDED CUSTOM METHODS UNDER THIS LINE
     * ------------------------------------------------------------------------- 
     */
    
    
    /**
     * Send email when user logged in
     * 
     * @author Floflo
     * @since 0.9.1
     * @update 2016-07-28
     */
    function login( $user_login, $user ) {
            
        //Get notifications
        $notifications = voynotif_get_notifications( $this->name );

        //Return if no matching notifications
        if( empty( $notifications ) ) {
            return;
        }
        
        //For each notification
        foreach( $notifications as $notification ) {

            //Set recipient email address
            if( $notification->recipient_type == 'requested_user' ) {
                $notification->add_recipient($user->user_email);
            }
            
            //Set context info
            $notification->set_context_info( array( 
                'user_id' => $user->ID, 
            ) );

            //Send notification
            $notification->send_notification();   

        }

    }
        
}
new VOYNOTIF_notification_type_user_login();



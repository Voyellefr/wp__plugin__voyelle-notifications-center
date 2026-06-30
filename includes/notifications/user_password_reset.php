<?php
/**
 * NOTIFICATION Pending Content
 */
class VOYNOTIF_notification_type_user_password_reset extends VOYNOTIF_notification_type {
    
    
    /**
     * Constructor
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-07-12
     */
    function __construct() {
        
        $this->name = 'user_password_reset';
        $this->label = __( 'User password reset', 'notifications-center' );
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
     * @since 0.9
     * @update 2016-07-12
     */
    function init() {
        add_filter( 'retrieve_password_message', array( $this, 'password_reset' ), 10, 4 );        
    }
    
    
    /**
     * Register content fields, displayed in the content metabox if current notification type is selected
     * Called in voynotif/notification/fields/settings/type={current_type} filter
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-12-10
     * 
     * @param array $fields array of registered fields
     * @return array array of registered fields
     */    
    function content_fields($fields) {
        /*$fields[] = 
            array(
                'id' => 'voynotif_content_display_cta',
                'label' => __( 'Add button with reset link', 'notifications-center' ),
                'description' => __( 'The notification will be send to all users in the selected roles', 'notifications-center' ),
                'type' => 'boolean',                
                'params' => array(
                    'title' => __( 'Add Link to the content', 'notifications-center' )
                ),                
            ); */     
        return $fields;        
    }
    
    
    /**
     * Add recipients choices (or unset existing ones) in the recipient select field
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-07-12
     * 
     * @param array $types array of default choices (emails, roles, users)
     * @return arrray array of choices
     */
    function set_recipient_types($types) {
        
        //Unset default recipient types
        unset($types['emails']);
        unset($types['roles']);
        unset($types['users']);
        
        //Add custom recipient types
        $types['requested_user'] = __( 'User who requested', 'notifications-center' );
        
        //return types
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
            'dummy_data' => 'John',
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
     * Add filter to wordpress default message. If no matching notification, do nothing. Else block defaut message and use custom notification instead
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-12-10
     */
    function password_reset( $message, $key, $user_login, $user_data ) {
            
        //Get notifications
        $notifications = voynotif_get_notifications( $this->name );

        //If Notifications
        if( ! empty( $notifications ) ) {
            
            //For each notification
            foreach( $notifications as $notification ) {
          
                $password_reset_link = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');
                $notification->set_context_info( array(
                    'user_id' => $user_data->ID,
                    'password_reset_link' => $password_reset_link,
                ) );

                //Set recipient email address
                $notification->add_recipient($user_data->user_email);

                //Add CTA
                $notification->set_button_info( array(
                    'label' => __( 'Reset password', 'notifications-center' ),
                    'url'   =>  $password_reset_link,
                ) );                   


                //Send notification
                $notification->send_notification();   

            }
        
        }       

        //Return false to block WP default message
        if( get_option( VOYNOTIF_FIELD_PREFIXE . 'block_' . $this->name . '_status' ) == 1 ) {
            return false;
        }      
        
        //Default return
        return $message;
    }
        
}
if( ! is_multisite() OR ( is_multisite() AND network_site_url() == site_url('/') ) ) {
    new VOYNOTIF_notification_type_user_password_reset();
}


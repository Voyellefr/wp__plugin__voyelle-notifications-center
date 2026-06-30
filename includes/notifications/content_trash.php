<?php
/**
 * NOTIFICATION Pending Content
 */
class VOYNOTIF_notification_type_content_trash extends VOYNOTIF_notification_type {
    
    
    /**
     * Constructor
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
     */
    function __construct() {
        
        $this->name = 'content_trash';
        $this->label = __( 'Trashed content', 'notifications-center' );
        $this->tags = array(
            'content'
        );
        
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
        add_action( 'transition_post_status', array( $this, 'content_trash' ), 10, 3 );        
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
                'id' => 'voynotif_settings_posttype',
                'label' => __( 'Post type', 'notifications-center' ),
                'description' => __( 'For which post type would you like to send the notification', 'notifications-center' ),
                'type' => 'selectmultiple',
                'choices' => parent::_get_post_types(),
                'params' => array(
                    'select2' => true,
                    'placeholder' => __( 'Select Post type(s)', 'notifications-center' ),
                )
            );
        return $fields;
    }

    
    /**
     * Add recipients choices (or unset existing ones) in the recipient select field
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
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
        $types['post_author'] = __( 'Post author', 'notifications-center' );
        
        //return types
        return $types;
    }
    
    
    /*
     * -------------------------------------------------------------------------
     * ADD ALL NEEDED CUSTOM METHODS UNDER THIS LINE
     * ------------------------------------------------------------------------- 
     */
    
    
    /**
     * Set up custom hooks to check all cpt trashed
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
     */
    function content_trash( $new_status, $old_status, $post ) {

        if ( 'trash' == $new_status AND $old_status != 'trash' ) {

            //Get notifications
            $notifications = voynotif_get_notifications( $this->name );

            //Return if no matching notifications
            if( empty( $notifications ) ) {
                return;
            }

            //For each notification
            foreach( $notifications as $notification ) {

                //If current post type isn't concerned by the notification
                if( ! in_array( $post->post_type, $notification->get_field('settings_posttype') ) ) {
                    continue;
                }

                //Set context info
                $notification->set_context_info( array(
                    'post_id' => $post->ID,
                ) );
                
                //Add author email to recipients
                if( $notification->recipient_type == 'post_author' ) { 
                    $author_id = $post->post_author;
                    $author_email = get_the_author_meta( 'user_email', $author_id );
                    $notification->add_recipient($author_email);
                }

                //Send notification
                $notification->send_notification();   
                
            }
        }
    }
        
}
new VOYNOTIF_notification_type_content_trash();


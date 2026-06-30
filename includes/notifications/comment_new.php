<?php
/**
 * NOTIFICATION Pending Content
 * 
 * -----SUMMARY-----
 * 1. Replace comment_new with my_type_name
 * 2. Replace TYPEDESCRIPTION with "My description"
 * 3. Change Tags
 * 
 */
class VOYNOTIF_notification_type_comment_new extends VOYNOTIF_notification_type {
    
    
    /**
     * Constructor
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
     */
    function __construct() {
        
        $this->name = 'comment_new';
        $this->label = __( 'New published comment', 'notifications-center' );
        $this->tags = array(
            'comment', 
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
        add_action( 'comment_post', array( $this, 'comment_new' ), 100, 3 );
        add_action( 'transition_comment_status', array( $this, 'transition_to_approved' ), 100, 3 ); 
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
                'id' => 'voynotif_settings_posttype',
                'label' => __( 'Post type', 'notifications-center' ),
                //'description' => __( 'For which post type would you like', 'notifications-center' ),
                'type' => 'selectmultiple',
                'choices' => parent::_get_post_types(),
                'params' => array(
                    'select2' => true
                )
            );
        $fields[] = 
            array(
                'id' => 'voynotif_settings_allow_responses',
                'label' => __( 'Send notification also for comment responses', 'notifications-center' ),
                'description' => __( 'If checked, notification will be send for comments AND responses to existing comments', 'notifications-center' ),
                'type' => 'boolean',                
                'params' => array(
                    'title' => __( 'Send notification also for comment responses', 'notifications-center' )
                ),                
            );          
        return $fields;
    }
    
    
    /**
     * Register content fields, displayed in the content metabox if current notification type is selected
     * Called in voynotif/notification/fields/settings/type={current_type} filter
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
     * 
     * @param array $fields array of registered fields
     * @return array array of registered fields
     */    
    function content_fields($fields) {
        $fields[] = 
            array(
                'id' => 'voynotif_content_display_cta',
                'label' => __( 'Add Link to the content', 'notifications-center' ),
                'description' => __( 'The notification will be send to all users in the selected roles', 'notifications-center' ),
                'type' => 'boolean',                
                'params' => array(
                    'title' => __( 'Add Link to the content', 'notifications-center' )
                ),                
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
        $types['comment_author'] = __( 'Comment author', 'notifications-center' );
        
        //return types
        return $types;
    }
    
    
    /*
     * -------------------------------------------------------------------------
     * ADD ALL NEEDED CUSTOM METHODS UNDER THIS LINE
     * ------------------------------------------------------------------------- 
     */
    
    
    /**
     * 
     * @param type $comment_id
     * @return type
     */
    function publish_comment_message( $comment_id ) {
        
        //Get notifications
        $notifications = voynotif_get_notifications( $this->name );

        //Return if no matching notifications
        if( empty( $notifications ) ) {
            return;
        }   
                
        $comment = get_comment( $comment_id );         
        $post = get_post( $comment->comment_post_ID );

        //For each notification
        foreach( $notifications as $notification ) {
            
            //send notification if has parent comment
            if( ! empty( $comment->comment_parent ) AND $notification->get_field('settings_allow_responses') == false ) {
                continue;
            }
            
            //If current post type isn't concerned by the notification
            $post_type = get_post_type( $post->ID );
            if( ! in_array( $post_type, $notification->get_field('settings_posttype') ) ) {
                continue;
            }
            
            $notification->set_context_info( array(
                'comment_id' => $comment_id,
                'post_id' => $post->ID,
            ) );
            
            if( $notification->get_field('content_display_cta') == true ) {
                $notification->set_button_info( array(
                    'label' => __( 'View post', 'notifications-center' ),
                    'url'   => get_permalink( $post->ID ),
                ) );
            }
            
            if( $notification->recipient_type == 'post_author' ) { 
                $author_id = $post->post_author;
                $author_email = get_the_author_meta( 'user_email', $author_id );
                $notification->add_recipient($author_email);
            } elseif( $notification->recipient_type == 'comment_author'  ) {
                $notifications->add_recipient( $comment->comment_author_email );
            }

            //Send notification
            $notification->send_notification();   

        }        
        
    }
    
    
    /**
     * 
     * @param type $comment_ID
     * @param type $comment_approved
     * @param type $commentdata
     */
    function comment_new( $comment_ID, $comment_approved, $commentdata ) {        
        if( $comment_approved === 1 ) {
            $this->publish_comment_message( $comment_ID );  
        }                     
    }
    
    
    /**
     * 
     * @param type $new_status
     * @param type $old_status
     * @param type $comment
     */
    function transition_to_approved( $new_status, $old_status, $comment ) {
        if( $new_status == 'approved' AND $old_status != 'approved'  ) {
            $this->publish_comment_message( $comment->comment_ID ); 
        }
    }
        
}
new VOYNOTIF_notification_type_comment_new();


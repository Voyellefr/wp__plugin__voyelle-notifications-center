<?php
/**
 * NOTIFICATION Pending Content
 * 
 * -----SUMMARY-----
 * 1. Replace comment_response with my_type_name
 * 2. Replace Comment response with "My description"
 * 3. Change Tags
 * 
 */
class VOYNOTIF_notification_type_comment_reply extends VOYNOTIF_notification_type {
    
    
    /**
     * Constructor
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
     */
    function __construct() {
        
        $this->name = 'comment_reply';
        $this->label = __( 'Reply to a comment', 'notifications-center' );
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
        unset($types['emails']);
        unset($types['roles']);
        unset($types['users']);
        
        //Add custom recipient types
        $types['comment_author'] = __( 'Comment author', 'notifications-center' );
        
        //return types
        return $types;
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
                'label' => __( 'Add Link to the post', 'notifications-center' ),
                //'description' => __( 'The notification will be send to all users in the selected roles', 'notifications-center' ),
                'type' => 'boolean',                
                'params' => array(
                    'title' => __( 'Add Link to the content', 'notifications-center' )
                ),                
            );      
        return $fields;        
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
        $masks['reply_author'] = array(
            'title' => __( 'Reply author', 'notifications-center'),
            'description' => '',
            'tag' => 'comment',
            'dummy_data' => 'John',
            'value' => '',
        );
        $masks['reply_author_email'] = array(
            'title' => __( 'Reply author email', 'notifications-center'),
            'description' => '',
            'tag' => 'comment',
            'dummy_data' => 'John',
            'value' => '',
        );
        $masks['reply_author_url'] = array(
            'title' => __( 'Reply author URL', 'notifications-center'),
            'description' => '',
            'tag' => 'comment',
            'dummy_data' => 'John',
            'value' => '',
        );
        $masks['reply_date'] = array(
            'title' => __( 'Reply date', 'notifications-center'),
            'description' => '',
            'tag' => 'comment',
            'dummy_data' => 'John',
            'value' => '',
        );
        $masks['reply_content'] = array(
            'title' => __( 'Reply content', 'notifications-center'),
            'description' => '',
            'tag' => 'comment',
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
     * 
     * @param type $reply_id
     * @return type
     */
    function comment_reply_message( $reply_id ) {
        
        //Get notifications
        $notifications = voynotif_get_notifications( $this->name );

        //Return if no matching notifications
        if( empty( $notifications ) ) {
            return;
        }   
                
        $reply = get_comment( $reply_id );  
        $comment = get_comment( $reply->comment_parent );
        $post = get_post( $reply->comment_post_ID );

        //For each notification
        foreach( $notifications as $notification ) {
            
            //set context info (add reply info)
            $notification->set_context_info( array(
                'comment_id' => $comment->comment_ID,
                'post_id' => $reply->comment_post_ID,
                'reply_author' => $reply->comment_author,
                'reply_author_email' => $reply->comment_author_email,
                'reply_author_url' => $reply->comment_author_url,
                'reply_date' => $reply->comment_date,
                'reply_content' => $reply->comment_content,
            ) );
            
            //Set button to post
            if( $notification->get_field('content_display_cta') == true ) {
                $notification->set_button_info( array(
                    'label' => __( 'View post', 'notifications-center' ),
                    'url'   => get_permalink( $post->ID ),
                ) );
            }
            
            //Set recipient
            $notification->add_recipient( $comment->comment_author_email );
            
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
            $comment = get_comment( $comment_ID );
            if( $comment->comment_parent > 0 ) {
                $this->comment_reply_message( $comment_ID );  
            }
            
        }                     
    }
    
    
    /**
     * 
     * @param type $new_status
     * @param type $old_status
     * @param type $comment
     */
    function transition_to_approved( $new_status, $old_status, $comment ) {
        if( $new_status == 'approved' && $old_status != 'approved' && $comment->comment_parent > 0 ) {
            $this->comment_reply_message( $comment->comment_ID ); 
        }
    }
        
}
new VOYNOTIF_notification_type_comment_reply();


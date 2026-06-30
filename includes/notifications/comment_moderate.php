<?php
/**
 * NOTIFICATION Pending Content
 * 
 * -----SUMMARY-----
 * 1. Replace comment_pending with my_type_name
 * 2. Replace Comment response with "My description"
 * 3. Change Tags
 * 
 */
class VOYNOTIF_notification_type_comment_pending extends VOYNOTIF_notification_type {
    
    
    /**
     * Constructor
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
     */
    function __construct() {
        
        $this->name = 'comment_moderate';
        $this->label = __( 'Comment Moderate', 'notifications-center' );
        $this->tags = array(
            'comment',
            'content'
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
        add_filter( 'comment_moderation_recipients', array( $this, 'comment_moderate' ), 20, 2 );
        //add_action( 'transition_comment_status', array( $this, 'transition_to_approved' ), 100, 3 );       
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
                //'description' => __( 'The notification will be send to all users in the selected roles', 'notifications-center' ),
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
        $types['comment_author'] = __( 'Comment author', 'notifications-center' );
        $types['post_author'] = __( 'Post author', 'notifications-center' );
        
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
        $masks['comment_approve_link'] = array(
            'title' => __( 'Comment approve link', 'notifications-center'),
            'description' => '',
            'tag' => 'comment',
        );
        $masks['comment_trash_link'] = array(
            'title' => __( 'Comment trash link', 'notifications-center'),
            'description' => '',
            'tag' => 'comment',
        );
        $masks['comment_spam_link'] = array(
            'title' => __( 'Comment spam link', 'notifications-center'),
            'description' => '',
            'tag' => 'comment',
        );
        return $masks;
    }
    
    
    /*
     * -------------------------------------------------------------------------
     * ADD ALL NEEDED CUSTOM METHODS UNDER THIS LINE
     * ------------------------------------------------------------------------- 
     */
    
    
    /**
     * Set up custom hooks to check all cpt pendings
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
     */
    function comment_moderate ( $emails, $comment_id ) {

        if( get_comment_type( $comment_id ) != 'comment' ) {
            return $emails;
        }             
        
        //Get notifications
        $notifications = voynotif_get_notifications( $this->name );

        //Return if no matching notifications
        if( ! empty( $notifications ) ) {

            $comment = get_comment( $comment_id );         
            $post = get_post( $comment->comment_post_ID );            
            $comment_approve_link = admin_url() . 'comment.php?action=approve&c='.$comment_id.'#wpbody-content';
            $comment_trash_link = admin_url() . 'comment.php?action=trash&c='.$comment_id.'#wpbody-content';
            $comment_spam_link = admin_url() . 'comment.php?action=spam&c='.$comment_id.'#wpbody-content';

            //For each notification
            foreach( $notifications as $notification ) {
                                
                //Set context
                $notification->set_context_info( array(
                    'post_id' => $post->ID,
                    'comment_id' => $comment_id,
                    'comment_approve_link' => $comment_approve_link,
                    'comment_trash_link' => $comment_trash_link,
                    'comment_spam_link' => $comment_spam_link,
                ) );

                //Set recipient(s)
                if( $notification->recipient_type == 'comment_author' ) {
                    $notification->add_recipient($comment->comment_author_email);
                } elseif( $notification->recipient_type == 'post_author' ) {
                    $author_id = $post->post_author;
                    $author_email = get_the_author_meta( 'user_email', $author_id );
                    $notification->add_recipient($author_email);
                }

                //Set button info
                if( $notification->get_field('content_display_cta') == true ) {
                    $notification->set_button_info( array(
                        'label' => __( 'View post', 'notifications-center' ),
                        'url'   => get_permalink( $post->ID ),
                    ) );
                }

                //Send notification
                $notification->send_notification(); 

            }   
            
        }
        
        //Return false to block WP default message
        if( get_option( VOYNOTIF_FIELD_PREFIXE . 'block_' . $this->name . '_status' ) == 1 ) {
            return false;
        }      
        
        //Default return
        return $emails;     
        
    }
        
}
new VOYNOTIF_notification_type_comment_pending();


<?php
/**
 * NOTIFICATION Pending Content
 * 
 * -----SUMMARY-----
 * 1. Replace TYPENAME with my_type_name
 * 2. Replace TYPEDESCRIPTION with "My description"
 * 3. Change Tags
 * 
 */
class VOYNOTIF_notification_type_duplicate_post extends VOYNOTIF_notification_type {
    
    
    /**
     * Constructor
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
     */
    function __construct() {
        
        $this->name = 'content_duplicate';
        $this->label = __( 'Duplicate content', 'notifications-center' );
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
        add_action( 'dp_duplicate_page', array( $this, 'send_notification' ), 10, 3 );  
        add_action( 'dp_duplicate_post', array( $this, 'send_notification' ), 10, 3 );   
    }
    
    
    /**
     * Register setting fields, displayed in the setting metabox if current notification type is selected
     * Called in voynotif/notification/fields/settings/type={current_type} filter
     * 
     * @author Floflo
     * @since 0.9
     * @update 2013-06-02
     * 
     * @param array $fields array of registered fields
     * @return array array of registered fields
     */
    function setting_fields($fields) {
        $fields[] = 
            array(
                'id' => 'voynotif_settings_posttype',
                'label' => __( 'Post type', 'notifications-center' ),
                'description' => __( 'For which post type would you like to send notification', 'notifications-center' ),
                'type' => 'selectmultiple',
                'choices' => parent::_get_post_types(),
                'params' => array(
                    'select2' => true
                )
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
        
        //return types
        return $types;
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
    function send_notification($new_post_id, $post, $status) {

        //Get notifications
        $notifications = voynotif_get_notifications($this->name);

        //Return if no matching notifications
        if (empty($notifications)) {
            return;
        }

        //For each notification
        foreach ($notifications as $notification) {

            //If current post type isn't concerned by the notification
            $poststype = $notification->get_field('settings_posttype');
            if ( !empty( $poststype ) && !in_array(get_post_type($new_post_id), $poststype) ) {
                continue;
            }

            
            $notification->set_context_info(array(
                'post_id' => $new_post_id,
            ));

            //Add author email to recipients
            if ($notification->get_field('recipient_type') == 'post_author') {
                $author_id = $post->post_author;
                $author_email = get_the_author_meta('user_email', $author_id);
                $notification->add_recipient($author_email);
            }

            if ($notification->get_field('content_display_cta') == true) {

                //Build CTA info
                $cta = array(
                    'label' => __('Read more', 'notifications-center'),
                    'url' => get_permalink($new_post_id),
                );

                //Add button info to notification
                $notification->set_button_info($cta);
            }

            //Send notification
            $notification->send_notification();
        }
    }

}
new VOYNOTIF_notification_type_duplicate_post();


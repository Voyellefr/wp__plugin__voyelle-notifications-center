<?php

/**
 * CLASS voy_notification
 * 
 * @author Floflo
 * @since 0.9
 */
if( !class_exists( 'VOYNOTIF_notification_type' ) ) {
    class VOYNOTIF_notification_type {

        var $name;


        /**
         * Constructor
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-07-06
         * 
         * @todo Restreindre le hook register_fields à la modification de notifications
         */
        function __construct() {

            $this->add_filter( 'voynotif/notifications/types', array( $this, 'register_notification_type' ) );
            $this->add_filter( 'voynotif/notification/recipients/type='.$this->name, array( $this, 'set_recipient_types' ) );

            $this->add_filter( 'voynotif/notification/fields/settings/type='.$this->name, array( $this, 'setting_fields' ) );
            $this->add_filter( 'voynotif/notification/fields/content/type='.$this->name, array( $this, 'content_fields' ) );

            $this->add_filter( 'voynotif/masks/context='.$this->name, array( $this, 'custom_masks' ) );

            if( method_exists( $this, 'init' ) )
                $this->init();

            add_action('init', array($this, 'register_fields') );
        }


        /**
         * Check if called function is present in child class before adding filter
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-06-02
         * 
         * @param string $tag filter tag
         * @param string $function_to_add function name present in child class
         * @param int $priority filter priority
         * @param mixed $accepted_args
         */
        function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
            if( is_callable($function_to_add) ) {
                    add_filter($tag, $function_to_add, $priority, $accepted_args);
            }
        } 


        /**
         * Check if called function is present in child class before adding action
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-06-02
         * 
         * @param string $tag action tag
         * @param string $function_to_add function name present in child class
         * @param int $priority filter priority
         * @param mixed $accepted_args
         */
        function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
            if( is_callable($function_to_add) ) {
                    add_action($tag, $function_to_add, $priority, $accepted_args);
            }
        }  


        /**
         * Add current notification type to registered types. 
         * Can be retrieved by voynotif_get_notifications_types function
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-06-02
         * 
         * @param array $types registered templates, like ([name] => label, etc)
         * @return array registerd types, including current type
         */
        function register_notification_type($types) {

            //Overrides
            if( ! empty( $this->overrides_wp ) AND $this->overrides_wp == true ) {
                $overrides = true;
            } else {
                $overrides = false;
            }

            //Register
            $types[$this->name] = array(
                'label' => $this->label,
                'tags' => $this->tags,
                'overrides_wp' => $overrides,
            );
            return $types;
        }


        /**
         * Add all fields to global $voynotif_fields
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-07-06
         *  
         * @global type $voynotif_fields
         */
        function register_fields() {

            global $voynotif_fields;

            //Get setting fields
            if( method_exists( $this, 'setting_fields' ) ) {
                $setting_fields = $this->setting_fields( array() );
            }

            //Get content fields
            if( method_exists( $this, 'content_fields' ) ) {
                $content_fields = $this->content_fields( array() );
            }

            //Add setting fields to global variable
            if( isset( $setting_fields ) AND ! empty( $setting_fields ) ) {
                foreach( $setting_fields as $field ) {
                    $field['location'] = 'settings';
                    $voynotif_fields[$field['id']] = $field; 
                }
            }

            //Add content fields to global variable
            if( isset( $content_fields ) AND ! empty( $content_fields ) ) {
                foreach( $content_fields as $field ) {
                    $field['location'] = 'settings';
                    $voynotif_fields[$field['id']] = $field; 
                }
            }

        }


        /**
         * -------------------------------------------------------------------------
         * SOME USEFULL METHODS CALLABLE IN EXTEND CLASSES
         * ------------------------------------------------------------------------- 
         */


        /**
         * Get post types, excluding voy_notification
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-07-11
         * 
         * @return array post types, like ( [name] => label )
         */
        protected function _get_post_types() {

            //Declare choices array
            $post_type_choices = array();

            //Get different post types
            $post_types = get_post_types( '', 'objects' );

            //Add post types to choices
            foreach( $post_types as $post_type ) {
                if( $post_type->name == 'voy_notification' ) {
                    continue;
                }
                $post_type_choices[$post_type->name] = $post_type->labels->name;
            }

            //Return
            return $post_type_choices;
        }    

        
        /**
         * Get roles to perform 
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-07-04
         * 
         * @global type $wp_roles
         * @return array WP roles
         */
        function _get_user_roles() {
            global $wp_roles;

            $all_roles = $wp_roles->roles;
            $editable_roles = apply_filters('editable_roles', $all_roles);
            
            $array_roles = array();
            foreach( $editable_roles as $role_name => $role_data ) {
                $array_roles[$role_name] = $role_data['name'];
            }

            return $array_roles;               
        }
        
        /**
         * 
         * @since 1.3.0
         * @update 2017-08-31
         * 
         * @return type
         */
        function _get_posts_status() {
            return array(
                'publish' => __('Publish', 'notifications-center'),
                'pending' => __('Pending', 'notifications-center'),
                'draft' => __('Draft', 'notifications-center'),
                'future' => __('Future', 'notifications-center'),
                'trash' => __('Trash', 'notifications-center'),
            );
        }

    }
}


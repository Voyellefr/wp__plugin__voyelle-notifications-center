<?php
/*
 * -------------------------------------------------------------------------
 * COMMON API
 * ------------------------------------------------------------------------- 
 */

/**
 * Get excerpt by post ID
 */
if( !function_exists('voynotif_get_excerpt_by_id') ) {
    function voynotif_get_excerpt_by_id( $post_id = 0, $length = 150 ) {
        global $post;
        $save_post = $post;
        $post = get_post( $post_id );
        setup_postdata( $post );
        $excerpt = substr(get_the_excerpt(), 0,$length);
        $post = $save_post;
        wp_reset_postdata( $post );
        return $excerpt;
    }
}

function voynotif_option( $option ) {
    return apply_filters('voynotif/load_field', get_option( VOYNOTIF_FIELD_PREFIXE . $option  ), $option );
}


/*
 * -------------------------------------------------------------------------
 * TEMPLATE API
 * ------------------------------------------------------------------------- 
 */


/**
 * FUNCTION get_current_email_template
 * Renvoie le template choisi par l'utilisateur
 * 
 * @deprecated since version 1.2.0
 * @author Floflo
 * @since 0.9
 * @update 2016-04-22
 * 
 * @return string template name
 **/
if( !function_exists('voynotif_get_current_email_template') ) {
    function voynotif_get_current_email_template() {
        return 'plugin';
    }
}


/**
 * Renvoi un array de tous les templates enregistrés
 * 
 * @deprecated since version 1.2.0
 * @author Floflo
 * @since 0.9
 * @return array Array of all registered templates
 */
if( !function_exists('voynotif_get_templates') ) {
    function voynotif_get_templates() {
        $templates = apply_filters( 'voynotif/get_templates', '' );
        return $templates;
    }
}


/**
 * Get template data by tempalte name/id
 * 
 * @deprecated since version 1.2.0
 * @author Floflo
 * @since 0.9
 * 
 * @global type $voy_notifs_global_registered_templates
 * @param type $template_id Template_id
 * @return mixed Array of template data or false
 */
if( !function_exists('voynotif_get_template') ) {
    function voynotif_get_template($template_id) {
        $templates = voynotif_get_templates(); 

        //If template exists
        if( array_key_exists( $template_id, $templates ) ) {
            return $templates[$template_id];
        }

        //Return False if template ID doesn't exist
        return false;
    }
}

    
/*
 * -------------------------------------------------------------------------
 * NOTIFICATIONS API
 * ------------------------------------------------------------------------- 
 */


/**
 * Get notifications types.
 * 
 * @author Floflo
 * @since 0.9
 * @since 1.1 New $tag parameter added to get types regarding to a specific tag
 * @update 2016/12-27
 * 
 * @param string $tag Tag ID
 * @return array array of notification types, like array( slug => label )
 */
if( !function_exists('voynotif_get_notifications_types') ) {
    function voynotif_get_notifications_types( $tag = null ) {   
        $types = apply_filters( 'voynotif/notifications/types', array() );  
        
        if( ! empty( $tag ) ) {
            $matching_types = array();
            foreach( $types as $id => $data ) {
                if( $data['tags'][0] != $tag ) continue;
                $matching_types[$id] = $data;
            }
            return $matching_types;
        }
        
        return $types;
    }
}


/**
 * Get allowed notifications tags. Used for displaying notification choice
 * 
 * @author Floflo
 * @since 0.9
 * @update 2016-07-28
 * 
 * @return array array of notification tags, like array( slug => label )
 */
if( !function_exists('voynotif_get_notifications_tags') ) {
    function voynotif_get_notifications_tags() {   
        $tags = apply_filters( 'voynotif/notifications/tags', array(
            'system' => __( 'System', 'notifications-center' ),
            'content' => __( 'Content', 'notifications-center' ),
            'comment' => __( 'Comment', 'notifications-center' ),
            'user' => __( 'User', 'notifications-center' ),
            'multisite' => __( 'Multisite', 'notifications-center' ),
        ) );
        return $tags;
    }
}


/**
 * Get notification regarding to a specific notification type
 * 
 * @author Floflo
 * @since 0.9
 * @update 2017-09-02
 * 
 * @param string $notification_type Slug of the notification type
 * @return array Array of notifications as objects like array ( 0 => NOTIF_notification, etc). Return empty array if no matching notifications
 */
if( !function_exists('voynotif_get_notifications') ) {
    function voynotif_get_notifications( $notification_type = null ) {

        $matching_notifications = array();

        //Build and send query to get notifications
        $args = array(
            'post_type' => 'voy_notification',  
            'posts_per_page' => -1
        );
        if( !empty($notification_type) ) {
            $args['meta_key'] = VOYNOTIF_FIELD_PREFIXE . 'type';
            $args['meta_value'] = $notification_type;
        }

        $notifications = get_posts( $args );
        
        //if there is no matching notification
        if( empty( $notifications ) ) {
            return $matching_notifications;  
        }

        //Build notifications objects
        foreach( $notifications as $notification ) {
            $matching_notifications[] = new VOYNOTIF_notification($notification->ID);
        }

        //Return only one or all matching notifications, regarding to $only_one param
        return $matching_notifications;

    }
}


/*
 * -------------------------------------------------------------------------
 * SETTINGS API
 * ------------------------------------------------------------------------- 
 */

/**
 * Wrapper of get_option wp function, with automatically add the VOYNOTIF_FIELD_PREFIX
 * 
 * @author Floflo
 * @since 1.3.0
 * @update 2017-08-31
 * 
 * @param string $settings The option name, without "voynotif_" prefix
 */
if( !function_exists('voynotif_get_option') ) {
    function voynotif_get_option( $setting ) {
        $setting = get_option( VOYNOTIF_FIELD_PREFIXE . $setting );
        if( empty( $setting ) ) {
            return false;
        }
        return $setting;
    }
}

/**
 * Get all Notifications Center options in an associative array
 * 
 * @author Floflo
 * @since 1.3.0
 * @update 2017-09-02
 */
if( !function_exists('voynotif_get_options') ) {
    function voynotif_get_options() {
        $settings = array();
        global $wpdb;       
        $results = $wpdb->get_results( 'SELECT * FROM '.$wpdb->prefix.'options WHERE option_name LIKE "voynotif_%"', OBJECT );
        error_log( print_r($results, true) );
        if( !empty( $results ) ) {
            foreach( $results as $option ) {
                if (@unserialize($option->option_value)) {
                    $option->option_value = @unserialize($option->option_value);
                }                
                $settings[$option->option_name] = $option->option_value;
            }
        }
        
        if( empty( $settings ) ) {
            return false;
        }
        
        return $settings;
    }
}

/**
 * Check if a plugin is at leas at some version
 * 
 * @author Floflo
 * @since 1.3.0
 * @update 2017-09-01
 * 
 * @param string $plugin Plugin name, as plugin-directory/plugin-file.php
 * @param string $version Plugin version, eg 1.1.2
 */
if( !function_exists('voynotif_is_plugin_version') ) {
    function voynotif_is_plugin_version( $plugin, $version = '1.0.0' ) {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if( !is_plugin_active($plugin) ) {
            return false;
        }

        $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
        if( version_compare( $plugin_data['Version'], $version ) >= 0 ) {
            return true;
        }
        
        return false;
    }
}


/**
 * 
 */
if( !function_exists('voynotif_get_settings_screens') ) {
    function voynotif_get_settings_screens() {
        return apply_filters( 'voynotif/settings/screens', array() );
    }
}


/**
 * 
 */
if( !function_exists('voynotif_get_settings_fields') ) {
    function voynotif_get_settings_fields( $screen_id = null ) {
        $fields = apply_filters( 'voynotif/settings/fields', array() );;
        if( empty( $screen_id ) ) {
            return $fields;
        }
        
        foreach( $fields as $field_id => $field_data ) {
            if( $field_data['screen'] != $screen_id ) {
                unset( $fields[$field_id] );
            }
        }      
        return $fields;
    }
}


/**
 * 
 */
if( !function_exists('voynotif_get_notification') ) {
    function voynotif_get_notification( $notification_id ) {
        if( get_post( $notification_id ) ) {
            return new VOYNOTIF_notification( $notification_id );
        }
        return null;
    }
}


/**
 * 
 * @global type $notifications_center
 * @param type $message
 * @param type $class
 */
function voynotif_add_admin_notice( $message, $class = '' ) {
    global $notifications_center;
    $notifications_center->admin_notices[] = array(
        'message'   => $message,
        'class'    => $class,
    );
}


/**
 * 
 * @global type $wpdb
 * @param type $value
 * @param type $value_type
 * @return boolean
 */
function voynotif_notification_exists( $value, $value_type = 'slug' ) {
    global $wpdb;
    if($wpdb->get_row("SELECT post_name FROM wp_posts WHERE post_name = '" . $value . "' AND post_type = 'voy_notification'", 'ARRAY_A')) {
        return true;
    } else {
        return false;
    }
}

/**
 * 
 * @since 1.4.0
 * 
 * @param type $type_id
 */
function voynotif_get_notification_type_title($type_id) {
    $types = voynotif_get_notifications_types();
    if (!empty($types[$type_id]['label'])) {
        return $types[$type_id]['label'];
    } else {
        return __('Unknown', 'notifications-center');
    }
}

function voynotif_update_option( $name, $value ) {
    $result = update_option( VOYNOTIF_FIELD_PREFIXE . $name, $value );
    do_action('voynotif/save_field', $name, $value, null, 'option');    
}


?>
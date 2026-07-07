<?php
/*
 * -------------------------------------------------------------------------
 * TEMPLATE DIRECTORY
 * ------------------------------------------------------------------------- 
 */

function voynotif_has_theme_email_template() {
    //Check for template in child theme
    $current_theme_path = get_stylesheet_directory();
    if( is_dir( $current_theme_path . '/notifications-center/' ) && is_file( $current_theme_path . '/notifications-center/email.php' ) ) {
        return true;
    }

    //Check for template in parent theme
    $parent_theme_path = get_template_directory();
    if( is_dir( $parent_theme_path . '/notifications-center/' ) && is_file( $parent_theme_path . '/notifications-center/email.php' ) ) {
        return true;
    } 

    return false;    
}

/**
 * 
 * @return boolean
 */
function voynotif_email_template_path() {
    
    //Check for Theme integration
    if( get_option( VOYNOTIF_FIELD_PREFIXE . 'email_template_path' ) == 'theme' ) {

        //Check for template in child theme
        $current_theme_path = get_stylesheet_directory();
        if( is_dir( $current_theme_path . '/notifications-center/' ) && is_file( $current_theme_path . '/notifications-center/email.php' ) ) {
            return $current_theme_path . '/notifications-center/';
        }

        //Check for template in parent theme
        $parent_theme_path = get_template_directory();
        if( is_dir( $parent_theme_path . '/notifications-center/' ) && is_file( $parent_theme_path . '/notifications-center/email.php' ) ) {
            return $parent_theme_path . '/notifications-center/';
        } 

    }

    //Default return    
    return VOYNOTIF_DIR . '/templates/';
    
}

/**
 * 
 * @param type $template_name
 * @return boolean
 */
function voynotif_email_template_exists( $template_name ) {
    if( is_file( voynotif_email_template_path() . $template_name ) ) {
        return true;
    }
    return false;
}

/**
 * Get main path to email template files
 * 
 * @author Floflo
 * @since 1.2.0
 * @update 2017-03-20
 * 
 * @param type $notification_id
 * @return type
 */
function voynotif_email_template( $notification_id = null ) {
    
    $notification = voynotif_get_notification($notification_id);
    
    if( $notification ) {
              
        //Check for template with ID syntax
        if( voynotif_email_template_exists("email-{$notification->id}.php") ) {
            return voynotif_email_template_path() . "email-{$notification->id}.php"; 
        }
        
        //Check for template with type syntax
        if( voynotif_email_template_exists("email-{$notification->type}.php") ) {
            return voynotif_email_template_path() . "email-{$notification->type}.php"; 
        }
        
    }
    
    //Return default template
    return voynotif_email_template_path() . 'email.php';  
    
}

/**
 * Load part email template if exists
 * 
 * @author Floflo
 * @since 1.2.0
 * @update 2017-03-27
 * 
 * @param type $template
 * @return type
 */
function voynotif_email_template_part( $template ) {
    $template_path = voynotif_email_template_path() . 'parts/' . $template . '.php';
    if( is_file( $template_path ) ) {
        include $template_path;
    }
    return;
}


/*
 * -------------------------------------------------------------------------
 * TEMPLATE DESIGN API
 * ------------------------------------------------------------------------- 
 */

/**
 * 
 * @global type $voynotif_template
 * @return type
 */
function voynotif_email_footer_text() {
    global $voynotif_template;
    return apply_filters( 'voynotif/template/footer_text' , $voynotif_template->footer );
}

/**
 * 
 * @global type $voynotif_template
 * @return type
 */
function voynotif_email_logo() {
    global $voynotif_template;
    $logo_url =  apply_filters( 'voynotif/template/logo_url' , $voynotif_template->logo_url );
    return '<img src="'.$logo_url.'" border="0">';
}

/**
 * 
 * @global type $voynotif_template
 * @return type
 */
function voynotif_email_background_color() {
    global $voynotif_template;
    return apply_filters( 'voynotif/template/background_color' , $voynotif_template->background_color );
}

/**
 * 
 * @global type $voynotif_template
 * @return type
 */
function voynotif_email_background_content_color() {
    global $voynotif_template;
    return apply_filters( 'voynotif/template/background_content_color' , $voynotif_template->backgroundcontent_color );
}

/**
 * 
 * @global type $voynotif_template
 * @return type
 */
function voynotif_email_title_color() {
    global $voynotif_template;
    return apply_filters( 'voynotif/template/title_color' , $voynotif_template->title_color );
}
/**
 * 
 * @global type $voynotif_template
 * @return type
 */
function voynotif_email_button_color() {
    global $voynotif_template;
    return apply_filters( 'voynotif/template/button_color' , $voynotif_template->button_color );
}

/*
 * -------------------------------------------------------------------------
 * TEMPLATE CONTENT API
 * ------------------------------------------------------------------------- 
 */

/**
 * 
 * @global type $voynotif_notification
 * @return type
 */
function voynotif_email_title() {
    global $voynotif_notification;
    $title = false;
    if( $voynotif_notification && $voynotif_notification->title ) {
        $title = $voynotif_notification->title;
    }
    if( empty( $title ) ) {
        global $voynotif_template;
        if( $voynotif_template && $voynotif_template->notification_content->title ) {
            $title = $voynotif_template->notification_content->title;
        }        
    }
    return apply_filters( 'voynotif/template/title' , $title );
}

/**
 * 
 * @global type $voynotif_notification
 * @return type
 */
function voynotif_email_content() {
    global $voynotif_notification;
    $content = false;
    if( $voynotif_notification && $voynotif_notification->content ) {
        $content = $voynotif_notification->content;
    }
    if( empty( $content ) ) {
        global $voynotif_template;
        if( $voynotif_template && $voynotif_template->notification_content->content ) {
            $content = $voynotif_template->notification_content->content;
        }        
    }
    return apply_filters( 'voynotif/template/content' , $content );
}

/**
 * 
 * @global type $voynotif_notification
 * @return type
 */
function voynotif_email_button_text() {
    global $voynotif_notification;
    $button_text = false;
    if( $voynotif_notification && isset($voynotif_notification->button_info['label']) ) {
        $button_text = $voynotif_notification->button_info['label'];
    }  
    if( empty( $button_text ) ) {
        global $voynotif_template;
        if( $voynotif_template && isset($voynotif_template->notification_content->button_info['label']) ) {
            $button_text = $voynotif_template->notification_content->button_info['label'];
        }        
    }
    return apply_filters( 'voynotif/template/button_text' , $button_text );
}

/**
 * 
 * @global type $voynotif_notification
 * @return type
 */
function voynotif_email_button_url() {
    global $voynotif_notification;
    $button_url = false;
    if( $voynotif_notification && isset($voynotif_notification->button_info['url']) ) {
        $button_url = $voynotif_notification->button_info['url'];
    }    
    if( empty( $button_url ) ) {
        global $voynotif_template;
        if( $voynotif_template && isset($voynotif_template->notification_content->button_info['url']) ) {
            $button_url = $voynotif_template->notification_content->button_info['url'];
        }        
    }
    return apply_filters( 'voynotif/template/button_url' , $button_url );
}

function voynotif_email_footer() {
    ob_start();
    do_action('voynotif/template/footer');
    $return = ob_get_clean();
    return $return;
}



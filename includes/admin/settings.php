<?php
if( !class_exists( 'VOYNOTIF_admin_settings' ) ) {
    class VOYNOTIF_admin_settings {

        const SCREEN_VAR = 'tab';

        /**
         * Constructor
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016/05/19
         */
        function __construct() {
            
            add_filter( 'voynotif/settings/fields', array( $this, 'settings_fields' ), 10, 1 );
            add_filter( 'voynotif/settings/screens', array( $this, 'settings_screens' ), 10, 1 );
            add_action( 'admin_menu', array($this, 'create_submenu') ); //Hook for submenu creation   
            
            //Add ajax calls
            add_action('admin_footer', array($this, 'add_js') ); //Hook for submenu creation 
            add_action('wp_ajax_voynotif_ajax_block_defaut_email', array( $this, 'ajax_block_default_email' ) );
            add_action('wp_ajax_voytnotif_ajax_duplicate_default', array( $this, 'ajax_duplicate_default' ) );
            add_action('wp_ajax_voytnotif_ajax_update_settings', array( $this, 'update_settings' ) );
        }


        /**
         * Submenu creation
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016/05/19
         */
        function create_submenu() {
            add_submenu_page(
                'edit.php?post_type=voy_notification',
                __( 'Settings', 'notifications-center' ),
                __( 'Settings', 'notifications-center' ),
                'manage_options',
                'voynotif_settings',
                array($this, 'render_page') 
            );    
        }
 
        
        function settings_fields($fields) {
            $fields['sender_name'] = array(
                'id' => 'sender_name',
                'label' => __( 'Sender name', 'notifications-center' ),
                'type' => 'text',
                'screen' => 'general',
                'fieldgroup' => 'sender'
            );
            $fields['sender_email'] = array(
                'id' => 'sender_email',
                'label' => __( 'Sender email', 'notifications-center' ),
                'type' => 'text',
                'screen' => 'general',
                'fieldgroup' => 'sender'
            );
            $fields['extend_sender'] = array(
                'id' => 'extend_sender',
                'label' => __( 'How to use this sender', 'notifications-center' ),
                'type' => 'select',
                'choices' => array(
                    'plugin' => __( 'Only for Notifications Center emails', 'notifications-center' ),
                    'wordpress' => __( 'For both Notifications Center emails and any other wordpress or plugin email', 'notifications-center' ),
                ),
                'screen' => 'general',
                'fieldgroup' => 'sender'
            );
            $fields['activate_email_title'] = array(
                'id' => 'activate_email_title',
                'label' => __( 'Activate email title', 'notifications-center' ),
                'description' => __( 'Otherwise, the same field will be used both as Email subject & Email Title', 'notifications-center' ),
                'type' => 'boolean',
                'params'    => array(
                    'title' => __( 'Activate email title (different from the email subject)', 'notifications-center' ),
                ),
                'screen' => 'general',
                'fieldgroup' => 'general'
            );
            $fields['activate_logs'] = array(
                'id' => 'activate_logs',
                'label' => __( 'Activate logs', 'notifications-center' ),
                'description' => __( 'Track any email sent threw Notifications Center', 'notifications-center' ),
                'type' => 'boolean',
                'params'    => array(
                    'title' => __( 'Activate logs', 'notifications-center' ),
                ),
                'screen' => 'general',
                'fieldgroup' => 'logs'
            );
            $fields['logs_activate_opentracking'] = array(
                'id' => 'logs_activate_opentracking',
                'label' => __( 'Activate open tracking', 'notifications-center' ),
                'description' => __( 'Track when email are opened', 'notifications-center' ),
                'type' => 'boolean',
                'params'    => array(
                    'title' => __( 'Activate open tracking', 'notifications-center' ),
                ),
                'screen' => 'general',
                'fieldgroup' => 'logs'
            );
            $fields['logs_duration'] = array(
                'id' => 'logs_duration',
                'label' => __( 'Number of days before deleting logs', 'notifications-center' ),
                'description' => __( '(keep empty to not delete logs)', 'notifications-center' ),
                'type' => 'number',
                'screen' => 'general',
                'fieldgroup' => 'logs'
            );
            
            //Choices
            $choices = array(
                'plugin' => __( 'Plugin template', 'notifications-center' ),
            );
            if( voynotif_has_theme_email_template() ) {
                $choices['theme'] = __( 'Current theme template', 'notifications-center' ); 
            }
            $fields['email_template_path'] = array(
                'id' => 'email_template_path',                
                'label' => __( 'Template to use', 'notifications-center' ),
                'description' => sprintf( __( 'Your Wordpress theme can now override Notifications Center Template. <a target="_blank" href="%1$s">Check documentation for more info</a>', 'notifications-center' ), 'http://www.notificationscenter.com/en/documentation/email-template-system/' ),
                'type' => 'select',
                'choices' => $choices,
                'screen' => 'general',
                'fieldgroup' => 'template'
            ); 
            return $fields;
        }
        
        
        function update_settings() {
            
            $fields = voynotif_get_settings_fields( $_POST['screen'] );
            
            $js = '';
            foreach( $fields as $field_id => $field_data ) {
                $field_obj = new VOYNOTIF_field( $field_data );
                $result = $field_obj->save($_POST[$field_id]);
            }
            _e( 'Changes succesfully saved !', 'notifications-center' ); 
            die();
        }
        

        /**
         * Generate Settings page HTML
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016/05/23
         */
        function render_page() {
            ?>
            <div class="wrap voy-wrap voy-settings">
                <!--<h1><?php _e( 'Settings', 'voy_notifications_center' ); ?></h1>-->
                <?php self::render_tabs(); ?>
                <?php $this->render_settings_screen(); ?>
            </div>
            <?php
        }
        
        
        /**
         * Add JS to footer to perform AJAX Call
         * 
         * @author Floflo
         * @since 1.0
         * @update 2016-12-11
         */
        function add_js() {
            
            $screen = get_current_screen();    
            if( $screen->id != 'admin_page_voynotif_settings' ) return;            
            
            //Get fields for ajax call
            $fields = voynotif_get_settings_fields();
            $js = '';
            foreach( $fields as $field_id => $field_data ) {
                if( $field_data['screen'] == self::get_current_screen_id() ) {
                    
                    if( $field_data['type'] == 'select' ) {
                        $js .= ', '.$field_data['id'].':jQuery(\'#'.VOYNOTIF_FIELD_PREFIXE.$field_data['id'].'\').find(":selected").val()';
                    } elseif( $field_data['type'] == 'boolean' ) {
                        $js .= ', '.$field_data['id'].':jQuery(\'#'.VOYNOTIF_FIELD_PREFIXE.$field_data['id'].':checked\').val()';
                    } else {
                        $js .= ', '.$field_data['id'].':jQuery(\'#'.VOYNOTIF_FIELD_PREFIXE.$field_data['id'].'\').val()';
                    }
                    
                }
            }
            
            ?>
            <script type="text/javascript">
                
                jQuery(".cbx").change(function() {
                    if(jQuery(this).is(":checked")){
                        var checked = 0;
                    } else {
                        var checked = 1;
                    }
                    jQuery.ajax({ 
                        data: {action: 'voynotif_ajax_block_defaut_email',notification_id:jQuery(this).data("id"), notification_status:checked},
                        type: 'post',
                        url: ajaxurl,
                        success: function(data) {
                            console.log(data);
                        }
                    });
                });
                
                jQuery(".voynotif_save_settings").click(function() {
                    var submit = jQuery(this);
                    var response_wrapper = jQuery('#voynotif_response_wrapper');
                    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
                    submit.html('<?php _e( 'Saving changes...', 'notifications-center' ); ?>');
                    submit.attr('disabled', 'disabled');
                    response_wrapper.html('');
                    jQuery.ajax({ 
                        data: {action: 'voytnotif_ajax_update_settings', screen:'<?php echo self::get_current_screen_id(); ?>'<?php echo $js; ?>},
                        type: 'post',
                        url: ajaxurl,
                        success: function(data) {
                            //alert(data);
                            response_wrapper.html(data);
                            submit.removeAttr('disabled');
                            submit.html('<?php _e( 'Save changes', 'notifications-center' ); ?>');
                        }
                    });
                });
                
                jQuery(".voynotif-create_default").click(function() {
                    var duplicate_default_wrapper = jQuery(this).parent();
                    jQuery(duplicate_default_wrapper).html('<img class="voynotif-spinner" src="<?php echo VOYNOTIF_URL.'/assets/img/spinner.gif' ?>"> Creating...');
                    jQuery.ajax({ 
                        data: {action: 'voytnotif_ajax_duplicate_default', id:jQuery(this).data('id')},
                        type: 'post',
                        url: ajaxurl,
                        success: function(data) {
                            //alert(data);
                            jQuery(duplicate_default_wrapper).html('Done ! You\'ll be redirectering...');
                            document.location.href=data;
                        }
                    });
                    
                });
         
            </script>
            <?php
        }

        
        public function settings_screens( $screens ) {
            $screens['notifications'] = array(
                'title' => __( 'Custom notifications', 'notifications-center' ),
                'url'   => admin_url().'/edit.php?post_type=voy_notification',
            ); 
            $screens['defaults'] = array(
                'title' => __( 'Default Notifications', 'notifications-center' ),
                'callback' => array( 'VOYNOTIF_admin_settings', 'settings_defaults_screen' )
            );
            $screens['general'] = array(
                'title' => __( 'Settings', 'notifications-center' ),
                'callback' => array( 'VOYNOTIF_admin_settings', 'settings_general_screen' )
            );
            $screens['template'] = array(
                'title' => __( 'Template', 'notifications-center' ),
                'url' => VOYNOTIF_template_customizer::customizer_url(),
            );
            return $screens;
        }
        

        
        function get_default_notifications( $tag = NULL ) {
            
            $defaults = apply_filters( 'voynotif/settings/defaults' ,array(
                'user_password_reset' => array(
                    'label' => __( 'User password reset', 'notifications-center' ),
                    'tag' => 'user',
                    'description' => __( 'Email sent when user asked for password reset, from the login screen. Email contains the password reset link', 'notifications-center' ),
                    'custom' => array(
                        'type' => 'user_password_reset',
                        'recipient_type' => 'requested_user',
                        'title' => sprintf( __('[%s] Password Reset'), get_bloginfo( 'name' ) ),
                        'content' => 
                            __('Someone has requested a password reset for the following account:').' {user_login}</p>'
                            . '<p>'.__('If this was a mistake, just ignore this email and nothing will happen.').'<br />'
                            .__('To reset your password, visit the following address:').' <{password_reset_link}></p>',
                    ),
                ),
                'user_password_changed' => array(
                    'label' => __( 'User password changed', 'notifications-center' ),
                    'tag' => 'user',
                    'description' => __( 'Email sent when after any user changed his password', 'notifications-center' ),
                    'custom' => array(
                        'type' => 'user_password_changed',
                        'recipient_type' => 'emails',
                        'recipient_emails' => get_site_option( 'admin_email' ),
                        'title' => sprintf( __('[%s] Password Changed'), get_bloginfo( 'name' ) ),
                        'content' => sprintf( __( 'Password changed for user: %s' ), '{user_login}' ),
                    ),
                ),
                'user_register_admin' => array(
                    'label' => __( 'User registered - admin', 'notifications-center' ),
                    'tag' => 'user',
                    'description' => __( 'Email sent when after any user changed his password', 'notifications-center' ),
                    'custom' => array(
                        'type' => 'user_register',
                        'recipient_type' => 'emails',
                        'recipient_emails' => get_site_option( 'admin_email' ),
                        'title' => sprintf( __('[%s] New User Registration'), get_bloginfo( 'name' ) ),
                        'content' => 
                            sprintf( __( 'New user registration on your site %s:' ), '{site_name}' )
                            . '<p>' . sprintf( __( 'Username: %s' ), '{user_login}' ) . '<br />'
                            . sprintf( __( 'Email: %s' ), '{user_email}' ) . '</p>',
                    ),
                ),
                'user_register_user' => array(
                    'label' => __( 'User registered - user', 'notifications-center' ),
                    'tag' => 'user',
                    'description' => __( 'Email sent when after any user changed his password', 'notifications-center' ),
                    'custom' => array(
                        'type' => 'user_register',
                        'recipient_type' => 'requested_user',
                        'title' => sprintf( __('[%s] Your username and password info'), get_bloginfo( 'name' ) ),
                        'content' => 
                            sprintf(__('Username: %s'), '{user_login}' )
                            . '<p>' . __('To set your password, visit the following address:') . '<br />'
                            . '<{password_reset_link}></p>'
                            . '<p>{site_url}/wp-login.php</p>',
                    ),
                ),
                'core_update_success' => array(
                    'label' => __( 'Wordpress update - Success', 'notifications-center' ),
                    'tag' => 'system',
                    'description' => __( 'Email sent after Wordpress succesfully updated to a new version.', 'notifications-center' ),
                    'custom' => array(
                        'type' => 'core_update',
                        'core_update_type' => 'success',
                        'recipient_type' => 'emails',
                        'recipient_emails' => get_site_option( 'admin_email' ),
                        'title' => sprintf( __('[%1$s] Your site has updated to WordPress %2$s'), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), '{core_update_version}' ),
                        'content' => 
                            sprintf( __( 'Howdy! Your site at %1$s has been updated automatically to WordPress %2$s.' ), '{site_url}', '{core_update_version}' )
                            . '<p>' . sprintf( __( "For more on version %s, see the About WordPress screen:" ), '{core_update_version}' ) . '<br />'
                            . admin_url( 'about.php' ) . '</p><p>'
                            . __( 'If you experience any issues or need support, the volunteers in the WordPress.org support forums may be able to help.' ) . '<br />'
                            . __( 'https://wordpress.org/support/' ) . '</p><p>'
                            . __( 'The WordPress Team' ) . '</p>',
                    ),
                ),
                'core_update_fail' => array(
                    'label' => __( 'Wordpress update - Fail', 'notifications-center' ),
                    'tag' => 'system',
                    'description' => __( 'Email sent after Wordpress tried to update but failed', 'notifications-center' ),
                    'custom' => array(
                        'type' => 'core_update',
                        'core_update_type' => 'fail',
                        'recipient_type' => 'emails',
                        'recipient_emails' => get_site_option( 'admin_email' ),
                        'title' => sprintf( __('[%1$s] WordPress %2$s is available. Please update!'), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), '{core_update_version}' ),
                        'content' => 
                            sprintf( __( 'Please update your site at %1$s to WordPress %2$s.' ), '{site_url}', '{core_update_version}' )
                            . '<p>' . __( 'We tried but were unable to update your site automatically.' ) . ' ' . __( 'Updating is easy and only takes a few moments:' ) . '<br />'
                            . network_admin_url( 'update-core.php' ) . '</p><p>'
                            . __( 'If you experience any issues or need support, the volunteers in the WordPress.org support forums may be able to help.' ) . '<br />'
                            . __( 'https://wordpress.org/support/' ) . '</p><p>'
                            . __( 'Keeping your site updated is important for security. It also makes the internet a safer place for you and your readers.' ) . '</p><p>'
                            . __( 'The WordPress Team' ) . '</p>',
                    ),
                ),
                'core_update_critical' => array(
                    'label' => __( 'Wordpress update - Critical error', 'notifications-center' ),
                    'tag' => 'system',
                    'description' => __( 'Email sent after Wordpress tried to update but experienced a critical failure', 'notifications-center' ),
                    'custom' => array(
                        'type' => 'core_update',
                        'core_update_type' => 'critical',
                        'recipient_type' => 'emails',
                        'recipient_emails' => get_site_option( 'admin_email' ),
                        'title' => sprintf( __('[%1$s] URGENT: Your site may be down due to a failed update'), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ),
                        'content' => 
                            sprintf( __( 'Your site at %1$s experienced a critical failure while trying to update WordPress to version %2$s.' ), '{site_url}', '{core_update_version}' )
                            . '<p>' . __( "This means your site may be offline or broken. Don't panic; this can be fixed." ) . '<br />'
                            . __( "Please check out your site now. It's possible that everything is working. If it says you need to update, you should do so:" ) . '<br />'
                            . network_admin_url( 'update-core.php' ) . '</p><p>'
                            . __( 'If you experience any issues or need support, the volunteers in the WordPress.org support forums may be able to help.' ) . '<br />'
                            . __( 'https://wordpress.org/support/' ) . '</p><p>'
                            . __( 'Keeping your site updated is important for security. It also makes the internet a safer place for you and your readers.' ) . '</p><p>'
                            . __( 'The WordPress Team' ) . '</p>',
                    ),
                ),
                'core_update_manual' => array(
                    'label' => __( 'Wordpress update available', 'notifications-center' ),
                    'tag' => 'system',
                    'description' => __( 'Email when Wordpress can be updated to a new version, but nothing has been done at this time', 'notifications-center' ),
                    'custom' => array(
                        'type' => 'core_update',
                        'core_update_type' => 'manual',
                        'recipient_type' => 'emails',
                        'recipient_emails' => get_site_option( 'admin_email' ),
                        'title' => sprintf( __('[%1$s] WordPress %2$s is available. Please update!'), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), '{core_update_version}' ),
                        'content' => 
                            sprintf( __( 'Please update your site at %1$s to WordPress %2$s.' ), '{site_url}', '{core_update_version}' )
                            . '<p>' . __( 'Updating is easy and only takes a few moments:' ) . '<br />'
                            . network_admin_url( 'update-core.php' ) . '</p><p>'
                            . __( 'If you experience any issues or need support, the volunteers in the WordPress.org support forums may be able to help.' ) . '<br />'
                            . __( 'https://wordpress.org/support/' ) . '</p><p>'
                            . __( 'Keeping your site updated is important for security. It also makes the internet a safer place for you and your readers.' ) . '</p><p>'
                            . __( 'The WordPress Team' ) . '</p>',
                    ),
                ),
                'comment_moderate' => array(
                    'label' => __( 'Comment to approve', 'notifications-center' ),
                    'tag' => 'comment',
                    'description' => __( 'Email sent when a visite submitted a comment on your website and if "A comment is held for moderation" is checked in Wordpress settings', 'notifications-center' ),
                    'custom' => array(
                        'type' => 'comment_moderate',
                        'recipient_type' => 'post_author',
                        'title' => sprintf( __('[%1$s] Please moderate: "%2$s"'), get_bloginfo( 'name' ), '{post_title}' ),
                        'content' => 
                            sprintf( __('A new comment on the post "%s" is waiting for your approval'), '{post_title}' ) . ' : {post_link}'
                            . '<p>'
                            . sprintf( __( 'Author: %1$s (IP: %2$s, %3$s)' ), '{comment_author}', '{comment_author_ip}', '{comment_author_ip}' ) . '<br />' 
                            . sprintf( __( 'Email: %s' ), '{comment_author_email}' ) . '<br />'
                            . sprintf( __( 'URL: %s' ), '{comment_author_url}' ) . '<br />'
                            . sprintf( __( 'Comment: %s' ), "\r\n" . '{comment_content}' ) 
                            . '</p><p>'
                            . sprintf( __( 'Approve it: %s' ), '{comment_approve_link}' ) . '<br />'
                            . sprintf( __( 'Trash it: %s' ), '{comment_trash_link}' ) . '<br />'
                            . sprintf( __( 'Spam it: %s' ), '{comment_spam_link}' ) . '<br />'
                            . '</p>',
                    ),
                ),
                /*'new_pingback' => array(
                    'label' => __( 'New Pingback', 'notifications-center' ),
                    'tag' => 'comment',
                    'description' => __( 'Email sent when another site mention one of your post and if "Allow link notifications from other blogs (pingbacks and trackbacks) on new articles " is checked in Wordpress settings', 'notifications-center' ),
                ),*/
            ) );
            
            if( ! empty( $tag ) ) {
                $defaults_filtered = array();
                foreach( $defaults as $k => $v ) {
                    if( $v['tag'] == $tag ) {
                        $defaults_filtered[$k] = $v;
                    } 
                } 
                return $defaults_filtered;
            }  
            
            return $defaults;
        }
        
        
        /**
         * 
         */
        public static function render_tabs() {           
            $screens = voynotif_get_settings_screens();
            $current_screen = self::get_current_screen_id();
            ?>
            <h2 class="nav-tab-wrapper">
                <?php foreach( $screens as $screen_id => $screen_data ) {
                    $url = ( array_key_exists('url', $screen_data) ) ? $screen_data['url'] : add_query_arg( 'tab', $screen_id, admin_url().'edit.php?post_type=voy_notification&page=voynotif_settings' ) ;
                    ?>
                    <a class="nav-tab <?php if( $screen_id == $current_screen ) { echo 'nav-tab-active'; } ?>" href="<?php echo $url; ?>">
                        <?php echo $screen_data['title']; ?>
                    </a>
                <?php } ?>
            </h2>
            <?php            
        }
        
        
        private static function get_current_screen_id() {
            $screens = voynotif_get_settings_screens();
            if( empty( $_GET[VOYNOTIF_admin_settings::SCREEN_VAR] ) ) {
                $screen_ids = array_keys($screens);
                $screen_id = $screen_ids[0];
            } else {
                $screen_id = $_GET[self::SCREEN_VAR];
            }
            return $screen_id;            
        }
        
        /**
         * 
         * @return boolean
         */
        private function render_settings_screen() {
            
            //Get setting screen to display
            $screens = voynotif_get_settings_screens();
            $current_screen = $screens[self::get_current_screen_id()];

            
            //Display needed screen regarding to query var
            if( !isset($current_screen['callback']) || empty($current_screen['callback']) ) {
                //
            } elseif(is_string( $current_screen['callback'] ) AND function_exists( $current_screen['callback'] ) ) {
                call_user_func( $current_screen['callback'] );
                return true;
            } elseif( is_array( $current_screen['callback'] ) AND method_exists( $current_screen['callback'][0], $current_screen['callback'][1] ) ) {
                call_user_func( array( $current_screen['callback'][0], $current_screen['callback'][1] ) );
                return true;        
            }
            
            //Return false if callback function failed
            return false;
            
        }
        
        
        /**
         * 
         * @param type $screen
         */
        function display_settings_fields( $screen, $fieldgroup ) {
            $fields = voynotif_get_settings_fields();
            
            if( ! empty( $fieldgroup ) ) {
                foreach( $fields as $field_id => $field_data ) {
                    if( isset( $field_data['screen'] ) && $field_data['screen'] == $screen && isset( $field_data['fieldgroup'] ) && $field_data['fieldgroup'] == $fieldgroup ) {
                        $field_obj = new VOYNOTIF_field($field_data);
                        echo $field_obj->display();
                    }
                } 
            }
            
            //Basic screen display
            else {
                foreach( $fields as $field_id => $field_data ) {
                    if( isset( $field_data['screen'] ) && $field_data['screen'] == $screen ) {
                        $field_obj = new VOYNOTIF_field($field_data);
                        echo $field_obj->display();
                    }
                }                
            }
            
        }
        
        
        /**
         * 
         */
        function settings_general_screen() {
            ?>
            <div id="voy-settings-main">
                <div class="voy-box">
                    <div class="header">
                        <h2><?php _e( 'General', 'voy_notifications_center' ); ?></h2>
                    </div>
                    <div class="main">
                        <?php $this->display_settings_fields( 'general', 'general' ); ?>
                    </div>
                </div> 
                <div class="voy-box">
                    <div class="header">
                        <h2><?php _e( 'Sender', 'voy_notifications_center' ); ?></h2>
                    </div>
                    <div class="main">
                        <?php $this->display_settings_fields( 'general', 'sender' ); ?>
                    </div>
                </div> 
                <div class="voy-box">
                    <div class="header">
                        <h2><?php _e( 'Template', 'voy_notifications_center' ); ?></h2>
                    </div>
                    <div class="main">
                        <?php $this->display_settings_fields( 'general', 'template' ); ?>
                    </div>
                </div>
                <div class="voy-box">
                    <div class="header">
                        <h2><?php _e( 'Logs', 'notifications-center' ); ?></h2>
                    </div>
                    <div class="main">
                        <?php $this->display_settings_fields( 'general', 'logs' ); ?>
                    </div>
                </div>
            </div>
            <div id="voy-settings-sidebar">
                <div class="voy-box">
                    <div class="header">
                        <h2><?php _e( 'Save', 'voy_notifications_center' ); ?></h2>
                    </div>
                    <div class="main">
                            <a class="button button-primary voynotif_save_settings"><?php _e( 'Save changes', 'notifications-center' ); ?></a>
                            <span id="voynotif_response_wrapper"></span>
                    </div>
                </div>
            </div>
            <?php
        }

        function ajax_block_default_email() {
           
            $notif_id = $_POST['notification_id'];
            $notif_status = $_POST['notification_status'];
            
            //Check if notification status is valid before saving. Prevent any damage to WP
            if( $notif_status != 1 AND $notif_status != 0 ) {
                echo 'Unable to save data (Notification status not valid)';
                die();
            }
            
            //Check if notification id is valid before saving. Prevent any damage to WP
            if( !array_key_exists( $notif_id, $this->get_default_notifications() ) ) {
                echo 'Unable to save data (Notification ID not valid)';
                die();                
            }
            
            voynotif_update_option( 'block_' . $notif_id . '_status', $notif_status );

            if( $result == true ) {
                echo $notif_id.' updated to '.$notif_status;
                die();                
            } else {
                echo $notif_id.' unable to update to '.$notif_status;
                die();                   
            }
            
        } 
        
        
        /**
         * 
         */
        function ajax_duplicate_default() {
            
            //get default notification information
            $default_id = $_POST['id'];
            $defaults = $this->get_default_notifications();
            $default = $defaults[$default_id];
            
            //Check if you really get it
            if( empty($default) ) die();
            
            /**
             * Add action to perform before Notification creation
             * 
             * @author Floflo
             * @since 1.1.0
             * @update 2016-12-19
             * 
             * @param array $default Array of default notification information
             */
            do_action( 'voynotif/settings/default/before_duplicate', $default );            
            
            //Add defaut information about notification
            $notification_id = wp_insert_post( array(
                'post_type'     => 'voy_notification',
                'post_status'   => 'draft',
                'post_title'    => $default['label'],
                'post_content'    => $default['custom']['content'],
            ) ); 
            add_post_meta( $notification_id, VOYNOTIF_FIELD_PREFIXE . 'object', $default['custom']['title'] );

            //Add all aditionnal information like reciptients, params, etc.
            $default_fields = array( 
                'type', 
                'recipient_type', 
                'recipient_emails',
                'recipient_users',
                'recipient_roles',
                'core_update_type', 
            );
            foreach( $default_fields as $default_field ) {
                if( ! empty( $default['custom'][$default_field] ) ) {
                    add_post_meta( $notification_id, VOYNOTIF_FIELD_PREFIXE . $default_field, $default['custom'][$default_field] );
                }
            }
            
            /**
             * Add action to perform after Notification creation
             * Basic post information & basic post_meta information ave alreaby been registered
             * 
             * @author Floflo
             * @since 1.1.0
             * @update 2016-12-19
             * 
             * @param array $default Array of default notification information
             * @param int $notification_id ID of Notifications peviously created
             */
            do_action( 'voynotif/settings/default/after_duplicate', $default, $notification_id );
                        
            echo get_edit_post_link($notification_id, '');
            die();
        }
        
        
        /**
         * About tab callback
         * 
         * @author Floflo
         * @since 1.1.0
         * @update 2017-01-17
         * 
         */
        function settings_about_screen() {
            include_once( __DIR__ . '/views/tab-about.php' );
        }
        
        
        /**
         * 
         */
        function settings_defaults_screen() {

            $default_tags = voynotif_get_notifications_tags();            
            $i = 0;
            ?>
            
            <?php foreach( $default_tags as $tag_id => $tag_label ) { ?>
                <?php $default_notifications = $this->get_default_notifications( $tag_id ); ?>
                <?php if( ! empty( $default_notifications ) ) { ?>
                    <div class="voy-box">
                        <div class="header">
                            <h2><?php echo $tag_label; ?></h2>
                        </div>
                        <div class="main">
                            <?php foreach( $default_notifications as $id => $data ) { $i++; ?>
                                <div class="voynotif-default-item">
                                    <div class="voynotif-default-check">
                                        <?php
                                            $status = voynotif_option( 'block_' . $id . '_status' );
                                            if( $status == 1 ) {
                                                $checked = ''; 
                                                $title = __( 'Blocked', 'notifications-center' );
                                            } else {
                                                $checked = 'checked';
                                                $title = __( 'Active', 'notifications-center' );
                                            }
                                        ?>
                                        <input type="checkbox" data-id="<?php echo $id; ?>" id="checkbox-<?php echo $i; ?>" <?php echo $checked; ?> class="cbx voy-hidden"/>
                                        <label for="checkbox-<?php echo $i; ?>" class="lbl" title="<?php echo $title; ?>"></label>                    
                                    </div>
                                    <div class="voynotif-default-content">
                                        <h3><?php echo $data['label']; ?></h3>
                                        <p><?php echo $data['description']; ?></p>
                                    </div>
                                    <div class="voynotif-default-actions">
                                        <?php if( ! empty( $data['custom'] ) ) { ?>
                                            <a class="voynotif-create_default button" data-id="<?php echo $id; ?>">Create custom</a>
                                        <?php } ?>
                                    </div>
                                </div>                        
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
            
            <?php
        }

    }
}

if( is_admin() ) {
    new VOYNOTIF_admin_settings();
}


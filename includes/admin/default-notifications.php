<?php
if( !class_exists( 'VOYNOTIF_admin_default_notifications' ) ) {
    class VOYNOTIF_admin_default_notifications {
    
        
        /**
         * Constructor
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-10-25
         */
        function __construct() {
            
            //Add hooks
            add_action('admin_menu', array($this, 'create_submenu') ); //Hook for submenu creation 
            add_action('in_admin_footer', array($this, 'add_js') ); //Hook for submenu creation 
                      
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
                __( 'Default notifications', 'notifications-center' ),
                __( 'Default notifications', 'notifications-center' ),
                'manage_options',
                'voy_notification-admin-defaultnotifications',
                array($this, 'render_page') 
            );    
        } 
        
        

        
        
        function add_js() {
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
            </script>
            <?php
        }
        
        
        function get_default_notifications() {
            $defaults = apply_filters( 'voynotif/settings/defaults' ,array(
                'user_password_reset' => array(
                    'label' => __( 'User password reset', 'notifications-center' ),
                    'tag' => 'system',
                    'description' => __( 'Email sent when user asked for password reset, from the login screen. Email contains the password reset link', 'notifications-center' ),
                ),
                'wordpress_updated' => array(
                    'label' => __( 'Wordpress core updated', 'notifications-center' ),
                    'tag' => 'system',
                    'description' => __( 'Email send after Wordpress updated to a new version.', 'notifications-center' ),
                ),
                'wordpress_update_available' => array(
                    'label' => __( 'Uvailable Wordpress update', 'notifications-center' ),
                    'tag' => 'system',
                    'description' => __( 'Email when Wordpress can be updated to a new version, but nothing has been done at this time', 'notifications-center' ),
                ),
                'comment_pending' => array(
                    'label' => __( 'Comment to approve', 'notifications-center' ),
                    'tag' => 'comment',
                    'description' => __( 'Email sent when a visite submitted a comment on your website and if "A comment is held for moderation" is checked in Wordpress settings', 'notifications-center' ),
                ),
                'new_pingback' => array(
                    'label' => __( 'New Pingback', 'notifications-center' ),
                    'tag' => 'comment',
                    'description' => __( 'Email sent when anothir site mention one of your post and if "Allow link notifications from other blogs (pingbacks and trackbacks) on new articles " is checked in Wordpress settings', 'notifications-center' ),
                ),
            ) );
            
            return $defaults;
        }
        
        
        function render_page() {
            ?>
                <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
                    <a href="<?php echo admin_url('edit.php?post_type=voy_notification'); ?>" class="nav-tab">Custom notifications</a>
                    <a href="<?php echo admin_url('edit.php?post_type=voy_notification&page=voy_notification-admin-defaultnotifications'); ?>" class="nav-tab nav-tab-active">Default notifications</a>
                </h2>
            <div class="wrap voy-wrap">
                <h1><?php _e( 'Default Notifications', 'notifications-center' ); ?></h1>
                <br>
                <table class="wp-list-table widefat fixed striped posts">
                    <thead>
                        <tr>
                            <th id="status" class="manage-column" style="width:50px;">Status</th>
                            <th id="title" class="manage-column" style="width:350px;">Title</th>
                            <th id="tag" class="manage-column" style="width:120px;">Thematique</th>
                            <th id="description" class="manage-column">Descriptions</th>
                            <th id="actions" class="manage-column" style="width:120px;">Actions</th>	
                        </tr>
                    </thead>
                    <tbody class="voy-default-list">
                        <?php 
                            $default_notifications = $this->get_default_notifications();
                            $tags = voynotif_get_notifications_tags();
                            $i = 0;
                        ?>
                        <?php foreach( $default_notifications as $id => $data ) { $i++; ?>
                            <tr id="<?php echo $id; ?>" class="hentry">
                                <td class="" data-colname="status">
                                    <?php
                                        $status = get_option( VOYNOTIF_FIELD_PREFIXE . 'block_' . $id . '_status' );
                                        if( $status == 1 ) {
                                            $checked = ''; 
                                        } else {
                                            $checked = 'checked';
                                        }
                                    ?>
                                    <input type="checkbox" data-id="<?php echo $id; ?>" id="checkbox-<?php echo $i; ?>" <?php echo $checked; ?> class="cbx hidden"/>
                                    <label for="checkbox-<?php echo $i; ?>" class="lbl"></label>
                                </td>
                                <td class="" data-colname="title"><strong><?php echo $data['label']; ?></strong></td>
                                <td class="" data-colname="tag"><?php echo $tags[$data['tag']]; ?></td>
                                <td class="" data-colname="description"><?php echo $data['description']; ?></td>
                                <td class="" data-colname="Type"><a href="#">Edit content</a></td>	
                            </tr>                        
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
        
    }
}
if( is_admin() ) {
    new VOYNOTIF_admin_default_notifications();
}

<?php
if( !class_exists( 'VOYNOTIF_admin_notifications' ) ) {
    class VOYNOTIF_admin_notifications {

        /**
         * @var string seralized name of the post type 
         */
        var $name;


        /**
         * Constructor
         * 
         * @author Floflo
         * @since 0.9
         */
        function __construct() {

            //Post type name and text domain
            $this->name = 'voy_notification';
            $this->text_domain = 'text_domain';

            //Register hooks
            add_action('admin_menu', array($this, 'create_submenu') ); //Hook for submenu creation
            add_filter( 'manage_edit-'.$this->name.'_columns',  array( $this, 'manage_columns' ) );
            add_action( 'manage_'.$this->name.'_posts_custom_column', array( $this, 'manage_custom_column' ) );
            add_action( 'restrict_manage_posts', array( $this, 'display_filter' ) );
            add_filter( 'parse_query', array( $this, 'filter_posts' ) );
            
        }
        
        function create_submenu() {
            $link = admin_url().'edit.php?post_type=voy_notification';
            add_submenu_page(
                'options-general.php',
                __( 'Notifications', 'notifications-center' ),
                __( 'Notifications', 'notifications-center' ),
                'manage_options',
                $link,
                NULL
            );    
        } 


        /**
         * Manage colums on post type listing
         * 
         * @author Floflo
         * @since 0.9
         * 
         * @param array $defaults registered colums
         * @return array new registered colums names
         */
        function manage_columns($defaults) {

            //Suppresion des anciennes colonnes
            //unset($defaults['author']);
            unset($defaults['tags']);
            unset($defaults['comments']);
            unset($defaults['date']);
            unset($defaults['featured_image']);

            //Ajout des nouvelles colonnes
            $defaults['voynotif_type'] = __( 'Type', 'notifications-center' );
            $defaults['voynotif_recipients'] = __( 'Recipients', 'notifications-center' );
            $defaults['voynotif_status'] = __( 'Status', 'notifications-center' );

            //Renvoi des colonnes
            return $defaults;        
        }


        /**
         * manage custom colum data
         * 
         * @author Floflo
         * @since 0.9
         * 
         * @global object $post
         * @param string $column column serialized name, as defined in manage_columns function 
         */
        function manage_custom_column($column) {
            global $post;              

            //Type column
            if ('voynotif_type' == $column) {
                $types = voynotif_get_notifications_types();
                $notification_type = get_post_meta($post->ID, 'voynotif_type', true); 
                if( ! empty( $types[$notification_type]['label'] ) ) {
                    echo $types[$notification_type]['label'];
                } else {
                    _e('Unknown', 'notifications-center');
                }
            }

            //Status column    
            elseif ('voynotif_status' == $column) {    
                if( $post->post_status == 'publish' ) {
                    _e('Active', 'notifications-center');
                } else {
                    _e('Inactive', 'notifications-center');
                }
            } 
            
            //Recipients column   
            elseif ('voynotif_recipients' == $column) {    
                $notification = new VOYNOTIF_notification($post->ID);
                
                if( $notification->recipient_type == 'emails' ) {
                    echo  implode( ', ', $notification->_get_recipients() );
                 
                } elseif($notification->recipient_type == 'users' ) {    
                    $users_to_display = array();
                    $users = $notification->get_field('recipient_users');
                    foreach( $users as $user_id ) {
                        $user_data = get_userdata($user_id);
                        $users_to_display[] = $user_data->user_login;
                    }
                    echo implode(', ', $users_to_display);
                    
                } elseif( $notification->recipient_type == 'roles' ) {
                    $roles = $notification->get_field('recipient_roles');
                    $roles_names = array();
                    if( !empty( $roles ) ) {
                        global $wp_roles;
                        foreach( $roles as $role ) {
                            $roles_names[] = translate_user_role( $wp_roles->roles[ $role ]['name'] );
                        }
                    }       
                    echo sprintf( __('All %s users'), implode( ' & ', $roles_names ) ); 
                    
                } else {
                    _e('Specific user(s)', 'notifications-center');
                }
            } 

        }
        
        
        /**
         * 
         * @return type
         */
        function display_filter() {
            
            $type = 'post';
            if (isset($_GET['post_type'])) {
                $type = $_GET['post_type'];
            }            
            if ( $this->name != $type ) return;
            $tags = voynotif_get_notifications_tags();
            
            ?>
            <select name="voynotif_type">
                <option value=""><?php _e( 'All types', 'notifications-center' ); ?></option>
                <?php foreach( $tags as $tag_id =>$tag_label ) {  ?>
                    <?php
                    $types = voynotif_get_notifications_types($tag_id);
                    if( empty( $types ) ) {
                        continue;
                    }                   
                    ?>
                    <optgroup label="<?php echo $tag_label; ?>">
                        <?php foreach( $types as $type_id =>$type_data ) { ?>
                            <?php 
                            $selected = '';
                            if( isset( $_GET['voynotif_type'] ) && $_GET['voynotif_type'] == $type_id ) $selected = 'selected';
                            ?>
                            <option value="<?php echo $type_id; ?>" <?php echo $selected; ?>><?php echo $type_data['label']; ?></option>
                        <?php } ?>
                    </optgroup>
                <?php } ?>
            </select>
            <?php
        }
        
        
        /**
         * 
         * @global type $pagenow
         * @param type $query
         */
        function filter_posts( $query ) {
        
            global $pagenow;
            $type = 'post';
            if (isset($_GET['post_type'])) {
                $type = $_GET['post_type'];
            }
            if ( $this->name == $type && is_admin() && $pagenow=='edit.php' && isset($_GET['voynotif_type']) && $_GET['voynotif_type'] != '') {
                $query->query_vars['meta_key'] = 'voynotif_type';
                $query->query_vars['meta_value'] = $_GET['voynotif_type'];
            }
            
        }

    }
}
if( is_admin() ) {
    new VOYNOTIF_admin_notifications();
}


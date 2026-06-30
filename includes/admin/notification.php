<?php
if( !class_exists( 'VOYNOTIF_admin_notification' ) ) {
    class VOYNOTIF_admin_notification {

        var $post_type;

        /**
         * Constrctor
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-06-02
         */
        function __construct() {

            $this->post_type = 'voy_notification';

            //Init
            add_action( 'add_meta_boxes',  array( $this, 'add_meta_boxes' ), 5, 2 );
            add_action( 'save_post', array( $this, 'save_metaboxes' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
            add_action( 'admin_footer', array( $this, 'footer_ajax' ) ); // Write our JS below here
            add_action( 'media_buttons', array( $this, 'add_mask_button' ), 50 );
            add_action( 'admin_notices', array( $this, 'toto' ) );

            //Add ajax calls
            add_action('wp_ajax_voy_notification_ajax_update', array( $this, 'ajax_update' ) );
        }
        
        public function toto() {
            
            $screen = get_current_screen();     
            if( $screen->id != 'edit-voy_notification' && $screen->id != 'voy_notification' ) return; 
            
            VOYNOTIF_admin_settings::render_tabs();
        }


        /**
         * 
         * @return type
         */
        function _get_setting_fields() {
            $fields = apply_filters( 'voynotif/notification/fields/settings/type='.$_POST['type'], array() );
            return $fields;
        }


        /**
         * 
         * @return type
         */
        function _get_content_fields() {
            $fields = apply_filters( 'voynotif/notification/fields/content/type='.$_POST['type'], array() );
            return $fields;        
        }


        /**
         * Get registered recipient types, regarding to notification type
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-05-27
         * 
         * @param string $notification_type $notification name
         * @return array array of available recipient types, like ([recipient_type_name] => [Recipient type label], etc)
         */
        function _get_recipient_types($notification_type) {
            $types = apply_filters( 'voynotif/notification/recipients/type='.$notification_type, array(          
                'users' => __( 'Specific user(s)', 'notifications-center' ),
                'roles' => __( 'Specific role(s)', 'notifications-center' ),
                'emails' => __( 'Specific e-mail address(es)', 'notifications-center' ),
            ) );   
            return $types;
        }


        /**
         * Get roles to perform recipient choice
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-07-04
         * 
         * @global type $wp_roles
         * @return array WP roles
         */
        function _get_roles() {
            global $wp_roles;

            $all_roles = $wp_roles->roles;
            $editable_roles = apply_filters('editable_roles', $all_roles);

            return $editable_roles;        
        }


        function _get_users() {
            $return_users = array();
            $args = apply_filters( 'voynotif/field/recipient_users/user_query', array() );
            $user_query = get_users( $args );
            if ( ! empty( $user_query ) ) {
                foreach ( $user_query as $user ) {
                    $return_users[$user->ID] = $user->display_name;
                }
            }
            return $return_users; 
        }


        /**
         * Display JS condition for a field
         * 
         * @author Floflo
         * @since non-implemented
         * @update 2016-07-04
         * 
         * @param array $field
         * @return string JS Snippet
         */
        function _get_js_conditions($field) {

            //Do nothing if no condition is defined
            if( empty( $field['condition'] ) ) {
                return;
            }

            global $voynotif_fields;

            //Check if conditonnal field is a registered field
            if( ! array_key_exists( $field['condition']['field_id'], $voynotif_fields ) ) {
                return;
            }

            $conditional_field = $field['condition']['field_id'];
            $js_selector = 'jQuery("#voy_field_'.$conditional_field.' select").val()';           
            $js_operator = $field['condition']['operator']; 
            $js_value = $field['condition']['value']; 

            //build js condition

            if( $voynotif_fields[$conditional_field]['type'] == 'boolean' ) {
                $js_condition = 'jQuery("#voy_field_'.$conditional_field.'").checked';
            } else {
                $js_condition = 'jQuery("#voy_field_'.$conditional_field.' select").val() '.$js_operator.' \''.$js_value.'\'';
            }

            //build JS
            $js_to_return = '<script type="text/javascript">jQuery("#voy_field_'.$field['condition']['field_id'].' select").change(function(){';
            $js_to_return .=
                'if('.$js_condition.') {
                    jQuery("#voy_field_'.$field['id'].'").removeClass(\'invisible\');
                } else {
                    jQuery("#voy_field_'.$field['id'].'").addClass(\'invisible\');
                }';
            $js_to_return .= '});</script>';

            //Return
            return $js_to_return;
        } 


        /**
         * Perforom Ajax request
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-07-05
         */
        function ajax_update() {

            //Get post id to render_field method
            $post = get_post( $_POST['post_id'] );
            $type = $_POST['type'];
            $recipient_types = $this->_get_recipient_types($type);

            //Get settings HTML
            $settings_html = '';
            $fields = $this->_get_setting_fields();
            foreach($fields as $field) {
                $settings_html .= $this->render_field($field, $post, false);
            } 

            //Get settings HTML
            $content_html = '';
            $fields = $this->_get_content_fields();
            foreach($fields as $field) {
                $field['context'] = $_POST['type'];
                $field['location'] = 'settings';
                $voynotif_fields[] = $field;            
                $content_html .= $this->render_field($field, $post, false);
            } 

            //Get recipient types
            $recipient_html = $this->render_field( array(
                'id' => 'voynotif_recipient_type',
                'label' => __( 'Recipient type', 'notifications-center' ),
                'type' => 'select',
                'choices' => $recipient_types,
            ), $post );  

            //Get masks
            $types = voynotif_get_notifications_types(); 
            $mask_engine = new voynotif_masks($_POST['type'], null, $types[$type]['tags'] );
            ob_start();
            ?>
             <select name="voy_mask_select" class="voy_mask_select">
                <option value="" selected disabled><?php _e( 'Add a variable', 'notifications-center' ); ?></option>
                <?php foreach( $mask_engine->tags as $tag_name => $tag_label ) { ?>
                    <?php 
                        $masks = $mask_engine->get_masks_by_tag($tag_name);
                        if( empty( $masks ) ) continue;
                    ?>
                    <optgroup label="<?php echo $tag_label; ?>">
                    <?php foreach( $masks as $mask_name => $mask_info ) { ?>
                        <option value="<?php echo $mask_name; ?>"><?php echo $mask_info['title'] ?></option>
                    <?php } ?>
                    </optgroup>
                <?php } ?>
            </select>       
            <?php
            $masks_html = ob_get_clean();

            //Return
            $return = array($recipient_html, $settings_html, $content_html, $masks_html );
            wp_send_json($return);    

        }


        /**
         * Add Ajax script to update some fields when user changes notification type
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-05-27
         */
        function footer_ajax() {
            
            $screen = get_current_screen();
            if( $screen->id != 'voy_notification' ) return;
            
            ?>
            <script type="text/javascript">

                //Function to load fields
                function voynotif_load_fields(){

                    //Registering variables
                    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
                    var type_fields_wrapper = jQuery('.voy-type_fields_wrapper');
                    var recipient_type_field_wrapper = jQuery('.voy-recipient_type_field_wrapper');
                    var content_fields_wrapper = jQuery('.voy-content_fields_wrapper'); 
                    var masks_wrapper = jQuery('.voy-type_masks_wrapper');

                    //Set ajax request for type fields
                    type_fields_wrapper.html('<div class="voy-loading"></div>');
                    recipient_type_field_wrapper.html('<div class="voy-loading"></div>');
                    content_fields_wrapper.html('<div class="voy-loading"></div>');


                    jQuery.ajax({ 
                        data: {action: 'voy_notification_ajax_update', post_id:<?php echo get_the_ID(); ?>, type:jQuery('#voy_field_voynotif_type select').val()},
                        type: 'post',
                        url: ajaxurl,
                        success: function(data) {
                            recipient_type_field_wrapper.html(data[0]);
                            type_fields_wrapper.html(data[1]);                       
                            content_fields_wrapper.html(data[2]);
                            masks_wrapper.html(data[3]);
                        }
                    });

                }

                //Load ajax script after page load
                jQuery(document).ready(function(){
                    voynotif_load_fields();

                }); 

                //Load ajax script after notification type change
                jQuery("#voy_field_voynotif_type").change(function(){
                    voynotif_load_fields();
                });

            </script>
            <?php         
        }

        /**
         * Add scripts to the edit screen, for fields condionnal logic and select2
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-05-27
         * 
         * @param type $hook
         */
        function enqueue_scripts($hook) {
            
            $screen = get_current_screen();
            if( $screen->id != 'voy_notification' ) return;
            
            wp_enqueue_script( 'voynotif_notificaiton_edit', VOYNOTIF_URL . '/assets/js/notification_edit.js' );  
            wp_enqueue_script( 'voynotif_notificaiton_edit', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.2/js/select2.min.js' );
            wp_enqueue_style( 'voynotif_notificaiton_edit', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.2/css/select2.min.css' );         
        }


        /**
         * Render fields displayeds in custom metaboxes
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-06-06
         * 
         * @global object $post
         * @param array $args field description
         * @param object $post 
         */
        function render_field( $args, $post = NULL ){

            //Define $post and $post_id
            if( $post == NULL ) {
                global $post;       
            }
            $post_id = $post->ID;

            //Sanitize main args 
            $type = $args['type'];
            $context = 'notification';
            $id = sanitize_text_field($args['id']);
            $label = esc_html($args['label']);

            //Get description
            $description = '';
            if( isset($args['description']) AND $args['description'] != '' ) {
                $description = esc_html($args['description']);
            }


            //Get select2 class if needed. Will be displayed later
            $select2 = '';
            if( isset($args['params']['select2']) AND $args['params']['select2'] == true ) {
                $select2 = 'select2';
            }

            //Check for conditions
            $visibility_class = '';
            if( isset($args['condition']) AND is_array($args['condition']) ) {       
                if( get_post_meta($post_id, $args['condition']['field_id'], true) == $args['condition']['value']  ) {
                    $visibility_class = '';
                } else {
                    $visibility_class = 'invisible';
                }
            }  

            //Set up placeholder
            $placeholder = '';
            if( ! empty( $args['params']['placeholder'] ) ) {
                $placeholder = 'placeholder="'.$args['params']['placeholder'].'"';
            }

            //Build input regarding to field type
            $input = '';
            switch( $type ) {

                //Hidden field
                case 'hidden' :
                    $input = '<input type="hidden" value="'.esc_html(get_post_meta($post_id, $id, true)).'">';
                    break;

                //Select field
                case 'select' :
                    $input = '<select name="'.$id.'" '.$placeholder.'>';
                    foreach( $args['choices'] as $k => $v ) {
                        $selected = '';
                        if( get_post_meta($post_id, $id, true) == $k ) {
                            $selected = 'selected';
                        }
                        $input .= '<option value="'.$k.'" '.$selected.'>'.$v.'</option>';
                    }
                    $input .= '</select>'; 
                    break;

                //Select multiple field
                case 'selectmultiple' :
                    $input = '<select multiple name="'.$id.'[]" '.$placeholder.'>';
                    foreach( $args['choices'] as $k => $v ) {
                        $selected = '';

                        if( ! is_array( get_post_meta($post_id, $id, true) ) ) {
                            if( get_post_meta($post_id, $id, true) == $k ) {
                                $selected = 'selected';
                            }
                        } else {
                            if(in_array( $k, get_post_meta($post_id, $id, true) ) ) {
                                $selected = 'selected';
                            }                
                        }

                        $input .= '<option value="'.$k.'" '.$selected.'>'.$v.'</option>';
                    }
                    $input .= '</select>';  
                    break;

                //Textfield
                case 'text' : 
                    $input = '<input type="text" name="'.$id.'" value="'.esc_html(get_post_meta($post_id, $id, true)).'">'; 
                    break;

                //Boolean field
                case 'boolean' :
                    $checked = '';
                    if( get_post_meta($post_id, $id, true) == true ) {
                        $checked = 'checked';
                    }
                    $input = '<input name="'.$id.'" type="checkbox" id="'.$id.'" value="true" '.$checked.'> <label for="'.$id.'">'.$args['params']['title'].'</label>';
                    break;

                //WYSIWYG field
                case 'wysiywg' :
                    $input = '';
                    break;

            }

            //Display field
            ob_start(); 
            ?>
            <div id="voy_field_<?php echo $id; ?>" class="voy-field_wrapper voy_field_type_<?php echo $type; ?> <?php echo $visibility_class; ?>">
                <div class="voy-field_label">
                    <span class="label"><?php echo $label; ?></span>
                    <?php if( ! empty( $description ) ) {?>
                        <span class="description"><?php echo $description; ?></span>
                    <?php } ?>
                </div>
                <div class="voy-field_input <?php echo $select2; ?>">
                    <?php echo $input; ?>
                </div>
            </div>
            <?php //echo $this->_get_js_conditions($args); ?>
            <?php
            return ob_get_clean();

        }

        /**
         * Save fields from custom metaboxes
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-06-06
         * 
         * @param int $post_id current notification id
         */
        function save_metaboxes($post_id) {        

            //----------------------------------------------------------------------
            // SRestric access
            //----------------------------------------------------------------------
            if ( $this->post_type !== get_post_type( $post_id ) ) {
                return;
            }

            // Check nonce.
            if ( empty( $_POST[ $this->post_type . '_settings_nonce' ] ) ) {
                return;
            }

            // Verify nonce.
            if ( ! wp_verify_nonce( $_POST[ $this->post_type . '_settings_nonce' ], $this->post_type ) ) {
                return;
            }

            //Check if autosaving
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }

            //Restrict access to user with needed rights
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }       

            //----------------------------------------------------------------------
            // Default fields
            //----------------------------------------------------------------------       
            if(isset($_POST['voynotif_type'])){
                update_post_meta($post_id,'voynotif_type', sanitize_text_field($_POST['voynotif_type']));
            }        

            if(isset($_POST['voynotif_specify_sender'])){
                update_post_meta($post_id,'voynotif_specify_sender', true);
            } else {
                update_post_meta($post_id,'voynotif_specify_sender', false);
            }        

            if(isset($_POST['voynotif_sender_name'])){
                update_post_meta($post_id,'voynotif_sender_name', sanitize_text_field($_POST['voynotif_sender_name']));
            }

            if(isset($_POST['voynotif_sender_email'])){
                update_post_meta($post_id,'voynotif_sender_email', sanitize_text_field($_POST['voynotif_sender_email']));
            }        

            if(isset($_POST['voynotif_title'])){
                update_post_meta($post_id,'voynotif_title', sanitize_text_field($_POST['voynotif_title']));
            }

            if(isset($_POST['voynotif_object'])){
                update_post_meta($post_id,'voynotif_object', sanitize_text_field($_POST['voynotif_object']));
            }  

            if(isset($_POST['voynotif_recipient_type'])){
                update_post_meta($post_id,'voynotif_recipient_type', sanitize_text_field($_POST['voynotif_recipient_type']));
            } 

            if(isset($_POST['voynotif_recipient_emails'])){
                update_post_meta($post_id,'voynotif_recipient_emails', sanitize_text_field($_POST['voynotif_recipient_emails']));
            }


            if( isset($_POST['voynotif_recipient_roles']) && is_array( $_POST['voynotif_recipient_roles'] ) AND ! empty( $_POST['voynotif_recipient_roles'] ) ) {
                $array_roles = array();
                foreach($_POST['voynotif_recipient_roles'] as $val_to_save) {
                    $array_roles[] = $val_to_save; 
                }
                update_post_meta($post_id, 'voynotif_recipient_roles', $array_roles);
            }

            if( isset( $_POST['voynotif_recipient_users'] ) && is_array( $_POST['voynotif_recipient_users'] ) AND ! empty( $_POST['voynotif_recipient_users'] ) ) {
                $array_users = array();
                foreach($_POST['voynotif_recipient_users'] as $val_to_save) {
                    $array_users[] = $val_to_save; 
                }
                update_post_meta($post_id, 'voynotif_recipient_users', $array_users);
            }

            //----------------------------------------------------------------------
            // Custom fields
            //----------------------------------------------------------------------

            //Save custom settings fields
            $setting_fields = apply_filters( 'voynotif/notification/fields/settings/type='.$_POST['voynotif_type'], array() );
            foreach( $setting_fields as $setting_field ) {
                $value = $_POST[$setting_field['id']];
                $this->save_field($post_id, $setting_field, $value);        
            }

            //Save custom settings fields
            $content_fields = apply_filters( 'voynotif/notification/fields/content/type='.$_POST['voynotif_type'], array() );
            foreach( $content_fields as $content_field ) {
                if( isset($_POST[$content_field['id']]) ) {
                    $value = $_POST[$content_field['id']];
                    $this->save_field($post_id, $content_field, $value);                      
                }      
            }

        }


        /**
         * Save custom field
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-05-27
         * 
         * @param int $post_id Notification ID to set values from
         * @param array $field array with field info
         * @param mixed $value User busmitted value to save
         */
        function save_field($post_id, $field, $value) {

            //Get field type
            $field_name = $field['id']; 
            $field_type = $field['type'];

            //Sanitize value for each field type
            switch ($field_type) {

                //Text field
                case 'text' :
                    update_post_meta($post_id, $field_name, sanitize_text_field($value));
                    break;

                //Select field
                case 'select' :
                    if( array_key_exists( $value, $field['choices'] ) ) {
                        update_post_meta($post_id, $field_name, sanitize_text_field($value));
                    } else {
                        update_post_meta($post_id, $field_name, '');
                    }
                    break;

                //Multiple select field
                case 'selectmultiple' :
                    $array_to_save = array();
                    if( is_array( $value ) AND ! empty( $value ) ) {
                        foreach($value as $val_to_save) {
                            if( array_key_exists( $val_to_save, $field['choices'] ) ) {
                                $array_to_save[] = $val_to_save; 
                            }
                        }
                    }
                    update_post_meta($post_id, $field_name, $array_to_save);
                    break; 

                //Boolean field
                case 'boolean' :
                    if( $value == true ) {
                        update_post_meta($post_id, $field_name, true);
                    } else {
                        update_post_meta($post_id, $field_name, false);
                    }
                    break;

            }

        }


        /**
         * Add Metaboxes to the notification edit screen
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016/05/23
         */
        function add_meta_boxes() {

            //Settings Meta box
            add_meta_box( 
                    'voynotif_metabox_settings', 
                    __( 'Settings', 'notifications-center' ), 
                    array( $this, 'metabox_settings_html' ),
                    'voy_notification',
                    'normal',
                    'high'
                );

            //Sender & recipient Meta box
            add_meta_box( 
                    'voynotif_metabox_sender', 
                    __( 'Sender & recipient', 'notifications-center' ), 
                    array( $this, 'metabox_sender_html' ),
                    'voy_notification',
                    'normal',
                    'high'
                );

            //Content Meta box
            add_meta_box( 
                    'voynotif_metabox_content', 
                    __( 'Content', 'notifications-center' ), 
                    array( $this, 'metabox_content_html' ),
                    'voy_notification' ,
                    'normal',
                    'high'
                );        

        }


        /**
         * Display Settings metabox
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-05-27
         * 
         * @param object $post current post object
         */
        function metabox_settings_html($post = NULL) {

            //Define nonce_field
            wp_nonce_field( 'voy_notification', $this->post_type . '_settings_nonce');

             ?>
            <!-- Global wrapper -->
            <div class="">            
                <?php
                    //Get notifcations types
                    $email_types = voynotif_get_notifications_types(); 
                    $tags = voynotif_get_notifications_tags();
                ?>

                <!-- Notification type field -->
                <div id="voy_field_voynotif_type" class="voy-field_wrapper voy_field_type_select">                      
                    <div class="voy-field_input select2">
                        <span class="label"><?php _e( 'When to send the notification?', 'notifications-center' ); ?></span>
                        <span class="description"></span>
                        <select name="voynotif_type">
                            <?php foreach( $tags as $tag_name => $tag_label ) { ?>
                                <optgroup label="<?php echo $tag_label; ?>">
                                    <?php foreach( $email_types as $k => $v ) { ?>
                                        <?php
                                            if( $v['tags'][0] != $tag_name ) {
                                                continue;
                                            }
                                            //Define selected option
                                            $current_type = get_post_meta($post->ID, 'voynotif_type', true);
                                            $selected = '';
                                            if( $current_type == $k ) {
                                                $selected = 'selected';
                                            }

                                            //Define label
                                            if( $v['overrides_wp'] == true ) {
                                                $v['label'] = '['.__( 'Overrides WP', 'notifications-center' ).'] '.$v['label'];
                                            }
                                        ?>
                                        <option value="<?php echo $k; ?>" <?php echo $selected; ?>><?php echo $v['label']; ?></option>
                                    <?php } ?>
                                </optgroup>
                            <?php } ?>
                        </select>        
                    </div>
                    <div class="voy-arrow"><span class="dashicons dashicons-arrow-down-alt2"></span></div>
                </div>
                <!-- ./Notification type field -->

                <!-- Specific settings fields, updated with ajax request -->
                <div class="voy-type_fields_wrapper"></div>
                <!-- ./Specific settings fields, updated with ajax request -->

            </div>
            <!-- ./Global wrapper -->
            <?php       
        }


        /**
         * Display Sender & recipient metabox
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-05-27
         * 
         * @param object $post current post object
         */
        function metabox_sender_html($post = NULL) {
            ?>
            <!-- Global wrapper -->
            <div class="">
                <?php

                    echo $this->render_field( array(
                        'id' => 'voynotif_specify_sender',
                        'label' => __( 'Specify sender ?', 'notifications-center' ),
                        'type' => 'boolean',
                        'params' => array(
                            'title' => __( 'Use a specific sender for this email instead of global settings', 'notifications-center' )
                        )
                    ), $post );            

                    echo $this->render_field( array(
                        'id' => 'voynotif_sender_name',
                        'label' => __( 'Sender name', 'notifications-center' ),
                        'description' => __( 'It may appear in the header of your email', 'notifications-center' ),
                        'type' => 'text',
                        'condition' => array(
                            'field_id' => 'voynotif_specify_sender',
                            'operator' => '==',
                            'value'    => true,
                        ),
                    ), $post );

                    echo $this->render_field( array(
                        'id' => 'voynotif_sender_email',
                        'label' => __( 'Sender email', 'notifications-center' ),
                        'description' => __( 'The notification will have been sent by this address', 'notifications-center' ),
                        'type' => 'text',
                        'condition' => array(
                            'field_id' => 'voynotif_specify_sender',
                            'operator' => '==',
                            'value'    => true,
                        ),
                    ), $post );
                    ?>

                    <!-- Recipient type field wrapper, updated with ajax request -->
                    <div class="voy-recipient_type_field_wrapper"></div>
                    <!-- ./Recipient type field wrapper, updated with ajax request -->

                    <?php
                    echo $this->render_field( array(
                        'id' => 'voynotif_recipient_emails',
                        'label' => __( 'Recipient Emails', 'notifications-center' ),
                        'description' => __( 'Recipient emails, separated by commas (",")', 'notifications-center' ),
                        'type' => 'text',
                        'condition' => array(
                            'field_id' => 'voynotif_recipient_type',
                            'operator' => '==',
                            'value'    => 'emails',                        
                        )
                    ), $post );               
                    $roles = $this->_get_roles();
                    $array_roles = array();
                    foreach( $roles as $role_name => $role_data ) {
                        $array_roles[$role_name] = $role_data['name'];
                    }
                    echo $this->render_field( array(
                        'id' => 'voynotif_recipient_roles',
                        'label' => __( 'Recipient Role(s)', 'notifications-center' ),
                        'description' => __( 'The notification will be send to all users in the selected roles', 'notifications-center' ),
                        'type' => 'selectmultiple',
                        'choices' => $array_roles,
                        'condition' => array(
                            'field_id' => 'voynotif_recipient_type',
                            'operator' => '==',
                            'value'    => 'roles',                        
                        ),
                        'params' => array(
                            'select2' => true,
                            'placeholder' => __( 'Select role(s)', 'notifications-center' ),
                        )
                    ), $post );     
                   echo $this->render_field( array(
                        'id' => 'voynotif_recipient_users',
                        'label' => __( 'User(s)', 'notifications-center' ),                
                        'type' => 'selectmultiple',
                        'choices' => $this->_get_users() ,
                        'condition' => array(
                            'field_id' => 'voynotif_recipient_type',
                            'operator' => '==',
                            'value'    => 'users',                        
                        ),
                        'params' => array(
                            'select2' => true,
                            'placeholder' => __( 'Select user(s)', 'notifications-center' ),
                        )
                    ), $post ); 
                ?>
            </div>
            <!-- ./Global wrapper -->
            <?php
        }


        /**
         * Display Content metabox
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-05-27
         * 
         * @param object $post current post object
         */    
        function metabox_content_html($post) {
            ?>
            <!-- Global wrapper -->
            <div class="">
                <?php

                    //Add Object field
                    $field = new VOYNOTIF_field( array(
                        'id' => 'object',
                        'label' => __( 'Subject', 'notifications-center' ),
                        'type' => 'text',
                        'screen' => 'post',
                        'post_id' => $post->ID
                    ) );
                    echo $field->display();
                    
                    //Add Object field
                    if( voynotif_get_option( 'activate_email_title' ) ) {
                        $field = new VOYNOTIF_field( array(
                            'id' => 'title',
                            'label' => __( 'Title', 'notifications-center' ),
                            'type' => 'text',
                            'screen' => 'post',
                            'post_id' => $post->ID
                        ) );
                        echo $field->display();
                    }
                    
                    //Add WYSIWYG field
                    $field = new VOYNOTIF_field( array(
                        'id' => 'content',
                        'label' => __( 'Content', 'notifications-center' ),
                        'description' => __( 'Buid here the message you want. You can use "Add variable" to add some variable information.', 'notifications-center' ),
                        'type' => 'wysiywg',
                        'screen' => 'post',
                        'post_id' => $post->ID
                    ) );
                    echo $field->display();
                ?> 

                <!-- Specific content fields, updated with ajax request -->
                <div class="voy-content_fields_wrapper"></div>
                <!-- ./Specific content fields, updated with ajax request -->

            </div>
            <!-- ./Global wrapper -->
            <?php
        }


        /**
         * Add a "Add variable" button next to add media button. Content will be called in AJAX
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-07-13
         */
        function add_mask_button() {
            $screen = get_current_screen();
            if( $screen->id != 'voy_notification' ) {
                return;
            }
            ?>
            <div class="voy-type_masks_wrapper"></div>     
            <?php        
        }
    }
}

//Load class if is admin
if( is_admin() ) {
    new VOYNOTIF_admin_notification();
}

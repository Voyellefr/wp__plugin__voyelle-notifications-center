<?php
/**
 * CLASS création de la page de choix des templates
 * 
 * @author Floflo
 * @since 0.9
 */
if( !class_exists( 'VOYNOTIF_admin_templates' ) ) {
    class VOYNOTIF_admin_templates {

        var $toto;

        /**
         * Constructeur
         * 
         * @author Floflo
         * @since 0.9
         * @param integer $notification_id ID de la notification
         */  
        function __construct() {

            //General settings
            add_action('admin_init', array($this, 'update_template') ); //Hook for template updating
            add_action('admin_notices', array($this, 'admin_notices') ); //Hook for displaying notices
            //add_action('admin_menu', array($this, 'create_submenu') ); //Hook for submenu creation

        } 


        /**
         * Création de l'onglet dans le menu
         * 
         * @author Floflo
         * @since 0.9
         */
        function create_submenu() {
            add_submenu_page(
                'edit.php?post_type=voy_notification',
                __( 'Templates', 'notifications-center' ),
                __( 'Email template', 'notifications-center' ),
                'manage_options',
                'voy_notification-admin-templates',
                array($this, 'render_page') 
            );    
        }


        /**
         * Renvoie l'URL du Customizer pour le template
         * 
         * @author Floflo
         * @since 0.9
         * @return string encoded Customizer URL
         */
        function _customizer_url( $template_name ) {

            // Generate Customizer URL
            $url = admin_url( 'customize.php' ); 
            $url = add_query_arg( 'voynotif_template_preview_html', $template_name, $url ); 
            $url = add_query_arg( 'url', home_url() . '/?voynotif_template_preview_html='.$template_name, $url ); 

            // Add URL marker 
            $url = add_query_arg( 'return', urlencode( admin_url() . 'edit.php?post_type=voy_notification&page=voy_notification-admin-templates' ), $url ); 

            // Return URL
            return esc_url_raw( $url );
        }


        /**
         * Création de la page d'admin que affiche les templates disponibles
         * 
         * @author Floflo
         * @since 0.9
         */
        function render_page() { ?>
            <?php add_thickbox(); ?>
            <div class="wrap">
                <h1><?php _e( 'Templates', 'notifications-center' ); ?></h1>
                <div class="templates_wrapper">
                <?php $templates = voynotif_get_templates(); ?>
                <?php foreach( $templates as $template_name => $template_data ) { ?>
                    <div class="template <?php if( voynotif_get_current_email_template() == $template_name  ) { echo 'selected'; } ?>">
                        <?php if($template_data['price'] == 'premium' ) { ?>
                            <div class="ribbon"><span><?php _e('PREMIUM', 'notifications-center'); ?></span></div>
                        <?php } ?>
                        <div class="template-thumbnail">
                            <img src="<?php echo $template_data['thumbnail']; ?>">
                        </div>

                        <a href="#TB_inline&height=420&width=1000&inlineId=popin-<?php echo $template_name; ?>" class="thickbox more-details"><?php _e( 'Template details', 'notifications-center' ); ?></a>

                        <div class="template-info">
                            <span class="template-title">
                                <?php if( voynotif_get_current_email_template() == $template_name ) { ?>
                                    <strong><?php _e( 'Current', 'notifications-center' ); ?> : </strong>
                                <?php } ?>
                                <?php echo $template_data['title']; ?>
                            </span>
                            <span class="template-select">
                                <?php $this->_template_actions($template_name, $template_data); ?>
                            </span>
                        </div>
                        <?php $this->_template_details($template_name, $template_data); ?>
                    </div>
                <?php } ?>
                </div>
            </div>


        <?php
        }


        /**
         * Met à jour le template retenu
         * 
         * @author Floflo
         * @since 0.9
         */
        function update_template() {

            //Récupération des variables
            $get_template_select = !empty($_GET['template_select']) ? $_GET['template_select'] : NULL; 
            $get_message = !empty($_GET['message']) ? $_GET['message'] : NULL;

            //Traitement
            if( $get_template_select AND empty($get_message) ) {

                //Sanitize value
                $template_to_save = sanitize_text_field($get_template_select);

                //Check if template to save is really a template (present in registered templates)
                if( !array_key_exists( $template_to_save, voynotif_get_templates() ) ) {
                    $url_success = remove_query_arg('template_select');
                    $url_success = add_query_arg('message', '2');
                    wp_redirect($url_success);
                    exit();
                }

                //Check if template to save is a real template and not a premium template teasing
                $template_data = voynotif_get_template($template_to_save);
                if( $template_data['price'] != 'free' ) {
                    $url_success = remove_query_arg('template_select');
                    $url_success = add_query_arg('message', '3');
                    wp_redirect($url_success);
                    exit();
                }

                //Template update
                update_option('voynotif_current_template', $template_to_save);

                //Return success URL
                $url_success = remove_query_arg('template_select');
                $url_success = add_query_arg('message', '1');
                wp_redirect($url_success);
                exit();
            }
        }


        /**
         * Gère les messages affichés (lors de la mise à jour d'un template ou dans d'autres cas de figure)
         * 
         * @author Floflo
         * @since 0.9
         */
        function admin_notices() {
            $message = !empty($_GET['message']) ? $_GET['message'] : NULL; 

            $current_screen = get_current_screen();
            if($current_screen->id != 'voy_notification_page_voy_notification-admin-templates') {
                return;
            }  

            if( $message == '1' ) { ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e( 'Template succesfully updated', 'notifications-center' );?></p>
            </div>

            <?php } elseif( $message == '2' ) { ?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e( 'Error. Template has not been updated', 'notifications-center' );?></p>
            </div>

            <?php } elseif( $message == '3' ) { ?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e( 'Sorry, this template is only available in the PRO version', 'notifications-center' );?></p>
            </div>
            <?php }        
        }


        /**
         * Get popin with template details
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-06-23
         * 
         * @param string $template_name
         * @param array $template_data
         */
        function _template_details($template_name, $template_data) {
            ?> 
                <div id="popin-<?php echo $template_name; ?>" style="display: none;">
                    <div class="template-details">
                        <div class="thumbnail">
                            <img src="<?php echo $template_data['thumbnail']; ?>">
                        </div>
                        <div class="content">
                            <?php if( $template_name == voynotif_get_current_email_template() ) { ?><span class="current-label"><?php _e( 'Current template', 'notifications-center' ); ?></span><?php } ?>
                            <h2 class="title"><?php echo $template_data['title']; ?><span class="version">Version&nbsp;: 1.0</span></h2>
                            <p class="author"><?php _e( 'By', 'notifications-center' ); ?> <a target="_blank" href="<?php echo $template_data['author_link']; ?>"><?php echo $template_data['author']; ?></a></p>
                            <p class="description"><?php echo $template_data['description']; ?></p>
                            <p class="credit"><?php echo $template_data['credit']; ?></p>
                            <hr />
                            <p class="cta">
                                <?php $this->_template_actions($template_name, $template_data); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php          
        }


        /**
         * Display action available regarding to templatename
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-06-23
         * 
         * @param string $template_name
         */
        function _template_actions($template_name, $template_data) {

            if( voynotif_get_current_email_template() != $template_name AND $template_data['price'] == 'free' ) { 
                $select_link = add_query_arg('template_select', $template_name); 
                $select_link = remove_query_arg('message', $select_link);
                ?>
                <a href="<?php echo $select_link; ?>" class="button"><?php _e( 'Select', 'notifications-center' ); ?></a>
                <!--<a href="<?php echo $this->_customizer_url($template_name); ?>" class="button button-primary"><?php _e( 'Preview', 'notifications-center' ); ?></a>-->
            <?php } elseif( voynotif_get_current_email_template() != $template_name ) { ?>
                <a target="_blank" href="<?php echo VOYNOTIF_PREMIUM_URL; ?>" class="button button-primary"><?php _e( 'Buy', 'notifications-center' ); ?></a>
            <?php } else { ?>
                <a href="<?php echo $this->_customizer_url($template_name); ?>" class="button button-primary"><?php _e( 'Personnalize', 'notifications-center' ); ?></a>
            <?php }       
        }

    }
}
new VOYNOTIF_admin_templates();




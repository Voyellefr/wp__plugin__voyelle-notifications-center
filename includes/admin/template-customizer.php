<?php

/**
 * CLASS création du Customizer de template pour personnaliser les templates d'email
 * 
 * @author Floflo
 * @since 0.9
 */
if( !class_exists( 'VOYNOTIF_template_customizer' ) ) {
    class VOYNOTIF_template_customizer {

        var $_trigger;

        /**
         * Constructor
         * 
         * @author Floflo
         * @since 0.9
         * 
         * @todo Commenter le code dans la fonction
         */
        public function __construct() {

            //Customize URL
            $this->_trigger = 'voynotif_template_preview_html';

            add_action('admin_menu', array($this, 'create_submenu') ); //Hook for submenu creation
            add_filter( 'query_vars', array( $this, 'add_query_vars' ), 5, 1 );
            add_action( 'template_redirect', array($this, 'template_preview_html') );
            //add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_customizer_script' ) );

            if( isset( $_GET[$this->_trigger] ) ) {           
                add_filter( 'customize_register', array( $this, 'customizer_sections' ), 50 ); 
                add_filter( 'customize_register', array( $this, 'remove_sections' ), 40 );
                //add_filter( 'customize_register', array( $this, 'remove_panels' ), 40 );            
            }

            add_action( 'customize_register', 'voynotif_custmizer_register_controls', 10);
            add_filter( 'customize_register', array( $this, 'customizer_settings' ), 50 ); 
            add_filter( 'customize_register', array( $this, 'customizer_controls' ), 60 );
            add_action( 'customize_panel_active', array( $this, 'remove_panels' ) );
        }


        /**
         * Création de l'onglet dans le menu
         * 
         * @author Floflo
         * @since 0.9
         */
        function create_submenu() {
            $link = self::customizer_url('salted');
            add_submenu_page(
                'themes.php',
                __( 'Templates', 'notifications-center' ),
                __( 'Email template', 'notifications-center' ),
                'manage_options',
                $link,
                NULL
            );    
        }  


        /**
         * Renvoie l'URL du Customizer pour le template
         * 
         * @author Floflo
         * @since 0.9
         * @return string encoded Customizer URL
         */
        public static function customizer_url( $template_name = 'salted' ) {
            $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
            $link = add_query_arg(
                array(
                    'url'             => urlencode( site_url( '?voynotif_template_preview_html='.$template_name ) ),
                    'return'          => urlencode( $current_url ),
                    'voynotif_template_preview_html' => $template_name
                ),
                'customize.php'
            );

            return $link;
        }


        /**
         * Ajout de l'identifiant du customizer aux query_vars
         * 
         * @author Floflo
         * @since 0.9
         * @param array $vars
         * @return array
         */
        public function add_query_vars( $vars ) {
            $vars[] = $this->_trigger;
            return $vars;
        }


        /**
         * Add JS script to customizer
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-07-19
         */
        public function enqueue_customizer_script() { 

            wp_enqueue_script( 
                    'voynotif_customizer_js', 
                    VOYNOTIF_URL . '/assets/js/customizer.js', 
                    array( 'jquery', 'customize-preview' ),
                    false,
                    true
                ); 

        }


        /**
         * Création des nouvelles sections pour le customizer
         * 
         * @author Floflo
         * @since 0.9
         * @global object $wp_customize
         */
        function customizer_sections() {

            // Ajout de section 
            global $wp_customize;
            $wp_customize->add_section( 'voynotif_settings', array ( 
                'title'       => __( 'Settings', 'notifications-center' ), 
                'capability'  => 'edit_theme_options', 
                'priority'    => 10, 
            ) ); 
            $wp_customize->add_section( 'voynotif_header', array ( 
                'title'       => __( 'Header', 'notifications-center' ), 
                'capability'  => 'edit_theme_options', 
                'priority'    => 10, 
            ) ); 
            $wp_customize->add_section( 'voynotif_body', array ( 
                'title'       => __( 'Body', 'notifications-center' ), 
                'capability'  => 'edit_theme_options', 
                'priority'    => 10, 
            ) );         
            $wp_customize->add_section( 'voynotif_footer', array ( 
                'title'       => __( 'Footer', 'notifications-center' ), 
                'capability'  => 'edit_theme_options', 
                'priority'    => 10, 
            ) ); 

        }


        /**
         * Chargement des champs à prendre en compte dans le customizer
         * 
         * @author Floflo
         * @since 0.9
         * @global object $wp_customize
         */
        function customizer_settings() {    

            global $wp_customize;

            /**
             * Template choise
             * @since 1.2.0
             */
            $wp_customize->add_setting( 'voynotif_email_template_path', array( 
                'type' => 'option', // Attention ! 
                'default' => 'plugin', 
                'transport' => 'refresh', ) 
            );           

            //Logo
            $wp_customize->add_setting( 'voynotif_email_logo', array( 
                'type' => 'option', // Attention ! 
                'default' => '', 
                'transport' => 'refresh', ) 
            );         

            //Title color
            $wp_customize->add_setting( 'voynotif_email_title_color', array( 
                'type' => 'option', // Attention ! 
                'default' => '#f5f5f5', 
                'transport' => 'refresh', ) 
            ); 

            //Button color
            $wp_customize->add_setting( 'voynotif_email_button_color', array( 
                'type' => 'option', // Attention ! 
                'default' => '#f5f5f5', 
                'transport' => 'refresh', ) 
            ); 

            //Content backbround color
            $wp_customize->add_setting( 'voynotif_email_backgroundcontent_color', array( 
                'type' => 'option', // Attention ! 
                'default' => '#f5f5f5', 
                'transport' => 'refresh', ) 
            );

            //Background color
            $wp_customize->add_setting( 'voynotif_email_background_color', array( 
                'type' => 'option', // Attention ! 
                'default' => '#fff', 
                'transport' => 'refresh', ) 
            ); 

            //Footer message
            $wp_customize->add_setting( 'voynotif_email_footer_message', array( 
                'type' => 'option', // Attention ! 
                'default' => '', 
                'transport' => 'refresh', ) 
            );  
        }


        /**
         * Chargement des controls (modificateur des settings identifiés au-dessus
         * 
         * @author Floflo
         * @since 0.9
         * 
         * @global object $wp_customize
         */
        function customizer_controls() {

            global $wp_customize; 
            
            /**
             * @since 1.2.0
             */
            $choices = array(
                'plugin' => __( 'Default template', 'notifications-center' ),
            );
            if( voynotif_has_theme_email_template() ) {
                $choices['theme'] = __( 'Current theme template', 'notifications-center' ); 
            }            
            $wp_customize->add_control(
                new WP_Customize_Control(
                    $wp_customize,
                    'voynotif_email_template_path_control',
                    array(
                        'label'          => __( 'Theme to use', 'notifications-center' ),
                        'description' => sprintf( __( 'Your Wordpress theme can now override Notifications Center Template. <a target="_blank" href="%1$s">Check documentation for more info</a>', 'notifications-center' ), 'http://www.notificationscenter.com/en/documentation/email-template-system/' ),
                        'section'        => 'voynotif_settings',
                        'settings'       => 'voynotif_email_template_path',
                        'type'           => 'radio',
                        'choices'        => $choices
                    )
                )
            );
            
            $wp_customize->add_control(
                new WP_Customize_Image_Control(
                    $wp_customize,
                    'voynotif_email_logo_control',
                    array(
                        'label'       => __( 'Upload a logo', 'notifications-center' ),
                        'description' => __( 'Choose the logo you want to appear on your notifications', 'notifications-center' ),
                        'section'     => 'voynotif_header',
                        'settings'    => 'voynotif_email_logo',
                    )
                )
            );          

            //Control for title color
            $wp_customize->add_control( 
                new WP_Customize_Color_Control( 
                    $wp_customize, 
                    'voynotif_email_title_color_control', 
                    array( 
                        'label' => __( 'Title color', 'notifications-center' ),
                        'priority' => 10, 
                        'section' => 'voynotif_body', 
                        'settings' => 'voynotif_email_title_color'
                    ) 
                ) 
            ); 

            //Control for button color
            $wp_customize->add_control( new WP_Customize_Color_Control( 
                    $wp_customize, 
                    'voynotif_email_button_color_control', 
                    array( 
                        'label' => __( 'Button color', 'notifications-center' ),
                        'priority' => 10, 
                        'section' => 'voynotif_body', 
                        'settings' => 'voynotif_email_button_color'
                    ) 
            ) ); 

            //Control for content background color
            $wp_customize->add_control( new WP_Customize_Color_Control( 
                    $wp_customize, 
                    'voynotif_email_backgroundcontent_color_control', 
                    array( 
                        'label' => __( 'Content background color', 'notifications-center' ), 
                        'priority' => 10, 
                        'section' => 'voynotif_body', 
                        'settings' => 'voynotif_email_backgroundcontent_color'
                    ) 
            ) ); 

            //Control for background color
            $wp_customize->add_control( new WP_Customize_Color_Control( 
                    $wp_customize, 
                    'voynotif_email_background_color_control', 
                    array( 
                        'label' => __( 'Background color', 'notifications-center' ), 
                        'priority' => 10, 
                        'section' => 'voynotif_settings', 
                        'settings' => 'voynotif_email_background_color'
                    ) 
            ) ); 

            //Controle for footer message
            $wp_customize->add_control( 
                new VOYNOTIF_Customize_Textarea_Control( 
                    $wp_customize, 
                    'voynotif_email_footer_message_control', 
                    array( 
                        'label'       => __( 'Footer message', 'notifications-center' ),
                        'description' => __( 'This short message will appear on the footer of your notifications', 'notifications-center' ),
                        'priority'    => 10, 
                        'section'     => 'voynotif_footer', 
                        'settings'    => 'voynotif_email_footer_message'
                    ) 
                ) 
            );         
        } 


        /**
         * On supprime toutes les sections autres que celles définies ici
         * 
         * @author Floflo
         * @since 0.9
         * 
         * @global object $wp_customize
         * @param object $wp_customize
         * @return boolean true
         */
        public function remove_sections( $wp_customize ) {      

            global $wp_customize;
            $registered_sections = $wp_customize->sections();
            // loop over and remove each section
            foreach( $registered_sections as $section ) {
               $wp_customize->remove_section( $section->id );
            } 
            return true;
        }


        /**
         * On supprime les panels d'origine (Widgets et menus)
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-07-21
         * 
         * @return boolean
         */
        public function remove_panels( $active, $panel = '' ) {       
           if( isset( $_GET[$this->_trigger] ) ) {
               if( !empty($panel) && in_array( $panel->id, array('voynotif_logo', 'mailtpl') ) ) {
                   return true;
               }
               return false;
           } 
           return true;
        } 


        /**
         * On génère la prévisualisation HTML du template sélectionne
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016/05/24
         * 
         * @todo Remplacer $current_template_id par la vraie valeur
         */
        function template_preview_html() {

            if ( get_query_var( $this->_trigger ) ) {
                $template_obj = new VOYNOTIF_email_template();
                $template_obj->set_title('Lorem ipsum dolor sit amet');
                $template_obj->set_content( apply_filters('voynotif/preview/content',
                    '<p>Hello John Doe</p><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur ut vestibulum orci, ut feugiat sapien. Aliquam nunc lectus, lobortis ac pharetra quis, bibendum non neque.</p>'
                ));
                $template_obj->set_button_info( array(
                    'url' => site_url(),
                    'label' => __('Button title', 'notifications-center'),
                ) );
                $html = $template_obj->get_html();
                echo '<head><title>Template</title></head>';
                echo $html;
                exit;           
            }

        }


    }
}
new VOYNOTIF_template_customizer();


function voynotif_custmizer_register_controls() {
    
    class VOYNOTIF_Customize_Textarea_Control extends WP_Customize_Control {
        public $type = 'textarea';

        public function render_content() {
            ?>
            <label>
            <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
            <span class="description"><?php echo esc_html( $this->description ); ?></span>
            <textarea rows="5" style="width:100%;" <?php $this->link(); ?>><?php echo esc_textarea( $this->value() ); ?></textarea>
            </label>
            <?php
        }
    }
    
}


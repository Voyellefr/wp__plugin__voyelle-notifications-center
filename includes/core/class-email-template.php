<?php
/**
 * CLASS VOYNOTIF_email_template
 * 
 * @author Floflo
 * @since 0.9
 */
if( !class_exists( 'VOYNOTIF_email_template' ) ) {
    class VOYNOTIF_email_template {

        var $type,
            $logo_url,
            $logo_width,
            $logo_height,
            $button_color,
            $background_color,
            $backgroundcontent_color,
            $title_color,
            $footer;


        /**
         *  Constructor
         * 
         * @author Floflo
         * @since 0.9
         * 
         * @param string $title Titre du mail
         * @param string $content Contenu du mail
         * @param string $context Contexte dans lequel le template est sollicité
         **/
        function __construct() {

            //Type
            if( get_option( VOYNOTIF_FIELD_PREFIXE . 'template_path' == 'theme' )  ) {
                $this->type = 'theme';
            } else {
                $this->type = 'plugin';
            }
            
            $args = array();
            $variables = array(
                'logo', 
                'button_color', 
                'background_color', 
                'backgroundcontent_color', 
                'title_color', 
                'footer_message', 
                'gf_table_bg', 
                'gf_table_color'
            );

            foreach( $variables as $variable ) {
                $option = get_option( VOYNOTIF_FIELD_PREFIXE . 'email_' . $variable);
                if( !empty( $option ) ) {
                    $args[$variable] = $option;
                }
            }

            $args = wp_parse_args( $args, array(
                'logo' => admin_url() . '/images/w-logo-blue.png',
                'button_color' => '#0073aa',
                'background_color' => '#fff',
                'backgroundcontent_color' => '#f5f5f5',
                'title_color' => '#0073aa',
                'footer_message' => __( 'Proudly powered by Wordpress', 'notifications-center' ),
                'gf_table_bg' => '#0073aa',
                'gf_table_color' => '#fff',
            ) );
            
            
            $this->logo_url = $args['logo'];
            $this->button_color = $args['button_color'];
            $this->background_color = $args['background_color'];
            $this->backgroundcontent_color = $args['backgroundcontent_color'];
            $this->title_color = $args['title_color'];
            $this->footer = $args['footer_message'];
            $this->gf_table_bg = $args['gf_table_bg'];
            $this->gf_table_color = $args['gf_table_color'];
            
            $this->notification_content = (object) array(
                'title'   => '',
                'content' => '',
                'button_text' => '',
                'button_url'  => '',
            );

        } 
        
        function set_title( $title ) {
            $this->notification_content->title = $title;
            add_filter('voynotif/template/title', array( $this, 'set_title_callback' ) );
        } 
        
        function set_title_callback( $title ) {
            return $this->notification_content->title;
        }
        
        function set_content( $content ) {
            $this->notification_content->content = $content;
            add_filter('voynotif/template/content', array( $this, 'set_content_callback' ) );
        } 
        
        function set_content_callback( $content ) {
          return $this->notification_content->content;
        }
        
        function set_button_info($button) {
            if( !is_array( $button ) ) {
                return;
            }
            $this->notification_content->button_text = $button['label'];
            $this->notification_content->button_url = $button['url'];
            add_filter('voynotif/template/button_text', array( $this, 'set_button_text_callback' ) );
            add_filter('voynotif/template/button_url', array( $this, 'set_button_url_callback' ) );
        }
        
        function set_button_text_callback( $button_text ) {
          return $this->notification_content->button_text;
        }
        
        function set_button_url_callback( $button_url ) {
          return $this->notification_content->button_url;
        }
        
        function get_html() {
            
            global $voynotif_template;
            $voynotif_template = $this;
            
            ob_start();
            include( voynotif_email_template() ); //Fix since 1.3.0
            return ob_get_clean();
                    
        }

    }
}

?>
<?php
/**
 * CLASS voy_notification
 * 
 * @author Floflo
 * @since 0.9
 */
if( !class_exists( 'VOYNOTIF_notification' ) ) {
    class VOYNOTIF_notification {

        /**
         * Content and fields variables
         */
        var $id,
            $template_name,
            $title,
            $content,
            $recipient_type,
            $recipients,
            $type,
            $button_info,
            $context_info;

        /**
         * Object variables
         */
        var $template;

        /**
         * Constructor
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-06-02
         * 
         * @param integer $notification_id ID de la notification
         */  
        function __construct($notification_id) {

            //Construction des variables
            $this->id = $notification_id;
            $this->subject = get_post_meta( $this->id, 'voynotif_object', true );
            $this->title = $this->subject;
            
            /**
             * Add difference between Title & subject
             * Since 1.3.0
             */
            if( voynotif_get_option( 'activate_email_title' ) && get_post_meta( $this->id, 'voynotif_title', true ) ) {
                $this->title = get_post_meta( $this->id, 'voynotif_title', true );
            }  
            
            $this->content = $this->_get_the_content();
            $this->type = get_post_meta( $this->id, 'voynotif_type', true );
            $this->tags = $this->_get_notification_type_tags();
            $this->recipient_type = get_post_meta( $this->id, 'voynotif_recipient_type', true );
            $this->recipients = array();
            $this->template = new VOYNOTIF_email_template();

        }

        /**
         * Wrapper of get_post_meta function
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-06-17
         * 
         * @param string $field_id
         * @return boolean
         */
        function get_field( $field_id ) {

            //Return if empty
            if( empty( $field_id ) ) {
                return false;
            }

            $value = get_post_meta($this->id, VOYNOTIF_FIELD_PREFIXE . $field_id, true);
            return $value;

        }

        /**
         * Get the content of current notification
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-06-02
         * 
         * @return type content
         */
        private function _get_the_content() {
            $my_postid = $this->id;//This is page id or post id
            $content_post = get_post($my_postid);
            $content = $content_post->post_content;
            $content = apply_filters('the_content', $content);
            $content = str_replace(']]>', ']]&gt;', $content);
            return $content;
        }
        
        /**
         * Get the notification_type tags to perform maks engine before sending. 
         * Resolves warning PHP message from 1.1.0 where mask_engine 3rd param were missing 
         * 
         * @author Floflo
         * @since 1.2.0
         * @return boolean
         */
        function _get_notification_type_tags() {
            $types = voynotif_get_notifications_types();
            
            //Prevent PHP warning message. Since 1.3.0
            if( isset( $types[$this->type] ) && !empty( $types[$this->type] ) ) {
                $type = $types[$this->type];
            }
            
            if( !empty( $type ) ) {
                return $type['tags'];
            }
            return false;
        }        

        
        /**
         * 
         * @param type $title
         */
        function set_title( $title ) {
            $this->title = $title;
        }
        
        
        /**
         * 
         * @deprecated since version 1.2.0
         * @param type $title
         */
        function set_object( $title ) {
            $this->subject = $title;
        }
        
        
        /**
         * 
         * @param type $content
         */
        function set_content( $content ) {
            $this->content = $content;
        }


        /**
         * Wrapper of set_button_info template method
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-06-05
         * 
         * @param array $cta
         * @return bool True if cta has been set, else false
         */
        function set_button_info( $cta ) {
            
            //Check if $cta is an array
            if( ! is_array($cta) ) {
                return false;
            }

            //Check if array contains all needed data
            if( ! isset($cta['url']) OR $cta['url'] == '' ) {
                $cta['url'] = '#';
            }   
            if( ! isset($cta['label']) OR $cta['label'] == '' ) {
                $cta['label'] = __('Click here', 'notifications-center');
            }
            
            //set cta
            $this->button_info = $cta;
            return true;
            
        }


        /**
         * Save context info, to perform maks updating
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-06-24
         * 
         * @param array $context_info
         * @return bool True if cta has been set, else false
         */
        function set_context_info( $context_info ) {

            //Check if is array
            if( ! is_array( $context_info ) ) {
                return false;
            } 

            //save context INFO_ALL
            $this->context_info = $context_info;
            return true;
        }


        /**
         * Spécifier le destinataire de l'email
         * 
         * @author Floflo
         * @since 0.9
         * @param string $email Adresse e-mail du destinataire
         */   
        function add_recipient($email) {
            if( is_array( $this->recipients ) ) {
                $this->recipients[] = $email; 
            }
        }


        /**
         * Renvoi des Headers de l'email (Format HTML UTF-8 + Expédieur, selon les paramètres définis dans la notification
         * 
         * @author Floflo
         * @since 0.9
         * @update 2016-12-16
         * 
         * @return array  Headers de l'email
         */
        function get_headers() {

            //Get sender info from current notification            
            $specify_sender = get_post_meta( $this->id, 'voynotif_specify_sender', true );
            
            /**
             * Remove filter that changes sender name and email with global settings
             * @since 1.1.0
             */        
            if( $specify_sender ) {
                global $notifications_center;
                remove_action( 'wp_mail_from_name', array( $notifications_center, 'extend_sender_name' ) );
                remove_action( 'wp_mail_from', array( $notifications_center, 'extend_sender_email' ) );                
            }
            
            $specific_sender_email = get_post_meta( $this->id, 'voynotif_sender_email', true );
            $specific_sender_name = get_post_meta( $this->id, 'voynotif_sender_name', true );

            //Nom de l'expéditeur (propre à la notification ou général)
            if( $specify_sender == true AND ! empty( $specific_sender_name ) ) {                              
                $sender_name = $specific_sender_name;               
            } else {
              $sender_name = get_option( VOYNOTIF_FIELD_PREFIXE . 'sender_name' );  
            }

            //Email de l'expéditeur (propre à la notification ou général)
            if( $specify_sender == true AND ! empty( $specific_sender_email )  ) {
                $sender_email = $specific_sender_email;
                
            } else {
              $sender_email = get_option( VOYNOTIF_FIELD_PREFIXE . 'sender_email' );  
            }

            //Construction des headers et renvoi
            $headers = array(
              'Content-Type: text/html; charset=UTF-8',
              'From: '.$sender_name.' <'.$sender_email.'>' . "\r\n",
            );

            //Renvoi
            return $headers; 

        }


        /**
         * Renvoi un tableau des destinataires de la notification
         * 
         * @author Floflo
         * @since 0.9
         * @return array Tableau des destinataires format ( [0] => 'email@emil.fr, [1] => 'emaail2@email2.fr' )
         */
        function _get_recipients() {

            //Set up recipients array
            $destinataires = $this->recipients;
            if( !is_array($destinataires) ) {
                $destinataires = array();
            }
            

            //Get values from current notification
            $recipient_emails = get_post_meta( $this->id, 'voynotif_recipient_emails', true );
            $recipient_roles = get_post_meta( $this->id, 'voynotif_recipient_roles', true );
            $recipient_users = get_post_meta( $this->id, 'voynotif_recipient_users', true );

            //Si ce sont des adresses mails spécifiques
            if( $this->recipient_type == 'emails' AND ! empty( $recipient_emails ) ) {
                $destinataires2 = explode( ',', $recipient_emails );
                if( $destinataires2 ) {
                    foreach( $destinataires2 as $destinataire2 ) {
                        $destinataires[] = trim($destinataire2);
                    }
                }
            } 

            //Si c'est une sélection par roles
            elseif( $this->recipient_type == 'roles' AND  is_array( $recipient_roles ) ) {   
                foreach( $recipient_roles as $role ) {
                    $users = get_users( array('role' => $role) );
                    foreach ( $users as $user ) {
                        $destinataires[] = $user->user_email;    
                    }   
                }    
            }

            //Si les destinataires sont choisis dans les utilisateurs du site
            elseif( $this->recipient_type == 'users' AND  is_array( $recipient_users )  ) {
                foreach( $recipient_users as $user ) {
                    $user_obj = get_user_by( 'id', $user );
                    $destinataires[] = $user_obj->user_email;
                }
            }  
            
            /**
             * FILTER voynotif/notification/recipients
             * 
             * @author Floflo
             * @since 1.3.0
             * @update 2017-08-28
             * 
             * @param array $destinataires Araay of recipients
             * @param object $notification Current notifcation Object
             */
            return apply_filters( 'voynotif/notification/recipients', $destinataires, $this);
        }
        
        
        /**
         * 
         * @global type $voynotif_notification
         * @global type $voynotif_template
         * @return type
         */
        function get_html() {
            
            //Prepare for HTML template
            global $voynotif_notification;
            $voynotif_notification = $this;
            
            global $voynotif_template;
            $voynotif_template = $this->template;
            
            ob_start();
            include( voynotif_email_template( $this->id ) );
            return ob_get_clean();            
        }


        /**
         * Envoi de l'email à tous les destnataires concernés
         * 
         * @author Floflo
         * @since 0.9
         * @param type $html Contenu HTML de l'email
         */
        function send_notification() {

            //Update content masks before sending
            $this->mask_engine = new voynotif_masks( $this->type, $this->context_info, $this->tags );
            $this->set_title( $this->mask_engine->update_masks( $this->title ) ); 
            $this->set_content( $this->mask_engine->update_masks( $this->content ) ); 

            /**
             * FILTER voynotif/notification/sending/auth
             * Add ability to block sending email
             * 
             * @author Floflo
             * @since 1.0.2
             * @update 2016-11-27
             * 
             * @param boolean $status Defaut to true. If false, email won't be sent
             * @param object $notification Current notifcation Object
             */
            $auth = apply_filters( 'voynotif/notification/sending/auth', true, $this );
            if( $auth == false ) return false;

            //Get all info
            $object = $this->mask_engine->update_masks($this->subject);
            $headers = $this->get_headers();
            $html = $this->get_html();           
            
            //send
            foreach( $this->_get_recipients() as $recipient ) {           

                //Add Logs
                if( get_option( VOYNOTIF_FIELD_PREFIXE . 'activate_logs' ) == true ) {

                    $log = VOYNOTIF_logs::add_log( array(
                        'notification_id'   => $this->id,
                        'type'              => $this->type,
                        'recipient'         => $recipient,
                        'subject'           => $object,
                        'title'             => $this->title,
                        'status'            => null,
                        'context'           => $this->context_info,
                    ) );  
                    
                    global $voynotif_log;
                    $voynotif_log = $log;
                }
                
                //Send
                $result = wp_mail($recipient, $object, $this->get_html(), $headers);

                //Update log status
                if( get_option( VOYNOTIF_FIELD_PREFIXE . 'activate_logs' ) == true ) {
                    VOYNOTIF_logs::update_log(array(
                        'id'        => $log->id,
                        'status'    => $result,
                    ));
                }
                
            }
            
            return true;

        }
    }
}


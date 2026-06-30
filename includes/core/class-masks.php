<?php   
if( !class_exists( 'VOYNOTIF_masks' ) ) {
    class VOYNOTIF_masks {


        /**
         * 
         * @param type $context
         * @param type $context_info
         */
        function __construct( $context, $context_info, $tags = null ) {

            //Build info
            $this->context = $context;
            $this->context_info = $context_info;
            
            if( !empty( $tags) ) {
                $this->tags = $tags;
            }
          
            //Add filter to register basic masks
            add_filter('voynotif/masks', array( $this, 'basic_masks' ), 5, 3 );

            //Get masks regarding to notification
            $masks = apply_filters('voynotif/masks', array(), $this->context, $this->context_info );
            $masks = apply_filters('voynotif/masks/context='.$this->context, $masks );
            $this->masks = $masks;
            $this->tags = apply_filters('voynotif/masks/tags', array(
                'basic' => __( 'Basic', 'notifications-center' ),
                'comment' => __( 'Comment   ', 'notifications-center' ),
                'content' => __( 'Post', 'notifications-center' ),
                'system' => __( 'System', 'notifications-center' ),
                'user' => __( 'User/author', 'notifications-center' ),
            ));
            
        }


        /**
         * 
         * @return type
         */
        function get_registered_masks() {
            $masks = $this->masks;
            return $masks;
        }


        /**
         * 
         * @param type $tag
         * @return type
         */
        function get_masks_by_tag( $tag ) {

            $tag_masks = array();

            foreach( $this->masks as $mask_name => $mask_info ) {
                if( $tag == $mask_info['tag'] ) {
                    $tag_masks[$mask_name] = $mask_info;    
                }
            }

            return $tag_masks;

        }


        /**
         * 
         * @param type $masks
         * @return boolean
         */
        function set_masks($masks) {
            if( ! is_array( $masks ) ) {
                return false;
            }
            $this->masks = $masks;
            return true;
        }



        /**
         * 
         * @param type $masks
         * @param type $context
         * @param type $context_info
         * @return string
         */
        function basic_masks($masks, $context, $context_info) {

            //----------------------------------------------------------------------
            // Site masks
            //----------------------------------------------------------------------
            $masks['site_name'] = array(
                'title' => __( 'Site name', 'notifications-center'),
                'description' => '',
                'tag' => 'basic',
                'dummy_data' => 'Demo site',
            );

            $masks['site_url'] = array(
                'title' => __( 'Site URL', 'notifications-center' ),
                'description' => '',
                'tag' => 'basic',
                'dummy_data' => 'http://www.demosite.com',
            );

            $masks['site_admin_email'] = array(
                'title' => __( 'Admin email', 'notifications-center' ),
                'description' => '',
                'tag' => 'basic',
                'dummy_data' => 'john.doe@mail.com',
            );
            $masks['current_date'] = array(
                'title' => __( 'Current date', 'notifications-center' ),
                'description' => '',
                'tag' => 'basic',
            );
            $masks['current_time'] = array(
                'title' => __( 'Current time', 'notifications-center' ),
                'description' => '',
                'tag' => 'basic',
            );

            //----------------------------------------------------------------------
            // Post masks
            //----------------------------------------------------------------------
            if( in_array( 'content', $this->tags ) ) {

                $masks['post_title'] = array(
                    'title' => __( 'Post title', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'content',
                    'dummy_data' => 'Exemple article',
                ); 

                $masks['post_author_name'] = array(
                    'title' => __( 'Post author', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'content',
                    'dummy_data' => 'John Doe',
                ); 

                $masks['post_author_email'] = array(
                    'title' => __( 'Post author email', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'content',
                    'dummy_data' => 'John Doe',
                ); 

                $masks['post_content'] = array(
                    'title' => __( 'Post content', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'content',
                    'dummy_data' => 'John',
                ); 

                $masks['post_excerpt'] = array(
                    'title' => __( 'Post excerpt', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'content',
                    'dummy_data' => 'John',
                ); 

                $masks['post_date'] = array(
                    'title' => __( 'Post date', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'content',
                    'dummy_data' => 'John',
                ); 
                
                $masks['post_link'] = array(
                    'title' => __( 'Post link', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'content',
                    'dummy_data' => 'John',
                ); 
                $masks['post_edit_link'] = array(
                    'title' => __( 'Post edit link', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'content',
                    'dummy_data' => 'John',
                ); 

            }

            //----------------------------------------------------------------------
            // Comment masks
            //----------------------------------------------------------------------
            if( in_array( 'comment', $this->tags ) ) {

                $masks['comment_author'] = array(
                    'title' => __( 'Comment author', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'comment',
                    'dummy_data' => 'Exemple article',
                ); 

                $masks['comment_author_email'] = array(
                    'title' => __( 'Comment author email', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'comment',
                    'dummy_data' => 'John Doe',
                ); 
                $masks['comment_author_url'] = array(
                    'title' => __( 'Comment author URL', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'comment',
                    'dummy_data' => 'John Doe',
                ); 
                $masks['comment_author_ip'] = array(
                    'title' => __( 'Comment author IP', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'comment',
                ); 
                $masks['comment_date'] = array(
                    'title' => __( 'Comment date', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'comment',
                    'dummy_data' => 'John Doe',
                );  
                $masks['comment_content'] = array(
                    'title' => __( 'Comment content', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'comment',
                    'dummy_data' => 'John Doe',
                );  

            }        

            //----------------------------------------------------------------------
            // User masks
            //----------------------------------------------------------------------
            if( in_array( 'user', $this->tags ) ) {

                $masks['user_firstname'] = array(
                    'title' => __( 'User firstname', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'user',
                    'dummy_data' => 'Exemple article',
                ); 

                $masks['user_lastname'] = array(
                    'title' => __( 'User lastname', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'user',
                    'dummy_data' => 'John Doe',
                ); 

                $masks['user_displayname'] = array(
                    'title' => __( 'User display name', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'user',
                    'dummy_data' => 'John',
                ); 

                $masks['user_login'] = array(
                    'title' => __( 'User login', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'user',
                    'dummy_data' => 'John',
                ); 

                $masks['user_email'] = array(
                    'title' => __( 'User email', 'notifications-center' ),
                    'description' => '',
                    'tag' => 'user',
                    'dummy_data' => 'John',
                ); 

            }        

            //----------------------------------------------------------------------
            // Return
            //----------------------------------------------------------------------        
            return $masks;

        }


        /**
         * 
         * @param type $content
         * @return type
         */
        function update_masks($content) {

            //----------------------------------------------------------------------
            // Site masks
            //----------------------------------------------------------------------     
            $content = str_replace( '{site_name}', get_option('blogname'), $content ); 
            $content = str_replace( '{site_url}', site_url(), $content ); 
            $content = str_replace( '{site_admin_email}', get_option('admin_email'), $content );
            
            $timestamp_time = current_time( 'timestamp' );
            $current_date = date( get_option('date_format'), $timestamp_time );
            $current_time = date(  get_option('time_format'), $timestamp_time );
            
            $content = str_replace( '{current_date}', $current_date, $content );
            $content = str_replace( '{current_time}', $current_time, $content );

            //----------------------------------------------------------------------
            // Post masks
            //----------------------------------------------------------------------     
            if( ! empty( $this->context_info['post_id'] ) ) {

                $post = get_post( $this->context_info['post_id'] );

                if( ! empty( $post ) ) {
                    $content = str_replace( '{post_title}', $post->post_title, $content ); 
                    $content = str_replace( '{post_content}', $post->post_content, $content ); 
                    $content = str_replace( '{post_excerpt}', $post->post_excerpt, $content ); 
                    $content = str_replace( '{post_date}', get_the_date( '', $post->ID ), $content );
                    $content = str_replace( '{post_link}', get_permalink( $post->ID ), $content ); 
                    $content = str_replace( '{post_edit_link}', admin_url( 'post.php?post=' . $post->ID ) . '&action=edit', $content ); 
                    $author = get_user_by( 'id', $post->post_author );
                    if( ! empty( $author ) ) {
                        $content = str_replace( '{post_author_name}', $author->display_name, $content ); 
                        $content = str_replace( '{post_author_email}', $author->user_email, $content );     
                    }

                }           

            }

            //----------------------------------------------------------------------
            // Comment masks
            //----------------------------------------------------------------------     
            if( ! empty( $this->context_info['comment_id'] ) ) {

                $comment = get_comment( $this->context_info['comment_id'] );

                if( ! empty( $comment ) ) {
                    $content = str_replace( '{comment_author}', $comment->comment_author, $content ); 
                    $content = str_replace( '{comment_author_email}', $comment->comment_author_email, $content ); 
                    $content = str_replace( '{comment_author_url}', $comment->comment_author_url, $content ); 
                    $content = str_replace( '{comment_author_ip}', $comment->comment_author_IP, $content ); 
                    $content = str_replace( '{comment_date}', get_comment_date( '', $comment->comment_ID ), $content ); 
                    $content = str_replace( '{comment_content}', $comment->comment_content, $content ); 
                }           

            }       

            //----------------------------------------------------------------------
            // User masks
            //----------------------------------------------------------------------     
            if( ! empty( $this->context_info['user_id'] ) ) {

                $user = get_user_by( 'id', $this->context_info['user_id'] );

                if( ! empty( $user ) ) {
                    $content = str_replace( '{user_firstname}', $user->first_name, $content ); 
                    $content = str_replace( '{user_lastname}', $user->last_name, $content ); 
                    $content = str_replace( '{user_displayname}', $user->display_name, $content ); 
                    $content = str_replace( '{user_login}', $user->user_login, $content ); 
                    $content = str_replace( '{user_email}', $user->user_email, $content ); 
                }           

            }        

            //----------------------------------------------------------------------
            // Custom Masks
            //----------------------------------------------------------------------     
            foreach( $this->masks as $mask_name => $mask_info ) {
                if( array_key_exists( $mask_name, $this->context_info ) ) {
                    //$value = apply_filters( 'voynotif/mask/name='.$mask_name, '', $this->context, $this->context_info ); 
                    $content = str_replace( '{'.$mask_name.'}', $this->context_info[$mask_name], $content );
                } 
            } 

            //----------------------------------------------------------------------
            // Return
            //----------------------------------------------------------------------          
            return apply_filters('voynotif/notification/content', $content, $this->context_info);

        }

    }
}
?>
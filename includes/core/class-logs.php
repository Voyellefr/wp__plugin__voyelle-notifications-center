<?php

class VOYNOTIF_logs {
    
    const TABLE_NAME_BASE = 'voynotif_sent_logs';
    const POSTS_PER_PAGE = 20;
    
    
    /**
     * 
     */
    function __construct() {
        add_action ('notifications_center_logs_deletion', array( $this, 'delete_old_logs') );
        add_action( 'template_redirect', array('VOYNOTIF_logs', 'update_notification_status') );
        add_action( 'voynotif/template/footer', array('VOYNOTIF_logs', 'add_pixel') );
        add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'add_gpdr_exporter' ), 1 );
        add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'add_gpdr_eraser' ), 1 );
    }
    
    /**
     * -------------------------------------------------------------------------
     * GPDR FUNCTIONS
     * -------------------------------------------------------------------------
     */
    
    /**
     * 
     * @param type $exporters
     * @return array
     */
    public function add_gpdr_exporter( $exporters ) {
	$exporters['notifications-center-logs'] = array(
		'exporter_friendly_name' => __( 'Email tracking (Notifications Center)', 'notifications-center' ),
		'callback'               => array( $this, 'export_user_logs' ),
	);        
        return $exporters;
    }
    
    
    /**
     * 
     * @param type $erasers
     * @return array
     */
    public function add_gpdr_eraser( $erasers ) {
	$erasers['notifications-center-logs'] = array(
		'eraser_friendly_name' => __( 'Email tracking (Notifications Center)', 'notifications-center' ),
		'callback'               => array( $this, 'erase_user_logs' ),
	);        
        return $erasers;
    }
    
    
    /**
     * 
     * @global type $wpdb
     * @param type $email_address
     * @return type
     */
    public function export_user_logs( $email_address ) {
	$email_address = trim( $email_address );
	$data_to_export = array();
        $logs_to_export = array();
        
        global $wpdb;
        $table = self::get_table_name();
        $logs = $wpdb->get_results( 
            "
            SELECT id, notification_id, type, recipient, subject, title, status, context, date, token, opens  
            FROM $table
                WHERE recipient LIKE '{$email_address}'
                ORDER BY date ASC
                LIMIT 99999999 OFFSET 0
            "
        );
        
        if( !empty( $logs ) ) {
            foreach( $logs as $log ) {
                $log_date = new DateTime($log->date);
                $log_context = unserialize($log->context);
                ob_start(); ?>
                    <ul>
                        <li><?php _e('Type: ', 'notifications-center'); ?><?php echo voynotif_get_notification_type_title($log->type) ?></li>
                        <li><?php _e('Date: ', 'notifications-center'); ?><?php echo $log_date->format('d/m/Y à G:i'); ?></li>
                        <li><?php _e('Subject: ', 'notifications-center'); ?><?php echo $log->subject; ?></li>
                        <li><?php _e('Status: ', 'notifications-center'); ?><?php echo VOYNOTIF_logs::get_status_title( $log->status ); ?></li>
                    </ul>
                <?php $value = ob_get_clean();
                $logs_to_export[] = array(
                    'name'  => $log_date->format('d/m/Y à G:i'),
                    'value' => __('Subject: ', 'notifications-center').$log->subject.' | '.VOYNOTIF_logs::get_status_title( $log->status ),
                );                
            }
        }

	$data_to_export[] = array(
            'group_id'    => 'notifications-center-logs',
            'group_label' => __( 'Email tracking (Notifications Center)', 'notifications-center' ),
            'item_id'     => "notifications-center-logs",
            'data'        => $logs_to_export,
	);
        
        error_log( print_r($data_to_export, true) );

	return array(
            'data' => $data_to_export,
            'done' => true,
	);        
    }
    
    
    /**
     * 
     * @global type $wpdb
     * @param type $email_address
     * @param type $page
     * @return type
     */
    public function erase_user_logs( $email_address, $page = 1 ) {
	if ( empty( $email_address ) ) {
            return array(
                'items_removed'  => false,
                'items_retained' => false,
                'messages'       => array(),
                'done'           => true,
            );
	}

        global $wpdb;
        $table = self::get_table_name();
        $logs = $wpdb->get_results( 
            "
            SELECT id, notification_id, type, recipient, subject, title, status, context, date, token, opens  
            FROM $table
                WHERE recipient LIKE '{$email_address}'
                ORDER BY date ASC
                LIMIT 99999999 OFFSET 0
            "
        );
                
        if( empty( $logs ) ) {
            return array(
                'items_removed'  => false,
                'items_retained' => false,
                'messages'       => array(),
                'done'           => true,
            );            
        }
        
        $items_removed = 0;
        foreach( $logs as $log ) {
            self::delete_log($log->id);
            $items_removed++;
        }

	return array(
		'items_removed'  => $items_removed,
		'items_retained' => 0,
		'messages'       => array( __('Email tracking succesfully deleted') ),
		'done'           => $items_removed,
	);        
    }
    
    /**
     * -------------------------------------------------------------------------
     * STATUS FUNCTIONS
     * -------------------------------------------------------------------------
     */
    
    
    /**
     * 
     * @global type $voynotif_log
     * @return type
     */
    public static function add_pixel() {
        global $voynotif_log;
        if( !isset($voynotif_log) || !isset($voynotif_log->id) ) return;
        echo '<img src="'.get_site_url().'?voynotif_log_id='.$voynotif_log->id.'&voynotif_log_token='.$voynotif_log->token.'" />';
    }
    
    
    /**
     * 
     * @return type
     */
    public static function update_notification_status() {
        
        //Check if $_get needed params are here
        if( !isset( $_GET['voynotif_log_id'] ) || !isset( $_GET['voynotif_log_token'] ) ) return;
        
        //Get basic info
        $log_id = absint($_GET['voynotif_log_id']);
        $log = VOYNOTIF_logs::get_log($log_id);  
        
        //Check if token is correct
        if( $log->token != $_GET['voynotif_log_token'] ) {
            return;
        }
        
        //Updade log status to opened & add new opened date
        $args['id'] = $log_id;
        $args['status'] = 2;
        $log->opens[] = date('Y-m-d H:i:s');
        $args['opens'] = serialize($log->opens);        
        VOYNOTIF_logs::update_log($args);
        
        //Return 1px x 1px png image
        $im = imagecreatefrompng(VOYNOTIF_URL . "/assets/img/pixel.png");
        ob_clean();
        header('Content-Type: image/png');
        header("Content-Disposition: inline; filename=\"pixel.png\"");
        imagepng($im);
        imagedestroy($im);
        die();      
    }
    
    public static function load_png($imgname) {
        /* Tente d'ouvrir l'image */
        $im = @imagecreatefrompng($imgname);

        /* Traitement en cas d'échec */
        if (!$im) {
            /* Création d'une image vide */
            $im = imagecreatetruecolor(150, 30);
            $bgc = imagecolorallocate($im, 255, 255, 255);
            $tc = imagecolorallocate($im, 0, 0, 0);

            imagefilledrectangle($im, 0, 0, 150, 30, $bgc);

            /* On y affiche un message d'erreur */
            imagestring($im, 1, 5, 5, 'Erreur de chargement ' . $imgname, $tc);
        }

        return $im;
    }
    
    /**
     * -------------------------------------------------------------------------
     * DB HELPERS
     * -------------------------------------------------------------------------
     */

    /**
     * 
     * @global type $wpdb
     * @return type
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME_BASE;
    }
    
    
    /**
     * 
     * @param type $type
     * @param type $recipient
     * @param type $object
     * @param type $result
     * @param type $args
     * @return boolean
     */
    public static function add_log( $args = array() ) {

        $defaults = array(
            'date'              => date('Y-m-d H:i:s'),
            'notification_id'   => '',
            'type'              => __('Unknown', 'notifications-center'),
            'recipient'         => '',
            'subject'           => '',
            'title'             => '',
            'status'            => '',
            'context'           => array(),   
        );
        
        $args = wp_parse_args($args, $defaults);
        
        if( empty( $args['recipient'] ) ) {
            return false;
        }
        
        global $wpdb;
        $result = $wpdb->insert( 
            self::get_table_name(), 
            array( 
                'id'                => '',
                'notification_id'   => $args['notification_id'],
                'date'              => $args['date'], 
                'type'              => $args['type'],
                'recipient'         => $args['recipient'],
                'subject'           => $args['subject'],
                'title'             => $args['title'],
                'status'            => $args['status'],
                'context'           => serialize($args['context']), 
                'token'             => wp_generate_password(40, false),
                'opens'             => serialize(array()),
            )            
        );
        
        if( $result === 1 ) {
            return VOYNOTIF_logs::get_log($wpdb->insert_id);
        }
        
        return false;
    }
    
    /**
     * 
     * @param array $args
     * @return int
     */
    public static function get_logs_count( $args ) {
        
        unset($args['paged']);
        $args['per_page'] = -1;  
        $logs = VOYNOTIF_logs::get_logs( $args );
        
        if( empty( $logs ) ) {
            return 0;
        }
        
        return count($logs);
    }
    
    /**
     * 
     * @global type $wpdb
     * @param type $args
     * @return type
     */
    public static function get_logs( $args = array() ) {
        
        $defaults = array(
            'orderby'       => 'date',
            'order'         => 'DESC',
            'date_begin'    => '',
            'date_end'      => '',
            'paged'         => 1,
            'per_page'      => self::POSTS_PER_PAGE,
            's'             => '',
        );
        $args = wp_parse_args($args, $defaults);

        $per_page = $args['per_page'];
        if( $per_page === -1 ) $per_page = 1000000000;
        $offset = 0;
        if( $args['paged'] > 1 ) {
            $offset = ($args['paged'] - 1) * $per_page; 
        }
        
        $where = '';
        if( !empty( $args['s'] ) ) {
            $where = "WHERE recipient like '%{$args['s']}%'";
        }
        
        global $wpdb;
        $table = self::get_table_name();
        $fivesdrafts = $wpdb->get_results( 
            "
            SELECT id, notification_id, type, recipient, subject, title, status, context, date, token, opens  
            FROM $table
                $where
                ORDER BY ".$args['orderby']." ".$args['order']."
                LIMIT $per_page OFFSET $offset
            "
        );
        return $fivesdrafts; 
        
    }
    
    
    /**
     * 
     */
    public static function update_log( $args ) {
        
        if( !is_array($args) || empty($args) || !isset( $args['id'] ) || empty($args['id']) ) {
            return false;
        }
        
        $log_id = $args['id'];
        unset( $args['id'] );
        
        global $wpdb;
        $result = $wpdb->update( 
                self::get_table_name(), 
                $args, 
                array( 'id' => $log_id ), 
                null,
                array( '%d' ) 
        ); 
        
    }
    
    public static function get_log( $log_id ) {
        
        if( empty( $log_id ) ) {
            return false;
        }
        global $wpdb;
        $table = self::get_table_name();
        $logs = $wpdb->get_results( 
            "
            SELECT id, notification_id, type, recipient, subject, title, status, context, date, token, opens  
            FROM $table
                WHERE id = '".$log_id."'
            "
        );
        if( !empty($logs) ) {
            $log = $logs[0];
            $log->context = unserialize($log->context);
            $log->opens = unserialize($log->opens);
            return $log;
        }
        return false;        
    }
    
    
    public static function delete_log( $log_id ) {
        if( empty( $log_id ) ) {
            return false;
        }
        global $wpdb; 
        $table = self::get_table_name();
        $wpdb->delete( $table, array( 'id' => $log_id ) );
        return true;
    } 
    
    
    /**
     * 
     * @param type $status_id
     * @return type
     */
    public static function get_status_title( $status_id, $log ) {
        
        $statuses = apply_filters( 'voynotif/logs/status', array(
            0 => __('Not sent', 'notifications-center'),
            1 => __('Sent', 'notifications-center'),
            2 => __('Opened', 'notifications-center'),
        ) );
        
        if( $status_id == 2 ) {
            $opens = maybe_unserialize( $log->opens );
            if( !empty( $opens ) ) {
                $date = new DateTime($opens[0]);
                return $statuses[$status_id].'&nbsp;<small>'.sprintf( __('on %1$s at %2$s', 'notifications-center'), $date->format( get_option('date_format') ), $date->format( get_option('time_format') ) ).'</small>'; 
            }
        }
        
        if( array_key_exists( $status_id, $statuses ) ) {
            return $statuses[$status_id];
        }
        
        return __('Unknown', 'notifications-center');
        
    }
    
    
    /**
     * 
     * @param type $param
     * @return type
     */
    public static function get_order_url( $param ) {
        $orderby = ( isset( $_GET['orderby'] ) && !empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : false;
        $order = ( isset( $_GET['order'] ) && $_GET['order'] == 'ASC' ) ? 'DESC' : 'ASC';       
        $url = add_query_arg( 'orderby', $param );
        $url = add_query_arg( 'order', $order, $url ); 
        $url = add_query_arg( 'paged', 1, $url ); 
        
        return $url;
    }
    
    public static function get_context_html( $context, $type ) {
        
        if( empty( $context ) ) return false;
        
        $specific_return = apply_filters('voynotif/logs/context/type='.$type, false, $context);
        
        if( $specific_return ) {
            return $specific_return;
        }
        
        $user_id = ( array_key_exists('user_id', $context) ) ? array_key_exists('user_id', $context) : false;
        $post_id = ( array_key_exists('post_id', $context) ) ? array_key_exists('post_id', $context) : false;
        $comment_id = ( array_key_exists('comment_id', $context) ) ? array_key_exists('comment_id', $context) : false;
        
        if( $user_id && !$post_id && !$comment_id ) {
            $user_html = '<a href="'.get_edit_user_link($context['user_id']).'">'.get_user_by('id',$context['user_id'])->user_nicename.'</a>';
            return sprintf( __('About user %s', 'notifications-center'), $user_html );            
        }
        
        elseif( !$user_id && $post_id && $comment_id ) {
            $post_html = '<a href="'.get_permalink($context['post_id']).'">'.get_the_title( $context['post_id'] ).'</a>';
            $comment_html = '<a href="'.get_comment_link($context['comment_id']).'">#'.$context['comment_id'].'</a>';
            return sprintf( __('About comment %s on %s', 'notifications-center'), $comment_html, $post_html );                        
        }
        
        elseif( !$user_id && $post_id && !$comment_id ) {
            $post_html = '<a href="'.get_permalink($context['post_id']).'">'.get_the_title( $context['post_id'] ).'</a>';
            return sprintf( __('About content %s', 'notifications-center'), $post_html );                        
        }
        
        return false;
        
    }
    
    public function delete_old_logs() {
        
        $days = get_option( VOYNOTIF_FIELD_PREFIXE . 'logs_duration' );
        
        if( empty( $days ) ) {
            return false;
        }
        
        $date = new \DateTime();
        $date->modify('-'.$days.' days');
        
        global $wpdb;
        $table = self::get_table_name();
        $wpdb->query(
            $wpdb->prepare(
                "
                DELETE FROM $table
                 WHERE date < %s
                ", $date->format('Y-m-d H:i:s')
            )
        );
    }
    
}

new VOYNOTIF_logs();


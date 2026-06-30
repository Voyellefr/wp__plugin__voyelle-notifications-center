<?php
/**
 * Compatibility with GravityForms
 */
class VOYNOTIF_compat_gravityforms extends VOYNOTIF_compat {
    
    /**
     * COnstructor
     */
    function __construct() {
        $this->dependencies = array(
            'Gravity Forms' => array(
                'file'      => 'gravityforms/gravityforms.php',
                'version'   => '2.2.5'
            ),
        );  
        parent::__construct();
    }
    
    /**
     * 
     */
    function init() {
        add_filter( 'customize_register', array( $this, 'add_customizer' ), 60 ); 
        add_filter( 'gform_pre_send_email', array( $this, 'add_html_template' ), 10, 4 );
        add_filter( 'voynotif/settings/fields', array($this, 'add_settings') );
        add_filter( 'voynotif/preview/content', array($this, 'add_preview') );
        
        add_filter( 'voynotif/notifications/types', array( $this, 'add_type' ) );
        add_filter( 'voynotif/logs/notification_title/type=gravityforms', array( $this, 'log_title' ), 10, 2 );
        add_filter( 'voynotif/logs/context/type=gravityforms', array( $this, 'log_context' ), 10, 2 );
    }
    
    public function add_type( $types ) {
        $types['gravityforms'] = array(
            'label' => __('Gravity Forms', 'notifications-center'),
            'tags' => 'custom',
            'overrides_wp' => false,
        );  
        return $types;
    }
    
    public function log_title( $return, $log ) {
        $notification_id = $log->context['notification_id'];
        $entry_id = $log->context['entry_id'];
        $entry = GFAPI::get_entry( $entry_id );
        $form_id = rgar( $entry, 'form_id' );
        $form = GFAPI::get_form( $form_id );
        $notifications = $form['notifications'];
        if( array_key_exists( $notification_id, $notifications ) ) {
            $url = admin_url().'admin.php?page=gf_edit_forms&view=settings&subview=notification&id='.$form_id.'&nid='.$notification_id;
            return '['.__('Gravity Forms', 'notifications-center').'] <a href="'.$url.'">'.$notifications[$notification_id]['name'].'</a>';
        }
        return $return;
    }
    
    public function log_context( $return, $context ) {
        if( isset( $context['entry_id'] ) ) {
            $entry_id = $context['entry_id'];
            $entry = GFAPI::get_entry( $entry_id );
            $form_id = rgar( $entry, 'form_id' );
            $form = GFAPI::get_form( $form_id );
            $entry_url = admin_url().'admin.php?page=gf_entries&view=entry&id='.$form_id.'&lid='.$entry_id.'&order=ASC&filter&paged=1&pos=0&field_id&operator';
            $form_url = admin_url().'admin.php?page=gf_edit_forms&id='.$form_id;
            
            $entry_html = '<a href="'.$entry_url.'">'.$entry_id.'</a>';
            $form_html = '<a href="'.$form_url.'">'.$form['title'] .'</a>';
            return sprintf( __('About %1$s Form (Entry n°%2$s)', 'notifications-center'), $form_html, $entry_html );            
        }
        return $return;
    }
    
    public function add_settings( $fields ) {
        $fields['gf_active'] = array(
            'id' => 'gf_active',                
            'label' => __( 'Gravity Forms', 'notifications-center' ),
            'type' => 'boolean',
                'params'    => array(
                    'title' => __( 'Use Notifications Center template on GravityForms emails', 'notifications-center' ),
                ),
            'screen' => 'general',
            'fieldgroup' => 'template'
        );
        $fields['gf_active_logs'] = array(
            'id' => 'gf_active_logs',                
            'label' => __( 'Gravity Forms', 'notifications-center' ),
            'type' => 'boolean',
                'params'    => array(
                    'title' => __( 'Activate logs on GravityForms emails', 'notifications-center' ),
                ),
            'screen' => 'general',
            'fieldgroup' => 'logs'
        );
        return $fields;
    }
    
    public function add_preview( $content ) {
        $template = new VOYNOTIF_email_template();
        ob_start(); ?>
        <table width="99%" border="0" cellpadding="1" cellspacing="0" bgcolor="#EAEAEA"><tr><td>
                    <table width="100%" border="0" cellpadding="8" cellspacing="0" bgcolor="#FFFFFF">
                        <tr style="color:<?php echo $template->gf_table_color; ?>" bgcolor="<?php echo $template->gf_table_bg; ?>">
                            <td colspan="2">
                                <font style="font-family: sans-serif; font-size:16px;"><strong>Label</strong></font>
                            </td>
                        </tr>
                        <tr bgcolor="#f5f5f5">
                            <td width="20">&nbsp;</td>
                            <td>
                                <font style="font-family: sans-serif; font-size:16px; color: #666666;">Value</font>
                            </td>
                        </tr>
                        <tr style="color:<?php echo $template->gf_table_color; ?>" bgcolor="<?php echo $template->gf_table_bg; ?>">
                            <td colspan="2">
                                <font style="font-family: sans-serif; font-size:16px;"><strong>Label</strong></font>
                            </td>
                        </tr>
                        <tr bgcolor="#f5f5f5">
                            <td width="20">&nbsp;</td>
                            <td>
                                <font style="font-family: sans-serif; font-size:16px; color: #666666;">Value</font>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>        
        <?php $table = ob_get_clean();
        return $content.$table;
    }
    
    /**
     * Add customizer settings if GF is active
     * @global type $wp_customize
     */
    public function add_customizer() {
        global $wp_customize;
        $wp_customize->add_setting( VOYNOTIF_FIELD_PREFIXE . 'email_gf_table_bg', array( 
            'type' => 'option', // Attention ! 
            'default' => '#0073aa', 
            'transport' => 'refresh', ) 
        );
        $wp_customize->add_setting( VOYNOTIF_FIELD_PREFIXE . 'email_gf_table_color', array( 
            'type' => 'option', // Attention ! 
            'default' => '#fff', 
            'transport' => 'refresh', ) 
        );
        $wp_customize->add_control(
            new WP_Customize_Color_Control(
                $wp_customize,
                VOYNOTIF_FIELD_PREFIXE . 'email_gf_table_bg',
                array(
                    'label'       => __( 'GravityForms table background', 'notifications-center' ),
                    'description' => __( 'Choose the background of GravityForms tables', 'notifications-center' ),
                    'section'     => 'voynotif_body',
                    'settings'    => VOYNOTIF_FIELD_PREFIXE . 'email_gf_table_bg',
                )
            )
        ); 
        $wp_customize->add_control(
            new WP_Customize_Color_Control(
                $wp_customize,
                VOYNOTIF_FIELD_PREFIXE . 'email_gf_table_color',
                array(
                    'label'       => __( 'GravityForms table color', 'notifications-center' ),
                    'description' => __( 'Choose the color of GravityForms tables', 'notifications-center' ),
                    'section'     => 'voynotif_body',
                    'settings'    => VOYNOTIF_FIELD_PREFIXE . 'email_gf_table_color',
                )
            )
        );
    }
    
    /**
     * 
     * @param array $email
     * @param type $message_format
     * @param type $notification
     * @param type $entry
     * @return type
     */
    public function add_html_template( $email, $message_format, $notification, $entry ) { 
        
        $auth = get_option( VOYNOTIF_FIELD_PREFIXE . 'gf_active' );
        if( empty( $auth ) ) return $email;

        if (get_option(VOYNOTIF_FIELD_PREFIXE . 'activate_logs') == true && get_option(VOYNOTIF_FIELD_PREFIXE . 'gf_active_logs') && $email['abort_email'] == false ) {

            $log = VOYNOTIF_logs::add_log(array(
                'notification_id' => false,
                'type' => 'gravityforms',
                'recipient' => $email['to'],
                'subject' => $email['subject'],
                'title' => $email['subject'],
                'status' => null,
                'context' => array(
                    'notification_id' => $notification['id'],
                    'entry_id' => $entry['id']
                ),
            ));

            global $voynotif_log;
            $voynotif_log = $log;
        }
        
        $template = new VOYNOTIF_email_template();
        
        $content = str_replace('<tr bgcolor="#FFFFFF">', '<tr style="color:#666666" bgcolor="'.$template->backgroundcontent_color.'">', $email['message'] );
        $content = str_replace('<tr bgcolor="#EAF2FA">', '<tr style="color:'.$template->gf_table_color.'" bgcolor="'.$template->gf_table_bg.'">', $content );
        $content = str_replace('font-size:12px', 'font-size:12px', $content );
        
        $template->set_title($email['subject']);
        $template->set_content($content);
        $html = $template->get_html();        
        $email['message'] = $html;



        return $email;
    }
    
}

new VOYNOTIF_compat_gravityforms();


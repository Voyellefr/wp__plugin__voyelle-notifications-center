<?php
/**
 * Compatibility woth Duplicate Post, to allow users to send Notifications when a post is duplicated
 */
class VOYNOTIF_compat_woocommerce extends VOYNOTIF_compat {
    
    function __construct() {
        $this->dependencies = array(
            'Woocommerce' => array(
                'file'      => 'woocommerce/woocommerce.php',
                'version'   => '3.3.0'
            ),
        );  
        parent::__construct();
    }
    
    function init() {       
        add_filter( 'voynotif/notifications/tags', array( $this, 'add_tags' ) ); 
        add_filter( 'voynotif/masks/tags', array( $this, 'add_maks_tags' ) );  
        add_filter( 'voynotif/notification/content', array( $this, 'perform_masks' ), 20, 2 );
        add_filter( 'voynotif/settings/defaults', array( $this, 'add_defaults') );
        add_filter( 'voynotif/settings/fields', array($this, 'add_settings') );
        
        add_filter( 'voynotif/load_field', array( $this, 'get_wc_email_status'), 10, 2 );
        add_action('voynotif/save_field', array( $this, 'update_wc_email_status' ), 10, 4);
        
        add_filter( 'voynotif/logs/context/type=wc_new_order', array( $this, 'log_context' ), 10, 2 );
        
        include_once( VOYNOTIF_DIR . '/includes/notifications/woocommerce/new-order.php');
    }
    
    public function add_settings( $fields ) {
        $fields['wc_active'] = array(
            'id' => 'wc_active',                
            'label' => __( 'Woocommerce', 'notifications-center' ),
            'type' => 'boolean',
                'params'    => array(
                    'title' => __( 'Use Notifications Center template on Woocommerce emails', 'notifications-center' ),
                ),
            'screen' => 'general',
            'fieldgroup' => 'template'
        );
        return $fields;
    }
    
    public function get_wc_email_status( $value, $option ) {
        $slug = str_replace('block_', '', str_replace('_status', '', $option) );
        if( array_key_exists( $slug, $this->get_defaults() ) ) {
            $base_slug = str_replace('wc_', '', $slug);
            $option = get_option('woocommerce_'.$base_slug.'_settings');
            if( is_array($option) && array_key_exists('enabled', $option) && $option['enabled'] == 'no' ) {
                return 1;
            } else {
                return 0;
            }           
        }          
        return $value;
    }
    
    public function update_wc_email_status( $field_id, $value, $result, $context ) {
        $slug = str_replace('block_', '', str_replace('_status', '', $field_id) );
        if( array_key_exists( $slug, $this->get_defaults() ) ) {
            $base_slug = str_replace('wc_', '', $slug);
            $option = get_option('woocommerce_'.$base_slug.'_settings');
            $enabled = ( $value == 1 ) ? 'no' : 'yes' ;
            if( is_array( $option ) ) {
                $option['enabled'] = $enabled;
                update_option( 'woocommerce_'.$base_slug.'_settings', $option );
            } else {
                $option = array('enabled' => $enabled);
                update_option( 'woocommerce_'.$base_slug.'_settings', $option );
            }
        }          
        return true;
    }
    
    public function get_defaults() {
        return array(
            'wc_new_order' => array(
                'label' => __( 'New order', 'notifications-center' ),
                'tag' => 'woocommerce',
                'description' => __( 'Email sent to admin after a new order', 'notifications-center' ),
            ),
            'wc_cancelled_order' => array(
                'label' => __( 'Cancelled order', 'notifications-center' ),
                'tag' => 'woocommerce',
                'description' => __( 'Email sent when an order is cancelled', 'notifications-center' ),
            ),
            'wc_failed_order' => array(
                'label' => __( 'Failed order', 'notifications-center' ),
                'tag' => 'woocommerce',
                'description' => __( 'Email sent when an order is failed', 'notifications-center' ),
            ),
        );
    }
    
    public function add_defaults( $defaults ) {
        $wc_defaults = $this->get_defaults();
        return array_merge($wc_defaults, $defaults);         
    }
    
    public function log_context( $return, $context ) {
        if( isset( $context['order_id'] ) ) {
            $order = wc_get_order($context['order_id']);
            $order_html = '<a href="'.get_edit_post_link($context['order_id'], false).'">'.$order->get_order_number().'</a>';
            return sprintf( __('About order n°%s', 'notifications-center'), $order_html );            
        }
        return $return;
    }
    
    public function add_tags( $tags ) {
        $tags['woocommerce'] = __('Woocommerce', 'notifications-center');
        return $tags;
    }
    
    public function add_maks_tags($tags) {
        $tags['woocommerce_order'] = __('Order', 'notifications-center');
        $tags['woocommerce_order_customer'] = __('Order - customer', 'notifications-center');
        $tags['woocommerce_order_payment'] = __('Order - payment', 'notifications-center');
        $tags['woocommerce_order_shipping'] = __('Order - shipping', 'notifications-center');
        return $tags;
    }
    
    public function perform_masks( $content, $context ) {
        
        if( !isset( $context['order_id'] ) || empty( $context['order_id'] ) ) return $content;
        
        $order = wc_get_order( $context['order_id'] );
        $gateway = wc_get_payment_gateway_by_order($order);
        $gateway_instructions = ( isset( $gateway->instructions ) ) ? wpautop( wptexturize( $gateway->instructions ) ) : '' ;
        $date_paid = ( $order->get_date_paid() ) ? $order->get_date_paid()->format( get_option('date_format') ) : '' ;
        
        $content = str_replace( '{wc_order_num}', $order->get_order_number(), $content );
        $content = str_replace( '{wc_order_date}', $order->get_date_created()->format( get_option('date_format') ), $content );
        
        $content = str_replace( '{wc_billing_firstname}', $order->get_billing_first_name(), $content );
        $content = str_replace( '{wc_billing_address}', $order->get_formatted_billing_address(), $content );
        
        $content = str_replace( '{wc_shipping_method}', $order->get_shipping_method(), $content );
        $content = str_replace( '{wc_shipping_method2}', $order->get_shipping_to_display(), $content );  
   
        $content = str_replace( '{wc_payment_method}', $order->get_payment_method_title(), $content );
        $content = str_replace( '{wc_payment_instructions}', $gateway_instructions, $content );
        $content = str_replace( '{wc_payment_date}', $date_paid, $content );
        
        ob_start();
        include( voynotif_email_template_path() . 'parts/woocommerce/order-details.php' );
        $order_details = ob_get_clean();
        $content = str_replace( '{wc_order_details}', $order_details, $content );
        
        error_log('----- $order->get_total() -----');
        error_log( print_r( $order->get_total(), true ) );
        
        return $content;
    }
    
    public static function get_masks() {
        return array(
            
            //Billing
            'wc_order_num' => array(
                'title' => __('Order number', 'notifications-center'),
                'tag' => 'woocommerce_order'
            ),
            'wc_order_date' => array(
                'title' => __('Order date', 'notifications-center'),
                'tag' => 'woocommerce_order'
            ),
            'wc_order_num' => array(
                'title' => __('Order number', 'notifications-center'),
                'tag' => 'woocommerce_order'
            ),
            'wc_order_details' => array(
                'title' => __('Order details', 'notifications-center'),
                'tag' => 'woocommerce_order'
            ),
            
            //Billing
            'wc_billing_firstname' => array(
                'title' => __('Billing - Firstname', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_billing_lastname' => array(
                'title' => __('Billing - Lastname', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_billing_email' => array(
                'title' => __('Billing - Phone', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_billing_phone' => array(
                'title' => __('Billing - Phone', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_billing_company' => array(
                'title' => __('Billing - Company', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_billing_address1' => array(
                'title' => __('Billing - Address 1', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_billing_address2' => array(
                'title' => __('Billing - Address 2', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_billing_state' => array(
                'title' => __('Billing - State', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_billing_postcode' => array(
                'title' => __('Billing - Postcode', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_billing_country' => array(
                'title' => __('Billing - Country', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_billing_address' => array(
                'title' => __('Billing - Full address', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            
            //Shipping
            'wc_shipping_firstname' => array(
                'title' => __('Shipping - Firstname', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_shipping_lastname' => array(
                'title' => __('Shipping - Lastname', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_shipping_email' => array(
                'title' => __('Shipping - Phone', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_shipping_phone' => array(
                'title' => __('Shipping - Phone', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_shipping_company' => array(
                'title' => __('Shipping - Company', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_shipping_address1' => array(
                'title' => __('Shipping - Address 1', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_shipping_address2' => array(
                'title' => __('Shipping - Address 2', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_shipping_state' => array(
                'title' => __('Shipping - State', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_shipping_postcode' => array(
                'title' => __('Shipping - Postcode', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_shipping_country' => array(
                'title' => __('Shipping - Country', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            'wc_shipping_address' => array(
                'title' => __('Shipping - Full address', 'notifications-center'),
                'tag' => 'woocommerce_order_customer'
            ),
            
            //Shipping
            'wc_shipping_method' => array(
                'title' => __('Shipping method', 'notifications-center'),
                'tag' => 'woocommerce_order_shipping'
            ),
            'wc_shipping_methods' => array(
                'title' => __('Shipping methods', 'notifications-center'),
                'tag' => 'woocommerce_order_shipping'
            ),
            'wc_shipping_method2' => array(
                'title' => __('Shipping method display', 'notifications-center'),
                'tag' => 'woocommerce_order_shipping'
            ),
            
            //Paiment
            'wc_payment_method' => array(
                'title' => __('Payment method', 'notifications-center'),
                'tag' => 'woocommerce_order_payment'
            ),
            'wc_payment_instructions' => array(
                'title' => __('Payment instructions', 'notifications-center'),
                'tag' => 'woocommerce_order_payment'
            ),
            'wc_payment_date' => array(
                'title' => __('Payment date', 'notifications-center'),
                'tag' => 'woocommerce_order_payment'
            ),
        );
    }
    
}

//new VOYNOTIF_compat_woocommerce();


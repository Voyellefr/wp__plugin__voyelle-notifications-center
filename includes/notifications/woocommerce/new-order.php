<?php
/**
 * NOTIFICATION Pending Content
 * 
 * -----SUMMARY-----
 * 1. Replace TYPENAME with my_type_name
 * 2. Replace TYPEDESCRIPTION with "My description"
 * 3. Change Tags
 * 
 */
class VOYNOTIF_notification_type_wc_new_order extends VOYNOTIF_notification_type {
    
    
    /**
     * Constructor
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
     */
    function __construct() {
        
        $this->name = 'wc_new_order';
        $this->label = __( 'New order', 'notifications-center' );
        $this->tags = array(
            'woocommerce'
        );
        
        parent::__construct();
    }
    
    
    /**
     * Declare all needed hook to perform the current notification
     * Called in parent class just after __construct method
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
     */
    function init() {
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'send' ), 1000, 3 );        
    }
    
    
    /**
     * Register setting fields, displayed in the setting metabox if current notification type is selected
     * Called in voynotif/notification/fields/settings/type={current_type} filter
     * 
     * @author Floflo
     * @since 0.9
     * @update 2013-06-02
     * 
     * @param array $fields array of registered fields
     * @return array array of registered fields
     */
    function setting_fields($fields) {
        $fields[] = 
            array(
                'id' => 'voynotif_settings_wc_statuses',
                'label' => __( 'Order status', 'notifications-center' ),
                'type' => 'selectmultiple',
                'choices' => wc_get_order_statuses(),
                'params' => array(
                    'select2' => true
                )
            );
        return $fields;
    }    
    
    
    /**
     * Add recipients choices (or unset existing ones) in the recipient select field
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
     * 
     * @param array $types array of default choices (emails, roles, users)
     * @return arrray array of choices
     */
    function set_recipient_types($types) {
        
        //Unset default recipient types
        //unset($types['emails']);
        //unset($types['roles']);
        //unset($types['users']);
        
        //Add custom recipient types
        $types['post_author'] = __( 'Customer', 'notifications-center' );
        
        //return types
        return $types;
    }

    
    /**
     * Register custom masks
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-07-12
     * 
     * @param array $masks
     * @return string
     */
    function custom_masks($masks) {
        
        //unset $masks['id'];
        $wc_masks = VOYNOTIF_compat_woocommerce::get_masks();
        $masks = array_merge($masks, $wc_masks);
        return $masks;
    }
    
    
    /*
     * -------------------------------------------------------------------------
     * ADD ALL NEEDED CUSTOM METHODS UNDER THIS LINE
     * ------------------------------------------------------------------------- 
     */
    
    
    /**
     * Set up custom hooks to check all cpt pendings
     * 
     * @author Floflo
     * @since 0.9
     * @update 2016-06-02
     */
    function send( $order_id ) {
        
        //Get notifications
        $notifications = voynotif_get_notifications( $this->name );

        //Return if no matching notifications
        if( empty( $notifications ) ) {
            return;
        }
        
        $order = wc_get_order( $order_id );

        //For each notification
        foreach( $notifications as $notification ) {
            
            if( !in_array( 'wc-'.$order->get_status(), $notification->get_field('settings_wc_statuses') ) ) {
                continue;
            }

            $notification->set_context_info( array(
                'order_id' => $order_id,
            ) );

            //Add author email to recipients
            if( $notification->get_field('recipient_type') == 'post_author' ) { 
                $notification->add_recipient($order->get_billing_email());
            }

            //Send notification
            $notification->send_notification();  

        }
    }
        
}
new VOYNOTIF_notification_type_wc_new_order();


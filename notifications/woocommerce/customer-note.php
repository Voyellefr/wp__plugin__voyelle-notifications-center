<?php
class VOYNOTIF_notification_type_wc_customer_note extends VOYNOTIF_notification_type {

    function __construct() {
        $this->name = 'wc_customer_note';
        $this->label = __( 'Customer note', 'notifications-center' );
        $this->tags = array('woocommerce');
        parent::__construct();
    }

    function init() {
        add_action( 'woocommerce_new_customer_note', array( $this, 'send' ), 10, 1 );
    }

    function set_recipient_types($types) {
        $types['customer'] = __( 'Customer', 'notifications-center' );
        return $types;
    }

    function custom_masks($masks) {
        $wc_masks = VOYNOTIF_compat_woocommerce::get_masks();
        $masks = array_merge($masks, $wc_masks);
        return $masks;
    }

    function send( $args ) {
        $notifications = voynotif_get_notifications( $this->name );
        if( empty( $notifications ) ) {
            return;
        }

        $order = wc_get_order( $args['order_id'] );
        foreach( $notifications as $notification ) {
            $notification->set_context_info( array(
                'order_id' => $args['order_id'],
                'customer_note' => $args['customer_note']
            ) );
            if( $notification->get_field('recipient_type') == 'customer' ) {
                $notification->add_recipient($order->get_billing_email());
            }
            $notification->send_notification();
        }
    }
}
new VOYNOTIF_notification_type_wc_customer_note();

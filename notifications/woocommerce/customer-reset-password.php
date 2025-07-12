<?php
class VOYNOTIF_notification_type_wc_customer_reset_password extends VOYNOTIF_notification_type {

    function __construct() {
        $this->name = 'wc_customer_reset_password';
        $this->label = __( 'Customer reset password', 'notifications-center' );
        $this->tags = array('woocommerce');
        parent::__construct();
    }

    function init() {
        add_action( 'woocommerce_customer_reset_password', array( $this, 'send' ), 10, 1 );
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

    function send( $user ) {
        $notifications = voynotif_get_notifications( $this->name );
        if( empty( $notifications ) ) {
            return;
        }

        foreach( $notifications as $notification ) {
            $notification->set_context_info( array(
                'user_id' => $user->ID,
                'user_login' => $user->user_login,
            ) );
            if( $notification->get_field('recipient_type') == 'customer' ) {
                $notification->add_recipient($user->user_email);
            }
            $notification->send_notification();
        }
    }
}
new VOYNOTIF_notification_type_wc_customer_reset_password();

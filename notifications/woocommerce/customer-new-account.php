<?php
class VOYNOTIF_notification_type_wc_customer_new_account extends VOYNOTIF_notification_type {

    function __construct() {
        $this->name = 'wc_customer_new_account';
        $this->label = __( 'Customer new account', 'notifications-center' );
        $this->tags = array('woocommerce');
        parent::__construct();
    }

    function init() {
        add_action( 'woocommerce_created_customer', array( $this, 'send' ), 10, 3 );
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

    function send( $customer_id, $new_customer_data, $password_generated ) {
        $notifications = voynotif_get_notifications( $this->name );
        if( empty( $notifications ) ) {
            return;
        }

        $user = get_user_by('id', $customer_id);

        foreach( $notifications as $notification ) {
            $notification->set_context_info( array(
                'user_id' => $customer_id,
                'user_login' => $user->user_login,
                'user_pass' => $password_generated,
                'customer_data' => $new_customer_data
            ) );
            if( $notification->get_field('recipient_type') == 'customer' ) {
                $notification->add_recipient($user->user_email);
            }
            $notification->send_notification();
        }
    }
}
new VOYNOTIF_notification_type_wc_customer_new_account();

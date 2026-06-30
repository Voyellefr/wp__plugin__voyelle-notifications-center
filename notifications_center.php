<?php
/*
Plugin Name: Notifications Center
Plugin URI: http://www.notificationscenter.com/
Description: Personnalized notifications for your Wordpress website with beautiful, responsive and personnalised emails.
Version: 1.5.2
Author: Florian Chaillou
Author URI: http://www.notificationscenter.com 
Text Domain: notifications-center
Domain Path: /languages
Requires at least: 7.0
Requires PHP: 8.2
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'VOYNOTIF_plugin' ) ) {
    class VOYNOTIF_plugin {

        /**
         * Updater instance.
         *
         * @var VOYNOTIF_updater
         */
        public VOYNOTIF_updater $updater;

        /**
         * Admin notices to render.
         *
         * @var array<int, array{class: string, message: string}>
         */
        public array $admin_notices = [];

        /**
         * Plugin init
         *
         * @author Floflo
         * @since 0.9
         * @update 2017-01-17
         */
        public function __construct() {

            //------------------------------------------------------------//
            // 1. Constants
            //------------------------------------------------------------// 
            define( 'VOYNOTIF_DIR', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) );
            define( 'VOYNOTIF_URL', plugins_url() . '/' . basename( dirname( __FILE__ ) ) );
            define( 'VOYNOTIF_VERSION', '1.5.2' );
            define( 'VOYNOTIF_FIELD_PREFIXE', 'voynotif_' );
            define( 'VOYNOTIF_PREMIUM_URL', 'http://www.notificationscenter.com' );
            define( 'VOYNOTIF_EXPORT_VERSION', '1.1.0' );

            //------------------------------------------------------------//
            // 2. Autoloader
            //------------------------------------------------------------//
            require_once __DIR__ . '/vendor/autoload.php';

            //------------------------------------------------------------//
            // 3. Plugin activation hook
            //------------------------------------------------------------// 
            register_activation_hook( __FILE__, [ $this, 'install' ] );

            //------------------------------------------------------------//
            // 4. Hook setup
            //------------------------------------------------------------// 
            add_action( 'init', [ $this, 'post_type_register' ], 0 );
            add_action( 'admin_enqueue_scripts', [ $this, 'admin_loadscripts' ] );
            add_action( 'plugins_loaded', [ $this, 'load_text_domain' ] );
            add_action( 'plugins_loaded', [ $this, 'init' ], 20 );
            add_action( 'template_redirect', [ $this, 'template_preview' ] );
            add_action( 'admin_notices', [ $this, 'admin_notices' ] );

            /**
             * @since 1.1.0
             */
            if ( get_option( VOYNOTIF_FIELD_PREFIXE . 'extend_sender' ) === 'wordpress' ) {
                add_action( 'wp_mail_from_name', [ $this, 'extend_sender_name' ], 100, 1 );
                add_action( 'wp_mail_from', [ $this, 'extend_sender_email' ], 100, 1 );
            }

            //------------------------------------------------------------//
            // 5. Includes with side effects (self-registering files)
            //    Pure classes are loaded on demand by the Composer autoloader.
            //------------------------------------------------------------//
            require_once VOYNOTIF_DIR . '/includes/core/class-logs.php';
            require_once VOYNOTIF_DIR . '/includes/core/class-helpers.php';
            require_once VOYNOTIF_DIR . '/includes/functions.php';
            require_once VOYNOTIF_DIR . '/includes/template.php';

            require_once VOYNOTIF_DIR . '/includes/admin/notifications.php';
            require_once VOYNOTIF_DIR . '/includes/admin/notification.php';
            require_once VOYNOTIF_DIR . '/includes/admin/template-customizer.php';
            require_once VOYNOTIF_DIR . '/includes/admin/settings.php';
            require_once VOYNOTIF_DIR . '/includes/admin/settings-import.php';
            require_once VOYNOTIF_DIR . '/includes/admin/settings-logs.php';
            //require_once VOYNOTIF_DIR . '/includes/admin/help.php';

            //------------------------------------------------------------//
            // 6. CLASS Init
            //------------------------------------------------------------//
            $this->updater = new VOYNOTIF_updater();

        }

        public function answer_expiration_event() {
            error_log( 'answer_expiration_event' );
        }


        /**
         * Install plugin with dummy data (current template, colors, etc)
         *
         * @author Floflo
         * @since 0.9
         * @update 2016-07-19
         */
        public function install() {

            //Multisite install
            if ( is_multisite() ) {
                $sites = get_sites();
                foreach ( $sites as $site ) {
                    switch_to_blog( (int) $site->blog_id );
                    $this->updater->init();
                    restore_current_blog();
                }

            //Single site install    
            } else {
                $this->updater->init();
            }

        }


        /**
         * Post type register
         *
         * @author Floflo
         * @since 0.9
         * @update 2016-06-02
         */
        public function post_type_register() {

            $labels = [
                'name'                => __( 'Notifications', 'Post Type General Name', 'notifications-center' ),
                'singular_name'       => __( 'Notification', 'Post Type Singular Name', 'notifications-center' ),
                'menu_name'           => __( 'Notifications', 'notifications-center' ),
                'all_items'           => __( 'All notifications', 'notifications-center' ),
                'view_item'           => __( 'See notification', 'notifications-center' ),
                'add_new_item'        => __( 'New notification', 'notifications-center' ),
                'add_new'             => __( 'New notification', 'notifications-center' ),
                'edit_item'           => __( 'Modify', 'notifications-center' ),
                'update_item'         => __( 'Update', 'notifications-center' ),
                'search_items'        => __( 'Search', 'notifications-center' ),
                'not_found'           => __( 'No notification found', 'notifications-center' ),
                'not_found_in_trash'  => __( 'Not found in Trash', 'notifications-center' ),
            ];
            $args = apply_filters( 'voy/notif/install/register_cpt',
                [
                    'label'               => __( 'voy_notification', 'notifications-center' ),
                    'description'         => __( 'voy_notification', 'notifications-center' ),
                    'labels'              => $labels,
                    'supports'            => [ 'title', 'editor', 'author', 'revisions' ],
                    'hierarchical'        => false,
                    'public'              => false,
                    'show_ui'             => true,
                    'show_in_menu'        => false,
                    'show_in_nav_menus'   => false,
                    'show_in_admin_bar'   => false,
                    'menu_position'       => 39,
                    'menu_icon'           => 'dashicons-email',
                    'can_export'          => true,
                    'has_archive'         => false,
                    'exclude_from_search' => false,
                    'publicly_queryable'  => false,
                    'capability_type'     => 'post',
                ]
            );
            register_post_type( 'voy_notification', $args );

        }


        /**
         * Load admin scripts
         *
         * @author Floflo
         * @since 0.9
         * @update 2016-06-02
         */
        public function admin_loadscripts() {
            wp_register_style( 'voynotif_admin_css', VOYNOTIF_URL . '/assets/css/admin.css', false, VOYNOTIF_VERSION );
            wp_enqueue_style( 'voynotif_admin_css' );
        }


        /**
         * Load text domain
         *
         * @author Floflo
         * @since 0.9
         * @update 2016-06-02
         */
        public function load_text_domain() {
            load_plugin_textdomain( 'notifications-center', false, basename( dirname( __FILE__ ) ) . '/languages/' );
        }


        /**
         * Init plugin with loading notifications & templates
         *
         * @author Floflo
         * @since 0.9
         * @update 2016-07-13
         */
        public function init() {

            //Add compat files with other plugins
            require_once VOYNOTIF_DIR . '/includes/compat/class-compat-duplicate-post.php';
            require_once VOYNOTIF_DIR . '/includes/compat/class-compat-gravityforms.php';
            require_once VOYNOTIF_DIR . '/includes/compat/class-compat-woocommerce.php';

            //Load Notifications
            require_once VOYNOTIF_DIR . '/includes/notifications/comment_new.php';
            require_once VOYNOTIF_DIR . '/includes/notifications/comment_reply.php';
            require_once VOYNOTIF_DIR . '/includes/notifications/comment_moderate.php';
            require_once VOYNOTIF_DIR . '/includes/notifications/content_draft.php';
            require_once VOYNOTIF_DIR . '/includes/notifications/content_future.php';
            require_once VOYNOTIF_DIR . '/includes/notifications/content_pending.php';
            require_once VOYNOTIF_DIR . '/includes/notifications/content_publish.php';
            require_once VOYNOTIF_DIR . '/includes/notifications/content_trash.php';
            require_once VOYNOTIF_DIR . '/includes/notifications/core_update.php';
            require_once VOYNOTIF_DIR . '/includes/notifications/user_login.php';
            require_once VOYNOTIF_DIR . '/includes/notifications/user_password_changed.php';
            require_once VOYNOTIF_DIR . '/includes/notifications/user_password_reset.php';
            require_once VOYNOTIF_DIR . '/includes/notifications/user_register.php';

            //Check for udpate
            if ( $this->updater->current_version !== $this->updater->new_version ) {
                $this->updater->update();
            }

            /**
             * ACTION voynotif/loaded
             * Triggered when Notifications Center is fully loaded (both function & notifications)
             *
             * @author Floflo
             * @since 1.3.0
             * @update 2017-08-26
             */
            do_action( 'voynotif/loaded' );

        }


        /**
         * Generate template preview
         *
         * @author Floflo
         * @since 0.9
         * @update 2016-07-13
         */
        public function template_preview() {
            if ( is_singular( 'voy_notification' ) ) {
                if ( is_user_logged_in() ) {
                    $notification = new VOYNOTIF_notification( get_the_ID() );
                    echo $notification->get_html();
                } else {
                    _e( 'You must be logged in to preview this notification', 'notifications-center' );
                }
                exit();
            }
        }


        /**
         * Overrides Wordpress default sender name
         *
         * @author Floflo
         * @since 1.1.0
         * @update 2016-12-11
         *
         * @param string $name Wordpress default sender name
         * @return string Notifications Center sender name
         */
        public function extend_sender_name( $name ) {

            $sender_name = get_option( VOYNOTIF_FIELD_PREFIXE . 'sender_name' );

            if ( ! empty( $sender_name ) ) {
                return $sender_name;
            }

            return $name;

        }


        /**
         * Overrides Wordpress default sender email
         *
         * @author Floflo
         * @since 1.1.0
         * @update 2016-12-11
         *
         * @param string $email Wordpress default sender email
         * @return string Notifications Center sender email
         */
        public function extend_sender_email( $email ) {

            $sender_email = get_option( VOYNOTIF_FIELD_PREFIXE . 'sender_email' );

            if ( is_email( $sender_email ) ) {
                return $sender_email;
            }

            return $email;

        }

        /**
         * Render queued admin notices.
         *
         * @return void
         */
        public function admin_notices() {

            if ( empty( $this->admin_notices ) ) {
                return;
            }

            foreach ( $this->admin_notices as $notice ) {
            ?>
            <div class="notice notice-<?php echo $notice['class']; ?> is-dismissible">
                <p><?php echo $notice['message']; ?></p>
            </div>
            <?php
            }
        }

        public function cron_activation() {
            if ( ! wp_next_scheduled( 'notifications_center_logs_deletion' ) ) {
                error_log( 'activation' );
                wp_schedule_event( time(), 'daily', 'notifications_center_logs_deletion' );
            }
        }

        public function cron_deactivation() {
            wp_clear_scheduled_hook( 'notifications_center_logs_deletion' );
        }

    }
}
$notifications_center = new VOYNOTIF_plugin();

register_activation_hook( __FILE__, [ $notifications_center, 'cron_activation' ] );
register_deactivation_hook( __FILE__, [ $notifications_center, 'cron_deactivation' ] );

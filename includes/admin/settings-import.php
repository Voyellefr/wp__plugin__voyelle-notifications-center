<?php

class VOYNOTIF_settings_import extends VOYNOTIF_settings_screen {

    /**
     * 
     */
    function __construct() {
        $this->id = 'import';
        $this->title = __('Import/Export', 'notifications-center');
        $this->order = 100;
        $this->callback = array($this, 'html');
        
        add_action('load-admin_page_voynotif_settings', array($this, 'do_actions'));
        parent::__construct();
    }

    
    /*
     * -------------------------------------------------------------------------
     * GENERIC METHODS
     * ------------------------------------------------------------------------- 
     */

    
    /**
     * 
     */
    public function do_actions() {
        if (isset($_POST['_voynotif_nounce'])) {
            if ($_POST['_voynotif_nounce'] == 'export') {
                $this->export();
            }
            if ($_POST['_voynotif_nounce'] == 'import') {
                $this->import();
            }
        }
    }

    
    /**
     * 
     */
    public function html() {
        ?>

        <div class="voy-box">
            <div class="header"><h2>Export</h2></div>
            <div class="main">
                <div class="voy-row">
                    <div class="voy-col-6">
                        <form method="post" action="">
                            <div class="voy-hidden">
                                <input type="hidden" name="_voynotif_nounce" value="export" />                        
                            </div>                       
                            <div>
                                <input type="checkbox" id="voynotif_export_notifications" name="voynotif_export_notifications" value="1" />
                                <label for="voynotif_export_notifications"><?php _e('Export notifications', 'notifications-center'); ?></label>               
                            </div>
                            <div>
                                <input type="checkbox" id="voynotif_export_settings" name="voynotif_export_settings" value="1" />
                                <label for="voynotif_export_settings"><?php _e('Export settings', 'notifications-center'); ?></label>                       
                            </div>
                            <input type="submit" name="download" class="button button-primary" value="<?php _e('Download export file', 'notifications-center'); ?>" />
                        </form>
                    </div>
                    <div class="voy-col-6">
                    </div>
                </div>
            </div>
        </div>

        <div class="voy-box">
            <div class="header"><h2>Import</h2></div>
            <div class="main">
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="voy-hidden">
                        <input type="hidden" name="_voynotif_nounce" value="import" />                        
                    </div>                       
                    <div>
                        <label for="voynotif_file_to_import"><?php _e('FIle to import', 'notifications-center'); ?></label>
                        <input type="file" name="voynotif_file_to_import">                              
                    </div>
                    <input type="submit" name="upload" class="button button-primary" value="<?php _e('Import file', 'notifications-center'); ?>" />
                </form>
            </div>
        </div> 

        <?php
    }

    
    /*
     * -------------------------------------------------------------------------
     * EXPORT METHODS
     * ------------------------------------------------------------------------- 
     */

    
    /**
     * 
     * @return type
     */
    function export() {

        $params = array(
            'export_notifications' => false,
            'export_settings' => false,
        );
        if (isset($_POST['voynotif_export_notifications']) && $_POST['voynotif_export_notifications'] == 1) {
            $params['export_notifications'] = true;
        }
        if (isset($_POST['voynotif_export_settings']) && $_POST['voynotif_export_settings'] == 1) {
            $params['export_settings'] = true;
        }

        if (empty($params)) {
            return;
        }

        $export_data = $this->get_export($params);
        $file_name = 'notifications-center-export-' . date('Y-m-d') . '.json';
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename={$file_name}");
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($export_data);
        die;
    }

    
    /**
     * 
     * @param type $params
     * @return type
     */
    function get_export($params) {

        $return = array();
        $return['meta'] = array(
            'date' => date('Y-m-d'),
            'export_version' => VOYNOTIF_EXPORT_VERSION,
        );

        if ($params['export_notifications'] == true) {
            $notifications = voynotif_get_notifications();
            foreach ($notifications as $notification) {
                $return['notifications'][] = $this->prepare_notification_for_export($notification);
            }
        }

        if ($params['export_settings'] == true) {
            $options = voynotif_get_options();
            if ($options) {
                unset($options['voynotif_update_options']);
                $return['settings'] = $options;
            }
        }

        return $return;
    }

    
    /**
     * 
     * @global type $wpdb
     * @param type $notification
     * @return type
     */
    function prepare_notification_for_export($notification) {
        $prepared_notification = array(
            'id' => $notification->id,
            'title' => get_the_title($notification->id),
            'content' => $notification->content,
            'status' => get_post_status($notification->id),
        );

        $post_notification = get_post($notification->id);
        $prepared_notification['slug'] = $post_notification->post_name;

        global $wpdb;
        $results = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'postmeta WHERE meta_key LIKE "voynotif_%" AND post_id = ' . $notification->id, OBJECT);
        if (!empty($results)) {
            foreach ($results as $meta) {
                if (@unserialize($meta->meta_value)) {
                    $meta->meta_value = @unserialize($meta->meta_value);
                }
                $prepared_notification[$meta->meta_key] = $meta->meta_value;
            }
        }
        return $prepared_notification;
    }

    
    /*
     * -------------------------------------------------------------------------
     * IMPORT METHODS
     * ------------------------------------------------------------------------- 
     */

    /**
     * 
     * @return type
     */
    function import() {

        //Check if get file
        if (empty($_FILES['voynotif_file_to_import'])) {
            voynotif_add_admin_notice(__('Import failed. Please add a file.', 'notifications-center'), 'error');
            return;
        }
        $file = $_FILES['voynotif_file_to_import'];

        // validate error
        if ($file['error']) {
            voynotif_add_admin_notice(__('Import failed. Please try again.', 'notifications-center'), 'error');
            return;
        }

        // validate type
        if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'json') {
            voynotif_add_admin_notice(__('Import failed. Please upload a correct JSON file.', 'notifications-center'), 'error');
            return;
        }

        // read file
        $json = file_get_contents($file['tmp_name']);
        $data = json_decode($json, true);

        // validate json
        if (empty($json)) {
            voynotif_add_admin_notice(__('Import failed. The file is empty.', 'notifications-center'), 'error');
            return;
        }

        if (!isset($data['meta']['export_version']) || empty($data['meta']['export_version'])) {
            voynotif_add_admin_notice(__('Import failed. Unable to detect import file version.', 'notifications-center'), 'error');
            return;
        }

        if (version_compare($data['meta']['export_version'], VOYNOTIF_EXPORT_VERSION) > 0) {
            voynotif_add_admin_notice(__('The import file is not compatible with your Notification Center version. Please try to update the plugin', 'notifications-center'), 'error');
            return;
        }

        if (isset($data['settings']) && !empty($data['settings'])) {
            $this->import_settings($data['settings']);
            voynotif_add_admin_notice(__('Settings succesfully imported', 'notifications-center'), 'success');
        }

        if (isset($data['notifications']) && !empty($data['notifications'])) {
            $result = $this->import_notifications($data['notifications']);
            voynotif_add_admin_notice(__($result['success'] . ' notifications imported (' . $result['skipped'] . ' notifications already exist).', 'notifications-center'), 'success');
        }

        return;
    }

    
    /**
     * 
     * @param type $settings
     * @return boolean
     */
    function import_settings($settings) {
        foreach ($settings as $name => $value) {
            $name = str_replace( VOYNOTIF_FIELD_PREFIXE, '', $name );
            voynotif_update_option( $name, $value );
        }
        return true;
    }

    
    /*
     * 
     */
    function import_notifications($notifications) {

        $success = 0;
        $skipped = 0;

        foreach ($notifications as $notification) {
            if (voynotif_notification_exists($notification['slug'])) {
                $skipped ++;
                continue;
            }

            $new_notification_id = wp_insert_post(array(
                'post_type' => 'voy_notification',
                'post_status' => $notification['status'],
                'post_title' => $notification['title'],
                'post_content' => $notification['content'],
                    ));
            foreach ($notification as $field => $value) {
                if ($field == 'status' || $field == 'title' || $field == 'content' || $field == 'id') {
                    continue;
                }
                update_post_meta($new_notification_id, $field, $value);
            }
            $success++;
        }

        return array(
            'skipped' => $skipped,
            'success' => $success,
        );
    }

}

new VOYNOTIF_settings_import();


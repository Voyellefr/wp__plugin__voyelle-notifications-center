<?php
/**
 * Compatibility woth Duplicate Post, to allow users to send Notifications when a post is duplicated
 */
class VOYNOTIF_compat_duplicate_post extends VOYNOTIF_compat {
    
    function __construct() {
        $this->dependencies = array(
            'Duplicate Post' => array(
                'file'      => 'duplicate-post/duplicate-post.php',
                'version'   => '3.0.0'
            ),
        );  
        parent::__construct();
    }
    
    function init() {
        include_once( VOYNOTIF_DIR . '/includes/notifications/content_duplicate.php');
    }
    
}

new VOYNOTIF_compat_duplicate_post();


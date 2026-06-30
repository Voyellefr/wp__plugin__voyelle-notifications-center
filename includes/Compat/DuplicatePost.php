<?php

namespace Voyelle\NotificationsCenter\Compat;

use Voyelle\NotificationsCenter\Framework\Compat;

/**
 * Compatibility woth Duplicate Post, to allow users to send Notifications when a post is duplicated
 */
class DuplicatePost extends Compat {
    
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
        include_once( VOYNOTIF_DIR . '/includes/Notifications/DuplicatePost.php');
    }
    
}

new DuplicatePost();


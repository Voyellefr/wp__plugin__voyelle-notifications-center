<?php

class VOYNOTIF_settings_screen {
    
    function __construct() {
        if( !$this->order || empty( $this->order ) ) {
            $this->order = 10;
        }
        add_filter( 'voynotif/settings/screens', array( $this, 'add_screen' ), $this->order );
    }
    
    function add_screen( $screens ) {
        $screens[$this->id] = array(
            'title'     => $this->title,
            'callback'  => $this->callback,
        );
        return $screens;
    }
    
}



<?php

namespace Voyelle\NotificationsCenter\Core;


class Helpers {
    
    public static function getDateTimeFormat( $dateTime ) {
        $date_format = $dateTime->format( get_option('date_format') );
        $time_format = $dateTime->format( get_option('time_format') );
        return sprintf( __('%1$s at %2$s', 'notifications-center'), $date_format, $time_format );        
    }
    
}

new Helpers();


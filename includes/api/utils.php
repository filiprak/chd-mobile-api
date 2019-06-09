<?php

if (!function_exists('chd_normalize_url')) {
    function chd_normalize_url($url) {
        $url = str_replace( '&#038;', '&' , $url);
        $url = strpos($url, 'http') === false ? 'http:' . $url : $url;

        return $url;
    }
}
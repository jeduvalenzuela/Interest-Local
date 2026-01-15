<?php
if (!defined('ABSPATH')) exit;

/**
 * Calcula la distancia entre dos puntos geográficos (Haversine)
 */
function geointerest_calculate_distance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371; // km
    
    $lat_diff = deg2rad($lat2 - $lat1);
    $lng_diff = deg2rad($lng2 - $lng1);
    
    $a = sin($lat_diff / 2) * sin($lat_diff / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lng_diff / 2) * sin($lng_diff / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earth_radius * $c;
}

/**
 * Formatea una distancia en km a texto legible
 */
function geointerest_format_distance($km) {
    if ($km < 1) {
        return round($km * 1000) . ' m';
    }
    return round($km, 1) . ' km';
}

/**
 * Limpia tokens expirados (ejecutar con cron)
 */
function geointerest_cleanup_expired_tokens() {
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->prefix}user_tokens WHERE expires_at < NOW()");
}
add_action('wp_scheduled_delete', 'geointerest_cleanup_expired_tokens');
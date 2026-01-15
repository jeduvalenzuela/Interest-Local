<?php
if (!defined('ABSPATH')) exit;

class GeoInterest_Matching_Engine {
    
    /**
     * Encuentra usuarios cercanos con intereses comunes
     * Usa Bounding Box para optimizar la búsqueda geográfica
     */
    public static function find_matches($user_id, $radius_km = 10, $limit = 50) {
        global $wpdb;
        
        // Obtener ubicación del usuario
        $user_location = $wpdb->get_row($wpdb->prepare(
            "SELECT latitude, longitude FROM {$wpdb->prefix}user_locations WHERE user_id = %d",
            $user_id
        ));
        
        if (!$user_location) {
            return [];
        }
        
        $lat = $user_location->latitude;
        $lng = $user_location->longitude;
        
        // Calcular bounding box
        $bounds = self::calculate_bounding_box($lat, $lng, $radius_km);
        
        // Query optimizada con bounding box + Haversine para precisión
        $query = $wpdb->prepare("
            SELECT DISTINCT
                u.ID as user_id,
                u.display_name,
                u.user_email,
                ul.latitude,
                ul.longitude,
                (
                    6371 * acos(
                        cos(radians(%f)) * cos(radians(ul.latitude)) *
                        cos(radians(ul.longitude) - radians(%f)) +
                        sin(radians(%f)) * sin(radians(ul.latitude))
                    )
                ) AS distance_km,
                COUNT(DISTINCT ui.interest_id) as common_interests
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->prefix}user_locations ul ON u.ID = ul.user_id
            INNER JOIN {$wpdb->prefix}user_interests ui ON u.ID = ui.user_id
            WHERE u.ID != %d
                AND ul.latitude BETWEEN %f AND %f
                AND ul.longitude BETWEEN %f AND %f
                AND ui.interest_id IN (
                    SELECT interest_id 
                    FROM {$wpdb->prefix}user_interests 
                    WHERE user_id = %d
                )
            GROUP BY u.ID
            HAVING distance_km <= %f
            ORDER BY common_interests DESC, distance_km ASC
            LIMIT %d
        ",
            $lat, $lng, $lat, // Haversine params
            $user_id, // Excluir usuario actual
            $bounds['min_lat'], $bounds['max_lat'], // Bounding box
            $bounds['min_lng'], $bounds['max_lng'],
            $user_id, // Subquery intereses
            $radius_km, // Filtro final de distancia
            $limit
        );
        
        $matches = $wpdb->get_results($query);
        
        // Enriquecer con intereses comunes
        foreach ($matches as &$match) {
            $match->shared_interests = self::get_shared_interests($user_id, $match->user_id);
        }
        
        return $matches;
    }
    
    /**
     * Calcula el bounding box para optimizar búsquedas geográficas
     */
    private static function calculate_bounding_box($lat, $lng, $radius_km) {
        $earth_radius = 6371; // km
        
        $lat_rad = deg2rad($lat);
        $lng_rad = deg2rad($lng);
        
        $angular_distance = $radius_km / $earth_radius;
        
        $min_lat = $lat - rad2deg($angular_distance);
        $max_lat = $lat + rad2deg($angular_distance);
        
        $delta_lng = rad2deg(asin(sin($angular_distance) / cos($lat_rad)));
        $min_lng = $lng - $delta_lng;
        $max_lng = $lng + $delta_lng;
        
        return [
            'min_lat' => $min_lat,
            'max_lat' => $max_lat,
            'min_lng' => $min_lng,
            'max_lng' => $max_lng
        ];
    }
    
    /**
     * Obtiene intereses compartidos entre dos usuarios
     */
    private static function get_shared_interests($user_id_1, $user_id_2) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT i.id, i.name, i.slug, i.icon, i.color
            FROM {$wpdb->prefix}interests i
            INNER JOIN {$wpdb->prefix}user_interests ui1 ON i.id = ui1.interest_id
            INNER JOIN {$wpdb->prefix}user_interests ui2 ON i.id = ui2.interest_id
            WHERE ui1.user_id = %d AND ui2.user_id = %d
        ", $user_id_1, $user_id_2));
    }
    
    /**
     * Obtiene mensajes de foro filtrados por interés y ubicación
     */
    public static function get_local_forum_messages($interest_id, $user_lat, $user_lng, $radius_km = 10, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $bounds = self::calculate_bounding_box($user_lat, $user_lng, $radius_km);
        
        $query = $wpdb->prepare("
            SELECT 
                fm.id,
                fm.content,
                fm.created_at,
                fm.latitude,
                fm.longitude,
                u.ID as user_id,
                u.display_name as author_name,
                (
                    6371 * acos(
                        cos(radians(%f)) * cos(radians(fm.latitude)) *
                        cos(radians(fm.longitude) - radians(%f)) +
                        sin(radians(%f)) * sin(radians(fm.latitude))
                    )
                ) AS distance_km
            FROM {$wpdb->prefix}forum_messages fm
            INNER JOIN {$wpdb->users} u ON fm.user_id = u.ID
            WHERE fm.interest_id = %d
                AND fm.latitude BETWEEN %f AND %f
                AND fm.longitude BETWEEN %f AND %f
            HAVING distance_km <= %f
            ORDER BY fm.created_at DESC
            LIMIT %d OFFSET %d
        ",
            $user_lat, $user_lng, $user_lat,
            $interest_id,
            $bounds['min_lat'], $bounds['max_lat'],
            $bounds['min_lng'], $bounds['max_lng'],
            $radius_km,
            $limit,
            $offset
        );
        
        return $wpdb->get_results($query);
    }
}
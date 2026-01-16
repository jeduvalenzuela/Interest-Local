<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function() {
    register_rest_route('geointerest/v1', '/system/radius', [
        'methods' => 'GET',
        'callback' => function() {
            $radius_km = get_option('interest_local_radius_km', 1);
            return [
                'radius_km' => floatval($radius_km),
                'radius_meters' => intval($radius_km * 1000)
            ];
        },
        'permission_callback' => '__return_true'
    ]);
});

<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function() {
    register_rest_route('geointerest/v1', '/user/forums', [
        'methods' => 'GET',
        'callback' => function($request) {
            global $wpdb;
            $user_id = isset($request['authenticated_user_id']) ? intval($request['authenticated_user_id']) : 0;
            if (!$user_id) return new WP_Error('no_user', 'Usuario no autenticado', ['status' => 401]);
            $forums = $wpdb->get_results($wpdb->prepare(
                "SELECT i.* FROM {$wpdb->prefix}interests i
                 INNER JOIN {$wpdb->prefix}user_interests ui ON i.id = ui.interest_id
                 WHERE ui.user_id = %d",
                $user_id
            ));
            return $forums;
        },
        'permission_callback' => 'geointerest_auth_middleware'
    ]);
});

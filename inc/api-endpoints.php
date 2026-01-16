<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', 'geointerest_register_endpoints');

function geointerest_register_endpoints() {
    $namespace = 'geointerest/v1';
    
    // Auth endpoints
    register_rest_route($namespace, '/auth/register', [
        'methods' => 'POST',
        'callback' => 'geointerest_register_user',
        'permission_callback' => '__return_true'
    ]);
    
    // NUEVO: Unified phone login endpoint
    register_rest_route($namespace, '/auth/phone', [
        'methods' => 'POST',
        'callback' => 'geointerest_phone_auth',
        'permission_callback' => '__return_true'
    ]);

    // Endpoint for token validation
    register_rest_route($namespace, '/auth/validate', [
        'methods' => 'POST',
        'callback' => 'geointerest_validate_token',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route($namespace, '/auth/login', [
        'methods' => 'POST',
        'callback' => 'geointerest_login_user',
        'permission_callback' => '__return_true'
    ]);

    // Endpoint de test para ver qué token se genera
    register_rest_route($namespace, '/auth/test-token', [
        'methods' => 'GET',
        'callback' => 'geointerest_test_token',
        'permission_callback' => '__return_true'
    ]);

    // Endpoint de diagnóstico para verificar la tabla
    register_rest_route($namespace, '/system/check-tables', [
        'methods' => 'GET',
        'callback' => 'geointerest_check_tables',
        'permission_callback' => '__return_true'
    ]);
    
    // User endpoints
    register_rest_route($namespace, '/user/location', [
        'methods' => 'POST',
        'callback' => 'geointerest_update_location',
        'permission_callback' => 'geointerest_auth_middleware'
    ]);
    
    register_rest_route($namespace, '/user/interests', [
        'methods' => 'GET',
        'callback' => 'geointerest_get_user_interests',
        'permission_callback' => 'geointerest_auth_middleware'
    ]);
    
    register_rest_route($namespace, '/user/interests', [
        'methods' => 'POST',
        'callback' => 'geointerest_update_user_interests',
        'permission_callback' => 'geointerest_auth_middleware'
    ]);

    // Endpoint to update profile (onboarding)
    register_rest_route($namespace, '/user/profile', [
        'methods' => 'POST',
        'callback' => 'geointerest_update_profile',
        'permission_callback' => function() {
            return GeoInterest_JWT::get_current_user_id() !== false;
        }
    ]);
    
    // Interests catalog
    register_rest_route($namespace, '/interests', [
        'methods' => 'GET',
        'callback' => 'geointerest_get_interests',
        'permission_callback' => '__return_true'
    ]);

        // Endpoint para crear intereses (solo usuarios autenticados, idealmente admin)
        register_rest_route($namespace, '/interests', [
            'methods' => 'POST',
            'callback' => 'geointerest_create_interest',
            'permission_callback' => function() {
                // Solo permitir a usuarios autenticados (puedes cambiar a is_admin si quieres solo admin)
                return GeoInterest_JWT::get_current_user_id() !== false;
            }
        ]);

    // Nearby interests endpoint (1km radius) - NUEVO
    register_rest_route($namespace, '/interests/nearby', [
        'methods' => 'GET',
        'callback' => 'geointerest_get_nearby_interests',
        'permission_callback' => '__return_true',
        'args' => [
            'latitude' => ['required' => true, 'type' => 'number'],
            'longitude' => ['required' => true, 'type' => 'number'],
            'radius' => ['required' => false, 'type' => 'number', 'default' => 1000], // 1km default
        ]
    ]);
    
    // Matching endpoint
    register_rest_route($namespace, '/matches', [
        'methods' => 'GET',
        'callback' => 'geointerest_get_matches',
        'permission_callback' => 'geointerest_auth_middleware'
    ]);
    
    // Forum endpoints
    register_rest_route($namespace, '/forum/(?P<interest_id>\d+)/messages', [
        'methods' => 'GET',
        'callback' => 'geointerest_get_forum_messages',
        'permission_callback' => 'geointerest_auth_middleware'
    ]);
    
    register_rest_route($namespace, '/forum/(?P<interest_id>\d+)/messages', [
        'methods' => 'POST',
        'callback' => 'geointerest_post_forum_message',
        'permission_callback' => 'geointerest_auth_middleware'
    ]);
    
    // Posts endpoints
    register_rest_route($namespace, '/posts/latest', [
        'methods' => 'GET',
        'callback' => 'geointerest_get_latest_posts',
        'permission_callback' => '__return_true'
    ]);
    
    register_rest_route($namespace, '/posts/user/(?P<user_id>\d+)', [
        'methods' => 'GET',
        'callback' => 'geointerest_get_user_posts',
        'permission_callback' => '__return_true'
    ]);
    
    register_rest_route($namespace, '/posts', [
        'methods' => 'POST',
        'callback' => 'geointerest_create_post',
        'permission_callback' => '__return_true'
    ]);
    
    // Users endpoints
    register_rest_route($namespace, '/users/latest', [
        'methods' => 'GET',
        'callback' => 'geointerest_get_latest_users',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route($namespace, '/users/nearby', [
        'methods' => 'GET',
        'callback' => 'geointerest_get_nearby_users',
        'permission_callback' => '__return_true',
        'args' => [
            'latitude' => ['required' => true, 'type' => 'number'],
            'longitude' => ['required' => true, 'type' => 'number'],
            'radius' => ['required' => false, 'type' => 'number', 'default' => 5000],
        ]
    ]);
    
    register_rest_route($namespace, '/users/(?P<user_id>\d+)', [
        'methods' => 'GET',
        'callback' => 'geointerest_get_user_profile',
        'permission_callback' => '__return_true'
    ]);

    // === ENDPOINT: Listar categorías de intereses ===
    register_rest_route('geointerest/v1', '/interest-categories', [
        'methods' => 'GET',
        'callback' => function() {
            global $wpdb;
            $table = $wpdb->prefix . 'interest_categories';
            $results = $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");
            return array_map(function($row) {
                return [
                    'id' => intval($row->id),
                    'name' => $row->name,
                    'slug' => $row->slug
                ];
            }, $results);
        },
        'permission_callback' => '__return_true',
    ]);
}

// === AUTH ENDPOINTS ===

// === CREAR INTERÉS DESDE FRONTEND ===
function geointerest_create_interest($request) {
    global $wpdb;
    $user_id = GeoInterest_JWT::get_current_user_id();
    if (!$user_id) {
        return new WP_Error('auth_error', 'No autorizado', ['status' => 401]);
    }

    $name = sanitize_text_field($request->get_param('name'));
    $icon = sanitize_text_field($request->get_param('icon'));
    $color = sanitize_text_field($request->get_param('color'));
    $category = sanitize_text_field($request->get_param('category'));

    if (empty($name)) {
        return new WP_Error('missing_name', 'El nombre del interés es obligatorio', ['status' => 400]);
    }

    // Evitar duplicados por nombre
    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}interests WHERE name = %s", $name));
    if ($exists) {
        return new WP_Error('duplicate_interest', 'Ya existe un interés con ese nombre', ['status' => 409]);
    }

    // Generar slug único
    $slug = sanitize_title($name);
    $slug_base = $slug;
    $i = 1;
    while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}interests WHERE slug = %s", $slug))) {
        $slug = $slug_base . '-' . $i;
        $i++;
    }

    $result = $wpdb->insert(
        $wpdb->prefix . 'interests',
        [
            'name' => $name,
            'slug' => $slug,
            'icon' => $icon ?: '⭐',
            'color' => $color ?: '#888',
            'category' => $category ?: '',
            'creator_id' => $user_id,
            'created_at' => current_time('mysql', 1)
        ],
        [
            '%s', '%s', '%s', '%s', '%s', '%d', '%s'
        ]
    );

    if ($result) {
        $id = $wpdb->insert_id;
        return [
            'success' => true,
            'interest' => [
                'id' => $id,
                'name' => $name,
                'slug' => $slug,
                'icon' => $icon ?: '⭐',
                'color' => $color ?: '#888',
                'category' => $category ?: '',
                'creator_id' => $user_id
            ]
        ];
    } else {
        return new WP_Error('db_error', 'No se pudo crear el interés', ['status' => 500]);
    }

}
function geointerest_register_user($request) {
    $email = sanitize_email($request->get_param('email'));
    $username = sanitize_user($request->get_param('username'));
    $password = $request->get_param('password');
    $display_name = sanitize_text_field($request->get_param('display_name'));
    
    if (empty($email) || empty($username) || empty($password)) {
        return new WP_Error('missing_fields', 'Faltan campos requeridos', ['status' => 400]);
    }
    
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        return $user_id;
    }
    
    wp_update_user([
        'ID' => $user_id,
        'display_name' => $display_name ?: $username
    ]);
    
    $token = GeoInterest_JWT::generate_token($user_id);
    
    return [
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user_id,
            'username' => $username,
            'email' => $email,
            'display_name' => $display_name ?: $username
        ]
    ];
}

function geointerest_login_user($request) {
    $username = sanitize_text_field($request->get_param('username'));
    $password = $request->get_param('password');
    
    $user = wp_authenticate($username, $password);
    
    if (is_wp_error($user)) {
        return new WP_Error('invalid_credentials', 'Credenciales inválidas', ['status' => 401]);
    }
    
    $token = GeoInterest_JWT::generate_token($user->ID);
    
    return [
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name
        ]
    ];
}

/**
 * NUEVO: Autenticación unificada por teléfono (sin contraseña)
 * Registra automáticamente si el número no existe
 */
function geointerest_phone_auth($request) {
    $phone = sanitize_text_field($request->get_param('phone'));

    if (empty($phone)) {
        return new WP_Error('missing_phone', 'El número de teléfono es obligatorio', ['status' => 400]);
    }

    // Format validation (8-15 digits)
    if (!preg_match('/^[0-9]{8,15}$/', $phone)) {
        return new WP_Error('invalid_phone', 'Formato de teléfono inválido', ['status' => 400]);
    }

    // Look for user by phone
    $users = get_users([
        'meta_key' => 'phone_number',
        'meta_value' => $phone,
        'number' => 1
    ]);

    if (empty($users)) {
        // AUTO REGISTRATION - Create unique username with full phone number
        $base_username = 'geo_' . $phone;  // geo_11123456789
        $username = $base_username;
        $counter = 1;
        
        // If the username already exists (very rare), add a number
        while (username_exists($username)) {
            $username = $base_username . '_' . $counter;
            $counter++;
        }
        
        $user_id = wp_create_user(
            $username, 
            wp_generate_password(20, true, true), 
            $username . '@geointerest.local'
        );

        if (is_wp_error($user_id)) {
            error_log('GeoInterest registration error: ' . $user_id->get_error_message());
            return new WP_Error('registration_failed', $user_id->get_error_message(), ['status' => 500]);
        }

        update_user_meta($user_id, 'phone_number', $phone);
        update_user_meta($user_id, 'first_login', current_time('mysql'));
        $user = get_user_by('id', $user_id);
        $is_new = true;
    } else {
        $user = $users[0];
        $is_new = false;
    }

    // ✅ Generate valid JWT (not base64)
    $token = GeoInterest_JWT::generate_token($user->ID);

    return [
        'success' => true,
        'token' => $token,
        'is_new_user' => $is_new,
        'user' => [
            'id' => $user->ID,
            'username' => $user->user_login,
            'phone' => $phone,
            'display_name' => $user->display_name ?: 'Usuario ' . substr($phone, -4)
        ]
    ];
}

/**
 * Validar token de sesión
 */
function geointerest_validate_token($request) {
    // Get token from Authorization header (new: JWT)
    // Or from parameter (legacy)
    $token = $request->get_param('token');
    
    if (empty($token)) {
        // Try to get from Authorization header
        $user_id = GeoInterest_JWT::get_current_user_id();
        if (!$user_id) {
            return new WP_Error('missing_token', 'Token requerido', ['status' => 401]);
        }
    } else {
        // Validate the JWT token provided as parameter
        $user_id = GeoInterest_JWT::validate_token($token);
        if (!$user_id) {
            return new WP_Error('invalid_token', 'Sesión inválida', ['status' => 401]);
        }
    }

    $user = get_user_by('id', $user_id);
    if (!$user) {
        return new WP_Error('user_not_found', 'Usuario no encontrado', ['status' => 404]);
    }

    $phone = get_user_meta($user->ID, 'phone_number', true);

    return [
        'valid' => true,
        'user' => [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'phone' => $phone,
            'display_name' => $user->display_name
        ]
    ];
}

/**
 * Update user profile (onboarding)
 */
function geointerest_update_profile($request) {
    // try eliminado, no se puede usar sin catch/finally en PHP
        // Get user_id from JWT
        $user_id = GeoInterest_JWT::get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error('unauthorized', 'No autenticado', ['status' => 401]);
        }

        // Get fields from request
        $display_name = sanitize_text_field($request->get_param('display_name'));
        $bio = sanitize_textarea_field($request->get_param('bio'));
        $phone = sanitize_text_field($request->get_param('phone'));
        $email = sanitize_email($request->get_param('email'));
        $address = sanitize_text_field($request->get_param('address'));
        $avatar_url = sanitize_url($request->get_param('avatar_url'));
        $instagram = sanitize_text_field($request->get_param('instagram'));
        $twitter = sanitize_text_field($request->get_param('twitter'));
        $facebook = sanitize_url($request->get_param('facebook'));
        $interests = $request->get_param('interests');

        // Validate name
        if (empty($display_name)) {
            return new WP_Error('empty_name', 'El nombre es requerido', ['status' => 400]);
        }

        // Update name in WordPress
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $display_name
        ]);

        // Update email if valid
        if (!empty($email)) {
            wp_update_user([
                'ID' => $user_id,
                'user_email' => $email
            ]);
        }

        // Save custom fields in user_meta
        if (!empty($bio)) {
            update_user_meta($user_id, 'bio', $bio);
        }
        
        if (!empty($phone)) {
            update_user_meta($user_id, 'phone_number', $phone);
        }
        
        if (!empty($address)) {
            update_user_meta($user_id, 'address', $address);
        }
        
        if (!empty($avatar_url)) {
            update_user_meta($user_id, 'avatar_url', $avatar_url);
        }
        
        if (!empty($instagram)) {
            update_user_meta($user_id, 'instagram', $instagram);
        }
        
        if (!empty($twitter)) {
            update_user_meta($user_id, 'twitter', $twitter);
        }
        
        if (!empty($facebook)) {
            update_user_meta($user_id, 'facebook', $facebook);
        }

        // Save interests
        if (is_array($interests) && !empty($interests)) {
            update_user_meta($user_id, 'user_interests', $interests);
        }

        return [
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
            'user_id' => $user_id
        ];
    // ...catch eliminado, no se puede usar sin try en PHP
}

// === USER ENDPOINTS ===

function geointerest_update_location($request) {
    global $wpdb;
    
    $user_id = $request->get_param('authenticated_user_id');
    $latitude_raw = $request->get_param('latitude');
    $longitude_raw = $request->get_param('longitude');
    $accuracy = floatval($request->get_param('accuracy'));

    // Accept 0.0 as valid coordinate; validate that they are numbers
    if (!is_numeric($latitude_raw) || !is_numeric($longitude_raw)) {
        return new WP_Error('invalid_location', 'Coordenadas inválidas', ['status' => 400]);
    }

    $latitude = floatval($latitude_raw);
    $longitude = floatval($longitude_raw);
    
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}user_locations WHERE user_id = %d",
        $user_id
    ));
    
    if ($exists) {
        $wpdb->update(
            $wpdb->prefix . 'user_locations',
            [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy
            ],
            ['user_id' => $user_id]
        );
    } else {
        $wpdb->insert(
            $wpdb->prefix . 'user_locations',
            [
                'user_id' => $user_id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy
            ]
        );
    }
    
    return ['success' => true];
}

function geointerest_get_user_interests($request) {
    global $wpdb;
    
    $user_id = $request->get_param('authenticated_user_id');
    // Intereses donde el usuario es miembro o creador
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}interests WHERE id IN (SELECT interest_id FROM {$wpdb->prefix}user_interests WHERE user_id = %d) OR creator_id = %d",
        $user_id, $user_id
    ));
    return array_map(function($row) {
        return [
            'id' => intval($row->id),
            'name' => $row->name,
            'slug' => $row->slug,
            'icon' => $row->icon,
            'color' => $row->color,
            'category' => isset($row->category) ? $row->category : '',
            'creator_id' => isset($row->creator_id) ? intval($row->creator_id) : null
        ];
    }, $results);
}

function geointerest_update_user_interests($request) {
    global $wpdb;
    
    $user_id = $request->get_param('authenticated_user_id');
    $interest_ids = $request->get_param('interest_ids');
    
    if (!is_array($interest_ids) || count($interest_ids) > 5) {
        return new WP_Error('invalid_interests', 'Máximo 5 intereses permitidos', ['status' => 400]);
    }
    
    // Remove current interests
    $wpdb->delete(
        $wpdb->prefix . 'user_interests',
        ['user_id' => $user_id]
    );
    
    // Insert new interests
    foreach ($interest_ids as $interest_id) {
        $wpdb->insert(
            $wpdb->prefix . 'user_interests',
            [
                'user_id' => $user_id,
                'interest_id' => intval($interest_id)
            ]
        );
    }
    
    return ['success' => true];
}

// === INTERESTS CATALOG ===

function geointerest_get_interests($request) {
    global $wpdb;
    
    $interests = $wpdb->get_results("
        SELECT id, name, slug, icon, color
        FROM {$wpdb->prefix}interests
        ORDER BY name ASC
    ");
    
    return $interests;
}

// Get nearby interests with member count (1km radius) - NUEVO
function geointerest_get_nearby_interests($request) {
    global $wpdb;
    
    $latitude = floatval($request->get_param('latitude'));
    $longitude = floatval($request->get_param('longitude'));
    $radius = intval($request->get_param('radius'));
    if (!$radius) {
        $radius_km = get_option('interest_local_radius_km', 1);
        $radius = intval($radius_km * 1000); // Convertir a metros
    }
    
    if (empty($latitude) || empty($longitude)) {
        return new WP_Error('missing_location', 'Latitude and longitude are required', ['status' => 400]);
    }
    
    // Use haversine formula to calculate distance
    // and get nearby interests with member count
    $query = $wpdb->prepare("
        SELECT 
            i.id,
            i.name,
            i.slug,
            i.icon,
            i.color,
            COUNT(DISTINCT um.user_id) as member_count,
            (
                6371000 * ACOS(
                    COS(RADIANS(%f)) * 
                    COS(RADIANS(um.latitude)) * 
                    COS(RADIANS(%f) - RADIANS(um.longitude)) + 
                    SIN(RADIANS(%f)) * 
                    SIN(RADIANS(um.latitude))
                )
            ) as distance
        FROM {$wpdb->prefix}interests i
        LEFT JOIN {$wpdb->prefix}user_interests ui ON i.id = ui.interest_id
        LEFT JOIN {$wpdb->prefix}user_meta um ON ui.user_id = um.user_id 
            AND um.meta_key IN ('latitude', 'longitude')
        WHERE (
            6371000 * ACOS(
                COS(RADIANS(%f)) * 
                COS(RADIANS(um.latitude)) * 
                COS(RADIANS(%f) - RADIANS(um.longitude)) + 
                SIN(RADIANS(%f)) * 
                SIN(RADIANS(um.latitude))
            )
        ) <= %d
        OR um.latitude IS NULL
        GROUP BY i.id
        ORDER BY member_count DESC, i.name ASC
    ", $latitude, $longitude, $latitude, $latitude, $longitude, $latitude, $radius);
    
    $nearby_interests = $wpdb->get_results($query);
    
    // If query fails, return all interests with 0 distance
    if ($wpdb->last_error) {
        $interests = $wpdb->get_results("
            SELECT id, name, slug, icon, color, 0 as member_count, 0 as distance
            FROM {$wpdb->prefix}interests
            ORDER BY name ASC
        ");
        return $interests;
    }
    
    return $nearby_interests ?: [];
}

// === MATCHING ENDPOINT ===

function geointerest_get_matches($request) {
    $user_id = $request->get_param('authenticated_user_id');
    $radius = intval($request->get_param('radius')) ?: 10;
    $limit = intval($request->get_param('limit')) ?: 50;
    
    $matches = GeoInterest_Matching_Engine::find_matches($user_id, $radius, $limit);
    
    return $matches;
}

// === FORUM ENDPOINTS ===

function geointerest_get_forum_messages($request) {
    global $wpdb;
    
    $user_id = $request->get_param('authenticated_user_id');
    $interest_id = intval($request->get_param('interest_id'));
    $radius = intval($request->get_param('radius')) ?: 10;
    $limit = intval($request->get_param('limit')) ?: 50;
    $offset = intval($request->get_param('offset')) ?: 0;
    
    // Get user location
    $user_location = $wpdb->get_row($wpdb->prepare(
        "SELECT latitude, longitude FROM {$wpdb->prefix}user_locations WHERE user_id = %d",
        $user_id
    ));

    if (!$user_location) {
        return new WP_Error('no_location', 'Usuario sin ubicación', ['status' => 400]);
    }

    // Registrar acceso del usuario al foro (solo si tiene ubicación válida)
    // Si no existe el registro en user_interests, lo crea
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}user_interests WHERE user_id = %d AND interest_id = %d",
        $user_id, $interest_id
    ));
    if (!$exists) {
        $wpdb->insert(
            $wpdb->prefix . 'user_interests',
            [
                'user_id' => $user_id,
                'interest_id' => $interest_id,
                'created_at' => current_time('mysql', 1)
            ]
        );
    }

    $messages = GeoInterest_Matching_Engine::get_local_forum_messages(
        $interest_id,
        $user_location->latitude,
        $user_location->longitude,
        $radius,
        $limit,
        $offset
    );

    return $messages;
}

function geointerest_post_forum_message($request) {
    global $wpdb;
    
    $user_id = $request->get_param('authenticated_user_id');
    $interest_id = intval($request->get_param('interest_id'));
    $content = sanitize_textarea_field($request->get_param('content'));
    
    if (empty($content)) {
        return new WP_Error('empty_content', 'El mensaje no puede estar vacío', ['status' => 400]);
    }
    
    // Obtener ubicación del usuario
    $user_location = $wpdb->get_row($wpdb->prepare(
        "SELECT latitude, longitude FROM {$wpdb->prefix}user_locations WHERE user_id = %d",
        $user_id
    ));
    
    if (!$user_location) {
        return new WP_Error('no_location', 'Usuario sin ubicación', ['status' => 400]);
    }
    
    $wpdb->insert(
        $wpdb->prefix . 'forum_messages',
        [
            'user_id' => $user_id,
            'interest_id' => $interest_id,
            'content' => $content,
            'latitude' => $user_location->latitude,
            'longitude' => $user_location->longitude
        ]
    );

    // Registrar participación en user_interests si no existe
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}user_interests WHERE user_id = %d AND interest_id = %d",
        $user_id, $interest_id
    ));
    if (!$exists) {
        $wpdb->insert(
            $wpdb->prefix . 'user_interests',
            [
                'user_id' => $user_id,
                'interest_id' => $interest_id,
                'created_at' => current_time('mysql', 1)
            ]
        );
    }

    $message_id = $wpdb->insert_id;

    return [
        'success' => true,
        'message_id' => $message_id
    ];
}

// === POSTS ENDPOINTS ===

function geointerest_get_latest_users($request) {
    $limit = intval($request->get_param('limit')) ?: 10;
    
    $users = get_users([
        'orderby' => 'user_registered',
        'order' => 'DESC',
        'number' => $limit,
    ]);
    
    $result = [];
    foreach ($users as $user) {
        $result[] = geointerest_get_user_profile_data($user->ID);
    }
    
    return $result;
}

function geointerest_get_user_profile($request) {
    $user_id = intval($request->get_param('user_id'));
    
    if (!$user_id) {
        return new WP_Error('invalid_user', 'ID de usuario inválido', ['status' => 400]);
    }
    
    $user = get_user_by('id', $user_id);
    
    if (!$user) {
        return new WP_Error('user_not_found', 'Usuario no encontrado', ['status' => 404]);
    }
    
    global $wpdb;
    
    // Obtener posts del usuario
    $posts = $wpdb->get_results($wpdb->prepare(
        "SELECT id, content, image_url, created_at FROM {$wpdb->prefix}user_posts WHERE user_id = %d ORDER BY created_at DESC",
        $user_id
    ));
    
    return [
        'id' => $user->ID,
        'username' => $user->user_login,
        'email' => $user->user_email,
        'display_name' => $user->display_name ?: $user->user_login,
        'created_at' => $user->user_registered,
        'avatar_url' => get_avatar_url($user->ID),
        'posts' => $posts ?: []
    ];
}

function geointerest_get_user_posts($request) {
    global $wpdb;
    
    $user_id = intval($request->get_param('user_id'));
    $limit = intval($request->get_param('limit')) ?: 50;
    
    if (!$user_id) {
        return new WP_Error('invalid_user', 'ID de usuario inválido', ['status' => 400]);
    }
    
    $posts = $wpdb->get_results($wpdb->prepare(
        "SELECT id, content, image_url, created_at FROM {$wpdb->prefix}user_posts WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
        $user_id,
        $limit
    ));
    
    return $posts ?: [];
}

function geointerest_get_latest_posts($request) {
    global $wpdb;
    
    $limit = intval($request->get_param('limit')) ?: 50;
    
    $posts = $wpdb->get_results($wpdb->prepare(
        "SELECT p.id, p.user_id, p.content, p.image_url, p.created_at, u.display_name, u.user_login
         FROM {$wpdb->prefix}user_posts p
         INNER JOIN {$wpdb->users} u ON p.user_id = u.ID
         ORDER BY p.created_at DESC
         LIMIT %d",
        $limit
    ));
    
    foreach ($posts as $post) {
        $post->avatar_url = get_avatar_url($post->user_id);
    }
    
    return $posts ?: [];
}

function geointerest_create_post($request) {
    global $wpdb;
    
    // try eliminado, no se puede usar sin catch/finally en PHP
        // Obtener el user_id del token JWT
        $user_id = GeoInterest_JWT::get_current_user_id();
        error_log('GeoInterest: get_current_user_id returned: ' . var_export($user_id, true));
        
        // Si no hay user_id válido del JWT, retornar error
        if (!$user_id) {
            error_log('GeoInterest: No valid user_id in JWT token');
            return new WP_Error('unauthorized', 'Token inválido o expirado', ['status' => 401]);
        }
        
        $content = sanitize_textarea_field($request->get_param('content'));
        $image_url = sanitize_url($request->get_param('image_url'));
        
        error_log('GeoInterest: Creating post for user_id=' . $user_id . ', content_length=' . strlen($content));
        
        if (empty($content)) {
            return new WP_Error('empty_content', 'El contenido del post no puede estar vacío', ['status' => 400]);
        }
        
        // Verificar que la tabla existe
        $table_name = $wpdb->prefix . 'user_posts';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        
        if (!$table_exists) {
            error_log('GeoInterest: Table ' . $table_name . ' does not exist!');
            return new WP_Error('table_not_found', 'Tabla de posts no existe', ['status' => 500]);
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'user_posts',
            [
                'user_id' => $user_id,
                'content' => $content,
                'image_url' => $image_url ?: NULL
            ]
        );
        
        if (!$result) {
            $error_msg = 'GeoInterest: Database insert failed - ' . $wpdb->last_error;
            error_log($error_msg);
            return new WP_Error('db_error', $error_msg, ['status' => 500]);
        }
        
        $post_id = $wpdb->insert_id;
        $post = $wpdb->get_row($wpdb->prepare(
            "SELECT id, content, image_url, created_at FROM {$wpdb->prefix}user_posts WHERE id = %d",
            $post_id
        ));
        
        return [
            'success' => true,
            'post_id' => $post_id,
            'post' => $post
        ];
    // ...catch eliminado, no se puede usar sin try en PHP
}

// Función de test para ver qué token se genera
function geointerest_test_token() {
    $user_id = 1; // Admin user para test
    $token = GeoInterest_JWT::generate_token($user_id);
    
    return [
        'user_id' => $user_id,
        'token' => $token,
        'token_length' => strlen($token),
        'is_jwt' => (substr_count($token, '.') === 2) ? 'SÍ' : 'NO - Token no es un JWT válido',
    ];
}

// Función para verificar y crear tablas si no existen
function geointerest_check_tables() {
    global $wpdb;
    
    $tables_status = [];
    
    // Verificar tabla user_posts
    $table_name = $wpdb->prefix . 'user_posts';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    
    $tables_status['user_posts'] = [
        'exists' => (bool)$table_exists,
        'table_name' => $table_name
    ];
    
    // Si no existe, crearla
    if (!$table_exists) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = geointerest_create_tables();
        $tables_status['user_posts']['created'] = true;
        $tables_status['user_posts']['result'] = var_export($result, true);
        
        // Verificar de nuevo
        $table_exists_now = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        $tables_status['user_posts']['exists_after_creation'] = (bool)$table_exists_now;
    }
    
    // Si existe, mostrar estructura
    if ($table_exists) {
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        $tables_status['user_posts']['columns'] = array_map(function($col) {
            return [
                'name' => $col->Field,
                'type' => $col->Type,
                'null' => $col->Null,
                'key' => $col->Key
            ];
        }, $columns);
    }
    
    return [
        'status' => 'OK',
        'tables' => $tables_status,
        'message' => 'All tables checked'
    ];
}

/**
 * Calcula la distancia entre dos coordenadas usando la fórmula de Haversine
 * @param float $lat1 Latitud del punto 1
 * @param float $lon1 Longitud del punto 1
 * @param float $lat2 Latitud del punto 2
 * @param float $lon2 Longitud del punto 2
 * @return float Distancia en metros
 */
function geointerest_haversine_distance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371000; // Radio de la Tierra en metros
    
    $lat1_rad = deg2rad($lat1);
    $lon1_rad = deg2rad($lon1);
    $lat2_rad = deg2rad($lat2);
    $lon2_rad = deg2rad($lon2);
    
    $d_lat = $lat2_rad - $lat1_rad;
    $d_lon = $lon2_rad - $lon1_rad;
    
    $a = sin($d_lat / 2) * sin($d_lat / 2) +
         cos($lat1_rad) * cos($lat2_rad) *
         sin($d_lon / 2) * sin($d_lon / 2);
    
    $c = 2 * asin(sqrt($a));
    
    return $earth_radius * $c;
}

/**
 * Obtiene usuarios cercanos (dentro de un radio) con intereses coincidentes
 * GET /users/nearby?latitude=40.7128&longitude=-74.0060&radius=5000
 */
function geointerest_get_nearby_users($request) {
    global $wpdb;
    
        $latitude = $request->get_param('latitude');
        $latitude = floatval($request->get_param('latitude'));
        $longitude = floatval($request->get_param('longitude'));
        $radius = intval($request->get_param('radius'));
        if (!$radius) {
            $radius_km = get_option('interest_local_radius_km', 1);
            $radius = intval($radius_km * 1000); // Convertir a metros
        }

        if (empty($latitude) || empty($longitude)) {
            return new WP_Error('missing_location', 'Latitude and longitude are required', ['status' => 400]);
        }

        $zone_mode = get_option('interest_local_zone_mode', 'anchored');

        // Si el modo es "anchored", la zona es fija (por ahora, la ubicación del usuario)
        // Si el modo es "dynamic", la zona es la media de los usuarios participantes en cada foro

        if ($zone_mode === 'anchored') {
            $query = $wpdb->prepare("
                SELECT 
                    i.id,
                    i.name,
                    i.slug,
                    i.icon,
                    i.color,
                    COUNT(DISTINCT ul.user_id) as member_count,
                    (
                        6371000 * ACOS(
                            COS(RADIANS(%f)) * 
                            COS(RADIANS(ul.latitude)) * 
                            COS(RADIANS(%f) - RADIANS(ul.longitude)) + 
                            SIN(RADIANS(%f)) * 
                            SIN(RADIANS(ul.latitude))
                        )
                    ) as distance
                FROM {$wpdb->prefix}interests i
                LEFT JOIN {$wpdb->prefix}user_interests ui ON i.id = ui.interest_id
                LEFT JOIN {$wpdb->prefix}user_locations ul ON ui.user_id = ul.user_id
                WHERE (
                    6371000 * ACOS(
                        COS(RADIANS(%f)) * 
                        COS(RADIANS(ul.latitude)) * 
                        COS(RADIANS(%f) - RADIANS(ul.longitude)) + 
                        SIN(RADIANS(%f)) * 
                        SIN(RADIANS(ul.latitude))
                    )
                ) <= %d
                OR ul.latitude IS NULL
                GROUP BY i.id
                ORDER BY member_count DESC, i.name ASC
            ", $latitude, $longitude, $latitude, $latitude, $longitude, $latitude, $radius);
            $nearby_interests = $wpdb->get_results($query);
        } else {
            // Modo dinámico: calcular la "zona" de cada foro como la media de los usuarios participantes
            $interests = $wpdb->get_results("SELECT id, name, slug, icon, color FROM {$wpdb->prefix}interests");
            $result = [];
            foreach ($interests as $interest) {
                // Obtener ubicaciones de los usuarios participantes en este foro
                $locations = $wpdb->get_results($wpdb->prepare(
                    "SELECT ul.latitude, ul.longitude FROM {$wpdb->prefix}user_interests ui
                     INNER JOIN {$wpdb->prefix}user_locations ul ON ui.user_id = ul.user_id
                     WHERE ui.interest_id = %d AND ul.latitude IS NOT NULL AND ul.longitude IS NOT NULL",
                    $interest->id
                ));
                $member_count = count($locations);
                if ($member_count === 0) continue;
                // Calcular centroide (media de latitudes y longitudes)
                $lat_sum = 0; $lng_sum = 0;
                foreach ($locations as $loc) {
                    $lat_sum += floatval($loc->latitude);
                    $lng_sum += floatval($loc->longitude);
                }
                $centroid_lat = $lat_sum / $member_count;
                $centroid_lng = $lng_sum / $member_count;
                // Calcular distancia entre el usuario y el centroide
                $distance = 6371000 * acos(
                    cos(deg2rad($latitude)) * cos(deg2rad($centroid_lat)) *
                    cos(deg2rad($centroid_lng) - deg2rad($longitude)) +
                    sin(deg2rad($latitude)) * sin(deg2rad($centroid_lat))
                );
                if ($distance <= $radius) {
                    $result[] = (object) [
                        'id' => $interest->id,
                        'name' => $interest->name,
                        'slug' => $interest->slug,
                        'icon' => $interest->icon,
                        'color' => $interest->color,
                        'member_count' => $member_count,
                        'distance' => $distance
                    ];
                }
            }
            // Ordenar por cantidad de miembros y nombre
            usort($result, function($a, $b) {
                return ($b->member_count <=> $a->member_count) ?: strcmp($a->name, $b->name);
            });
            $nearby_interests = $result;
        }
    }

/**
 * Helper para obtener datos del perfil de un usuario
 */
function geointerest_get_user_profile_data($user_id) {
    global $wpdb;
    
    $user = get_userdata($user_id);
    
    if (!$user) {
        return null;
    }
    
    // Obtener intereses
    $user_interests = [];
    $interest_ids = get_user_meta($user_id, 'interests', true);
    
    if (!empty($interest_ids)) {
        if (!is_array($interest_ids)) {
            $interest_ids = [$interest_ids];
        }
        
        $table = $wpdb->prefix . 'interests';
        
        // Filtrar IDs para asegurar que son números
        $interest_ids = array_map('intval', $interest_ids);
        
        // Construir la query de forma segura
        $query = "SELECT id, name FROM $table WHERE id IN (" . implode(',', $interest_ids) . ")";
        $interests = $wpdb->get_results($query);
        
        if (!empty($interests)) {
            $user_interests = array_map(function($interest) {
                return [
                    'id' => (int)$interest->id,
                    'name' => $interest->name
                ];
            }, $interests);
        }
    }
    
    // Obtener conteo de posts
    $posts_count = 0;
    $user_posts_table = $wpdb->prefix . 'user_posts';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$user_posts_table'");
    
    if ($table_exists) {
        $posts_count = intval($wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $user_posts_table WHERE user_id = %d",
                $user_id
            )
        ));
    }
    
    return [
        'success' => true,
        'count' => count($nearby_interests),
        'search_center' => [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ],
        'radius_meters' => $radius,
        'radius_km' => round($radius / 1000, 2),
        'interests' => $nearby_interests
    ];
}
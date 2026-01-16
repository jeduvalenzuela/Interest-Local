<?php
if (!defined('ABSPATH')) exit;

function geointerest_create_tables() {
        // Tabla de categorías de intereses
        $sql_categories = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}interest_categories (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY name_idx (name)
        ) $charset_collate;";

    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // User Locations
    $sql_locations = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}user_locations (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        accuracy FLOAT DEFAULT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_id (user_id),
        KEY lat_lng_idx (latitude, longitude)
    ) $charset_collate;";
    
    // Interests
    $sql_interests = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}interests (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        icon VARCHAR(50) DEFAULT NULL,
        color VARCHAR(7) DEFAULT NULL,
        category VARCHAR(50) DEFAULT NULL,
        creator_id BIGINT(20) UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY name_idx (name),
        KEY creator_idx (creator_id)
    ) $charset_collate;";
    
    // User Interests
    $sql_user_interests = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}user_interests (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        interest_id BIGINT(20) UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_interest (user_id, interest_id),
        KEY user_id_idx (user_id),
        KEY interest_id_idx (interest_id)
    ) $charset_collate;";
    
    // Forum Messages
    $sql_messages = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}forum_messages (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        interest_id BIGINT(20) UNSIGNED NOT NULL,
        content TEXT NOT NULL,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id_idx (user_id),
        KEY interest_id_idx (interest_id),
        KEY created_at_idx (created_at),
        KEY lat_lng_idx (latitude, longitude)
    ) $charset_collate;";
    
    // JWT Tokens
    $sql_tokens = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}user_tokens (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        token_hash VARCHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id_idx (user_id),
        KEY token_hash_idx (token_hash),
        KEY expires_at_idx (expires_at)
    ) $charset_collate;";
    
    // Posts de usuarios
    $sql_posts = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}user_posts (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        content TEXT NOT NULL,
        image_url VARCHAR(255) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id_idx (user_id),
        KEY created_at_idx (created_at)
    ) $charset_collate;";
    
    dbDelta($sql_locations);
    dbDelta($sql_interests);
    dbDelta($sql_user_interests);
    dbDelta($sql_messages);
    dbDelta($sql_tokens);
    dbDelta($sql_posts);
    dbDelta($sql_categories);
    
    // Seed inicial de intereses
    geointerest_seed_interests();
}

function geointerest_seed_interests() {
    global $wpdb;
    $table = $wpdb->prefix . 'interests';
    
    $interests = [
        ['name' => 'Deportes', 'slug' => 'deportes', 'icon' => '⚽', 'color' => '#FF5733'],
        ['name' => 'Tecnología', 'slug' => 'tecnologia', 'icon' => '💻', 'color' => '#3498DB'],
        ['name' => 'Música', 'slug' => 'musica', 'icon' => '🎵', 'color' => '#9B59B6'],
        ['name' => 'Arte', 'slug' => 'arte', 'icon' => '🎨', 'color' => '#E74C3C'],
        ['name' => 'Gastronomía', 'slug' => 'gastronomia', 'icon' => '🍕', 'color' => '#F39C12'],
        ['name' => 'Viajes', 'slug' => 'viajes', 'icon' => '✈️', 'color' => '#1ABC9C'],
        ['name' => 'Lectura', 'slug' => 'lectura', 'icon' => '📚', 'color' => '#34495E'],
        ['name' => 'Cine', 'slug' => 'cine', 'icon' => '🎬', 'color' => '#E67E22'],
    ];
    
    foreach ($interests as $interest) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE slug = %s",
            $interest['slug']
        ));
        
        if (!$exists) {
            $wpdb->insert($table, $interest);
        }
    }
}
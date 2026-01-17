<?php
/**
 * Theme Name: Interest Local
 * Description: Hyper-local social platform
 * Version: 1.0.0
 */


// Prevent direct access
if (!defined('ABSPATH')) exit;

/**
 * ============================
 * Theme Constants
 * ============================
 */
define('GEOINTEREST_VERSION', '1.0.34');
define('GEOINTEREST_INC', get_template_directory() . '/inc/');

/**
 * Create custom table for forum messages on theme activation
 */
register_activation_hook(__FILE__, function() {
    global $wpdb;
    $table = $wpdb->prefix . 'forum_messages';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        interest_id BIGINT UNSIGNED NOT NULL,
        author_id BIGINT UNSIGNED NOT NULL,
        author_name VARCHAR(100) NOT NULL,
        author_avatar VARCHAR(255) DEFAULT '',
        content TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(20) NOT NULL DEFAULT 'approved',
        report_count INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});


/**
 * Add admin menu for moderating forum messages and managing interest categories
 */
add_action('admin_menu', function() {
    global $wpdb;
    // Forum Messages
    add_menu_page(
        'Forum Messages',
        'Forum Messages',
        'manage_options',
        'forum-messages',
        function() use ($wpdb) {
            $table = $wpdb->prefix . 'forum_messages';
            $messages = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 100");
            echo '<div class="wrap"><h1>Forum Messages</h1>';
            echo '<table class="widefat"><thead><tr><th>ID</th><th>Interest</th><th>Author</th><th>Content</th><th>Date</th><th>Status</th><th>Reports</th><th>Actions</th></tr></thead><tbody>';
            foreach ($messages as $msg) {
                echo '<tr>';
                echo '<td>' . esc_html($msg->id) . '</td>';
                echo '<td>' . esc_html($msg->interest_id) . '</td>';
                // author_name
                $author_name = isset($msg->author_name) ? $msg->author_name : (isset($msg->user_id) ? $msg->user_id : '');
                echo '<td>' . esc_html($author_name) . '</td>';
                echo '<td>' . esc_html($msg->content) . '</td>';
                echo '<td>' . esc_html($msg->created_at) . '</td>';
                // status
                $status = isset($msg->status) ? $msg->status : '';
                echo '<td>' . esc_html($status) . '</td>';
                // report_count
                $report_count = isset($msg->report_count) ? $msg->report_count : '';
                echo '<td>' . esc_html($report_count) . '</td>';
                echo '<td><a href="?page=forum-messages&delete=' . esc_attr($msg->id) . '" onclick="return confirm(\'Delete this message?\')">Delete</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
            // Handle deletion
            if (isset($_GET['delete'])) {
                $del_id = intval($_GET['delete']);
                $wpdb->delete($table, ['id' => $del_id]);
                echo '<script>location.href="?page=forum-messages";</script>';
            }
        },
        'dashicons-admin-comments',
        6
    );

    // Interest Categories (nueva tabla)
    add_menu_page(
        'Interest Categories',
        'Interest Categories',
        'manage_options',
        'interest-categories',
        function() use ($wpdb) {
            $cat_table = $wpdb->prefix . 'interest_categories';
            // Crear nueva categoría
            if (isset($_POST['new_category'])) {
                $new_cat = sanitize_text_field($_POST['new_category_name']);
                $slug = sanitize_title($new_cat);
                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $cat_table WHERE slug = %s", $slug));
                if (!$exists) {
                    $wpdb->insert($cat_table, [
                        'name' => $new_cat,
                        'slug' => $slug,
                        'created_at' => current_time('mysql', 1)
                    ]);
                    echo '<div class="updated"><p>Category created!</p></div>';
                } else {
                    echo '<div class="error"><p>Category already exists!</p></div>';
                }
            }
            // Editar nombre de categoría
            if (isset($_POST['update_category'])) {
                $id = intval($_POST['category_id']);
                $name = sanitize_text_field($_POST['category_name']);
                $slug = sanitize_title($name);
                $wpdb->update($cat_table, [ 'name' => $name, 'slug' => $slug ], [ 'id' => $id ]);
                echo '<div class="updated"><p>Category updated!</p></div>';
            }
            // Listar categorías
            $categories = $wpdb->get_results("SELECT * FROM $cat_table ORDER BY name ASC");
            echo '<div class="wrap"><h1>Interest Categories</h1>';
            echo '<form method="post"><h2>Create New Category</h2>';
            echo '<input type="text" name="new_category_name" placeholder="Category name" required />';
            echo '<button type="submit" name="new_category">Create Category</button></form>';
            echo '<h2>Edit Categories</h2>';
            echo '<table class="widefat"><thead><tr><th>ID</th><th>Name</th><th>Slug</th><th>Edit</th></tr></thead><tbody>';
            foreach ($categories as $cat) {
                echo '<tr>';
                echo '<td>' . esc_html($cat->id) . '</td>';
                echo '<td>' . esc_html($cat->name) . '</td>';
                echo '<td>' . esc_html($cat->slug) . '</td>';
                echo '<td>';
                echo '<form method="post" style="display:inline-block;">';
                echo '<input type="hidden" name="category_id" value="' . esc_attr($cat->id) . '" />';
                echo '<input type="text" name="category_name" value="' . esc_attr($cat->name) . '" />';
                echo '<button type="submit" name="update_category">Update</button>';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        },
        'dashicons-category',
        7
    );

    // Interest Forums (tabla custom)
    add_menu_page(
        'Interest Forums',
        'Interest Forums',
        'manage_options',
        'interest-forums',
        function() use ($wpdb) {
            $forums_table = $wpdb->prefix . 'interests';
            // Editar foro
            if (isset($_POST['update_forum'])) {
                $id = intval($_POST['forum_id']);
                $name = sanitize_text_field($_POST['forum_name']);
                $icon = sanitize_text_field($_POST['forum_icon']);
                $color = sanitize_text_field($_POST['forum_color']);
                $category = sanitize_text_field($_POST['forum_category']);
                $wpdb->update($forums_table, [
                    'name' => $name,
                    'icon' => $icon,
                    'color' => $color,
                    'category' => $category
                ], [ 'id' => $id ]);
                echo '<div class="updated"><p>Forum updated!</p></div>';
            }
            // Eliminar foro
            if (isset($_POST['delete_forum'])) {
                $id = intval($_POST['forum_id']);
                $wpdb->delete($forums_table, [ 'id' => $id ]);
                echo '<div class="updated"><p>Forum deleted!</p></div>';
            }
            // Listar foros
            $forums = $wpdb->get_results("SELECT * FROM $forums_table ORDER BY created_at DESC LIMIT 100");
            echo '<div class="wrap"><h1>Interest Forums</h1>';
            echo '<table class="widefat"><thead><tr><th>ID</th><th>Name</th><th>Icon</th><th>Color</th><th>Category</th><th>Actions</th></tr></thead><tbody>';
            foreach ($forums as $forum) {
                echo '<tr>';
                echo '<td>' . esc_html($forum->id) . '</td>';
                echo '<td>';
                echo '<form method="post" style="display:inline-block;">';
                echo '<input type="hidden" name="forum_id" value="' . esc_attr($forum->id) . '" />';
                echo '<input type="text" name="forum_name" value="' . esc_attr($forum->name) . '" style="width:120px;" /> ';
                echo '<input type="text" name="forum_icon" value="' . esc_attr($forum->icon) . '" style="width:40px;" maxlength="2" /> ';
                echo '<input type="color" name="forum_color" value="' . esc_attr($forum->color) . '" style="width:40px;" /> ';
                echo '<input type="text" name="forum_category" value="' . esc_attr($forum->category) . '" style="width:100px;" /> ';
                echo '<button type="submit" name="update_forum">Update</button> ';
                echo '<button type="submit" name="delete_forum" onclick="return confirm(\'Delete this forum?\')">Delete</button>';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        },
        'dashicons-groups',
        8
    );

    // Opciones de configuración del sistema
    add_options_page(
        'Interest Local Settings',
        'Interest Local Settings',
        'manage_options',
        'interest-local-settings',
        function() {
            // Guardar configuración
            if (isset($_POST['save_settings'])) {
                $radius_km = floatval($_POST['radius_km']);
                $zone_mode = isset($_POST['zone_mode']) ? sanitize_text_field($_POST['zone_mode']) : 'anchored';
                update_option('interest_local_radius_km', $radius_km);
                update_option('interest_local_zone_mode', $zone_mode);
                echo '<div class="updated"><p>Configuración guardada.</p></div>';
            }
            $radius_km = get_option('interest_local_radius_km', 1);
            $zone_mode = get_option('interest_local_zone_mode', 'anchored');
            echo '<div class="wrap"><h1>Interest Local Settings</h1>';
            echo '<form method="post">';
            echo '<label for="radius_km"><strong>Radio de búsqueda (km):</strong></label><br />';
            echo '<input type="number" step="0.1" min="0.1" name="radius_km" id="radius_km" value="' . esc_attr($radius_km) . '" style="width:100px;" />';
            echo '<p>Determina el radio en kilómetros para mostrar foros e intereses cercanos.</p>';
            echo '<label for="zone_mode"><strong>Modo de zona de intereses:</strong></label><br />';
            echo '<select name="zone_mode" id="zone_mode">';
            echo '<option value="anchored"' . ($zone_mode === 'anchored' ? ' selected' : '') . '>Zona anclada (fija)</option>';
            echo '<option value="dynamic"' . ($zone_mode === 'dynamic' ? ' selected' : '') . '>Zona dinámica (se mueve con los usuarios)</option>';
            echo '</select>';
            echo '<p>Zona anclada: el foro/interés permanece en la ubicación original.<br>Zona dinámica: la zona se actualiza según los usuarios que participan.</p>';
            echo '<button type="submit" name="save_settings" class="button button-primary">Guardar</button>';
            echo '</form>';
            echo '</div>';
        }
    );
});

/**
 * ============================
 * CORS (decoupled development)
 * ============================
 */
add_action('rest_api_init', function () {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');

    add_filter('rest_pre_serve_request', function ($value) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
        header('Access-Control-Allow-Credentials: true');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            status_header(200);
            exit;
        }

        return $value;
    });
}, 15);

/**
 * ============================
 * Load Dependencies
 * ============================
 */
require_once GEOINTEREST_INC . 'database.php';
require_once GEOINTEREST_INC . 'jwt-auth.php';
require_once GEOINTEREST_INC . 'matching-engine.php';
require_once GEOINTEREST_INC . 'api-endpoints.php';

require_once GEOINTEREST_INC . 'helpers.php';
require_once GEOINTEREST_INC . 'admin-posts.php';
require_once GEOINTEREST_INC . 'admin-users.php';
require_once GEOINTEREST_INC . 'api-endpoints-extra.php';
require_once GEOINTEREST_INC . 'api-endpoints-user-forums.php';


/**
 * ============================
 * Theme Activation
 * ============================
 */
add_action('after_switch_theme', function () {
    geointerest_create_tables();
    flush_rewrite_rules();
});

/**
 * ============================
 * Enqueue React (Vite build)
 * ============================
 */
add_action('wp_enqueue_scripts', function () {
    if (is_admin()) return;

    // Remove WordPress bloat
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('classic-theme-styles');

    $theme_url = str_replace('http://', 'https://', get_template_directory_uri());

    wp_enqueue_script(
        'geointerest-app',
        $theme_url . '/build/index.js',
        [],
        GEOINTEREST_VERSION,
        true
    );

    wp_enqueue_style(
        'geointerest-styles',
        $theme_url . '/build/index.css',
        [],
        GEOINTEREST_VERSION
    );

    // ✅ Ensure URLs are HTTPS (replace http:// with https://)
    $api_url = str_replace('http://', 'https://', rest_url('geointerest/v1/'));
    $site_url = str_replace('http://', 'https://', get_site_url());
    $home_url = str_replace('http://', 'https://', get_home_url());
    $wp_api_url = str_replace('http://', 'https://', rest_url());

    wp_localize_script('geointerest-app', 'geointerestConfig', [
        'apiUrl'   => $api_url,
        'nonce'    => wp_create_nonce('wp_rest'),
        'siteUrl'  => $site_url,
        'homeUrl'  => $home_url,
        'wpApiUrl' => $wp_api_url
    ]);
});

/**
 * ============================
 * SPA Rewrite Rules
 * ============================
 */
add_action('init', function () {
    $prefix = 'stg';
    $routes = [
        'dashboard',
        'onboarding',
        'forum/[0-9]+',
        'interests',
        'login',
        'register',
        'map',
        'auth',
        ''
    ];

    foreach ($routes as $route) {
        $pattern = $route === '' ? "^{$prefix}/?$" : "^{$prefix}/{$route}/?$";
        add_rewrite_rule($pattern, 'index.php', 'top');
    }
    
    flush_rewrite_rules(false); // ✅ Refresh rules without clearing DB
});

/**
 * ============================
 * Cleanup (emoji + admin bar)
 * ============================
 */
add_filter('show_admin_bar', '__return_false');

add_action('init', function () {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

    add_filter('tiny_mce_plugins', function ($plugins) {
        return is_array($plugins)
            ? array_diff($plugins, ['wpemoji'])
            : [];
    });
});
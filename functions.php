
<?php
/**
 * Theme Name: Interest Local
 * Description: Hyper-local social platform
 * Version: 1.0.0
 */


// Prevent direct access
if (!defined('ABSPATH')) exit;

/**
 * Register Custom Post Type: Interest
 */
add_action('init', function() {
    register_post_type('interest', [
        'labels' => [
            'name' => 'Interests',
            'singular_name' => 'Interest',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Interest',
            'edit_item' => 'Edit Interest',
            'new_item' => 'New Interest',
            'view_item' => 'View Interest',
            'search_items' => 'Search Interests',
            'not_found' => 'No interests found',
            'not_found_in_trash' => 'No interests found in Trash',
        ],
        'public' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-groups',
        'supports' => ['title', 'editor', 'custom-fields'],
        'show_in_rest' => true,
        'capability_type' => 'post',
        'menu_position' => 5,
    ]);
});


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
 * Add admin menu for moderating forum messages
 */
add_action('admin_menu', function() {
    add_menu_page(
        'Forum Messages',
        'Forum Messages',
        'manage_options',
        'forum-messages',
        function() {
            global $wpdb;
            $table = $wpdb->prefix . 'forum_messages';
            $messages = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 100");
            echo '<div class="wrap"><h1>Forum Messages</h1>';
            echo '<table class="widefat"><thead><tr><th>ID</th><th>Interest</th><th>Author</th><th>Content</th><th>Date</th><th>Status</th><th>Reports</th><th>Actions</th></tr></thead><tbody>';
            foreach ($messages as $msg) {
                echo '<tr>';
                echo '<td>' . esc_html($msg->id) . '</td>';
                echo '<td>' . esc_html($msg->interest_id) . '</td>';
                echo '<td>' . esc_html($msg->author_name) . '</td>';
                echo '<td>' . esc_html($msg->content) . '</td>';
                echo '<td>' . esc_html($msg->created_at) . '</td>';
                echo '<td>' . esc_html($msg->status) . '</td>';
                echo '<td>' . esc_html($msg->report_count) . '</td>';
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
});


/**
 * ============================
 * Theme Constants
 * ============================
 */
define('GEOINTEREST_VERSION', '1.0.1');
define('GEOINTEREST_INC', get_template_directory() . '/inc/');

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
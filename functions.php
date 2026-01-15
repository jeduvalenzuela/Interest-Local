<?php
/**
 * Theme Name: GeoInterest
 * Description: Hyper-local social platform
 * Version: 1.0.2
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

/**
 * ============================
 * Theme Constants
 * ============================
 */
define('GEOINTEREST_VERSION', '1.0.42');
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
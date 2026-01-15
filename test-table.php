<?php
// Test file - borrar después
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar WordPress
require_once('wp-load.php');

global $wpdb;

// Check if table exists
$table_name = $wpdb->prefix . 'user_posts';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

echo "Table exists: " . ($table_exists ? "YES" : "NO") . "\n";
echo "Table name: $table_name\n";
echo "\n";

if ($table_exists) {
    // Get table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo "Columns:\n";
    foreach ($columns as $col) {
        echo "  - {$col->Field} ({$col->Type})\n";
    }
} else {
    echo "\n❌ Table does not exist!\n";
    echo "Creating table...\n";
    
    // Try to create it
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}user_posts (
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
    
    $result = dbDelta($sql);
    echo "Create result:\n";
    print_r($result);
}
?>

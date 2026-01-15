<?php
if (!defined('ABSPATH')) exit;

/**
 * Página de administración para ver posts de usuarios
 */

// Registrar la página en el menú admin
add_action('admin_menu', function() {
    add_menu_page(
        'GeoInterest Posts',           // Título de la página
        'GeoInterest Posts',           // Título del menú
        'manage_options',              // Capacidad requerida
        'geointerest-posts',           // Slug de la página
        'geointerest_admin_posts_page',// Función callback
        'dashicons-chat',              // Ícono
        20                             // Posición en el menú
    );
});

/**
 * Renderizar la página de posts en admin
 */
function geointerest_admin_posts_page() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'user_posts';
    
    // Verificar que la tabla exista
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    
    if (!$table_exists) {
        echo '<div class="wrap"><h1>GeoInterest Posts</h1>';
        echo '<p style="color: red;">❌ Tabla de posts no encontrada.</p>';
        echo '</div>';
        return;
    }
    
    // Obtener posts con paginación
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    // Contar total de posts
    $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    $total_pages = ceil($total_posts / $per_page);
    
    // Obtener posts de la página actual
    $posts = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ));
    
    // Obtener info de usuarios para mostrar junto a los posts
    $posts_with_user = [];
    foreach ($posts as $post) {
        $user = get_user_by('id', $post->user_id);
        $posts_with_user[] = (object) [
            'post' => $post,
            'user' => $user
        ];
    }
    ?>
    
    <div class="wrap">
        <h1>📱 GeoInterest Posts</h1>
        <p>Total de posts: <strong><?php echo $total_posts; ?></strong></p>
        
        <?php if (empty($posts_with_user)) : ?>
            <p style="background: #f0f0f0; padding: 15px; border-radius: 5px;">
                No hay posts aún.
            </p>
        <?php else : ?>
            
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="width: 150px;">Usuario</th>
                        <th>Contenido</th>
                        <th style="width: 150px;">Imagen</th>
                        <th style="width: 180px;">Fecha</th>
                        <th style="width: 120px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts_with_user as $item) : 
                        $post = $item->post;
                        $user = $item->user;
                        $user_name = $user ? $user->display_name . ' (' . $user->user_login . ')' : 'Usuario eliminado';
                        $user_id = $post->user_id;
                        $post_id = $post->id;
                        $content = wp_trim_words($post->content, 30);
                        $image = !empty($post->image_url) ? '✓ Sí' : '—';
                        $date = wp_date('d/m/Y H:i', strtotime($post->created_at));
                    ?>
                        <tr>
                            <td><strong><?php echo $post_id; ?></strong></td>
                            <td>
                                <?php if ($user) : ?>
                                    <a href="<?php echo admin_url('user-edit.php?user_id=' . $user_id); ?>">
                                        <?php echo esc_html($user_name); ?>
                                    </a>
                                <?php else : ?>
                                    <span style="color: #999;">Usuario #<?php echo $user_id; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="max-width: 400px; word-wrap: break-word;">
                                    <?php echo esc_html($content); ?>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <?php if (!empty($post->image_url)) : ?>
                                    <a href="<?php echo esc_url($post->image_url); ?>" target="_blank">
                                        🖼️ Ver
                                    </a>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 12px;">
                                <?php echo $date; ?><br>
                                <span style="color: #999;">Hace <?php echo human_time_diff(strtotime($post->created_at)); ?></span>
                            </td>
                            <td style="text-align: center;">
                                <a href="#" class="button button-small" onclick="copyPost(<?php echo $post_id; ?>)">
                                    Copiar
                                </a>
                                <a href="#" class="button button-small button-link-delete" onclick="deletePost(<?php echo $post_id; ?>, '<?php echo wp_create_nonce('delete_post_' . $post_id); ?>')">
                                    Eliminar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Paginación -->
            <?php if ($total_pages > 1) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo $total_posts; ?> items</span>
                        <?php 
                        $page_links = paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo; Anterior',
                            'next_text' => 'Siguiente &raquo;',
                            'total' => $total_pages,
                            'current' => $current_page,
                            'type' => 'array'
                        ]);
                        if ($page_links) {
                            echo '<span class="pagination-links">' . implode(' ', $page_links) . '</span>';
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
        
    </div>
    
    <style>
        .geointerest-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #0073aa;
        }
    </style>
    
    <script>
        function copyPost(postId) {
            alert('Post ID: ' + postId + ' copiado');
            // Aquí podrías copiar al portapapeles
        }
        
        function deletePost(postId, nonce) {
            if (!confirm('¿Estás seguro de que deseas eliminar este post?')) {
                return false;
            }
            
            // Hacer request para eliminar
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=geointerest_delete_post&post_id=' + postId + '&nonce=' + nonce
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.data || 'No se pudo eliminar'));
                }
            });
            
            return false;
        }
    </script>
    
    <?php
}

/**
 * Acción AJAX para eliminar posts
 */
add_action('wp_ajax_geointerest_delete_post', function() {
    global $wpdb;
    
    // Verificar nonce
    $post_id = intval($_POST['post_id']);
    $nonce = $_POST['nonce'];
    
    if (!wp_verify_nonce($nonce, 'delete_post_' . $post_id)) {
        wp_send_json_error('Nonce inválido');
    }
    
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No tienes permisos');
    }
    
    // Eliminar el post
    $result = $wpdb->delete(
        $wpdb->prefix . 'user_posts',
        ['id' => $post_id]
    );
    
    if ($result) {
        wp_send_json_success('Post eliminado');
    } else {
        wp_send_json_error('Error al eliminar');
    }
});

<?php
if (!defined('ABSPATH')) exit;

/**
 * Página de administración para gestionar usuarios de GeoInterest
 */

// Registrar la página en el menú admin
add_action('admin_menu', function() {
    add_submenu_page(
        'geointerest-posts',           // Parent slug
        'GeoInterest Usuarios',        // Título de la página
        'Usuarios',                    // Título del menú
        'manage_options',              // Capacidad requerida
        'geointerest-users',           // Slug de la página
        'geointerest_admin_users_page' // Función callback
    );
});

/**
 * Renderizar la página de usuarios en admin
 */
function geointerest_admin_users_page() {
    // Verificar acción (vista de detalle de usuario)
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    if ($action === 'edit' && $user_id > 0) {
        geointerest_admin_user_edit_page($user_id);
    } else {
        geointerest_admin_users_list_page();
    }
}

/**
 * Lista de usuarios
 */
function geointerest_admin_users_list_page() {
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    // Obtener usuarios
    $users = get_users([
        'number' => $per_page,
        'offset' => $offset,
        'orderby' => 'user_registered',
        'order' => 'DESC'
    ]);
    
    $total_users = count_users();
    $total_count = $total_users['total_users'];
    $total_pages = ceil($total_count / $per_page);
    ?>
    
    <div class="wrap">
        <h1>👥 Gestionar Usuarios GeoInterest</h1>
        <p>Total de usuarios: <strong><?php echo $total_count; ?></strong></p>
        
        <?php if (empty($users)) : ?>
            <p style="background: #f0f0f0; padding: 15px; border-radius: 5px;">
                No hay usuarios registrados.
            </p>
        <?php else : ?>
            
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Usuario</th>
                        <th style="width: 180px;">Email</th>
                        <th style="width: 150px;">Teléfono</th>
                        <th style="width: 120px;">Posts</th>
                        <th style="width: 180px;">Registrado</th>
                        <th style="width: 150px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user) : 
                        $phone = get_user_meta($user->ID, 'phone_number', true);
                        $posts_count = geointerest_get_user_posts_count($user->ID);
                        $registered = wp_date('d/m/Y H:i', strtotime($user->user_registered));
                    ?>
                        <tr>
                            <td><strong><?php echo $user->ID; ?></strong></td>
                            <td>
                                <strong><?php echo esc_html($user->display_name); ?></strong><br>
                                <span style="color: #666; font-size: 12px;">@<?php echo esc_html($user->user_login); ?></span>
                            </td>
                            <td>
                                <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                                    <?php echo esc_html($user->user_email); ?>
                                </a>
                            </td>
                            <td style="font-size: 12px;">
                                <?php echo !empty($phone) ? esc_html($phone) : '—'; ?>
                            </td>
                            <td style="text-align: center;">
                                <strong><?php echo $posts_count; ?></strong>
                            </td>
                            <td style="font-size: 12px;">
                                <?php echo $registered; ?><br>
                                <span style="color: #999;">Hace <?php echo human_time_diff(strtotime($user->user_registered)); ?></span>
                            </td>
                            <td style="text-align: center;">
                                <a href="<?php echo add_query_arg(['action' => 'edit', 'user_id' => $user->ID], admin_url('admin.php?page=geointerest-users')); ?>" class="button button-small">
                                    ✏️ Editar
                                </a>
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>" class="button button-small">
                                    👤 WP
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
                        <span class="displaying-num"><?php echo $total_count; ?> usuarios</span>
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
    
    <?php
}

/**
 * Página de edición de usuario
 */
function geointerest_admin_user_edit_page($user_id) {
    $user = get_user_by('id', $user_id);
    
    if (!$user) {
        wp_die('Usuario no encontrado.');
    }
    
    // Procesar formulario si se envió
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['geointerest_nonce'])) {
        if (!wp_verify_nonce($_POST['geointerest_nonce'], 'edit_user_' . $user_id)) {
            wp_die('Nonce inválido.');
        }
        
        // Actualizar datos
        wp_update_user([
            'ID' => $user_id,
            'display_name' => sanitize_text_field($_POST['display_name']),
            'user_email' => sanitize_email($_POST['user_email'])
        ]);
        
        // Actualizar meta datos
        $fields = [
            'bio', 'phone_number', 'address', 'avatar_url',
            'instagram', 'twitter', 'facebook'
        ];
        
        foreach ($fields as $field) {
            $value = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';
            if ($field === 'avatar_url') {
                $value = sanitize_url($value);
            } elseif ($field === 'facebook') {
                $value = sanitize_url($value);
            } elseif ($field === 'bio') {
                $value = sanitize_textarea_field($value);
            }
            
            if (!empty($value)) {
                update_user_meta($user_id, $field, $value);
            } else {
                delete_user_meta($user_id, $field);
            }
        }
        
        // Guardar intereses
        $interests = isset($_POST['interests']) ? array_map('sanitize_text_field', $_POST['interests']) : [];
        if (!empty($interests)) {
            update_user_meta($user_id, 'user_interests', $interests);
        }
        
        echo '<div class="notice notice-success"><p>✓ Usuario actualizado correctamente.</p></div>';
        
        // Recargar datos
        $user = get_user_by('id', $user_id);
    }
    
    // Obtener datos actuales
    $display_name = $user->display_name;
    $user_email = $user->user_email;
    $bio = get_user_meta($user_id, 'bio', true);
    $phone = get_user_meta($user_id, 'phone_number', true);
    $address = get_user_meta($user_id, 'address', true);
    $avatar_url = get_user_meta($user_id, 'avatar_url', true);
    $instagram = get_user_meta($user_id, 'instagram', true);
    $twitter = get_user_meta($user_id, 'twitter', true);
    $facebook = get_user_meta($user_id, 'facebook', true);
    $interests = get_user_meta($user_id, 'user_interests', true) ?: [];
    $posts_count = geointerest_get_user_posts_count($user_id);
    
    $available_interests = [
        'Deportes', 'Música', 'Arte', 'Tecnología', 
        'Gastronomía', 'Naturaleza', 'Cine', 'Lectura',
        'Fotografía', 'Viajes', 'Fitness', 'Gaming'
    ];
    ?>
    
    <div class="wrap">
        <h1>Editar Usuario: <?php echo esc_html($user->display_name); ?></h1>
        
        <div style="display: grid; grid-template-columns: 1fr 300px; gap: 20px; margin-top: 20px;">
            
            <div>
                <form method="POST" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                    <?php wp_nonce_field('edit_user_' . $user_id, 'geointerest_nonce'); ?>
                    
                    <!-- Información básica -->
                    <h2 style="margin-top: 0;">📋 Información Básica</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="display_name">Nombre completo</label></th>
                            <td>
                                <input 
                                    type="text" 
                                    id="display_name" 
                                    name="display_name" 
                                    value="<?php echo esc_attr($display_name); ?>" 
                                    class="regular-text"
                                >
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="user_email">Email</label></th>
                            <td>
                                <input 
                                    type="email" 
                                    id="user_email" 
                                    name="user_email" 
                                    value="<?php echo esc_attr($user_email); ?>" 
                                    class="regular-text"
                                >
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="bio">Descripción</label></th>
                            <td>
                                <textarea 
                                    id="bio" 
                                    name="bio" 
                                    rows="4" 
                                    class="large-text"
                                    maxlength="200"
                                ><?php echo esc_textarea($bio); ?></textarea>
                                <p class="description"><?php echo strlen($bio); ?>/200 caracteres</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="avatar_url">Foto de perfil (URL)</label></th>
                            <td>
                                <input 
                                    type="url" 
                                    id="avatar_url" 
                                    name="avatar_url" 
                                    value="<?php echo esc_attr($avatar_url); ?>" 
                                    class="large-text"
                                    placeholder="https://ejemplo.com/foto.jpg"
                                >
                                <?php if (!empty($avatar_url)) : ?>
                                    <p style="margin-top: 10px;">
                                        <img src="<?php echo esc_url($avatar_url); ?>" alt="Avatar" style="max-width: 150px; border-radius: 50%; border: 2px solid #ddd;">
                                    </p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- Contacto -->
                    <h2>📞 Contacto</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="phone">Teléfono</label></th>
                            <td>
                                <input 
                                    type="tel" 
                                    id="phone" 
                                    name="phone_number" 
                                    value="<?php echo esc_attr($phone); ?>" 
                                    class="regular-text"
                                    placeholder="+54 911 2345 6789"
                                >
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="address">Dirección</label></th>
                            <td>
                                <input 
                                    type="text" 
                                    id="address" 
                                    name="address" 
                                    value="<?php echo esc_attr($address); ?>" 
                                    class="regular-text"
                                    placeholder="Ciudad, zona o barrio"
                                >
                            </td>
                        </tr>
                    </table>
                    
                    <!-- Redes sociales -->
                    <h2>🌐 Redes Sociales</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="instagram">Instagram</label></th>
                            <td>
                                <input 
                                    type="text" 
                                    id="instagram" 
                                    name="instagram" 
                                    value="<?php echo esc_attr($instagram); ?>" 
                                    class="regular-text"
                                    placeholder="@tu_usuario"
                                >
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="twitter">Twitter</label></th>
                            <td>
                                <input 
                                    type="text" 
                                    id="twitter" 
                                    name="twitter" 
                                    value="<?php echo esc_attr($twitter); ?>" 
                                    class="regular-text"
                                    placeholder="@tu_usuario"
                                >
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="facebook">Facebook</label></th>
                            <td>
                                <input 
                                    type="url" 
                                    id="facebook" 
                                    name="facebook" 
                                    value="<?php echo esc_attr($facebook); ?>" 
                                    class="large-text"
                                    placeholder="https://facebook.com/tu_usuario"
                                >
                            </td>
                        </tr>
                    </table>
                    
                    <!-- Intereses -->
                    <h2>⭐ Intereses</h2>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin: 15px 0;">
                        <?php foreach ($available_interests as $interest) : 
                            $checked = in_array($interest, (array)$interests) ? 'checked' : '';
                        ?>
                            <label style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; cursor: pointer; background: #f9f9f9;">
                                <input 
                                    type="checkbox" 
                                    name="interests[]" 
                                    value="<?php echo esc_attr($interest); ?>"
                                    <?php echo $checked; ?>
                                >
                                <?php echo esc_html($interest); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                        <?php submit_button('Guardar cambios', 'primary', 'submit', true); ?>
                        <a href="<?php echo admin_url('admin.php?page=geointerest-users'); ?>" class="button">Volver</a>
                    </div>
                </form>
            </div>
            
            <!-- Sidebar con información -->
            <div>
                <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                    <h3>ℹ️ Información</h3>
                    
                    <p>
                        <strong>ID:</strong><br>
                        <?php echo $user_id; ?>
                    </p>
                    
                    <p>
                        <strong>Usuario:</strong><br>
                        @<?php echo esc_html($user->user_login); ?>
                    </p>
                    
                    <p>
                        <strong>Posts:</strong><br>
                        <span style="font-size: 24px; font-weight: bold;"><?php echo $posts_count; ?></span>
                    </p>
                    
                    <p>
                        <strong>Registrado:</strong><br>
                        <?php echo wp_date('d/m/Y H:i', strtotime($user->user_registered)); ?>
                    </p>
                    
                    <hr style="margin: 20px 0;">
                    
                    <p>
                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $user_id); ?>" class="button button-block">
                            👤 Editar en WordPress
                        </a>
                    </p>
                    
                    <p>
                        <a href="<?php echo add_query_arg(['action' => 'list'], admin_url('admin.php?page=geointerest-users')); ?>" class="button button-block">
                            ← Volver a lista
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <?php
}

/**
 * Función auxiliar para contar posts de un usuario
 */
function geointerest_get_user_posts_count($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_posts';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    
    if (!$table_exists) {
        return 0;
    }
    
    return intval($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d",
        $user_id
    )));
}

# 🚀 Guía de Implementación - Interest Local v1.2.0

## 📋 Requisitos Previos

1. **Base de datos con tablas requeridas:**
   - `wp_interests` - Catálogo de intereses
   - `wp_user_interests` - Relación usuario-intereses
   - `wp_user_meta` - Metadatos de usuario (debe incluir latitude y longitude)

2. **WordPress REST API habilitada**

3. **Librerías necesarias:**
   - React Query (@tanstack/react-query)
   - React Router (react-router-dom)
   - Vite o bundler similar

## 🔧 Pasos de Instalación

### 1. Copiar Componentes Nuevos
```bash
# El componente NearbyInterests ya está en:
src/components/Dashboard/NearbyInterests.jsx
src/components/Dashboard/NearbyInterests.css
```

### 2. Actualizar Imports en NewDashboard
```jsx
// Verificar que NewDashboard.jsx importa:
import NearbyInterests from '../components/Dashboard/NearbyInterests';
```

### 3. Crear/Actualizar Tabla de Intereses

Si no existe la tabla `wp_interests`, crearla con:

```sql
CREATE TABLE `wp_interests` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) UNIQUE,
  `icon` varchar(10),
  `color` varchar(7),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- Insertar intereses de ejemplo
INSERT INTO `wp_interests` (name, slug, icon, color) VALUES
('Basketball', 'basketball', '🏀', '#FF6B6B'),
('Billiards', 'billiards', '🎳', '#4ECDC4'),
('Whisky', 'whisky', '🥃', '#FFE66D'),
('Reading', 'reading', '📚', '#95E1D3'),
('Music', 'music', '🎵', '#A8D8EA'),
('Sports', 'sports', '⚽', '#FF8B94'),
('Art', 'art', '🎨', '#C7CEEA'),
('Technology', 'technology', '💻', '#73D8FF'),
('Gastronomy', 'gastronomy', '🍽️', '#FDB96D'),
('Nature', 'nature', '🌿', '#95E77D'),
('Cinema', 'cinema', '🎬', '#FFB3BA'),
('Photography', 'photography', '📸', '#FFDFBA');
```

### 4. Tabla de Relaciones Usuario-Intereses

Si no existe `wp_user_interests`:

```sql
CREATE TABLE `wp_user_interests` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `interest_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_interest` (`user_id`, `interest_id`),
  FOREIGN KEY (`user_id`) REFERENCES `wp_users`(ID) ON DELETE CASCADE,
  FOREIGN KEY (`interest_id`) REFERENCES `wp_interests`(id) ON DELETE CASCADE
);
```

### 5. Asegurar que wp_user_meta tiene Ubicación

El endpoint `/user/location` debe guardar:
- `latitude` 
- `longitude`
- `accuracy` (opcional)

En la tabla `wp_user_meta`:

```php
// Ejemplo de cómo se guarda en el backend:
update_user_meta($user_id, 'latitude', $latitude);
update_user_meta($user_id, 'longitude', $longitude);
update_user_meta($user_id, 'accuracy', $accuracy);
```

### 6. Verificar Endpoint `/interests/nearby`

El archivo `inc/api-endpoints.php` ya tiene:
- ✅ Ruta registrada: `/geointerest/v1/interests/nearby`
- ✅ Función: `geointerest_get_nearby_interests()`
- ✅ Parámetros: latitude, longitude, radius (default 1000m)

### 7. Actualizar Función de Guardar Perfil

En `inc/api-endpoints.php`, la función `geointerest_update_profile` debe:

```php
function geointerest_update_profile($request) {
    $user_id = GeoInterest_JWT::get_current_user_id();
    
    // ... otros campos ...
    
    // ✅ Guardar ubicación
    $latitude = floatval($request->get_param('latitude'));
    $longitude = floatval($request->get_param('longitude'));
    
    if ($latitude && $longitude) {
        update_user_meta($user_id, 'latitude', $latitude);
        update_user_meta($user_id, 'longitude', $longitude);
    }
    
    // ... resto del código ...
}
```

## 🧪 Pruebas de API

### Test 1: Obtener Intereses Cercanos

```bash
curl -X GET "http://localhost/wp-json/geointerest/v1/interests/nearby?latitude=-34.6037&longitude=-58.3816&radius=1000"
```

**Respuesta esperada:**
```json
[
  {
    "id": 1,
    "name": "Basketball",
    "slug": "basketball",
    "icon": "🏀",
    "color": "#FF6B6B",
    "member_count": 5,
    "distance": 450
  },
  {
    "id": 4,
    "name": "Reading",
    "slug": "reading",
    "icon": "📚",
    "color": "#95E1D3",
    "member_count": 3,
    "distance": 850
  }
]
```

### Test 2: Login de Usuario

```bash
curl -X POST "http://localhost/wp-json/geointerest/v1/auth/phone" \
  -H "Content-Type: application/json" \
  -d '{"phone": "1234567890"}'
```

### Test 3: Completar Onboarding

```bash
curl -X POST "http://localhost/wp-json/geointerest/v1/user/profile" \
  -H "Authorization: Bearer [TOKEN]" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "display_name": "Juan Pérez",
    "bio": "Me encanta el basketball",
    "interests": ["Basketball", "Sports", "Photography"],
    "latitude": -34.6037,
    "longitude": -58.3816
  }'
```

## 📱 Flujo de Usuario (QA)

### Nuevo Usuario:
1. ✅ Abre app → ve pantalla de login (Interest Local)
2. ✅ Ingresa número de teléfono
3. ✅ Redirige a Onboarding
4. ✅ Se pide habilitar ubicación (OBLIGATORIO)
5. ✅ Completar nombre e intereses
6. ✅ Redirige a Dashboard
7. ✅ Ve lista de intereses cercanos (1km)
8. ✅ Click en interés → abre conversación

### Usuario Existente:
1. ✅ Ingresa número
2. ✅ Va directo a Dashboard
3. ✅ Ve intereses cercanos

## 🐛 Debugging

### Si no aparecen intereses:
1. Verificar tabla `wp_interests` tiene datos
2. Verificar tabla `wp_user_interests` tiene relaciones
3. Verificar `wp_user_meta` tiene latitude/longitude
4. Verificar en console del navegador:
   ```javascript
   localStorage.getItem('geoi_token') // Debe tener token
   ```

### Si falla el endpoint `/interests/nearby`:
1. Verificar que `geointerest_get_nearby_interests()` existe
2. Verificar parámetros: latitude, longitude, radius
3. Verificar errores de SQL en `wp_errors`

### Si ubicación no se guarda:
1. Verificar que `useUserLocation` está en Onboarding
2. Verificar que `requestPermission()` se llama
3. Verificar que navegador permite acceso a geolocalización
4. Revisar console para errores de permisos

## 🔐 Notas de Seguridad

1. **Ubicación no es pública:**
   - Se usa solo para filtrar intereses cercanos
   - Otros usuarios no ven la ubicación exacta

2. **Token JWT:**
   - Se genera en `/auth/phone`
   - Se valida en endpoints protegidos
   - Expira después de 7 días

3. **Validación en Backend:**
   - Verificar que usuario está autenticado
   - Sanitizar todos los inputs
   - Usar prepared statements para SQL

## 📞 Soporte

Si hay problemas:

1. Revisar archivo `CHANGES_v1.2.0.md`
2. Verificar archivos modificados:
   - `inc/api-endpoints.php`
   - `src/pages/NewDashboard.jsx`
   - `src/components/Onboarding/Onboarding.jsx`
   - `src/components/Dashboard/NearbyInterests.jsx`
   - `src/components/Auth/UnifiedAuth.jsx`

3. Ejecutar tests de API
4. Revisar browser console para errores

## ✨ Características Futuras

- [ ] Editar intereses después del onboarding
- [ ] Ver perfil de otros usuarios en el mismo interés
- [ ] Chat en tiempo real en la conversación
- [ ] Notificaciones cuando hay actividad
- [ ] Filtro por distancia (aunque sea 1km por defecto)
- [ ] Historial de intereses visitados
- [ ] Puntuación/ratings de intereses

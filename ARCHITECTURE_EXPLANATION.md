# Para detalles técnicos y de arquitectura actualizados, consulta también:
# - `PROJECT_STRUCTURE_v1.1.0.md`
# - `CHANGELOG_v1.1.0.md`
# - `IMPLEMENTATION_SUMMARY_v1.1.0.md`
# 🏗️ Arquitectura GeoInterest - Explicación Completa

## ¿Cómo está funcionando el sistema?

---

## 1. ¿Quiénes son los usuarios?

### Usuarios Reales de WordPress
Sí, son **usuarios reales de WordPress**. Cada uno tiene:
- `ID` (usuario de WP)
- `user_login` (username)
- `user_email`
- `display_name`

### Cómo se crean:
```
/auth/register → Crea usuario en wp_users → Se asigna automáticamente un ID de WP
```

### Ejemplo:
```
Usuario: juan_perez
Email: juan@example.com
ID en WP: 5
```

---

## 2. ¿Cómo funcionan los Posts?

### NO son posts de WordPress tradicionales
Los posts **NO** se guardan como `wp_posts` (esos son para artículos/páginas).

### Se guardan en tabla custom: `wp_user_posts`
```sql
CREATE TABLE wp_user_posts (
    id                 BIGINT PRIMARY KEY AUTO_INCREMENT
    user_id            BIGINT (referencia a wp_users.ID)
    content            TEXT
    image_url          VARCHAR(255)
    created_at         DATETIME
    updated_at         DATETIME
)
```

### ¿Por qué tabla custom y no wp_posts?
1. **Flexibilidad** - No interfiere con WordPress posts normales
2. **Rendimiento** - Tabla optimizada solo para posts de usuarios
3. **Simplificidad** - Menos metadata que procesar
4. **Aislamiento** - Los plugins de WordPress no interfieren

---

## 3. Flujo de Autenticación

### Paso 1: Login (POST /auth/login)
```javascript
// Frontend (React)
const response = await apiClient.post('/auth/login', {
  username: 'juan_perez',
  password: 'password123'
});
// response.token → JWT token (válido 7 días)
```

### Paso 2: Backend valida credenciales
```php
// Backend (WordPress)
$user = wp_authenticate($username, $password);
$token = GeoInterest_JWT::generate_token($user->ID);
// Retorna JWT token
```

### Paso 3: Frontend guarda token
```javascript
// AuthContext.jsx
localStorage.setItem('geoi_token', token);
apiClient.setToken(token); // Configura header Authorization
```

### Paso 4: Cada solicitud POST/GET incluye token
```
GET /users/latest HTTP/1.1
Authorization: Bearer eyJhbGc.eyJpc3M.SflKxw...
```

---

## 4. Flujo de Creación de Post

### Paso 1: Usuario llena formulario
```javascript
// CreatePostForm.jsx
{
  content: "¡Hola GeoInterest! 🚀",
  image_url: "https://example.com/img.jpg"
}
```

### Paso 2: Frontend envía con token
```javascript
// api.js
const headers = {
  'Authorization': `Bearer ${this.token}`,
  'Content-Type': 'application/json'
};

fetch('https://gavaweb.com/stg/wp-json/geointerest/v1/posts', {
  method: 'POST',
  headers,
  body: JSON.stringify(data)
});
```

### Paso 3: Backend valida token
```php
// jwt-auth.php
public static function get_current_user_id() {
  // Extrae "Authorization: Bearer {token}"
  $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
  // Valida firma JWT
  // Retorna user_id o false
}
```

### Paso 4: Backend inserta en tabla
```php
// api-endpoints.php → geointerest_create_post()
$wpdb->insert(
  $wpdb->prefix . 'user_posts',
  [
    'user_id' => $user_id,        // Del token
    'content' => $content,
    'image_url' => $image_url
  ]
);
```

### Paso 5: Post aparece en dashboard
```javascript
// NewDashboard.jsx
const { data: latestPosts } = useQuery({
  queryKey: ['posts/latest'],
  queryFn: () => apiClient.get('/posts/latest'),
  refetchInterval: 10000  // Actualiza cada 10s
});
```

---

## 5. JWT Token - Qué contiene

### Estructura del Token
```
eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.
eyJpc3MiOiJodHRwczovL2dhdmF3ZWIuY29tIiwiaWF0IjoxNjczODcwMjAwLCJleHAiOjE2NzQ0NzUwMDAsInVzZXJfaWQiOjV9.
abc123...
```

### Header (primera parte)
```json
{
  "alg": "HS256",
  "typ": "JWT"
}
```

### Payload (segunda parte) ← Lo importante
```json
{
  "iss": "https://gavaweb.com",     // Sitio que lo emitió
  "iat": 1673870200,                 // Fecha emisión (timestamp)
  "exp": 1674475000,                 // Fecha expiración (7 días después)
  "user_id": 5                       // ← ID del usuario de WP
}
```

### Signature (tercera parte)
```
HMAC-SHA256(
  base64(header) + "." + base64(payload),
  JWT_AUTH_SECRET_KEY
)
```

---

## 6. Flujo Completo: Usuario A crea post

```
┌─────────────────────────────────────────────────────────┐
│                                                         │
│ 1. Usuario en Dashboard (React)                         │
│    - Ve formulario "¿Qué estás pensando?"              │
│                                                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ 2. Escribe: "¡Hola a todos!"                           │
│    - Presiona [Publicar]                               │
│    - CreatePostForm.jsx hace:                          │
│      apiClient.post('/posts', {                        │
│        content: '¡Hola a todos!',                      │
│        image_url: null                                 │
│      })                                                 │
│                                                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ 3. api.js agrega header:                               │
│    Authorization: Bearer eyJhbGc.eyJpc3M.SflKxw...    │
│                                                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ 4. WordPress REST API:                                 │
│    POST /wp-json/geointerest/v1/posts                 │
│                                                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ 5. Backend verifica:                                   │
│    - ¿Existe Authorization header? ✓                  │
│    - ¿Token válido (firma correcta)? ✓               │
│    - ¿Token no expirado? ✓                            │
│    - user_id = 5 ✓                                    │
│                                                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ 6. Backend inserta en wp_user_posts:                   │
│    INSERT INTO wp_user_posts (                         │
│      user_id: 5,                                       │
│      content: '¡Hola a todos!',                        │
│      image_url: NULL,                                  │
│      created_at: NOW()                                 │
│    )                                                    │
│                                                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ 7. Frontend (cada 10 segundos):                        │
│    GET /posts/latest?limit=50                         │
│                                                         │
│    SELECT * FROM wp_user_posts                        │
│    ORDER BY created_at DESC                           │
│    LIMIT 50                                            │
│                                                         │
│    Resultado incluye nuevo post                        │
│                                                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ 8. PostsList.jsx renderiza:                            │
│    ┌──────────────────────────┐                        │
│    │ 😊 juan_perez            │                        │
│    │ hace 5 segundos          │                        │
│    │ "¡Hola a todos!"         │                        │
│    └──────────────────────────┘                        │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

GET /posts/user/{id}
  ← Retorna posts de un usuario específico
```

### Protegidos (requieren JWT)
```
POST /posts (REQUIERE Authorization header)
  ← Crea nuevo post del usuario autenticado
  
  Parámetros:
  {
    content: "string (max 500 chars)",
    image_url: "url (opcional)"
  }
  
  Respuesta:
  {
    success: true,
    post_id: 123,
    post: {
      id: 123,
      content: "...",
      image_url: "...",
      created_at: "2026-01-15T10:30:00"
    }
  }
```

---

## 9. Seguridad

### ¿Cómo se protege el JWT?
```
1. Firma HMAC-SHA256 con secret key
2. Expira en 7 días
3. Contiene user_id (no información sensible)
4. Se valida en cada petición POST protegida
```

### ¿Qué pasa si alguien intenta crear post sin token?
```
POST /posts

Response: 401 Unauthorized
{
  "code": "rest_forbidden",
  "message": "Lo siento, no tienes permisos para hacer eso.",
  "data": { "status": 401 }
}
```

### ¿Y si envían token expirado?
```
Authorization: Bearer eyJhbGc.eyJleHAiOjEyMzQ1Njc4OTB9.xxx

Backend valida:
- Decodifica JWT
- Comprueba: payload['exp'] < time()  ← FALSO
- Retorna: false
- Response: 401 Unauthorized
```

---

## 10. Resumen Arquitectura

```
┌─────────────────┐
│   React SPA     │  Frontend (src/)
│   (Dashboard)   │
└────────┬────────┘
         │
         │ HTTP + JWT
         │
┌────────▼──────────────────────────────────────┐
│     WordPress REST API + JWT Auth             │
│     (functions.php, inc/api-endpoints.php)    │
└────────┬───────────────────────────────────────┘
         │
         │ Query
         │
┌────────▼───────────────────────────────────────┐
│         MySQL Database                        │
│                                                │
│  ├─ wp_users (usuarios reales WP)            │
│  ├─ wp_user_posts (posts sociales)           │
│  ├─ wp_user_tokens (jwt almacenados)         │
│  └─ wp_user_locations (geolocalización)      │
└────────────────────────────────────────────────┘
```

---

## 11. ¿Por qué este error: "No tienes permisos"?

El error 401 en `POST /posts` significa:

1. **No hay Authorization header** ← El más común
   ```javascript
   // ❌ INCORRECTO
   apiClient.post('/posts', data); // Sin token
   
   // ✓ CORRECTO
   // El apiClient ya tiene token porque se hizo setToken() en login
   ```

2. **Token no está siendo enviado**
   ```javascript
   // Verificar en la consola:
   console.log('Token:', localStorage.getItem('geoi_token'));
   // Debería mostrar un JWT largo
   ```

3. **Token expirado**
   ```javascript
   // JWT válido por 7 días desde que hiciste login
   // Si hace 8 días que no relogueas, expira
   ```

4. **Encabezado Authorization incorrecto**
   ```javascript
   // ❌ Incorrecto
   headers['Authorization'] = 'JWT ' + token;
   headers['Authorization'] = token;
   
   // ✓ Correcto
   headers['Authorization'] = 'Bearer ' + token;
   ```

---

## 12. Para Debuggear

### En el navegador (Console):
```javascript
// 1. ¿Existe token?
localStorage.getItem('geoi_token')
// Debe mostrar algo como: "eyJhbGc.eyJpc3M.SflKxw..."

// 2. ¿Se está enviando el header?
// Abre DevTools → Network → POST /posts
// En "Request Headers" busca: Authorization: Bearer eyJhbGc...
```

### En WordPress (logs):
```php
// Error logs en /wp-content/debug.log
error_log('GeoInterest: No Authorization header found');
error_log('GeoInterest: Token validation failed');
```

---

## Conclusión

- ✅ **Usuarios** = Usuarios reales de WordPress (en wp_users)
- ✅ **Posts** = Tabla custom wp_user_posts (no interfiere con posts normales)
- ✅ **Autenticación** = JWT tokens de 7 días validados en cada petición
- ✅ **Seguridad** = HMAC-SHA256 + tokens con expiración
- ✅ **Dashboard** = React Query con auto-refresh cada 10 segundos


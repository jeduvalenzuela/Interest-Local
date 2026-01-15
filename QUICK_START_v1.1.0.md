# 🚀 Guía Rápida - Dashboard v1.1.0

## ¿Qué Cambió?

**Antes (v1.0.2):**
- Dashboard mostraba solo intereses del usuario
- Ruteo principal al mapa

**Ahora (v1.1.0):**
- ✨ **Nuevo dashboard social** con posts y usuarios
- Listado de últimos 10 usuarios creados
- Feed de posts personales (público)
- Creación de nuevos posts
- Perfiles de usuarios con sus posts
- Auto-refresco en tiempo real

---

## Acceso

```
http://localhost/stg/dashboard   # Nuevo dashboard
http://localhost/stg/map         # Mapa anterior (sigue disponible)
```

O simplemente:
```
http://localhost/stg/   # Redirige automáticamente al dashboard
```

---

## Flujo de Usuario

### 1. Iniciar Sesión
```
/auth → Login/Register
```

### 2. Ver Dashboard Social
```
/dashboard
```
Verás:
- 📱 **Izquierda:** Últimos 10 usuarios (clickeable)
- 📝 **Derecha:** Formulario para crear posts + feed

### 3. Crear Post
```
1. Escribe en el formulario
2. (Opcional) Agregá URL de imagen
3. Click en "Publicar"
→ Se actualiza automáticamente el feed
```

### 4. Ver Perfil de Usuario
```
1. Haz click en un usuario de la lista izquierda
2. Se muestra su perfil con todos sus posts
3. Click en "← Volver" para regresar al feed
```

---

## Endpoints para Testing

### Obtener últimos usuarios
```bash
curl http://localhost/wp-json/geointerest/v1/users/latest?limit=10
```

### Obtener posts recientes
```bash
curl http://localhost/wp-json/geointerest/v1/posts/latest?limit=50
```

### Crear post (requiere JWT)
```bash
TOKEN="tu-token-aqui"
curl -X POST http://localhost/wp-json/geointerest/v1/posts \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "¡Hola GeoInterest!",
    "image_url": null
  }'
```

### Ver perfil de usuario
```bash
curl http://localhost/wp-json/geointerest/v1/users/123
```

---

## Configuración

### Auto-refresco
En `src/pages/NewDashboard.jsx`, modifica:

```javascript
// Usuarios cada 30 segundos
refetchInterval: 30000,

// Posts cada 10 segundos
refetchInterval: 10000,
```

### Límite de items
En `src/pages/NewDashboard.jsx`:

```javascript
// Últimos 10 usuarios
apiClient.get('/users/latest', { limit: 10 })

// Últimos 50 posts
apiClient.get('/posts/latest', { limit: 50 })
```

---

## Componentes Utilizados

### UsersList.jsx
Muestra listado de usuarios con avatar, nombre y username.

### PostsList.jsx
Renderiza posts con:
- Avatar del autor
- Nombre del autor
- Fecha relativa ("hace 5 minutos")
- Contenido
- Imagen (si existe)

### CreatePostForm.jsx
Formulario para crear posts:
- Campo de contenido (max 500 caracteres)
- URL de imagen (opcional)
- Validación de input
- Indicador de carga

### UserProfile.jsx
Perfil de usuario que muestra:
- Avatar grande
- Nombre y username
- Número de posts
- Lista completa de posts

---

## Estilos

### Variables CSS Principales
```css
/* Colores */
--primary: #007bff
--text-dark: #333
--text-light: #999
--border: #e0e0e0
--bg-hover: #f0f0f0

/* Tamaños */
--sidebar-width: 25%
--content-width: 75%
```

Editable en `src/pages/NewDashboard.css`

---

## Troubleshooting

### "Error al crear el post"
- Verifica que estés logueado
- Chequea la consola del navegador
- Verifica que el token JWT sea válido

### "No hay usuarios"
- Asegúrate de que hay usuarios registrados en la BD
- Prueba con: `SELECT * FROM wp_users LIMIT 10;`

### "Posts no se actualizan"
- Refresca la página manualmente
- Verifica que el endpoint `/posts/latest` retorna datos
- Chequea la consola por errores de red

### "Imágenes no cargan"
- Verifica que la URL de la imagen sea válida
- Asegúrate de que el servidor pueda acceder a esa URL
- Prueba con una URL de ejemplo válida

---

## Scripts para Desarrollo

```bash
# Iniciar dev server
npm run dev

# Build para producción
npm run build

# Preview del build
npm run preview
```

---

## Actualizar a v1.1.0

### En el Backend:
1. Editar `inc/database.php` → Se ejecuta automáticamente al activar tema
2. Editar `inc/api-endpoints.php` → Endpoints nuevos registrados

### En el Frontend:
1. Instalar dependencias: `npm install`
2. Build: `npm run build`
3. Los archivos se generarán en `build/`

### Verificar:
```bash
# Chequear que tabla existe
mysql> DESCRIBE wp_user_posts;

# Chequear que endpoints están activos
curl http://localhost/wp-json/geointerest/v1/users/latest
curl http://localhost/wp-json/geointerest/v1/posts/latest
```

---

**¿Necesitas ayuda?**  
Revisa `CHANGELOG_v1.1.0.md` para más detalles sobre qué cambió.

---

**Versión:** 1.1.0  
**Fecha:** 15 de enero de 2026

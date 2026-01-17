# 📝 Changelog - GeoInterest v1.1.0

## Nuevas Características (15 de enero de 2026)

### 🎯 Nuevo Dashboard Social
Se implementó un **nuevo dashboard completamente rediseñado** con enfoque social:

#### Características Principales:
1. **Layout de 3 secciones:**
   - **Sidebar Izquierdo (25%):** Listado de últimos 10 usuarios creados
   - **Contenido Principal (75%):** Feed de posts personales + formulario para crear posts
   - **Vista de Perfil:** Al clickear un usuario, muestra su perfil con sus posts

2. **Posts Personales:**
   - Crear nuevos posts con contenido de texto + imagen (opcional)
   - Ver feed con últimos posts de todos los usuarios
   - Auto-refresco cada 10 segundos (tiempo real)
   - Mostrar autor, fecha relativa, avatar y contenido

3. **Listado de Usuarios:**
   - Últimos 10 usuarios creados
   - Avatar + nombre + username
   - Clickeable para ver perfil completo
   - Indicador visual del usuario seleccionado

4. **Perfiles de Usuarios:**
   - Ver información del usuario
   - Listar todos sus posts
   - Contador de posts
   - Botón para volver al feed principal

### 📊 Base de Datos
Nueva tabla agregada:
```sql
wp_user_posts (
  id, user_id, content, image_url, 
  created_at, updated_at
)
```

### 🔌 Nuevos Endpoints API

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/users/latest` | Últimos 10 usuarios |
| `GET` | `/users/{id}` | Perfil completo de usuario |
| `GET` | `/posts/latest` | Feed de posts reciente |
| `GET` | `/posts/user/{id}` | Posts de un usuario |
| `POST` | `/posts` | Crear nuevo post |

### 🎨 Componentes Nuevos

```
src/pages/
├── NewDashboard.jsx          # Página principal
└── NewDashboard.css          # Estilos

src/components/Dashboard/
├── UsersList.jsx             # Listado de usuarios
├── PostsList.jsx             # Feed de posts
├── CreatePostForm.jsx        # Formulario para crear posts
└── UserProfile.jsx           # Perfil del usuario
```

### 📦 Dependencias Agregadas
- `date-fns` - Para formateo de fechas relativas (Ej: "hace 5 minutos")

### 🔄 Cambios en Rutas

**Anterior:**
- `/dashboard` → Vista de intereses
- `/` → Redirección a `/map`

**Nuevo:**
- `/dashboard` → **Nuevo dashboard social** ✨
- `/map` → Mapa de usuarios cercanos (mantiene funcionalidad anterior)
- `/` → Redirección a `/dashboard`

### ✨ Mejoras en UX
- Layout responsive (mobile-friendly)
- Auto-refresco de feed y usuarios
- Indicadores visuales de carga
- Mensajes de error claros
- Diseño moderno y limpio

### 🔒 Seguridad
- Endpoints públicos: `/users/latest`, `/posts/latest`, `/posts/user/{id}`, `/users/{id}`
- Endpoint protegido: `POST /posts` (requiere JWT)
- Validaciones de input en cliente y servidor

---

## Información Técnica

### Flujo de la Aplicación
```
1. Usuario logueado → /dashboard
2. Se cargan:
   - Últimos 10 usuarios (cada 30s)
   - Posts recientes (cada 10s)
3. Usuario puede:
   - Crear nuevo post → se refresca automáticamente
   - Hacer click en usuario → ver su perfil
   - Volver al feed desde perfil
```

### Performance
- **Límites de datos:** 10 usuarios, 50 posts por defecto
- **Auto-refresco:** 30s usuarios, 10s posts (configurable)
- **Cache:** React Query con manejo automático
- **Paginación:** Listo para agregar (base implementada)

---


## Próximas Mejoras Planeadas (v1.2.0)

Consulta los archivos `PROJECT_STRUCTURE_v1.1.0.md` y `IMPLEMENTATION_SUMMARY_v1.1.0.md` para detalles adicionales y roadmap actualizado.

**Versión:** 1.1.0  
**Fecha:** 15 de enero de 2026  
**Desarrollador:** GeoInterest Team

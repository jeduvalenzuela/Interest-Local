
# 📍 GeoInterest - v1.1.0
### Plataforma social hiperlocal con dashboard social

GeoInterest es una plataforma social **SPA** para conectar usuarios en tiempo real según ubicación e intereses, usando **WordPress (Headless API)** y **React**.

**Estado:** ✅ Producción | **Última Actualización:** 15 de enero de 2026

---

## 🚀 Stack Tecnológico

| Componente | Tecnología | Versión |
|-----------|-----------|---------|
| **Backend** | WordPress (Custom Theme) | 6.4+ |
| **Base de Datos** | MySQL | 5.7+ |
| **API** | REST API + JWT | Custom |
| **Autenticación** | JSON Web Tokens (HS256) | 7 días |
| **Frontend** | React + Vite | 18.2 / 4.5 |
| **Estado** | TanStack React Query | 5.0 |
| **Enrutamiento** | React Router | 6.20 |
| **Estilos** | CSS3 | Mobile First |

---

## 🛠️ Requisitos Previos

- **Node.js:** v18.x o superior
- **npm:** v9.x o superior
- **PHP:** 7.4 o superior
- **MySQL:** 5.7 o superior
- **WordPress:** 6.0 o superior (instalación limpia)

---

## 📦 Instalación y Configuración

### 1. Backend (WordPress)

#### Paso 1: Instalación del Tema
```bash
# Copiar el tema a WordPress
cp -r geointerest-theme /ruta/a/wp-content/themes/
```

#### Paso 2: Activación en WordPress
1. Accede al panel de administración (`/wp-admin`)
2. Ve a **Apariencia > Temas**
3. Busca "GeoInterest" y haz clic en **Activar**
4. El tema creará automáticamente las tablas personalizadas y semillas iniciales

#### Paso 3: Configuración de Permalinks
1. Ve a **Configuración > Enlaces permanentes**
2. Selecciona **Nombre de la entrada**
3. Guarda cambios

#### Paso 4: Configuración de Seguridad JWT
Añade a tu `wp-config.php`:

```php
// Definir clave secreta para JWT (REQUERIDO en producción)
define('JWT_AUTH_SECRET_KEY', 'tu-clave-secreta-super-segura-aqui-minimo-32-caracteres');
```

⚠️ **Importante en Producción:** Generar una clave segura con `wp_generate_password(64)` o similar.

---

### 2. Frontend (React + Vite)

#### Paso 1: Instalar Dependencias
```bash
npm install
```

#### Paso 2: Configuración de Desarrollo

Crear archivo `.env.local` (opcional, para override):
```env
VITE_API_BASE=http://localhost/wp-json/geointerest/v1/
VITE_SITE_URL=http://localhost
```

#### Paso 3: Iniciar Servidor de Desarrollo
```bash
npm run dev
```

Accede a `http://localhost:5173` en tu navegador.

#### Paso 4: Build de Producción
```bash
npm run build
```

Los archivos compilados se generarán en la carpeta `build/`.

---


## 📚 Estructura del Proyecto (v1.1.0)

Ver archivo `PROJECT_STRUCTURE_v1.1.0.md` para la estructura completa y actualizada.

---

## 🔌 API REST Endpoints

### Autenticación (Sin JWT requerido)

| Método | Endpoint | Descripción | Body |
|--------|----------|-------------|------|
| `POST` | `/auth/register` | Registrar usuario | `{username, email, password, display_name}` |
| `POST` | `/auth/login` | Login (genera JWT) | `{username, password}` |

**Response:**
```json
{
  "success": true,
  "token": "eyJhbGc...",
  "user": {
    "id": 123,
    "username": "john_doe",
    "email": "john@example.com",
    "display_name": "John"
  }
}
```

### Usuarios (JWT requerido)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `POST` | `/user/location` | Actualizar ubicación actual |
| `GET` | `/user/interests` | Obtener intereses del usuario |
| `POST` | `/user/interests` | Guardar intereses del usuario |

### Intereses (Sin autenticación)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/interests` | Catálogo completo de intereses |

### Matching y Foros (JWT requerido)

| Método | Endpoint | Descripción | Parámetros |
|--------|----------|-------------|-----------|
| `GET` | `/matches` | Usuarios cercanos con intereses comunes | `?radius=10&limit=50` |
| `GET` | `/forum/{id}/messages` | Mensajes de un foro local | `?radius=10&limit=50&offset=0` |
| `POST` | `/forum/{id}/messages` | Publicar mensaje en foro | `{content}` |

---

## 🗄️ Esquema de Base de Datos

### Tablas Personalizadas

#### `wp_user_locations`
```sql
id (PK)
user_id (FK wp_users)
latitude (DECIMAL 10,8)
longitude (DECIMAL 11,8)
accuracy (FLOAT)
updated_at (DATETIME)
```

#### `wp_interests`
```sql
id (PK)
name (VARCHAR 100)
slug (VARCHAR 100 UNIQUE)
icon (VARCHAR 50)
color (VARCHAR 7)
created_at (DATETIME)
```

#### `wp_user_interests`
```sql
id (PK)
user_id (FK wp_users)
interest_id (FK wp_interests)
UNIQUE(user_id, interest_id)
created_at (DATETIME)
```

#### `wp_forum_messages`
```sql
id (PK)
user_id (FK wp_users)
interest_id (FK wp_interests)
content (TEXT)
latitude (DECIMAL 10,8)
longitude (DECIMAL 11,8)
created_at (DATETIME)
updated_at (DATETIME)
```

#### `wp_user_tokens` (JWT)
```sql
id (PK)
user_id (FK wp_users)
token_hash (VARCHAR 64)
expires_at (DATETIME)
created_at (DATETIME)
```

---

## 🔐 Seguridad y CORS

### Headers CORS Configurados

```php
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Credentials: true
Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce
```

⚠️ **Nota:** En producción, cambiar `*` por dominio específico.

### Autenticación JWT

- **Algoritmo:** HS256 (HMAC-SHA256)
- **Expiración:** 7 días
- **Storage Frontend:** `localStorage` (considerar httpOnly cookies en producción)
- **Header:** `Authorization: Bearer <token>`

---

## 🚀 Flujo de Aplicación

```
1. LANDING/LOGIN
   └─> POST /auth/login {username, password}
       └─> Guardar JWT en localStorage

2. SELECCIÓN DE INTERESES
   └─> GET /interests (catálogo)
   └─> POST /user/interests {interest_ids}

3. SOLICITAR UBICACIÓN
   └─> navigator.geolocation.getCurrentPosition()
       └─> POST /user/location {latitude, longitude, accuracy}

4. DASHBOARD
   └─> GET /user/interests (intereses del usuario)
   └─> Navegar a foros o buscar matches

5. FORO LOCAL
   └─> GET /forum/{id}/messages (cargar mensajes cercanos)
   └─> POST /forum/{id}/messages {content} (publicar)

6. MATCHES (Usuarios cercanos)
   └─> GET /matches (encuentra usuarios cercanos con intereses comunes)
```

---

## 🧪 Testing Manual

### 1. Probar Registro y Login
```bash
# Terminal 1: Frontend
npm run dev

# Terminal 2: Verificar API en navegador/Postman
curl -X POST http://localhost/wp-json/geointerest/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "email": "test@example.com",
    "password": "Test@1234",
    "display_name": "Test User"
  }'
```

### 2. Probar Endpoints con JWT
```bash
# Usar token obtenido del registro/login
TOKEN="eyJhbGc..."

curl -X POST http://localhost/wp-json/geointerest/v1/user/location \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": 40.7128,
    "longitude": -74.0060,
    "accuracy": 10
  }'
```

---

## 🔄 Variables de Entorno

### Frontend (`.env.local`)
```env
VITE_API_BASE=http://localhost/wp-json/geointerest/v1/
VITE_SITE_URL=http://localhost
VITE_DEBUG=false
```

### Backend (`wp-config.php`)
```php
define('JWT_AUTH_SECRET_KEY', 'tu-clave-secreta');
define('WP_DEBUG', false); // true en desarrollo
define('WP_DEBUG_LOG', true);
```

---

## 📋 Checklist de Desarrollo

### Antes de Nuevo Feature
- [ ] Crear rama feature: `git checkout -b feature/nombre`
- [ ] Actualizar versión en `functions.php` (v1.0.2 → v1.0.3)
- [ ] Escribir tests (si aplica)
- [ ] Documentar endpoints en este README
- [ ] Hacer build: `npm run build`
- [ ] Verificar sin errores en consola

### Antes de Merge a Main
- [ ] Código reviewed
- [ ] Tests pasados
- [ ] Documentación actualizada
- [ ] Build generado
- [ ] Versión bumpada

### Antes de Deploy
- [ ] Backup de BD
- [ ] Test en staging
- [ ] HTTPS habilitado
- [ ] JWT_AUTH_SECRET_KEY configurado
- [ ] CORS restringido a dominio
- [ ] Logs de error monitoreados

---

## 🛠️ Scripts Disponibles

```bash
# Desarrollo
npm run dev              # Iniciar servidor Vite (http://localhost:5173)

# Producción
npm run build            # Compilar para producción
npm run preview          # Preview de build

# Otros
npm run clean            # Limpiar carpeta build/ (si existe)
```

---

## 📝 Convenciones de Código

### PHP
- Función: `geointerest_snake_case()`
- Clase: `GeoInterest_CamelCase`
- Hook: `geointerest_hook_name`

### JavaScript/React
- Componente: `PascalCase.jsx`
- Hook personalizado: `useHookName()`
- Función auxiliar: `camelCase.js`

---

## 🐛 Troubleshooting

### Frontend no se conecta a API
```
✓ Verificar que WordPress está en http://localhost
✓ Verificar CORS headers en functions.php
✓ Verificar que el tema está activado
✓ Limpiar localStorage y recargar
```

### JWT Token expirado
```
✓ Limpiar localStorage: localStorage.clear()
✓ Re-login para generar nuevo token
✓ Verificar que JWT_AUTH_SECRET_KEY está definido en wp-config.php
```

### Geolocalización no funciona
```
✓ Asegurar que estás en HTTPS o localhost
✓ Dar permiso al navegador
✓ Verificar que el endpoint /user/location retorna 200
```

---


## 📋 Registro de Cambios

Ver archivo `CHANGELOG_v1.1.0.md` para el historial de cambios y roadmap.

## 📞 Soporte y Contribución

Para reportar bugs o proponer features:
1. Abrir issue detallando el problema
2. Incluir versión de GeoInterest
3. Pasos para reproducir

## 📄 Licencia

MIT

**Versión:** 1.1.0  
**Última actualización:** 15 de enero de 2026  
**Responsable:** GeoInterest Team

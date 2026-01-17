# Para detalles técnicos y de desarrollo actualizados, consulta también:
# - `PROJECT_STRUCTURE_v1.1.0.md`
# - `CHANGELOG_v1.1.0.md`
# - `IMPLEMENTATION_SUMMARY_v1.1.0.md`
# 👨‍💻 Guía para Futuros Desarrolladores - GeoInterest v1.0.2

Este documento contiene instrucciones detalladas para trabajar en nuevas features y mejoras del proyecto GeoInterest.

---

## 📌 Estado Actual del Proyecto (v1.0.2)

### ✅ Completado
- Autenticación JWT
- Geolocalización en tiempo real
- Selección de intereses
- Matching de usuarios
- Foros locales
- API REST completa
- Frontend React + Vite
- CORS habilitado
- Build de producción

### 🔄 En Desarrollo
- (Ninguno actualmente)

### 📋 Próximas Features
1. WebSocket para mensajes en tiempo real
2. Chat privado entre usuarios
3. Fotos de perfil
4. Sistema de ratings
5. Moderación de contenido
6. App móvil (React Native)

---

## 🔧 Configuración del Entorno de Desarrollo

### 1. Clonar y Configurar
```bash
# Clonar repo
git clone <repo-url>
cd geointerest-theme

# Instalar dependencias
npm install

# Crear archivo de variables (opcional)
cp .env.example .env.local
```

### 2. WordPress Localmente
```bash
# Opción 1: Docker (recomendado)
docker run --name wordpress-geointerest \
  -e WORDPRESS_DB_PASSWORD=secret \
  -p 80:80 \
  -v $(pwd):/var/www/html/wp-content/themes/geointerest-theme \
  -d wordpress:latest

# Opción 2: XAMPP/Local
# Descargar XAMPP, crear DB, copiar tema a wp-content/themes/
```

### 3. Activar Tema
1. Acceder a http://localhost/wp-admin
2. Apariencia > Temas > GeoInterest > Activar
3. Configuración > Enlaces permanentes > Nombre entrada > Guardar

### 4. Configurar JWT
Editar `wp-config.php`:
```php
define('JWT_AUTH_SECRET_KEY', wp_generate_password(64, true, true));
```

### 5. Iniciar Desarrollo
```bash
# Terminal 1
npm run dev      # Vite en puerto 5173

# Terminal 2 (opcional)
npm run build    # Watch mode (si lo necesitas)
```

---

## 📂 Estructura de Ramas Git

```
main (producción)
  ├─ develop (desarrollo)
  │   ├─ feature/websocket-realtime
  │   ├─ feature/user-chat
  │   ├─ feature/profile-photos
  │   └─ bugfix/...
  └─ release/v1.1.0
```

### Crear Feature Nueva
```bash
# Desde develop
git checkout develop
git pull origin develop
git checkout -b feature/nombre-feature

# Después de cambios
git add .
git commit -m "feat: descripción clara"
git push origin feature/nombre-feature
# → Crear Pull Request en GitHub
```

---

## 🚀 Flujo de Trabajo: Agregar Nueva Feature

### Paso 1: Planificar

**Crear issue con:**
- Descripción
- Endpoints API necesarios
- Cambios en BD (si aplica)
- Cambios en frontend
- Estimación de tiempo

### Paso 2: Backend (PHP)

#### 2.1 Si requiere nueva tabla
Editar `inc/database.php`:
```php
$sql_nueva_tabla = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tabla_nueva (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    ... columnas ...
    PRIMARY KEY (id),
    KEY indice_idx (columna)
) $charset_collate;";

dbDelta($sql_nueva_tabla);
```

#### 2.2 Si requiere nuevo endpoint
Editar `inc/api-endpoints.php`:
```php
register_rest_route($namespace, '/nueva-ruta', [
    'methods' => 'POST',
    'callback' => 'geointerest_nueva_funcion',
    'permission_callback' => 'geointerest_auth_middleware' // si requiere auth
]);

function geointerest_nueva_funcion($request) {
    // Validaciones
    $param = sanitize_text_field($request->get_param('param'));
    
    if (empty($param)) {
        return new WP_Error('invalid', 'Mensaje', ['status' => 400]);
    }
    
    // Lógica
    global $wpdb;
    // ...
    
    return ['success' => true, 'data' => $data];
}
```

#### 2.3 Si requiere nueva función auxiliar
Editar `inc/helpers.php`:
```php
function geointerest_nueva_funcion($param) {
    // Implementación
    return $resultado;
}
```

### Paso 3: Frontend (React)

#### 3.1 Nuevo endpoint en API client
Editar `src/utils/api.js`:
```javascript
// Agregar método al APIClient
newFeature(data) {
    return this.post('/nueva-ruta', data);
}
```

#### 3.2 Nuevo context (si es estado global)
Crear `src/context/NuevaContext.jsx`:
```javascript
import React, { createContext, useContext, useState } from 'react';

const NuevaContext = createContext();

export const useNueva = () => {
    const context = useContext(NuevaContext);
    if (!context) {
        throw new Error('useNueva debe usarse dentro de NuevaProvider');
    }
    return context;
};

export default function NuevaProvider({ children }) {
    const [state, setState] = useState(null);
    
    // Métodos y lógica
    
    return (
        <NuevaContext.Provider value={{ state, /* ... */ }}>
            {children}
        </NuevaContext.Provider>
    );
}
```

#### 3.3 Nuevo componente
Crear `src/pages/NuevaPagina.jsx`:
```javascript
import React from 'react';
import { apiClient } from '../utils/api';

export default function NuevaPagina() {
    // Componente
    
    return <div>Contenido</div>;
}
```

#### 3.4 Agregar ruta
Editar `src/App.jsx`:
```javascript
<Route
    path="/nueva-ruta"
    element={
        <ProtectedRoute>
            <NuevaPagina />
        </ProtectedRoute>
    }
/>
```

### Paso 4: Testing


#### 4.1 Testing Manual

Consulta los endpoints y flujos actuales en `CHANGELOG_v1.1.0.md` y `PROJECT_STRUCTURE_v1.1.0.md` para pruebas manuales y automatizadas.

#### 4.2 Testing de BD
```sql
-- Verificar que tabla fue creada
SHOW TABLES LIKE 'wp_tabla_nueva';

-- Verificar índices
SHOW INDEX FROM wp_tabla_nueva;
```

### Paso 5: Documentación

1. Actualizar `Readme.md`:
   - Agregar endpoint en tabla de API
   - Actualizar esquema BD si aplica
   - Actualizar flujo de aplicación

2. Actualizar `REVISION_REPORT.md`:
   - Agregar sección de nueva feature

### Paso 6: Build y Deploy

```bash
# Build
npm run build

# Verificar build sin errores
npm run preview

# Commit
git add .
git commit -m "feat: descripción clara"
git push origin feature/nombre-feature

# Pull Request → Review → Merge
```

---

## 🧪 Testing Checklist

Antes de merge, verificar:

```
Backend (PHP)
□ Validaciones de input correctas
□ Manejo de errores con HTTP status
□ Query SQL preparadas (prevención SQL injection)
□ Índices en tablas
□ No hay warnings/notices en debug

Frontend (React)
□ Componentes sin errores en consola
□ Manejo de loading states
□ Manejo de errores
□ Responsive en mobile
□ No hay memory leaks
□ Performance aceptable

API Integration
□ Endpoints retornan datos esperados
□ Autenticación JWT funciona
□ CORS headers correctos
□ Paginación (si aplica)

Build
□ npm run build sin errores
□ No hay warnings
□ Archivos .js y .css generados
□ Tamaño de bundle razonable
```

---

## 🔍 Debugging

### Frontend
```javascript
// En consola del navegador
localStorage.clear()                    // Limpiar datos
console.log(window.geointerestConfig)  // Ver config
fetch('http://localhost/wp-json/geointerest/v1/interests')  // Probar API
```

### Backend
Editar `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
// Los errores irán a wp-content/debug.log
```

### Base de Datos
```bash
# Conectar a BD
mysql -u root -p wordpress_db

# Verificar datos
SELECT * FROM wp_user_locations;
SELECT * FROM wp_forum_messages;
```

---

## 📦 Versionado

### Incrementar Versión
```
v1.0.2 → v1.0.3 (patch - bugfix)
v1.0.2 → v1.1.0 (minor - feature)
v1.0.2 → v2.0.0 (major - breaking changes)
```

Actualizar en:
1. `functions.php` → `define('GEOINTEREST_VERSION', '1.0.3');`
2. `package.json` → `"version": "1.0.3"`
3. `Readme.md` → título y fecha

---

## 🚨 Errores Comunes y Soluciones

### Error: "Token inválido o expirado"
```
Causa: JWT_AUTH_SECRET_KEY no definido o mal configurado
Solución: Editar wp-config.php y define correcto
```

### Error: "CORS policy"
```
Causa: Headers CORS no configurados
Solución: Verificar functions.php, hook rest_api_init
```

### Error: "Undefined table 'wp_user_locations'"
```
Causa: Tema no activado correctamente
Solución: Reactivar tema en Apariencia > Temas
```

### Error: "npm not found"
```
Causa: Node.js no instalado
Solución: Descargar e instalar de nodejs.org
```

---

## 📚 Recursos Útiles

- [WordPress REST API](https://developer.wordpress.org/rest-api/)
- [React Documentation](https://react.dev)
- [Vite Documentation](https://vitejs.dev)
- [React Query Docs](https://tanstack.com/query/latest)
- [JWT.io](https://jwt.io)
- [MySQL Documentation](https://dev.mysql.com/doc/)

---

## 📞 Contacto y Preguntas

Si tienes dudas sobre cómo implementar algo:
1. Revisa esta guía
2. Revisa la documentación de Readme.md
3. Revisa REVISION_REPORT.md
4. Abre una issue describiendo el problema

---

**Última actualización:** 14 de enero de 2026  
**Mantenedor:** Development Team

# 🌐 URLs Dinámicas - Configuración de Dominios

## Problema Resuelto

Anteriormente, la URL del sitio estaba **hardcodeada** en múltiples lugares:
```
❌ https://gavaweb.com/stg/wp-json/geointerest/v1
❌ https://gavaweb.com/stg/wp-json
```

Esto significaba que si cambiaras el dominio, tenías que editar manualmente 10+ archivos.

## Solución Implementada

Ahora **TODO es dinámico**, detectando automáticamente:
- ✅ El protocolo (http o https)
- ✅ El dominio
- ✅ La ruta base (ej: /stg, /staging, /prod, etc)

## Cómo Funciona

### 1. En el Frontend (src/utils/api.js)

```javascript
const getApiBase = () => {
  const { protocol, host, pathname } = window.location;
  const pathParts = pathname.split('/').filter(Boolean);
  const basePath = pathParts.length > 0 ? `/${pathParts[0]}` : '';
  return `${protocol}//${host}${basePath}/wp-json/geointerest/v1`;
};
```

**Ejemplos:**

| URL actual | API Base detectada |
|------------|-------------------|
| `https://gavaweb.com/stg/` | `https://gavaweb.com/stg/wp-json/geointerest/v1` |
| `https://localhost:3000/` | `https://localhost:3000/wp-json/geointerest/v1` |
| `https://ejemplo.com/prod/` | `https://ejemplo.com/prod/wp-json/geointerest/v1` |
| `https://ejemplo.com/` | `https://ejemplo.com/wp-json/geointerest/v1` |

### 2. En el Router (src/App.jsx)

```javascript
const getBasename = () => {
  const pathname = window.location.pathname;
  const parts = pathname.split('/').filter(Boolean);
  
  if (parts.length > 0 && !parts[0].includes('.')) {
    return `/${parts[0]}`;
  }
  
  return '/';
};

const basename = getBasename();
// <BrowserRouter basename={basename}>
```

**Ejemplos:**

| URL actual | Basename detectado |
|------------|-------------------|
| `https://gavaweb.com/stg/` | `/stg` |
| `https://localhost:3000/` | `/` |
| `https://ejemplo.com/prod/` | `/prod` |

### 3. En el Logout (src/context/AuthContext.jsx)

```javascript
const basename = (parts.length > 0 && !parts[0].includes('.')) ? `/${parts[0]}` : '';
window.location.href = `${basename}/auth`;
```

## Archivos Modificados

✅ `src/utils/api.js` - Construcción dinámica de API_BASE
✅ `src/App.jsx` - Basename dinámico para Router
✅ `src/context/AuthContext.jsx` - Logout dinámico
✅ `.env` - URL relativa
✅ `.env.local` - Comentarios aclaratorios

## Ventajas

✅ **0 hardcoding** - Todo es dinámico
✅ **Cambio de dominio simple** - Solo cambia el dominio, todo funciona
✅ **Desarrollo flexible** - Funciona en localhost, staging, producción
✅ **Múltiples entornos** - localhost, /stg, /staging, /prod, etc

## Casos de Uso

### Cambiar de dominio (Producción)

**Antes (había que editar 10+ archivos):**
```
gavaweb.com/stg → nuevaempresa.com/
```

**Ahora (Solo cambias el dominio en DNS):**
- La aplicación detecta automáticamente el nuevo dominio
- Todas las URLs se actualizan sin cambiar código

### Múltiples entornos en el mismo servidor

```
- https://ejemplo.com/dev/      → API en /dev/wp-json/geointerest/v1
- https://ejemplo.com/staging/  → API en /staging/wp-json/geointerest/v1
- https://ejemplo.com/prod/     → API en /prod/wp-json/geointerest/v1
```

Todo funciona con el mismo código compilado.

## Debug

En la consola del navegador verás:

```javascript
🌐 API Base URL: https://gavaweb.com/stg/wp-json/geointerest/v1
📍 Basename: /stg
📍 Location: https://gavaweb.com/stg/dashboard
```

## Variables de Entorno

Las variables en `.env` ahora son genéricas:

```dotenv
# .env
VITE_WP_API_URL=/wp-json
```

No es necesario cambiarlas para diferentes entornos.

---

**Beneficio final:** Cambio de dominio = 0 código modificado ✨

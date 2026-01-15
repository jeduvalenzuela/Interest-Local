
// ✅ CONSTRUIR LA URL DINÁMICAMENTE DESDE WORDPRESS
const getApiBase = () => {
  console.log('🔍 getApiBase() llamado');
  console.log('   window.geointerestConfig:', window.geointerestConfig);
  
  // Verificar si existe la variable global de WordPress
  if (window.geointerestConfig?.apiUrl) {
    const apiUrl = window.geointerestConfig.apiUrl;
    console.log('✅ API URL desde WordPress (geointerestConfig):', apiUrl);
    return apiUrl.replace(/\/$/, ''); // Remover trailing slash
  }

  // Fallback: Si no existe geointerestConfig, usar el método de pathname
  console.log('⚠️ geointerestConfig no disponible, usando fallback de pathname');
  
  const { host, pathname } = window.location;
  
  // ✅ Usar SIEMPRE HTTPS en lugar de detectar protocolo
  const protocol = 'https:';
  
  console.log('🔍 DETECTANDO RUTA BASE...');
  console.log('   protocol:', protocol);
  console.log('   host:', host);
  console.log('   pathname:', pathname);
  
  let basePath = '';
  
  // Extraer del pathname
  const pathParts = pathname.split('/').filter(Boolean);
  
  console.log('   pathParts:', pathParts);
  
  // Si hay partes en el path y la primera no es un archivo
  if (pathParts.length > 0 && !pathParts[0].includes('.')) {
    basePath = `/${pathParts[0]}`;
    console.log('   ✅ Ruta base extraída:', basePath);
  } else {
    basePath = '';
    console.log('   ✅ Raíz detectada');
  }
  
  // Construir la URL base de la API
  const apiBase = `${protocol}//${host}${basePath}/wp-json/geointerest/v1`;
  
  console.log('🌐 API Base URL FINAL (fallback):', apiBase);
  
  return apiBase;
};

const API_BASE = getApiBase();

class APIClient {
  constructor() {
    this.token = null;
  }

  setToken(token) {
    this.token = token;
  }

  async request(endpoint, options = {}) {
    const headers = {
      'Content-Type': 'application/json',
      ...options.headers,
    };

    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
      console.log('📤 Token enviado:', this.token.substring(0, 20) + '...');
    } else {
      console.log('⚠️ NO hay token disponible en apiClient');
    }

    // ✅ Garantizar que la URL sea absoluta
    const url = `${API_BASE}${endpoint.startsWith('/') ? endpoint : '/' + endpoint}`;

    console.log("Fetching URL:", url); // <-- Para debuggear
    console.log("Headers:", headers); // Ver exactamente qué headers se envían

    const response = await fetch(url, {
      ...options,
      headers,
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({}));
      throw new Error(error.message || 'Error en la petición');
    }

    return response.json();
  }

  get(endpoint, params = {}) {
    const query = new URLSearchParams(params).toString();
    const urlWithQuery = query ? `${endpoint}?${query}` : endpoint;
    return this.request(urlWithQuery, { method: 'GET' });
  }

  post(endpoint, data) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  put(endpoint, data) {
    return this.request(endpoint, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  delete(endpoint) {
    return this.request(endpoint, { method: 'DELETE' });
  }
}

export const apiClient = new APIClient();
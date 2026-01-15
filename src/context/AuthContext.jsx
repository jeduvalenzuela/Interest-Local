// src/context/AuthContext.jsx
import React, { createContext, useContext, useState, useEffect } from 'react';
import { apiClient } from '../utils/api';

const AuthContext = createContext();

export const useAuth = () => useContext(AuthContext);

export default function AuthProvider({ children }) {
  // Inicializamos directamente desde localStorage
  const [token, setToken] = useState(localStorage.getItem('geoi_token'));
  const [user, setUser] = useState(() => {
    const savedUser = localStorage.getItem('geoi_user');
    return savedUser ? JSON.parse(savedUser) : null;
  });
  const [loading, setLoading] = useState(true);

  // Función para que otros componentes (como UnifiedAuth) puedan notificar al contexto
  const setAuthData = (newToken, newUser) => {
    setToken(newToken);
    setUser(newUser);
    localStorage.setItem('geoi_token', newToken);
    localStorage.setItem('geoi_user', JSON.stringify(newUser));
    apiClient.setToken(newToken);
  };

  useEffect(() => {
    if (token) {
      apiClient.setToken(token);
    }
    setLoading(false);
  }, [token]);

  const login = async (username, password) => {
    const response = await apiClient.post('/auth/login', { username, password });
    
    if (response.success && response.token) {
      setAuthData(response.token, response.user);
    }
    return response;
  };

  const logout = () => {
    localStorage.removeItem('geoi_token');
    localStorage.removeItem('geoi_user');
    setToken(null);
    setUser(null);
    apiClient.setToken(null);
    
    // Redirigir a /auth de forma dinámica
    if (typeof window !== 'undefined') {
      let basePath = '/';
      
      // Primero: Usar siteUrl de WordPress
      if (window.geointerestConfig?.siteUrl) {
        try {
          const url = new URL(window.geointerestConfig.siteUrl);
          const path = url.pathname;
          const pathParts = path.split('/').filter(Boolean);
          
          if (pathParts.length > 0) {
            basePath = `/${pathParts[0]}`;
          }
        } catch (e) {
          console.log('⚠️ Error parseando siteUrl:', e);
        }
      } else {
        // Fallback: Usar pathname
        const { pathname } = window.location;
        const pathParts = pathname.split('/').filter(Boolean);
        
        if (pathParts.length > 0 && !pathParts[0].includes('.')) {
          basePath = `/${pathParts[0]}`;
        }
      }
      
      window.location.href = `${basePath}/auth`;
    }
  };

  // Valor único y completo del contexto
  const value = {
    user,
    token,
    isAuthenticated: !!token,
    login,
    logout,
    setAuthData, // Esta es la clave para que UnifiedAuth funcione
    loading
  };

  return (
    <AuthContext.Provider value={value}>
      {!loading && children}
    </AuthContext.Provider>
  );
}
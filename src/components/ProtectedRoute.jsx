// src/components/ProtectedRoute.jsx
import React, { useEffect, useState } from 'react';
import { Navigate } from 'react-router-dom';
import { apiClient } from '../utils/api'; // ✅ Importar apiClient

const ProtectedRoute = ({ children }) => {
  const [isValidating, setIsValidating] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  useEffect(() => {
    const validateSession = async () => {
      const token = localStorage.getItem('geoi_token');
      
      if (!token) {
        setIsValidating(false);
        return;
      }

      try {
        // ✅ Usar apiClient que ya tiene la URL correcta
        apiClient.setToken(token);
        
        const data = await apiClient.post('/auth/validate', {});
        setIsAuthenticated(data.valid === true);
      } catch (err) {
        console.error('Error validando sesión:', err);
        setIsAuthenticated(false);
      } finally {
        setIsValidating(false);
      }
    };

    validateSession();
  }, []);

  if (isValidating) {
    return <div className="loading-screen">Verificando sesión...</div>;
  }

  return isAuthenticated ? children : <Navigate to="/auth" replace />;
};

export default ProtectedRoute;
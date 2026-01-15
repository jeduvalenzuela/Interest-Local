import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import AuthProvider from './context/AuthContext';
import Navbar from './components/Navbar';
import UnifiedAuth from './components/Auth/UnifiedAuth';
import MainMap from './components/Map/MainMap';
import Onboarding from './components/Onboarding/Onboarding';
import ProtectedRoute from './components/ProtectedRoute';
import NewDashboard from './pages/NewDashboard';

function App() {
  // ✅ OBTENER BASENAME DE FORMA DINÁMICA
  const getBasename = () => {
    // Primero: Intentar usar siteUrl de WordPress
    if (window.geointerestConfig?.siteUrl) {
      try {
        const url = new URL(window.geointerestConfig.siteUrl);
        const path = url.pathname;
        const pathParts = path.split('/').filter(Boolean);
        
        if (pathParts.length > 0) {
          const basename = `/${pathParts[0]}`;
          console.log('📍 Basename desde siteUrl:', basename);
          return basename;
        }
      } catch (e) {
        console.log('⚠️ Error parseando siteUrl:', e);
      }
    }

    // Fallback: Usar pathname
    console.log('⚠️ Usando fallback de pathname');
    const { pathname } = window.location;
    
    const pathParts = pathname.split('/').filter(Boolean);
    
    if (pathParts.length > 0 && !pathParts[0].includes('.')) {
      const basename = `/${pathParts[0]}`;
      console.log('📍 Basename desde pathname:', basename);
      return basename;
    }
    
    console.log('📍 Basename: / (raíz)');
    return '/';
  };
  
  const basename = getBasename();

  return (
    <BrowserRouter basename={basename}>
      <AuthProvider>
        <Navbar />
    
        <Routes>
          {/* Ruta de autenticación unificada */}
          <Route path="/auth" element={<UnifiedAuth />} />
          
          {/* Onboarding para nuevos usuarios */}
          <Route 
            path="/onboarding" 
            element={
              <ProtectedRoute>
                <Onboarding />
              </ProtectedRoute>
            } 
          />
          
          {/* Dashboard nuevo - posts y usuarios */}
          <Route 
            path="/dashboard" 
            element={
              <ProtectedRoute>
                <NewDashboard />
              </ProtectedRoute>
            } 
          />
          
          {/* Mapa principal (requiere autenticación) */}
          <Route 
            path="/map" 
            element={
              <ProtectedRoute>
                <MainMap />
              </ProtectedRoute>
            } 
          />
          
          {/* Redirección por defecto */}
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
          
          {/* 404 - Redirigir al auth */}
          <Route path="*" element={<Navigate to="/auth" replace />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
}

export default App;
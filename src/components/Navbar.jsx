import React, { useEffect } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const Navbar = () => {
  const { isAuthenticated, logout, token } = useAuth(); // ✅ Añadimos 'token' aquí
  const location = useLocation();
  const navigate = useNavigate(); // ✅ Obtenemos navigate

  const handleLogout = () => {
    logout(); // Ejecutamos el logout del contexto
    navigate('/auth'); // Redirigimos sin recargar
  };

  // Este log se disparará CADA VEZ que el token cambie
  useEffect(() => {
    console.log("NAVBAR STATE - ¿Autenticado?:", isAuthenticated);
    console.log("NAVBAR STATE - Token actual:", token);
  }, [isAuthenticated, token]);

  // Ahora isAuthenticated será true porque setAuthData actualizó el estado 'token'
  if (!isAuthenticated || location.pathname === '/auth') {
    return null;
  }

  return (
    <nav className="navbar"> {/* Borde rojo temporal para verlo */}
      <div className="navbar-container">
        <Link to="/dashboard">📍 Interest Local</Link>
        <div className="navbar-menu">
          <Link to="/dashboard">📍 Intereses Cercanos</Link>
          <Link to="/onboarding">👤 Perfil</Link>
          <button onClick={handleLogout} className="btn-logout">Cerrar Sesión</button>
        </div>
      </div>
    </nav>
  );
};

export default Navbar;
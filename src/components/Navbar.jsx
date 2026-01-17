import React, { useEffect } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const Navbar = () => {
  const { isAuthenticated, logout, token } = useAuth(); // ✅ Added 'token' here
  const location = useLocation();
  const navigate = useNavigate(); // ✅ Get navigate

  const handleLogout = () => {
    logout(); // Ejecutamos el logout del contexto
    navigate('/auth'); // Redirigimos sin recargar
  };

  // This log will fire EVERY TIME the token changes
  useEffect(() => {
    console.log("NAVBAR STATE - Authenticated?:", isAuthenticated);
    console.log("NAVBAR STATE - Current token:", token);
  }, [isAuthenticated, token]);

  // Now isAuthenticated will be true because setAuthData updated the 'token' state
  if (!isAuthenticated || location.pathname === '/auth') {
    return null;
  }

  return (
    <nav className="navbar"> {/* Temporary red border for visibility */}
      <div className="navbar-container">
        <Link to="/dashboard">📍 Interest Local</Link>
        <div className="navbar-menu">
          <Link to="/dashboard">📍 Nearby Interests</Link>
          <Link to="/onboarding">👤 Profile</Link>
          <button onClick={handleLogout} className="btn-logout">Log Out</button>
        </div>
      </div>
    </nav>
  );
};

export default Navbar;
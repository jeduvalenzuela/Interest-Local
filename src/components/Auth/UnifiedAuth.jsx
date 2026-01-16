// src/components/Auth/UnifiedAuth.jsx
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext'; // 1. Import the authentication hook
import { apiClient } from '../../utils/api'; // ✅ Import apiClient to use the correct URL
import './UnifiedAuth.css';

const UnifiedAuth = () => {
  const { setAuthData } = useAuth();
  const [phone, setPhone] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    if (phone.length < 8) {
      setError('Phone number must have at least 8 digits');
      setLoading(false);
      return;
    }

    try {
      // ✅ Use apiClient which already has the correct URL configured
      const data = await apiClient.post('/auth/phone', { phone });

      // ✅ Validate that data has the necessary properties
      if (data && data.success && data.token && data.user) {
        // Save to localStorage
        localStorage.setItem('geoi_token', data.token);
        localStorage.setItem('geoi_user', JSON.stringify(data.user));

        // Notify the global context
        setAuthData(data.token, data.user);

        // Redirect based on user type
        if (data.is_new_user) {
          navigate('/onboarding');
        } else {
          navigate('/map');
        }
      } else {
        // Handle server errors
        setError(data?.message || 'Unknown error during login');
      }
    } catch (err) {
      console.error('Connection error:', err);
      setError(err.message || 'Could not connect to the server. Try again.');
    } finally {
      setLoading(false);
    }
  };

  const handlePhoneChange = (e) => {
    // Allow only numbers
    const value = e.target.value.replace(/\D/g, '');
    setPhone(value);
  };

  return (
    <div className="unified-auth">
      <div className="auth-card">
        <div className="logo">
          <h1>Interest Local</h1>
          <p className="tagline">Conecta con tu comunidad local</p>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label htmlFor="phone">Número de Teléfono</label>
            <div className="phone-input">
              <span className="country-code">+54</span>
              <input
                id="phone"
                type="tel"
                placeholder="11 2345 6789"
                value={phone}
                onChange={handlePhoneChange}
                maxLength="15"
                required
                disabled={loading}
              />
            </div>
          </div>

          {error && (
            <div className="error-message">
              {error}
            </div>
          )}

          <button 
            type="submit" 
            className="btn-primary"
            disabled={loading || phone.length < 8}
          >
            {loading ? 'Conectando...' : 'Ingresar'}
          </button>
        </form>

        <p className="info-text">
          {phone.length === 0 
            ? 'Ingresa tu número de teléfono para comenzar'
            : 'Si es tu primera vez, crearemos una cuenta automáticamente'
          }
        </p>
      </div>
    </div>
  );
};

export default UnifiedAuth;
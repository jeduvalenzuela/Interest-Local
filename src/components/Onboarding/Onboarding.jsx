import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { apiClient } from '../../utils/api'; // ✅ Import apiClient
import { useUserLocation } from '../../hooks/useUserLocation';
import './Onboarding.css';

const Onboarding = () => {
  const navigate = useNavigate();
  const { location, loading: locationLoading, requestPermission } = useUserLocation();
  
  const [formData, setFormData] = useState({
    display_name: '',
    bio: '',
    phone: '',
    email: '',
    address: '',
    avatar_url: '',
    instagram: '',
    twitter: '',
    facebook: '',
    interests: []
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [availableCategories, setAvailableCategories] = useState([]);


  // Cargar datos del usuario al montar el componente
  useEffect(() => {
    const user = JSON.parse(localStorage.getItem('geoi_user'));
    const token = localStorage.getItem('geoi_token');
    if (!user || !token) {
      // Si no hay usuario, redirigir o mostrar error
      setError('No se encontró información de usuario. Por favor, inicia sesión de nuevo.');
      // navigate('/login'); // Descomenta si quieres redirigir automáticamente
      return;
    }
    apiClient.setToken(token);
    apiClient.get(`/users/${user.id}`)
      .then(data => {
        if (data && data.id) {
          setFormData(prev => ({
            ...prev,
            display_name: data.display_name || '',
            bio: data.bio || '',
            phone: data.phone || '',
            email: data.email || '',
            address: data.address || '',
            avatar_url: data.avatar_url || '',
            instagram: data.instagram || '',
            twitter: data.twitter || '',
            facebook: data.facebook || '',
            interests: Array.isArray(data.interests) ? data.interests : []
          }));
        }
      })
      .catch(err => {
        console.error('Error loading user profile:', err);
      });
    if (!location && !locationLoading) {
      // Location permission not granted, ask user
      console.log('Requesting location permission...');
    }
  }, [location, locationLoading]);

  // Cargar categorías de intereses desde la API nueva
  useEffect(() => {
    apiClient.get('/interest-categories').then(data => {
      if (Array.isArray(data)) {
        setAvailableCategories(data.map(cat => cat.name));
      }
    }).catch(err => {
      console.error('Error fetching categories:', err);
    });
  }, []);

  const toggleInterest = (interest) => {
    if (formData.interests.includes(interest)) {
      setFormData({
        ...formData,
        interests: formData.interests.filter(i => i !== interest)
      });
    } else {
      setFormData({
        ...formData,
        interests: [...formData.interests, interest]
      });
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData({
      ...formData,
      [name]: value
    });
  };

  const handleComplete = async () => {
    // ✅ Verificar ubicación obligatoria
    if (!location) {
      setError('Debe habilitar la ubicación para continuar. Por favor, permite el acceso a tu ubicación.');
      return;
    }

    const user = JSON.parse(localStorage.getItem('geoi_user'));
    const token = localStorage.getItem('geoi_token');

    if (!user || !token) {
      setError('No se encontró información de usuario. Por favor, inicia sesión de nuevo.');
      // navigate('/login'); // Descomenta si quieres redirigir automáticamente
      return;
    }

    if (!formData.display_name) {
      setError('El nombre es requerido');
      return;
    }

    if (formData.interests.length < 3) {
      setError('Selecciona al menos 3 intereses');
      return;
    }

    setLoading(true);
    setError('');

    try {
      // ✅ Use apiClient which already has the correct URL
      apiClient.setToken(token);
      const data = await apiClient.post('/user/profile', {
        user_id: user.id,
        latitude: location.latitude,
        longitude: location.longitude,
        ...formData
      });

      if (data && data.success) {
        // Update local data
        user.display_name = formData.display_name;
        localStorage.setItem('geoi_user', JSON.stringify(user));
        navigate('/dashboard');
      } else {
        setError(data?.message || 'Error guardando el perfil');
      }
    } catch (err) {
      console.error('Error saving profile:', err);
      setError(err.message || 'Error de conexión');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="onboarding">
      <div className="onboarding-card">
        <h2>¡Bienvenido a Interest Local! 🎉</h2>
        <p className="subtitle">Completa tu perfil para conectar con tu comunidad local</p>

        {error && <div className="error-message">{error}</div>}

        {/* Location Permission (MANDATORY) */}
        <div className="form-section location-section">
          <h3>📍 Ubicación (Requerida)</h3>
          {location ? (
            <div className="location-success">
              <span className="check-icon">✓</span>
              <div className="location-info">
                <strong>Ubicación habilitada</strong>
                <small>Lat: {location.latitude.toFixed(4)}, Lng: {location.longitude.toFixed(4)}</small>
              </div>
            </div>
          ) : (
            <div className="location-warning">
              <p>Para usar Interest Local, <strong>necesitamos acceso a tu ubicación</strong>.</p>
              <p>Esto permite que veas intereses cercanos en un radio de 1km.</p>
              <button 
                onClick={requestPermission}
                disabled={locationLoading}
                className="btn-enable-location"
              >
                {locationLoading ? 'Obteniendo ubicación...' : 'Habilitar Ubicación'}
              </button>
              <small>Tu ubicación nunca será compartida públicamente.</small>
            </div>
          )}
        </div>

        {/* Only show form if location is enabled */}
        {location && (
          <>
            {/* Basic Information */}
            <div className="form-section">
              <h3>📋 Información Básica</h3>
              
              <div className="form-group">
                <label>Nombre Completo *</label>
                <input
                  type="text"
                  name="display_name"
                  placeholder="Tu nombre completo"
                  value={formData.display_name}
                  onChange={handleInputChange}
                  maxLength="50"
                  disabled={loading}
                />
              </div>

              <div className="form-group">
                <label>Biografía</label>
                <textarea
                  name="bio"
                  placeholder="Cuéntanos sobre ti (máx. 200 caracteres)"
                  value={formData.bio}
                  onChange={handleInputChange}
                  maxLength="200"
                  disabled={loading}
                  rows="3"
                />
                <small>{formData.bio.length}/200</small>
              </div>

              <div className="form-group">
                <label>Foto de Perfil</label>
                <input
                  type="url"
                  name="avatar_url"
                  placeholder="https://ejemplo.com/foto.jpg"
                  value={formData.avatar_url}
                  onChange={handleInputChange}
                  disabled={loading}
                />
              </div>
            </div>

            {/* Contact */}
            <div className="form-section">
              <h3>📞 Contacto</h3>
              
              <div className="form-group">
                <label>Teléfono</label>
                <input
                  type="tel"
                  name="phone"
                  placeholder="+54 911 2345 6789"
                  value={formData.phone}
                  onChange={handleInputChange}
                  disabled={loading}
                />
              </div>

              <div className="form-group">
                <label>Email</label>
                <input
                  type="email"
                  name="email"
                  placeholder="tu@email.com"
                  value={formData.email}
                  onChange={handleInputChange}
                  disabled={loading}
                />
              </div>

              <div className="form-group">
                <label>Dirección</label>
                <input
                  type="text"
                  name="address"
                  placeholder="Tu zona (ciudad, barrio)"
                  value={formData.address}
                  onChange={handleInputChange}
                  disabled={loading}
                />
              </div>
            </div>

            {/* Social Media */}
            <div className="form-section">
              <h3>🌐 Redes Sociales</h3>
              
              <div className="form-group">
                <label>Instagram</label>
                <input
                  type="text"
                  name="instagram"
                  placeholder="@tu_usuario"
                  value={formData.instagram}
                  onChange={handleInputChange}
                  disabled={loading}
                />
              </div>

              <div className="form-group">
                <label>Twitter</label>
                <input
                  type="text"
                  name="twitter"
                  placeholder="@tu_usuario"
                  value={formData.twitter}
                  onChange={handleInputChange}
                  disabled={loading}
                />
              </div>

              <div className="form-group">
                <label>Facebook</label>
                <input
                  type="text"
                  name="facebook"
                  placeholder="facebook.com/tu_usuario"
                  value={formData.facebook}
                  onChange={handleInputChange}
                  disabled={loading}
                />
              </div>
            </div>

            {/* Interests */}
            <div className="form-section">
              <label>Selecciona tus intereses/categorías (mínimo 3) *</label>
              <div className="interests-grid">
                {availableCategories.length === 0 && <span>No hay categorías disponibles.</span>}
                {availableCategories.map(category => (
                  <button
                    key={category}
                    className={`interest-tag ${formData.interests.includes(category) ? 'selected' : ''}`}
                    onClick={() => toggleInterest(category)}
                    disabled={loading}
                  >
                    {category}
                  </button>
                ))}
              </div>
              <small>{formData.interests.length} seleccionados</small>
            </div>

            <div className="actions">
              <button 
                className="btn-primary"
                onClick={handleComplete}
                disabled={!formData.display_name || formData.interests.length < 3 || loading}
              >
                {loading ? 'Guardando...' : 'Comenzar'}
              </button>
            </div>
          </>
        )}
      </div>
    </div>
  );
};

export default Onboarding;
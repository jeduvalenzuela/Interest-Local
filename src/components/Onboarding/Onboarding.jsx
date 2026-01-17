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


  // Load user data on component mount
  useEffect(() => {
    const user = JSON.parse(localStorage.getItem('geoi_user'));
    const token = localStorage.getItem('geoi_token');
    if (!user || !token) {
      // If no user, redirect or show error
      setError('No user information found. Please log in again.');
      // navigate('/login'); // Uncomment if you want to redirect automatically
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

  // Load interest categories from the new API
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
      setError('You must enable location to continue. Please allow access to your location.');
      return;
    }

    const user = JSON.parse(localStorage.getItem('geoi_user'));
    const token = localStorage.getItem('geoi_token');

    if (!user || !token) {
      setError('No user information found. Please log in again.');
      // navigate('/login'); // Uncomment if you want to redirect automatically
      return;
    }

    if (!formData.display_name) {
      setError('Name is required');
      return;
    }

    if (formData.interests.length < 3) {
      setError('Select at least 3 interests');
      return;
    }

    setLoading(true);
    setError('');

    try {
      apiClient.setToken(token);
      const data = await apiClient.post('/user/profile', {
        user_id: user.id,
        latitude: location.latitude,
        longitude: location.longitude,
        ...formData
      });

      if (data && data.success) {
        // Refrescar usuario actualizado desde la API y actualizar contexto
        const updatedUser = await apiClient.get(`/users/${user.id}`);
        if (updatedUser && updatedUser.id) {
          localStorage.setItem('geoi_user', JSON.stringify(updatedUser));
          // Actualizar contexto si existe window.setAuthData global (React context)
          if (window.setAuthData) {
            window.setAuthData(token, updatedUser);
          }
        }
        navigate('/dashboard');
      } else {
        setError(data?.message || 'Error saving profile');
      }
    } catch (err) {
      console.error('Error saving profile:', err);
      setError(err.message || 'Connection error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="onboarding">
      <div className="onboarding-card">
        <h2>Welcome to Interest Local! 🎉</h2>
        <p className="subtitle">Complete your profile to connect with your local community</p>

        {error && <div className="error-message">{error}</div>}

        {/* Location Permission (MANDATORY) */}
        <div className="form-section location-section">
          <h3>📍 Location (Required)</h3>
          {location ? (
            <div className="location-success">
              <span className="check-icon">✓</span>
              <div className="location-info">
                <strong>Location enabled</strong>
                <small>Lat: {location.latitude.toFixed(4)}, Lng: {location.longitude.toFixed(4)}</small>
              </div>
            </div>
          ) : (
            <div className="location-warning">
              <p>To use Interest Local, <strong>we need access to your location</strong>.</p>
              <p>This allows you to see nearby interests within a 1km radius.</p>
              <button 
                onClick={requestPermission}
                disabled={locationLoading}
                className="btn-enable-location"
              >
                {locationLoading ? 'Getting location...' : 'Enable Location'}
              </button>
              <small>Your location will never be shared publicly.</small>
            </div>
          )}
        </div>

        {/* Only show form if location is enabled */}
        {location && (
          <>
            {/* Basic Information */}
            <div className="form-section">
              <h3>📋 Basic Information</h3>
              
              <div className="form-group">
                <label>Full Name *</label>
                <input
                  type="text"
                  name="display_name"
                  placeholder="Your full name"
                  value={formData.display_name}
                  onChange={handleInputChange}
                  maxLength="50"
                  disabled={loading}
                />
              </div>

              <div className="form-group">
                <label>Bio</label>
                <textarea
                  name="bio"
                  placeholder="Tell us about yourself (max 200 characters)"
                  value={formData.bio}
                  onChange={handleInputChange}
                  maxLength="200"
                  disabled={loading}
                  rows="3"
                />
                <small>{formData.bio.length}/200</small>
              </div>

              <div className="form-group">
                <label>Profile Photo</label>
                <input
                  type="url"
                  name="avatar_url"
                  placeholder="https://example.com/photo.jpg"
                  value={formData.avatar_url}
                  onChange={handleInputChange}
                  disabled={loading}
                />
              </div>
            </div>

            {/* Contact */}
            <div className="form-section">
              <h3>📞 Contact</h3>
              
              <div className="form-group">
                <label>Phone</label>
                <input
                  type="tel"
                  name="phone"
                  placeholder="+1 555 123 4567"
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
                  placeholder="your@email.com"
                  value={formData.email}
                  onChange={handleInputChange}
                  disabled={loading}
                />
              </div>

              <div className="form-group">
                <label>Address</label>
                <input
                  type="text"
                  name="address"
                  placeholder="Your area (city, neighborhood)"
                  value={formData.address}
                  onChange={handleInputChange}
                  disabled={loading}
                />
              </div>
            </div>

            {/* Social Media */}
            <div className="form-section">
              <h3>🌐 Social Media</h3>
              
              <div className="form-group">
                <label>Instagram</label>
                <input
                  type="text"
                  name="instagram"
                  placeholder="@your_username"
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
                  placeholder="@your_username"
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
                  placeholder="facebook.com/your_username"
                  value={formData.facebook}
                  onChange={handleInputChange}
                  disabled={loading}
                />
              </div>
            </div>

            {/* Interests */}
            <div className="form-section">
              <label>Select your interests/categories (minimum 3) *</label>
              <div className="interests-grid">
                {availableCategories.length === 0 && <span>No categories available.</span>}
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
              <small>{formData.interests.length} selected</small>
            </div>

            <div className="actions">
              <button 
                className="btn-primary"
                onClick={handleComplete}
                disabled={!formData.display_name || formData.interests.length < 3 || loading}
              >
                {loading ? 'Saving...' : 'Get Started'}
              </button>
            </div>
          </>
        )}
      </div>
    </div>
  );
};

export default Onboarding;
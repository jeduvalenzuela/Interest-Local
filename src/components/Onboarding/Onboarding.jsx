import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { apiClient } from '../../utils/api'; // ✅ Import apiClient
import './Onboarding.css';

const Onboarding = () => {
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
  const navigate = useNavigate();

  const availableInterests = [
    'Sports', 'Music', 'Art', 'Technology', 
    'Gastronomy', 'Nature', 'Cinema', 'Reading',
    'Photography', 'Travel', 'Fitness', 'Gaming'
  ];

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
    const user = JSON.parse(localStorage.getItem('geoi_user'));
    const token = localStorage.getItem('geoi_token');

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
      // ✅ Use apiClient which already has the correct URL
      apiClient.setToken(token);
      
      const data = await apiClient.post('/user/profile', {
        user_id: user.id,
        ...formData
      });

      if (data && data.success) {
        // Update local data
        user.display_name = formData.display_name;
        localStorage.setItem('geoi_user', JSON.stringify(user));
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

  const handleSkip = () => {
    navigate('/dashboard');
  };

  return (
    <div className="onboarding">
      <div className="onboarding-card">
        <h2>Welcome to GeoInterest! 🎉</h2>
        <p className="subtitle">Complete your profile to better connect with your surroundings</p>

        {error && <div className="error-message">{error}</div>}

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
            <label>Profile Picture</label>
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
              placeholder="Your location (city, area)"
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
          <label>Select your interests (minimum 3) *</label>
          <div className="interests-grid">
            {availableInterests.map(interest => (
              <button
                key={interest}
                className={`interest-tag ${formData.interests.includes(interest) ? 'selected' : ''}`}
                onClick={() => toggleInterest(interest)}
                disabled={loading}
              >
                {interest}
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
          <button className="btn-skip" onClick={handleSkip} disabled={loading}>
            Skip for now
          </button>
        </div>
      </div>
    </div>
  );
};

export default Onboarding;
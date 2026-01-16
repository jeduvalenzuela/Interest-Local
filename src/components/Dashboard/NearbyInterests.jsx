import React, { useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { apiClient } from '../../utils/api';
import { useUserLocation } from '../../hooks/useUserLocation';
import { useAuth } from '../../context/AuthContext';
import './NearbyInterests.css';

/**
 * Component to display nearby interests with member count
 * Fixed radius: 1km (1000 meters)
 */
export default function NearbyInterests() {
  const navigate = useNavigate();
  const { location, loading: locationLoading, error: locationError } = useUserLocation();
  const { user } = useAuth();

  // Load nearby interests
  const { data: nearbyInterests = [], isLoading, error, refetch } = useQuery({
    queryKey: ['nearbyInterests', location?.latitude, location?.longitude],
    queryFn: () => {
      if (location) {
        return apiClient
          .get('/interests/nearby', {
            latitude: location.latitude,
            longitude: location.longitude,
            radius: 1000, // 1km fixed
          })
          .then((res) => {
            const interests = Array.isArray(res) ? res : res.interests || [];
            return interests.sort((a, b) => (b.member_count || 0) - (a.member_count || 0));
          });
      }
      return [];
    },
    enabled: !!location,
    refetchInterval: 30000,
  });

  // Foros creados por el usuario
  const userCreated = nearbyInterests.filter(i => i.creator_id === user?.id);
  // Foros categorizados con intereses de onboarding Y con participantes a menos de 1km
  const userCategories = Array.isArray(user?.interests) ? user.interests : [];
  const categorizedWithMembers = nearbyInterests.filter(i => userCategories.includes(i.category) && i.member_count > 0);

  // If we don't have location, request it
  useEffect(() => {
    if (!location && !locationLoading && locationError) {
      console.warn('Location error:', locationError);
    }
  }, [location, locationLoading, locationError]);

  if (locationLoading) {
    return (
      <div className="nearby-interests loading">
        <div className="spinner">⏳</div>
        <p>Getting your location...</p>
      </div>
    );
  }

  if (locationError || !location) {
    return (
      <div className="nearby-interests error">
        <div className="error-icon">📍</div>
        <p>Could not get your location. Please try again.</p>
      </div>
    );
  }

  if (isLoading) {
    return (
      <div className="nearby-interests loading">
        <div className="spinner">⏳</div>
        <p>Searching for nearby interests...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="nearby-interests error">
        <p>Error loading interests: {error.message}</p>
        <button onClick={() => refetch()} className="btn-retry">
          Retry
        </button>
      </div>
    );
  }

  if (!nearbyInterests || nearbyInterests.length === 0) {
    return (
      <div className="nearby-interests empty">
        <div className="empty-icon">🔍</div>
        <p>No nearby interests within 1km</p>
        <small>Try moving to another location or select interests in your profile</small>
      </div>
    );
  }

  return (
    <div className="nearby-interests">
      <div className="interests-header">
        <h2>Nearby Interests (1km)</h2>
        <span className="count">{nearbyInterests.length}</span>
      </div>

      <div className="interests-list">
        {/* Foros creados por el usuario */}
        {userCreated.length > 0 && <>
          <h3>Your Forums</h3>
          {userCreated.map((interest) => (
            <div
              key={interest.id}
              className="interest-card"
              onClick={() => navigate(`/forum/${interest.id}`)}
            >
              <div className="interest-content">
                <div className="interest-icon">{interest.icon || '⭐'}</div>
                <div className="interest-info">
                  <h3 className="interest-name">{interest.name}</h3>
                  <p className="interest-distance">
                    📍 {interest.distance ? `${(interest.distance / 1000).toFixed(2)}km` : '<1km'}
                  </p>
                  <span className="category-label">Category: {interest.category}</span>
                </div>
              </div>
              <div className="interest-members">
                <span className="member-count">{interest.member_count || 0}</span>
                <span className="member-label">
                  {interest.member_count === 1 ? 'member' : 'members'}
                </span>
              </div>
            </div>
          ))}
        </>}

        {/* Foros categorizados con intereses de onboarding Y con participantes a menos de 1km */}
        {categorizedWithMembers.length > 0 && <>
          <h3>Forums in Your Categories (with nearby members)</h3>
          {categorizedWithMembers.map((interest) => (
            <div
              key={interest.id}
              className="interest-card"
              onClick={() => navigate(`/forum/${interest.id}`)}
            >
              <div className="interest-content">
                <div className="interest-icon">{interest.icon || '⭐'}</div>
                <div className="interest-info">
                  <h3 className="interest-name">{interest.name}</h3>
                  <p className="interest-distance">
                    📍 {interest.distance ? `${(interest.distance / 1000).toFixed(2)}km` : '<1km'}
                  </p>
                  <span className="category-label">Category: {interest.category}</span>
                </div>
              </div>
              <div className="interest-members">
                <span className="member-count">{interest.member_count || 0}</span>
                <span className="member-label">
                  {interest.member_count === 1 ? 'member' : 'members'}
                </span>
              </div>
            </div>
          ))}
        </>}
      </div>

      <div className="location-info">
        <small>
          📍 Location: {location.latitude.toFixed(4)}, {location.longitude.toFixed(4)}
        </small>
      </div>
    </div>
  );
}

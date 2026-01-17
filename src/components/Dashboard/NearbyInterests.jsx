import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../../utils/api';
import { useUserLocation } from '../../hooks/useUserLocation';
import { useAuth } from '../../context/AuthContext';
import './NearbyInterests.css';

export default function NearbyInterests() {
  const navigate = useNavigate();
  const { location, loading: locationLoading, error: locationError } = useUserLocation();
  const { user } = useAuth();

  // State for dynamic radius
  const [radius, setRadius] = useState(1000); // default initial value

  // Get dynamic radius from backend
  useEffect(() => {
    apiClient.get('/system/radius').then((res) => {
      if (res && res.radius_meters) {
        setRadius(res.radius_meters);
      }
    }).catch(() => {});
  }, []);

  // Forums where the user has participated (only if user exists)
  const { data: userForums = [], isLoading: loadingUserForums } = useQuery({
    queryKey: ['userForums', user?.id],
    queryFn: () => {
      if (user && user.id) {
        return apiClient.get('/user/forums', { authenticated_user_id: user.id });
      }
      return [];
    },
    enabled: !!user && !!user.id
  });

  // Load nearby interests
  const { data: nearbyInterests = [], isLoading, error, refetch } = useQuery({
    queryKey: ['nearbyInterests', location?.latitude, location?.longitude, radius],
    queryFn: () => {
      if (location && radius) {
        return apiClient
          .get('/interests/nearby', {
            latitude: location.latitude,
            longitude: location.longitude,
            radius: radius,
          })
          .then((res) => {
            console.log('API /interests/nearby response:', res); // <-- Agrega esto
            res.forEach((interest, idx) => {
                console.log(`Interest[${idx}]:`, interest);
            });
            const interests = Array.isArray(res) ? res : res.interests || [];
            return interests.sort((a, b) => (b.member_count || 0) - (a.member_count || 0));
          });
      }
      return [];
    },
    enabled: !!location && !!radius,
    refetchInterval: 30000,
  });

  // Forums created by the user
  const userCreated = nearbyInterests.filter(i => i.creator_id === user?.id);
  // Forums categorized with onboarding interests AND with participants within 1km
  const userCategories = Array.isArray(user?.interests) ? user.interests : [];

  //const categorizedWithMembers = nearbyInterests.filter(i => userCategories.includes(i.category));
  const categorizedWithMembers = nearbyInterests.filter(i =>
    userCategories.includes(i.category) &&
    Array.isArray(i.participants) &&
    i.participants.some(p => typeof p.distance_km === 'number' && (p.distance_km * 1000) <= radius)
  );
  console.log('userCategories:', userCategories);
  nearbyInterests.forEach(i => {
    console.log('Interest:', i.name, 'Category:', i.category, 'Participants:', i.participants);
  });
  console.log('Filtered interests:', categorizedWithMembers);

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
        <p>No nearby interests within { (radius/1000).toFixed(2) } km</p>
        <small>Try moving to another location or select interests in your profile</small>
      </div>
    );
  }

  return (
    <div className="nearby-interests">
      <div className="interests-header">
        <h2>Nearby Interests ({ (radius/1000).toFixed(2) } km)</h2>
        <span className="count">{nearbyInterests.length}</span>
      </div>

      <div className="interests-list">
        {/* Forums created by the user */}
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
                  <div style={{ marginTop: 8 }}>
                    <strong>Participants:</strong>
                    {Array.isArray(interest.participants) && interest.participants.length > 0 ? (
                      <ul style={{ margin: 0, paddingLeft: 16 }}>
                        {interest.participants.map((p) => (
                          <li key={p.user_id}>
                            {p.display_name} — {p.distance_km} km
                          </li>
                        ))}
                      </ul>
                    ) : (
                      <span> No participants nearby</span>
                    )}
                  </div>
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

        {/* Forums categorized with onboarding interests AND with participants within 1km */}
        {categorizedWithMembers.length > 0 && <>
          <h3>Forums in Your Categories (within 1km)</h3>
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

      {/* Section: forums where the user has participated */}
      <div className="user-forums-section">
        <h2>Forums you participated in</h2>
        {!user || !user.id ? (
          <p>You must be logged in to see your forums.</p>
        ) : loadingUserForums ? (
          <div className="spinner">⏳</div>
        ) : userForums.length === 0 ? (
          <p>You haven't participated in any forum yet.</p>
        ) : (
          <div className="interests-list">
            {userForums.map((forum) => (
              <div
                key={forum.id}
                className="interest-card"
                onClick={() => navigate(`/forum/${forum.id}`)}
              >
                <div className="interest-content">
                  <div className="interest-icon">{forum.icon || '⭐'}</div>
                  <div className="interest-info">
                    <h3 className="interest-name">{forum.name}</h3>
                    <span className="category-label">Category: {forum.category}</span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      <div className="location-info">
        <small>
          📍 Location: {location.latitude.toFixed(4)}, {location.longitude.toFixed(4)}
        </small>
      </div>
    </div>
  );
}

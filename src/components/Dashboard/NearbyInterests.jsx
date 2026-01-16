import React, { useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { apiClient } from '../../utils/api';
import { useUserLocation } from '../../hooks/useUserLocation';
import './NearbyInterests.css';

/**
 * Componente para mostrar intereses cercanos con número de miembros
 * Radio fijo: 1km (1000 metros)
 */
export default function NearbyInterests() {
  const navigate = useNavigate();
  const { location, loading: locationLoading, error: locationError } = useUserLocation();

  // Load nearby interests
  const { data: nearbyInterests = [], isLoading, error, refetch } = useQuery({
    queryKey: ['nearbyInterests', location?.latitude, location?.longitude],
    queryFn: () => {
      if (location) {
        return apiClient
          .get('/interests/nearby', {
            latitude: location.latitude,
            longitude: location.longitude,
            radius: 1000, // 1km fijo
          })
          .then((res) => {
            // Asegurar que sea un array y ordenar por miembros (descendente)
            const interests = Array.isArray(res) ? res : res.interests || [];
            return interests.sort((a, b) => (b.member_count || 0) - (a.member_count || 0));
          });
      }
      return [];
    },
    enabled: !!location,
    refetchInterval: 30000, // Actualizar cada 30 segundos
  });

  // Si no tenemos ubicación, pedirla
  useEffect(() => {
    if (!location && !locationLoading && locationError) {
      console.warn('Location error:', locationError);
    }
  }, [location, locationLoading, locationError]);

  if (locationLoading) {
    return (
      <div className="nearby-interests loading">
        <div className="spinner">⏳</div>
        <p>Obteniendo tu ubicación...</p>
      </div>
    );
  }

  if (locationError || !location) {
    return (
      <div className="nearby-interests error">
        <div className="error-icon">📍</div>
        <p>No pudimos obtener tu ubicación. Intenta de nuevo.</p>
      </div>
    );
  }

  if (isLoading) {
    return (
      <div className="nearby-interests loading">
        <div className="spinner">⏳</div>
        <p>Buscando intereses cercanos...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="nearby-interests error">
        <p>Error al cargar intereses: {error.message}</p>
        <button onClick={() => refetch()} className="btn-retry">
          Reintentar
        </button>
      </div>
    );
  }

  if (!nearbyInterests || nearbyInterests.length === 0) {
    return (
      <div className="nearby-interests empty">
        <div className="empty-icon">🔍</div>
        <p>No hay intereses cercanos en un radio de 1km</p>
        <small>Intenta moverte a otra ubicación o selecciona intereses en tu perfil</small>
      </div>
    );
  }

  return (
    <div className="nearby-interests">
      <div className="interests-header">
        <h2>Intereses Cercanos (1km)</h2>
        <span className="count">{nearbyInterests.length}</span>
      </div>

      <div className="interests-list">
        {nearbyInterests.map((interest) => (
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
              </div>
            </div>
            <div className="interest-members">
              <span className="member-count">{interest.member_count || 0}</span>
              <span className="member-label">
                {interest.member_count === 1 ? 'miembro' : 'miembros'}
              </span>
            </div>
          </div>
        ))}
      </div>

      <div className="location-info">
        <small>
          📍 Ubicación: {location.latitude.toFixed(4)}, {location.longitude.toFixed(4)}
        </small>
      </div>
    </div>
  );
}

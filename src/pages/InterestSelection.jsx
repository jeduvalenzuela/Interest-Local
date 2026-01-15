import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { apiClient } from '../utils/api';
import { useLocation } from '../context/LocationContext';

export default function Dashboard() {
  const navigate = useNavigate();
  const { location, requestLocation } = useLocation();

  const { data: interests, isLoading } = useQuery({
    queryKey: ['userInterests'],
    queryFn: () => apiClient.get('/user/interests'),
  });

  React.useEffect(() => {
    if (!location) {
      requestLocation();
    }
  }, []);

  if (isLoading) return <div className="loading">Cargando...</div>;

  if (!interests || interests.length === 0) {
    navigate('/interests');
    return null;
  }

  return (
    <div style={{ display: 'flex', minHeight: '100vh' }}>
      <aside style={{ width: '250px', background: '#2c3e50', color: 'white', padding: '2rem' }}>
        <h2>Tus Intereses</h2>
        <ul style={{ listStyle: 'none', marginTop: '2rem' }}>
          {interests.map((interest) => (
            <li
              key={interest.id}
              onClick={() => navigate(`/forum/${interest.id}`)}
              style={{
                padding: '1rem',
                marginBottom: '0.5rem',
                cursor: 'pointer',
                borderRadius: '8px',
                background: '#34495e',
                borderLeft: `4px solid ${interest.color}`,
              }}
            >
              <span style={{ marginRight: '0.5rem' }}>{interest.icon}</span>
              {interest.name}
            </li>
          ))}
        </ul>
      </aside>

      <main style={{ flex: 1, padding: '2rem' }}>
        <h1>Bienvenido a GeoInterest</h1>
        <p>Seleccioná un interés para ver los foros locales.</p>
        {location && (
          <div style={{ marginTop: '2rem', padding: '1rem', background: '#e8f5e9', borderRadius: '8px' }}>
            <p>📍 Ubicación activa</p>
            <small>Lat: {location.latitude.toFixed(4)}, Lng: {location.longitude.toFixed(4)}</small>
          </div>
        )}
      </main>
    </div>
  );
}
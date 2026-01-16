import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { apiClient } from '../utils/api';
import { useLocation } from '../context/LocationContext';
import './Dashboard.css';

export default function Dashboard() {
  const navigate = useNavigate();
  const { location, requestLocation } = useLocation();

  // Fetch user interests
  const { data: interests, isLoading } = useQuery({
    queryKey: ['userInterests'],
    queryFn: () => apiClient.get('/user/interests'),
  });

  React.useEffect(() => {
    if (!location) {
      requestLocation();
    }
  }, []);

  if (isLoading) {
    return <div className="loading">Cargando intereses...</div>;
  }

  if (!interests || interests.length === 0) {
    navigate('/interests');
    return null;
  }

  return (
    <div className="dashboard">
      <aside className="sidebar">
        <h2>Tus Intereses</h2>
        <ul className="interests-list">
          {interests.map((interest) => (
            <li
              key={interest.id}
              onClick={() => navigate(`/forum/${interest.id}`)}
              style={{ borderLeftColor: interest.color }}
            >
              <span className="icon">{interest.icon}</span>
              <span className="name">{interest.name}</span>
            </li>
          ))}
        </ul>
        
        <button
          className="btn-secondary"
          onClick={() => navigate('/interests')}
        >
          Editar Intereses
        </button>
      </aside>

      <main className="main-content">
        <h1>Bienvenido a Interest Local</h1>
        <p>Selecciona un interés de la barra lateral para ver los foros locales.</p>
        
        {location && (
          <div className="location-info">
            <p>📍 Ubicación activa</p>
            <small>
              Lat: {location.latitude.toFixed(4)}, 
              Lng: {location.longitude.toFixed(4)}
            </small>
          </div>
        )}
      </main>
    </div>
  );
}
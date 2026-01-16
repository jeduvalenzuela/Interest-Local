import React from 'react';
import CreateInterestForm from '../components/Dashboard/CreateInterestForm';
import { useAuth } from '../context/AuthContext';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { apiClient } from '../utils/api';
import { useLocation } from '../context/LocationContext';
import './Dashboard.css';


export default function Dashboard() {
  const navigate = useNavigate();
  const { location, requestLocation } = useLocation();
  const { user } = useAuth();

  // Fetch user interests
  const { data: interests, isLoading } = useQuery({
    queryKey: ['userInterests'],
    queryFn: () => apiClient.get('/user/interests'),
  });

  // Interests created by the user
  const userInterests = interests?.filter(i => i.creator_id === user?.id) || [];

  React.useEffect(() => {
    if (!location) {
      requestLocation();
    }
  }, []);

  if (isLoading) {
    return <div className="loading">Loading interests...</div>;
  }

  return (
    <div className="dashboard" style={{ display: 'flex', minHeight: '100vh' }}>
      <aside className="sidebar" style={{ flex: '0 0 320px', background: '#2c3e50', color: '#fff', padding: '1.5rem', minHeight: '100vh' }}>
        <h2>👋 Welcome to Interest Local</h2>
        <p>Hello {user?.display_name || 'User'}, here are your created interests:</p>
        <CreateInterestForm />
        <ul className="interests-list">
          {userInterests.length === 0 && <li>No interests created yet.</li>}
          {userInterests.map((interest) => (
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
      </aside>

      <main className="main-content" style={{ flex: 1, background: '#f5f5f5', padding: '2rem' }}>
        <h1>Nearby Interest 1km</h1>
        <p>Below you will see interests with members within 1km of your location.</p>
        {/* Mostrar NearbyInterests a la derecha */}
        <div style={{ background: '#e0e0e0', borderRadius: '12px', padding: '1.5rem', minHeight: '400px' }}>
          {/* NearbyInterests component */}
          {/* Usar lazy import si es necesario, aquí directo: */}
          {location && <div style={{ marginBottom: '1rem' }}>
            <small>📍 Location: {location.latitude.toFixed(4)}, {location.longitude.toFixed(4)}</small>
          </div>}
          {/* NearbyInterests */}
          <div id="nearby-interests-panel">
            {/* Aquí se debe renderizar el componente NearbyInterests */}
          </div>
        </div>
      </main>
    </div>
  );
}
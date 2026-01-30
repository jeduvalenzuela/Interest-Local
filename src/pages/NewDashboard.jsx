
import React from 'react';
import { useAuth } from '../context/AuthContext';
import { useQuery } from '@tanstack/react-query';
import CreateInterestForm from '../components/Dashboard/CreateInterestForm';
import NearbyInterests from '../components/Dashboard/NearbyInterests';
import { useNavigate } from 'react-router-dom';
import { apiClient } from '../utils/api';
import './NewDashboard.css';

export default function NewDashboard() {
  const { user } = useAuth();
  const navigate = useNavigate();
  // Fetch user interests
  const { data: interests, isLoading } = useQuery({
    queryKey: ['userInterests'],
    queryFn: () => apiClient.get('/user/interests'),
  });
  // Interests created by the user
  const userInterests = interests?.filter(i => i.creator_id === user?.id) || [];

  return (
    <div className="new-dashboard" style={{ display: 'flex', minHeight: '100vh' }}>
      <aside className="dashboard-sidebar">
        <div className="sidebar-header">
          <h2>👋 Welcome to Interest Local</h2>
        </div>
        <p>Hello <strong>{user?.display_name || user?.username}</strong>, here are your created interests:</p>
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

      <main className="dashboard-content">
        <div className="dashboard-header">
          <h1>Nearby Interest</h1>
          <p>Below you will see interests with members near you..</p>
        </div>
        <div style={{ background: '#e0e0e0', borderRadius: '12px', padding: '1.5rem', minHeight: '400px' }}>
          <NearbyInterests />
        </div>
      </main>
    </div>
  );
}

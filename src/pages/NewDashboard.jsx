import React from 'react';
import { useAuth } from '../context/AuthContext';
import NearbyInterests from '../components/Dashboard/NearbyInterests';
import './NewDashboard.css';

export default function NewDashboard() {
  const { user } = useAuth();

  return (
    <div className="new-dashboard">
      <div className="dashboard-header">
        <h1>👋 Bienvenido a Interest Local</h1>
        <p>Hola <strong>{user?.display_name || user?.username}</strong>, aquí están los intereses cercanos en tu zona</p>
      </div>

      <div className="dashboard-content">
        <NearbyInterests />
      </div>
    </div>
  );
}

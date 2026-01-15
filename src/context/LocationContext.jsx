import React, { createContext, useContext, useState } from 'react';
import { apiClient } from '../utils/api';

const LocationContext = createContext();

export const useLocation = () => {
  const context = useContext(LocationContext);
  if (!context) {
    throw new Error('useLocation debe usarse dentro de LocationProvider');
  }
  return context;
};

export default function LocationProvider({ children }) {
  const [location, setLocation] = useState(null);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(false);

  const requestLocation = () => {
    setLoading(true);
    setError(null);

    if (!navigator.geolocation) {
      setError('Geolocalización no soportada');
      setLoading(false);
      return;
    }

    navigator.geolocation.getCurrentPosition(
      async (position) => {
        const coords = {
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          accuracy: position.coords.accuracy,
        };

        setLocation(coords);

        try {
          await apiClient.post('/user/location', coords);
        } catch (err) {
          console.error('Error al actualizar ubicación:', err);
        }

        setLoading(false);
      },
      (err) => {
        setError(err.message);
        setLoading(false);
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0,
      }
    );
  };

  return (
    <LocationContext.Provider
      value={{
        location,
        error,
        loading,
        requestLocation,
      }}
    >
      {children}
    </LocationContext.Provider>
  );
}
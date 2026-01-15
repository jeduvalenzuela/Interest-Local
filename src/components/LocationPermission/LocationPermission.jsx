import React from 'react';
import { useUserLocation } from '../../hooks/useUserLocation';
import './LocationPermission.css';

/**
 * Componente para manejar permisos y estado de ubicación
 */
export default function LocationPermission() {
  const { location, loading, error, permissionDenied, requestPermission } = useUserLocation();

  if (!loading && location) {
    // Ubicación obtenida correctamente
    return (
      <div className="location-permission location-success">
        <div className="location-content">
          <span className="location-icon">📍</span>
          <div className="location-info">
            <strong>Ubicación activa</strong>
            <small>Viendo usuarios en radio de 5 km</small>
          </div>
          <span className="location-accuracy">
            ±{Math.round(location.accuracy)}m
          </span>
        </div>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="location-permission location-loading">
        <span className="spinner">⏳</span>
        <span>Obteniendo tu ubicación...</span>
      </div>
    );
  }

  if (permissionDenied) {
    return (
      <div className="location-permission location-error">
        <div className="location-header">
          <span className="icon">🔒</span>
          <h3>Ubicación deshabilitada</h3>
        </div>

        <div className="location-body">
          <p>
            Para ver usuarios cercanos en un radio de 5 km, necesitamos acceso a tu ubicación.
          </p>

          <div className="privacy-info">
            <h4>🛡️ Privacidad y Seguridad</h4>
            <ul>
              <li>✅ Tu ubicación no se comparte públicamente</li>
              <li>✅ Solo usamos tu ubicación para filtrar usuarios cercanos</li>
              <li>✅ Los otros usuarios NO ven tu ubicación exacta</li>
              <li>✅ Puedes deshabilitar esto en cualquier momento</li>
            </ul>
          </div>

          <button onClick={requestPermission} className="btn-enable-location">
            Habilitar Ubicación
          </button>

          <p className="info-text">
            <small>
              Nota: Necesitarás permitir el acceso en el aviso que te aparecerá en el navegador.
            </small>
          </p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="location-permission location-error">
        <div className="location-header">
          <span className="icon">⚠️</span>
          <h3>Error de ubicación</h3>
        </div>

        <div className="location-body">
          <p>{error}</p>
          <button onClick={requestPermission} className="btn-retry">
            Intentar de Nuevo
          </button>
        </div>
      </div>
    );
  }

  return null;
}

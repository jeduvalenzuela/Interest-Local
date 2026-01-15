import { useEffect, useState } from 'react';
import { apiClient } from '../utils/api';

/**
 * Hook para manejar la geolocalización del usuario
 * Obtiene ubicación, la envía al backend y maneja permisos
 */
export const useUserLocation = () => {
  const [location, setLocation] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [permissionDenied, setPermissionDenied] = useState(false);

  useEffect(() => {
    // Verificar si el navegador soporta geolocalización
    if (!navigator.geolocation) {
      setError('Tu navegador no soporta geolocalización');
      setLoading(false);
      return;
    }

    // Obtener ubicación actual
    navigator.geolocation.getCurrentPosition(
      async (position) => {
        const { latitude, longitude, accuracy } = position.coords;

        console.log('📍 Ubicación obtenida:', { latitude, longitude, accuracy });

        // Guardar en estado
        setLocation({
          latitude,
          longitude,
          accuracy,
          timestamp: new Date()
        });

        // Enviar al backend
        try {
          const response = await apiClient.post('/user/location', {
            latitude,
            longitude,
            accuracy
          });

          if (response.success) {
            console.log('✅ Ubicación guardada en backend');
          }
        } catch (err) {
          console.error('Error guardando ubicación en backend:', err);
          // No es crítico si falla, continuamos igual
        }

        setLoading(false);
      },
      (error) => {
        console.error('Error de geolocalización:', error);

        switch (error.code) {
          case error.PERMISSION_DENIED:
            setError('Permitiste acceso a tu ubicación. Para ver usuarios cercanos, necesitas habilitarlo en configuración.');
            setPermissionDenied(true);
            break;
          case error.POSITION_UNAVAILABLE:
            setError('No se pudo obtener tu ubicación. Intenta de nuevo.');
            break;
          case error.TIMEOUT:
            setError('Tiempo agotado al obtener ubicación. Intenta de nuevo.');
            break;
          default:
            setError('Error desconocido al obtener ubicación');
        }

        setLoading(false);
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0 // No usar caché, siempre ubicación actual
      }
    );

    // Actualizar ubicación cada 30 segundos si está disponible
    const interval = setInterval(() => {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          const { latitude, longitude, accuracy } = position.coords;
          setLocation({
            latitude,
            longitude,
            accuracy,
            timestamp: new Date()
          });

          // Enviar al backend silenciosamente
          apiClient.post('/user/location', {
            latitude,
            longitude,
            accuracy
          }).catch(err => console.error('Error actualizando ubicación:', err));
        },
        (error) => console.error('Error en actualización de ubicación:', error)
      );
    }, 30000);

    return () => clearInterval(interval);
  }, []);

  // Función para solicitar permiso nuevamente
  const requestPermission = () => {
    setPermissionDenied(false);
    setLoading(true);
    setError(null);

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const { latitude, longitude, accuracy } = position.coords;
        setLocation({
          latitude,
          longitude,
          accuracy,
          timestamp: new Date()
        });

        apiClient.post('/user/location', {
          latitude,
          longitude,
          accuracy
        }).catch(err => console.error('Error guardando ubicación:', err));

        setLoading(false);
      },
      (error) => {
        setError('Error al obtener ubicación');
        setLoading(false);
      }
    );
  };

  return {
    location,
    loading,
    error,
    permissionDenied,
    requestPermission
  };
};

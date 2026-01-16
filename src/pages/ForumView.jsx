import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../utils/api';

export default function ForumView() {
  const { interestId } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [newMessage, setNewMessage] = useState('');

  const { data: messages, isLoading } = useQuery({
    queryKey: ['forumMessages', interestId],
    queryFn: () => apiClient.get(`/forum/${interestId}/messages`, { radius: 10 }),
    refetchInterval: 10000,
  });

  const postMessage = useMutation({
    mutationFn: (content) => apiClient.post(`/forum/${interestId}/messages`, { content }),
    onSuccess: () => {
      queryClient.invalidateQueries(['forumMessages', interestId]);
      setNewMessage('');
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    if (newMessage.trim()) {
      postMessage.mutate(newMessage);
    }
  };

  return (
    <div style={{ maxWidth: '800px', margin: '0 auto', padding: '2rem' }}>
      <button onClick={() => navigate('/dashboard')}>← Volver</button>
      <h1 style={{ margin: '1rem 0' }}>Foro Local</h1>

      <div style={{ marginBottom: '2rem' }}>
        {isLoading ? (
          <div>Cargando mensajes...</div>
        ) : messages && messages.length > 0 ? (
          messages.map((msg) => (
            <div key={msg.id} style={{ padding: '1rem', marginBottom: '1rem', background: 'white', borderRadius: '8px', boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                <strong>{msg.author_name || 'Usuario'}</strong>
                <span style={{ color: '#666', fontSize: '0.9rem' }}>
                  {typeof msg.distance_km === 'number' ? msg.distance_km.toFixed(1) + ' km' : '—'}
                </span>
              </div>
              <p>{msg.content}</p>
              <small style={{ color: '#999' }}>{msg.created_at ? new Date(msg.created_at).toLocaleString() : ''}</small>
            </div>
          ))
        ) : (
          <p>No hay mensajes cerca. ¡Sé el primero!</p>
        )}
      </div>
      {postMessage.isError && (
        <div style={{ color: 'red', marginBottom: '1rem' }}>
          Error al publicar: {postMessage.error?.message || 'Intenta nuevamente.'}
        </div>
      )}

      <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
        <textarea
          value={newMessage}
          onChange={(e) => setNewMessage(e.target.value)}
          placeholder="Escribe un mensaje..."
          rows="3"
        />
        <button type="submit" disabled={postMessage.isLoading}>
          {postMessage.isLoading ? 'Enviando...' : 'Publicar'}
        </button>
      </form>
    </div>
  );
}
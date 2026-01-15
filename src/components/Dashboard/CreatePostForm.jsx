import React, { useState } from 'react';
import { apiClient } from '../../utils/api';

export default function CreatePostForm({ onPostCreated }) {
  const [content, setContent] = useState('');
  const [imageUrl, setImageUrl] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!content.trim()) {
      setError('El contenido no puede estar vacío');
      return;
    }

    setLoading(true);
    setError('');

    try {
      await apiClient.post('/posts', {
        content: content.trim(),
        image_url: imageUrl.trim() || null
      });

      setContent('');
      setImageUrl('');
      onPostCreated?.();
    } catch (err) {
      setError(err.message || 'Error al crear el post');
    } finally {
      setLoading(false);
    }
  };

  return (
    <form className="create-post-form" onSubmit={handleSubmit}>
      <div className="form-group">
        <label htmlFor="content">¿Qué estás pensando?</label>
        <textarea
          id="content"
          placeholder="Escribe tu post aquí..."
          value={content}
          onChange={(e) => setContent(e.target.value)}
          maxLength={500}
          disabled={loading}
        />
        <small style={{ color: '#999' }}>{content.length}/500</small>
      </div>

      <div className="form-group">
        <label htmlFor="imageUrl">URL de imagen (opcional)</label>
        <input
          id="imageUrl"
          type="url"
          placeholder="https://ejemplo.com/imagen.jpg"
          value={imageUrl}
          onChange={(e) => setImageUrl(e.target.value)}
          disabled={loading}
        />
      </div>

      {error && <p style={{ color: '#dc3545', margin: '0.5rem 0' }}>{error}</p>}

      <div className="form-actions">
        <button 
          type="submit" 
          className="btn-submit" 
          disabled={loading || !content.trim()}
        >
          {loading ? 'Publicando...' : 'Publicar'}
        </button>
      </div>
    </form>
  );
}

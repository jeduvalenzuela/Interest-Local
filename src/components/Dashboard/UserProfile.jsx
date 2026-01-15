import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../../utils/api';
import PostsList from './PostsList';

export default function UserProfile({ userId, onClose }) {
  const { data: profile, isLoading } = useQuery({
    queryKey: ['userProfile', userId],
    queryFn: () => apiClient.get(`/users/${userId}`),
  });

  if (isLoading) {
    return <div className="loading">Cargando perfil...</div>;
  }

  if (!profile) {
    return <div className="no-posts">Perfil no encontrado</div>;
  }

  return (
    <div>
      <div className="profile-header">
        <div className="profile-avatar-large">
          <img src={profile.avatar_url} alt={profile.display_name} />
        </div>
        <div className="profile-info">
          <h2>{profile.display_name}</h2>
          <p className="profile-username">@{profile.username}</p>
          <p>{profile.posts?.length || 0} posts</p>
          <button className="btn-close" onClick={onClose}>
            ← Volver
          </button>
        </div>
      </div>

      <div style={{ marginTop: '1.5rem' }}>
        <h3 style={{ marginBottom: '1rem' }}>Posts de {profile.display_name}</h3>
        {profile.posts && profile.posts.length > 0 ? (
          <PostsList posts={profile.posts} isLoading={false} />
        ) : (
          <div className="no-posts">Este usuario no tiene posts aún</div>
        )}
      </div>
    </div>
  );
}

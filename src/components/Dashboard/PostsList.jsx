import React from 'react';
import { formatDistanceToNow } from 'date-fns';
import { es } from 'date-fns/locale';

export default function PostsList({ posts, isLoading, currentUserId }) {
  if (isLoading) {
    return <div className="loading">Cargando posts...</div>;
  }

  if (!posts || posts.length === 0) {
    return <div className="no-posts">No hay posts aún. ¡Sé el primero!</div>;
  }

  const formatDate = (dateString) => {
    try {
      return formatDistanceToNow(new Date(dateString), { 
        addSuffix: true,
        locale: es 
      });
    } catch {
      return 'Hace poco';
    }
  };

  return (
    <div className="posts-list">
      {posts.map((post) => (
        <div key={post.id} className="post-item">
          <div className="post-header">
            <div className="post-avatar">
              <img src={post.avatar_url} alt={post.display_name} />
            </div>
            <div className="post-meta">
              <p className="post-author">
                {post.display_name}
                {post.user_id === currentUserId && <span> (Tú)</span>}
              </p>
              <p className="post-time">{formatDate(post.created_at)}</p>
            </div>
          </div>
          
          <div className="post-content">{post.content}</div>
          
          {post.image_url && (
            <div className="post-image">
              <img src={post.image_url} alt="Post" />
            </div>
          )}
        </div>
      ))}
    </div>
  );
}

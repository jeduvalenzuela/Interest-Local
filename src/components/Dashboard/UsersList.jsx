import React from 'react';

export default function UsersList({ 
  users, 
  isLoading, 
  selectedUser, 
  onSelectUser, 
  showDistance = false 
}) {
  if (isLoading) {
    return <div className="loading">Cargando usuarios...</div>;
  }

  if (!users || users.length === 0) {
    return <div className="no-posts">No hay usuarios aún</div>;
  }

  return (
    <div className="users-list">
      {users.map((user) => (
        <div
          key={user.id}
          className={`user-item ${selectedUser?.id === user.id ? 'active' : ''}`}
          onClick={() => onSelectUser(user)}
        >
          <div className="user-avatar">
            <img src={user.avatar_url || user.avatar} alt={user.display_name || user.name} />
          </div>
          <div className="user-info">
            <p className="user-name">{user.display_name || user.name}</p>
            <p className="user-username">
              {user.username ? `@${user.username}` : user.email}
            </p>
            
            {showDistance && user.distance_km && (
              <p className="user-distance">
                📍 {user.distance_km} km
              </p>
            )}
          </div>
        </div>
      ))}
    </div>
  );
}

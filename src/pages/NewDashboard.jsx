import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../utils/api';
import { useAuth } from '../context/AuthContext';
import { useUserLocation } from '../hooks/useUserLocation';
import UsersList from '../components/Dashboard/UsersList';
import PostsList from '../components/Dashboard/PostsList';
import CreatePostForm from '../components/Dashboard/CreatePostForm';
import UserProfile from '../components/Dashboard/UserProfile';
import LocationPermission from '../components/LocationPermission/LocationPermission';
import './NewDashboard.css';

export default function NewDashboard() {
  const { user } = useAuth();
  const { location, loading: locationLoading, error: locationError } = useUserLocation();
  const [selectedUser, setSelectedUser] = useState(null);
  const [refreshPosts, setRefreshPosts] = useState(0);

  // Load nearby users if we have location
  const { data: nearbyUsers, isLoading: nearbyUsersLoading } = useQuery({
    queryKey: ['nearbyUsers', location?.latitude, location?.longitude],
    queryFn: () => {
      if (location) {
        return apiClient.get('/users/nearby', {
          latitude: location.latitude,
          longitude: location.longitude,
          radius: 5000, // 5km
          limit: 50
        }).then(res => res.users || []);
      }
      return [];
    },
    enabled: !!location, // Only execute if we have location
    refetchInterval: 30000, // Reload every 30 seconds
  });

  // Fallback: load latest users if we don't have location
  const { data: latestUsers, isLoading: latestUsersLoading } = useQuery({
    queryKey: ['latestUsers'],
    queryFn: () => {
      return apiClient.get('/users/latest', { limit: 10 }).then(res => 
        Array.isArray(res) ? res : []
      );
    },
    enabled: !location, // Only if we DON'T have location
    refetchInterval: 30000,
  });

  // Use nearby users if available, otherwise use latest users
  const displayUsers = nearbyUsers && nearbyUsers.length > 0 ? nearbyUsers : latestUsers;
  const usersLoading = location ? nearbyUsersLoading : latestUsersLoading;

  // Load latest posts
  const { data: latestPosts, isLoading: postsLoading, refetch: refetchPosts } = useQuery({
    queryKey: ['latestPosts', refreshPosts],
    queryFn: () => {
      return apiClient.get('/posts/latest', { limit: 50 }).then(res => 
        Array.isArray(res) ? res : []
      );
    },
    refetchInterval: 10000, // Reload every 10 seconds
  });

  const handlePostCreated = () => {
    setRefreshPosts(prev => prev + 1);
    refetchPosts();
  };

  return (
    <div className="new-dashboard">
      {/* Left Sidebar - Latest Users */}
      <aside className="dashboard-sidebar">
        {/* Location status */}
        <LocationPermission />

        <div className="sidebar-header">
          <h2>
            {location ? '📍 Nearby Users' : '👥 Latest Users'}
          </h2>
          <span className="count">{displayUsers?.length || 0}</span>
        </div>

        <UsersList
          users={displayUsers}
          isLoading={usersLoading}
          selectedUser={selectedUser}
          onSelectUser={setSelectedUser}
          showDistance={!!location}
        />
      </aside>

      {/* Central Content - User Posts */}
      {selectedUser ? (
        <div className="dashboard-content profile-view">
          <UserProfile 
            userId={selectedUser.id} 
            onClose={() => setSelectedUser(null)}
            showDistance={location ? selectedUser.distance : undefined}
          />
        </div>
      ) : (
        <div className="dashboard-content posts-view">
          <div className="posts-header">
            <h1>📱 My Feed</h1>
            <p>Welcome, <strong>{user?.display_name || user?.username}</strong></p>
          </div>

          {/* Form to create posts */}
          <CreatePostForm onPostCreated={handlePostCreated} />

          {/* List of posts */}
          <div className="posts-container">
            <h3>Latest Posts</h3>
            <PostsList
              posts={latestPosts}
              isLoading={postsLoading}
              currentUserId={user?.id}
            />
          </div>
        </div>
      )}
    </div>
  );
}

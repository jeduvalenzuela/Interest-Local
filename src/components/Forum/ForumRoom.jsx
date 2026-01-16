import React, { useState, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useParams } from 'react-router-dom';
import { apiClient } from '../../utils/api';

/**
 * ForumRoom component: Displays messages and allows posting in a forum (interest room)
 */
export default function ForumRoom() {
  const { interestId } = useParams();
  const queryClient = useQueryClient();
  const [message, setMessage] = useState('');

  // Fetch forum messages
  const { data: messages = [], isLoading } = useQuery({
    queryKey: ['forumMessages', interestId],
    queryFn: () => apiClient.get(`/forum/${interestId}/messages?radius=1000`),
    refetchInterval: 10000,
  });

  // Post a new message
  const mutation = useMutation({
    mutationFn: (newMessage) => apiClient.post(`/forum/${interestId}/messages`, { content: newMessage }),
    onSuccess: () => {
      setMessage('');
      queryClient.invalidateQueries(['forumMessages', interestId]);
    },
  });

  // Report a message
  const reportMutation = useMutation({
    mutationFn: (msgId) => apiClient.post(`/forum/${interestId}/messages/${msgId}/report`),
    onSuccess: () => {
      queryClient.invalidateQueries(['forumMessages', interestId]);
    },
  });

  const handleSend = (e) => {
    e.preventDefault();
    if (message.trim().length === 0) return;
    mutation.mutate(message);
  };

  // Get unique users who posted messages
  const participants = useMemo(() => {
    const userMap = {};
    messages.forEach((msg) => {
      if (!msg.author_id) return;
      // If user already exists, keep the most recent message
      if (!userMap[msg.author_id] || new Date(msg.created_at) > new Date(userMap[msg.author_id].last_activity)) {
        userMap[msg.author_id] = {
          id: msg.author_id,
          name: msg.author_name || 'Anonymous',
          avatar: msg.author_avatar || '',
          last_activity: msg.created_at,
        };
      }
    });
    return Object.values(userMap);
  }, [messages]);

  // Determine online status (active in last 2 minutes)
  const isOnline = (last_activity) => {
    const now = Date.now();
    const last = new Date(last_activity).getTime();
    return (now - last) < 2 * 60 * 1000; // 2 minutes
  };

  return (
    <div className="forum-room">
      <h2>Forum Room</h2>

      {/* Participants List */}
      <div className="participants-list">
        <h3>Participants</h3>
        {participants.length === 0 ? (
          <div className="empty">No participants yet.</div>
        ) : (
          <div className="avatars-row">
            {participants.map((user) => (
              <div key={user.id} className="participant-avatar">
                <div className="avatar-img-wrapper">
                  {user.avatar ? (
                    <img src={user.avatar} alt={user.name} className="avatar-img" />
                  ) : (
                    <div className="avatar-placeholder">{user.name[0]}</div>
                  )}
                  <span className={`status-dot ${isOnline(user.last_activity) ? 'online' : 'offline'}`}></span>
                </div>
                <div className="avatar-name">{user.name}</div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Messages List */}
      {isLoading ? (
        <div className="loading">Loading messages...</div>
      ) : (
        <div className="messages-list">
          {messages.length === 0 ? (
            <div className="empty">No messages yet. Be the first to post!</div>
          ) : (
            messages
              .filter((msg) => (msg.report_count || 0) < 5)
              .map((msg) => (
                <div key={msg.id} className="message-item">
                  <div className="message-author">{msg.author_name || 'Anonymous'}</div>
                  <div className="message-content">{msg.content}</div>
                  <div className="message-date">{new Date(msg.created_at).toLocaleString()}</div>
                  <div className="message-actions">
                    <button
                      className="report-btn"
                      onClick={() => reportMutation.mutate(msg.id)}
                      disabled={reportMutation.isLoading}
                    >
                      {reportMutation.isLoading ? 'Reporting...' : 'Report'}
                    </button>
                    {msg.report_count > 0 && (
                      <span className="report-count">Reported: {msg.report_count}</span>
                    )}
                  </div>
                </div>
              ))
          )}
        </div>
      )}
      <form className="message-form" onSubmit={handleSend}>
        <input
          type="text"
          placeholder="Type your message..."
          value={message}
          onChange={(e) => setMessage(e.target.value)}
          disabled={mutation.isLoading}
        />
        <button type="submit" disabled={mutation.isLoading || message.trim().length === 0}>
          {mutation.isLoading ? 'Sending...' : 'Send'}
        </button>
      </form>
    </div>
  );
}

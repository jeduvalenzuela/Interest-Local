import React, { useState } from 'react';
import { useMutation, useQueryClient, useQuery } from '@tanstack/react-query';
import { useAuth } from '../../context/AuthContext';
import './CreateInterestForm.css';
import { apiClient } from '../../utils/api';

export default function CreateInterestForm({ onCreated }) {
  const [name, setName] = useState('');
  const [icon, setIcon] = useState('');
  const [color, setColor] = useState('#2196f3');
  const [category, setCategory] = useState('');
  const [error, setError] = useState('');
  const queryClient = useQueryClient();
  const { user } = useAuth();

  // Icon options (emojis)
  const iconOptions = ['⭐', '🎵', '⚽', '🎨', '💻', '🍔', '🌳', '🎬', '📚', '📷', '✈️', '🏋️', '🎮'];

  // Obtener categorías desde la API
  const { data: interestsData } = useQuery({
    queryKey: ['allInterests'],
    queryFn: () => apiClient.get('/interests'),
  });
  const availableCategories = Array.isArray(interestsData)
    ? Array.from(new Set(interestsData.map(i => i.category).filter(Boolean)))
    : Array.isArray(interestsData?.interests)
      ? Array.from(new Set(interestsData.interests.map(i => i.category).filter(Boolean)))
      : [];

  const mutation = useMutation({
    mutationFn: (data) => apiClient.post('/interests', data),
    onSuccess: (data) => {
      setName('');
      setIcon('');
      setColor('#2196f3');
      setError('');
      queryClient.invalidateQueries(['userInterests']);
      if (onCreated) onCreated(data);
    },
    onError: (err) => {
      setError(err.message || 'Error creating interest');
    }
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!name.trim()) {
      setError('Name is required');
      return;
    }
    if (!category) {
      setError('Category is required');
      return;
    }
    if (!icon) {
      setError('Icon is required');
      return;
    }
    mutation.mutate({ name, icon, color, category });
  };

  return (
    <form className="create-interest-form" onSubmit={handleSubmit}>
      <h3>Create a New Forum</h3>
      <input
        type="text"
        placeholder="Forum name"
        value={name}
        onChange={e => setName(e.target.value)}
        required
      />
      <div className="form-group">
        <label>Category</label>
        <select value={category} onChange={e => setCategory(e.target.value)} required>
          <option value="">Select a category</option>
          {availableCategories.length === 0 && <option disabled>No categories available</option>}
          {availableCategories.map(cat => (
            <option key={cat} value={cat}>{cat}</option>
          ))}
        </select>
      </div>
      <div className="form-group">
        <label>Icon</label>
        <select value={icon} onChange={e => setIcon(e.target.value)} required>
          <option value="">Select an icon</option>
          {iconOptions.map(ico => (
            <option key={ico} value={ico}>{ico}</option>
          ))}
        </select>
      </div>
      <input
        type="color"
        value={color}
        onChange={e => setColor(e.target.value)}
        title="Pick a color"
      />
      <button type="submit" disabled={mutation.isLoading}>
        {mutation.isLoading ? 'Creating...' : 'Create Forum'}
      </button>
      {error && <div className="error-msg">{error}</div>}
    </form>
  );
}

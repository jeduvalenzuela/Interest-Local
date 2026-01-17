# System Architecture Overview

This document provides a high-level overview of the Interest Local system architecture, including its main components, data flow, and extensibility points.

---

## Table of Contents
- [Architecture Summary](#architecture-summary)
- [Main Components](#main-components)
- [Data Flow](#data-flow)
- [Backend (WordPress)](#backend-wordpress)
- [Frontend (React SPA)](#frontend-react-spa)
- [API Layer](#api-layer)
- [Authentication & Security](#authentication--security)
- [Extensibility](#extensibility)
- [Scaling Considerations](#scaling-considerations)

---

## Architecture Summary

Interest Local is a decoupled web application with a clear separation between frontend and backend. The backend is a custom WordPress theme exposing a REST API, while the frontend is a modern React SPA consuming this API.

---

## Main Components

- **Backend:** WordPress (PHP), custom theme, REST API, JWT authentication
- **Frontend:** React (Vite), React Query, React Router
- **Database:** MySQL (custom tables for users, interests, posts, locations)
- **API:** Custom endpoints for all business logic

---

## Data Flow

1. **User interacts with React SPA** (login, post, select interests, etc.)
2. **Frontend sends API requests** to the WordPress backend (REST endpoints)
3. **Backend processes requests, interacts with DB, returns JSON**
4. **Frontend updates UI** based on API responses (using React Query for caching and reactivity)

---

## Backend (WordPress)

- **Custom Theme:** All business logic is in the theme (no plugins required)
- **inc/database.php:** Table creation and migrations
- **inc/api-endpoints.php:** All REST API endpoints
- **inc/jwt-auth.php:** JWT authentication and validation
- **inc/matching-engine.php:** Geolocation and matching logic
- **inc/helpers.php:** Utility functions

### Custom Tables
- `wp_user_posts` (user posts)
- `wp_user_locations` (user geolocation)
- `wp_interests` (interest catalog)
- `wp_user_interests` (user-interest mapping)
- `wp_user_tokens` (JWT tokens)

---

## Frontend (React SPA)

- **src/pages/**: Main pages (Dashboard, Login, Register, etc.)
- **src/components/**: UI components (UserList, PostsList, etc.)
- **src/context/**: Global state (auth, location)
- **src/utils/api.js**: API client (dynamic base URL)
- **React Query:** Handles data fetching, caching, and auto-refresh

---

## API Layer

- **RESTful endpoints** (see API docs)
- **JWT-protected endpoints** for user data, posts, and interests
- **Public endpoints** for registration, login, and interests catalog
- **Dynamic base URL** detection for multi-environment support

---

## Authentication & Security

- **JWT tokens** for all authenticated requests
- **CORS headers** set in backend for cross-origin requests
- **Input validation** on both backend and frontend
- **HTTPS recommended** for all deployments

---

## Extensibility

- **Add new endpoints** in `inc/api-endpoints.php`
- **Add new tables** in `inc/database.php`
- **Frontend:** Add new pages/components in `src/`
- **Use React context** for new global state
- **Document all changes** in the changelog and structure files

---

## Scaling Considerations

- **Backend:** Can be split into microservices if needed
- **Frontend:** Can be deployed on CDN/static hosting
- **Database:** Use indexes and optimize queries for large datasets
- **API:** Rate limiting and caching can be added for high traffic

---

For more details, see the main README and developer guide.

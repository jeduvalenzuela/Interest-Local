# Interest Local

A high-performance, hyper-local social platform connecting users in real time based on geographic proximity and shared interests. Built as a decoupled SPA with a WordPress backend (REST API + JWT) and a modern React frontend.

---

## Table of Contents
- [Project Overview](#project-overview)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Project Structure](#project-structure)
- [Setup & Installation](#setup--installation)
- [Usage](#usage)
- [API Overview](#api-overview)
- [Contribution Guidelines](#contribution-guidelines)
- [Troubleshooting](#troubleshooting)
- [License](#license)
- [Contact](#contact)

---

## Project Overview

Interest Local is a social platform designed to connect people based on their location and interests. The system leverages a decoupled architecture: WordPress serves as a headless backend, while a React SPA provides a fast, modern user experience.

---

## Features

- Dynamic Dashboard: Social dashboard with real-time feed and user list.
- User Posts: Create, view, and interact with posts (text + optional image).
- User Profiles: View user details and their posts.
- Interest Matching: Find and join local interest-based forums.
- Geolocation: All features are proximity-aware.
- JWT Authentication: Secure login and protected endpoints.
- Responsive Design: Mobile-first, works across devices.
- Auto-refresh: Real-time updates for posts and users.

---

## Technology Stack

- Frontend: React (Vite), React Router, TanStack React Query, CSS3
- Backend: WordPress (Custom Theme), PHP 7.4+, MySQL 5.7+
- API: REST API (custom endpoints), JWT authentication
- Build Tools: Vite, npm
- Other: date-fns (date formatting), dotenv

---

## Project Structure

```
geointerest-theme/
│
├── functions.php                # Theme hooks and setup
├── index.php                    # SPA template
├── style.css                    # Theme metadata
│
├── inc/                         # Backend logic
│   ├── database.php             # DB schema and migrations
│   ├── jwt-auth.php             # JWT authentication
│   ├── api-endpoints.php        # Custom REST API endpoints
│   ├── matching-engine.php      # Geomatching logic
│   ├── helpers.php              # Utility functions
│   └── onboarding.php           # Onboarding logic
│
├── build/                       # Production build output
│   ├── index.js                 # Compiled React app
│   └── index.css                # Compiled styles
│
├── src/                         # Frontend source
│   ├── main.jsx                 # Entry point
│   ├── App.jsx                  # Router and providers
│   ├── App.css                  # Global styles
│   ├── pages/                   # Page components
│   ├── components/              # UI components
│   ├── context/                 # React context (auth, location)
│   └── utils/                   # API client, helpers
│
├── package.json                 # Frontend dependencies
├── vite.config.js               # Vite config
└── .env, .env.local             # Environment variables
```

---

## Setup & Installation

### Prerequisites
- Node.js v18+
- npm v9+
- PHP 7.4+
- MySQL 5.7+
- WordPress 6.4+ (clean install)

### Backend (WordPress)
1. Copy the theme to your WordPress installation:
   ```bash
   cp -r geointerest-theme /path/to/wp-content/themes/
   ```
2. Activate the theme in the WordPress admin panel.
3. Set permalinks to "Post name" in WordPress settings.
4. Add a secure JWT secret to `wp-config.php`:
   ```php
   define('JWT_AUTH_SECRET_KEY', 'your-very-secure-key-here');
   ```

### Frontend (React)
1. Install dependencies:
   ```bash
   npm install
   ```
2. (Optional) Create a `.env.local` for custom API URLs.
3. Start the development server:
   ```bash
   npm run dev
   ```
4. Build for production:
   ```bash
   npm run build
   ```

---

## Usage
- Access the app at `http://localhost:5173` (or your configured domain).
- The dashboard auto-refreshes users and posts in real time.
- Use the sidebar to view users, create posts, and navigate profiles.

---

## API Overview

Key endpoints (see `api-endpoints.php` for full list):

| Method | Endpoint                | Description                  |
|--------|-------------------------|------------------------------|
| GET    | /users/latest           | Latest 10 users              |
| GET    | /users/{id}             | User profile + posts         |
| GET    | /posts/latest           | Latest posts feed            |
| GET    | /posts/user/{id}        | Posts by user                |
| POST   | /posts                  | Create new post (JWT)        |
| POST   | /auth/register          | Register user                |
| POST   | /auth/login             | Login, returns JWT           |
| POST   | /user/location          | Update user location (JWT)   |
| GET    | /user/interests         | Get user interests (JWT)     |
| POST   | /user/interests         | Save user interests (JWT)    |
| GET    | /interests              | List all interests           |
| GET    | /matches                | Find nearby users (JWT)      |
| GET    | /forum/{id}/messages    | Forum messages (JWT)         |
| POST   | /forum/{id}/messages    | Post forum message (JWT)     |

---

## Contribution Guidelines
- Fork the repository and create feature branches.
- Follow code style conventions (see below).
- Document new endpoints and features.
- Submit pull requests with clear descriptions.

### Code Style
- PHP: snake_case for functions, CamelCase for classes.
- JS/React: PascalCase for components, camelCase for functions/hooks.

---

## Troubleshooting
- **Frontend not connecting:** Check CORS headers and theme activation.
- **JWT errors:** Ensure JWT secret is set in `wp-config.php`.
- **Geolocation issues:** Use HTTPS or localhost, grant browser permissions.
- **Build errors:** Run `npm install` and check Node/npm versions.

---

## License
MIT

---

## Contact
For support or contributions, open an issue or contact the maintainers.

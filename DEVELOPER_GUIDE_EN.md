# Developer Onboarding & Scaling Guide

Welcome to the Interest Local project! This guide is designed to help new developers get up to speed quickly and provide best practices for scaling and extending the platform.

---

## Table of Contents
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Codebase Overview](#codebase-overview)
- [Best Practices](#best-practices)
- [Adding Features & Scaling](#adding-features--scaling)
- [Testing](#testing)
- [Deployment](#deployment)
- [Troubleshooting](#troubleshooting)
- [Resources](#resources)

---

## Getting Started

1. **Clone the repository** and install dependencies:
   ```bash
   git clone <repo-url>
   cd interest-local
   npm install
   ```
2. **Set up the backend:**
   - Copy the theme to your WordPress installation.
   - Activate the theme and configure permalinks.
   - Set the JWT secret in `wp-config.php`.
3. **Configure environment variables** as needed in `.env.local`.
4. **Start the development server:**
   ```bash
   npm run dev
   ```

---

## Development Workflow

- Use feature branches for new work: `git checkout -b feature/your-feature`
- Commit often with clear messages.
- Keep pull requests focused and well-documented.
- Run `npm run build` before merging to main.

---

## Codebase Overview

- **Backend:** WordPress custom theme (PHP, REST API, JWT)
- **Frontend:** React (Vite), React Query, React Router
- **Key folders:**
  - `inc/` (backend logic)
  - `src/` (frontend source)
  - `build/` (production output)

---

## Best Practices

- **API:** Document all new endpoints in the API docs.
- **Security:** Always validate and sanitize input on both backend and frontend.
- **Performance:** Use indexes in SQL, cache where possible, and avoid unnecessary re-renders in React.
- **Code Style:**
  - PHP: snake_case for functions, CamelCase for classes.
  - JS/React: PascalCase for components, camelCase for functions/hooks.
- **Testing:** Write unit and integration tests for new features.

---

## Adding Features & Scaling

- **Modularize:** Place new features in their own components or modules.
- **API Extensions:** Add new endpoints in `inc/api-endpoints.php` and document them.
- **Database:** Use migrations for schema changes; keep schema documented.
- **Frontend:** Use React context for global state, React Query for data fetching.
- **Scaling:**
  - Consider splitting backend into microservices if needed.
  - Use CDN and caching for static assets.
  - Monitor performance and optimize queries/components.

---

## Testing

- Use Postman or curl to test API endpoints.
- Use React Testing Library/Jest for frontend tests.
- Manual testing for geolocation and real-time features.

---

## Deployment

- Build frontend: `npm run build` (output in `build/`)
- Deploy backend (WordPress theme) to production server.
- Set environment variables and JWT secret for production.
- Restrict CORS to your production domain.

---

## Troubleshooting

- See the main README for common issues and solutions.
- Check browser console and server logs for errors.
- Use `localStorage.clear()` to reset frontend auth state if needed.

---

## Resources

- [React Documentation](https://react.dev/)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)
- [JWT Auth for WP REST API](https://github.com/usefulteam/jwt-auth)
- [Vite Documentation](https://vitejs.dev/)

---

For further questions, open an issue or contact the maintainers.

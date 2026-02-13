# Dr. O Admin

Admin dashboard for Dr. O (Nuxt 3 + Nuxt UI). Auth: Sanctum with `admin` role.

## Setup

```bash
yarn install
```

Copy `.env.example` to `.env` and set `NUXT_PUBLIC_API_BASE` (default `http://localhost:8000/api/v1`).

## Development

From repo root you can run:

```bash
yarn dev:admin
```

Or from this directory:

```bash
yarn dev
```

Admin runs at **http://localhost:3002**. The API must be running (e.g. `yarn dev:api`) for login and data.

## E2E tests

Playwright E2E tests cover admin login and users list. **Prerequisites:** API running (e.g. `yarn dev:api` from repo root) and seeded admin user (`php artisan db:seed --class=AdminUserSeeder` in `api/`).

From `admin/`:

```bash
yarn test:e2e
```

On first run, install browsers: `npx playwright install chromium`.

With UI: `yarn test:e2e:ui`. Optional env: `PLAYWRIGHT_BASE_URL`, `ADMIN_TEST_EMAIL`, `ADMIN_TEST_PASSWORD` (defaults match `AdminUserSeeder`).

## Build

```bash
yarn build
yarn preview
```

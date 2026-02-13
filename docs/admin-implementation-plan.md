# Admin Dashboard Implementation Plan

Nuxt 3 admin app under `admin/`. Auth: Sanctum with `admin` role. Layout: default layout with header (Dashboard, Users, theme toggle, user menu).

---

## Overview

| Area | Status | Notes |
|------|--------|--------|
| Auth | Done | login, admin-role check, redirect to dashboard, auth-admin middleware; clearAuth on redirect; only token in cookie; 30 min idle timeout + session expired toast |
| Layout | Done | sidebar nav (mobile hamburger), theme toggle, user dropdown; breadcrumbs on detail/create pages |
| Dashboard | Done | metric cards, quick links, Recent consultations + Active prescriptions sections |
| Users | Done | list, create, detail, edit (PATCH), export CSV, breadcrumbs |
| Institutions | Done | list, detail, create, edit (PUT), breadcrumbs |
| Healthcare professionals | Done | list, detail, create, edit (PUT), breadcrumbs |
| Consultations | Done | list (filters), detail (read-only), breadcrumbs |
| Prescriptions | Done | list (status filter), detail, breadcrumbs |
| Notifications | Done | API + bell dropdown + list page; preferences pending |
| Settings | Done | settings page placeholder; API-backed config pending |

---

## Todos

### Dashboard
- [x] Add dashboard metrics: cards from existing admin index endpoints (counts in parallel).
- [ ] Optional: recent activity or last logins (if API provides).
- [x] Quick links: “Recent consultations” and “Active prescriptions” sections with “View all” links.

### Users
- [x] User detail page: `users/[id].vue` — view single user (GET `/admin/users/{id}`), show profile and related data.
- [x] User edit: inline edit on detail page using PATCH `/admin/users/{id}`.
- [x] Create user: API POST `/admin/users` + admin page `users/create.vue` (name, email, password, role: patient/doctor/admin, phone, DOB). Use “Create user” to add doctors; then link them to institutions via Professionals.
- [x] Bulk actions: Export users as CSV (all pages, respects current filters).

### Institutions
- [x] List page: `institutions/index.vue` — GET `/admin/institutions` with pagination and filters.
- [x] Detail page: `institutions/[id].vue` — GET `/admin/institutions/{id}`.
- [x] Create: `institutions/create.vue` — POST `/admin/institutions`.
- [x] Edit: PUT `/admin/institutions/{id}` on detail page.
- [x] Add “Institutions” to header nav and dashboard quick links.

### Healthcare professionals
- [x] List page: `healthcare-professionals/index.vue` — GET `/admin/healthcare-professionals` with pagination/filters.
- [x] Detail page: `healthcare-professionals/[id].vue` — GET `/admin/healthcare-professionals/{id}`.
- [x] Create/Edit: POST/PUT using API; add to nav.
- [x] Add “Professionals” to header nav.

### Consultations (admin)
- [x] List page: `consultations/index.vue` — GET `/admin/consultations` with filters (status, type).
- [x] Detail page: `consultations/[id].vue` — GET `/admin/consultations/{id}` (read-only).
- [x] Add “Consultations” to nav and dashboard.

### Prescriptions (admin)
- [x] List page: `prescriptions/index.vue` — GET `/admin/prescriptions` with pagination/filters.
- [x] Detail page: `prescriptions/[id].vue` — GET `/admin/prescriptions/{id}`.
- [x] Add “Prescriptions” to nav and dashboard.

### Layout & navigation
- [x] Dashboard as home (`/`), Users, Institutions, Professionals, Consultations, Prescriptions in nav.
- [x] Sidebar layout: sidebar with nav links, mobile hamburger; current page title in top bar.
- [x] Breadcrumbs on detail and create pages (Dashboard > Section > Current page) via `AdminBreadcrumbs.vue`.

### Auth & security
- [x] Session restore and logout clear token and redirect to login; `clearAuth()` used in middleware on redirect and in `fetchUser` on failure; only auth token stored in cookie, no other sensitive data.
- [x] Idle timeout (30 min): “Session expired” toast and redirect to login via `useIdleTimeout` in layout.

### Notifications (core)
- [x] API: GET `/notifications` (paginated), PATCH `/notifications/{id}/read`, PATCH `/notifications/read-all` for authenticated user (patient or admin). Model + migration; created on consultation booked and prescription issued.
- [x] Admin notification center: bell in top bar opens dropdown with recent notifications; “View all” link to list page.
- [x] Notifications list page: `/notifications` — list, mark as read, mark all read, pagination. Sidebar link added.
- [ ] Notification preferences: per-user or global (email, in-app); store via API (pending).

### Settings
- [x] Settings page: `/settings` — placeholder (General section). Sidebar link added.
- [ ] API-backed settings (app name, support email, maintenance mode, tenant/branding) when API provides endpoint.

### Dev & docs
- [x] Document admin URL and how to run (`yarn dev:admin`) in root README. Admin runs at `http://localhost:3002`.
- [x] Optional: e2e test for admin login and users list (Playwright in `admin/e2e/`, `yarn test:e2e`).

---

## Remaining (optional, not done)

- Recent activity / last logins (needs API support).
- Bulk suspend or other bulk user actions (needs API support).
- E2E test for admin login and users list (e.g. Playwright).

---

## Reference: API endpoints used by admin

```text
# Already used
GET    /api/v1/admin/users?page=&per_page=&search=&role=
POST   /api/v1/admin/users
GET    /api/v1/admin/users/{id}
PATCH  /api/v1/admin/users/{id}
DELETE /api/v1/admin/users/{id}

# To be wired
GET  /api/v1/admin/institutions
GET  /api/v1/admin/institutions/{id}
POST /api/v1/admin/institutions
PUT  /api/v1/admin/institutions/{id}
DELETE /api/v1/admin/institutions/{id}

GET  /api/v1/admin/healthcare-professionals
GET  /api/v1/admin/healthcare-professionals/{id}
POST /api/v1/admin/healthcare-professionals
PUT  /api/v1/admin/healthcare-professionals/{id}
DELETE /api/v1/admin/healthcare-professionals/{id}

GET  /api/v1/admin/consultations
GET  /api/v1/admin/consultations/{id}
... (and POST/PUT/DELETE if needed)

GET  /api/v1/admin/prescriptions
GET  /api/v1/admin/prescriptions/{id}
... (and POST/PUT/DELETE if needed)

# Notifications (authenticated user; same for admin and patient)
GET   /api/v1/notifications?page=&per_page=&unread_only=
PATCH /api/v1/notifications/read-all
PATCH /api/v1/notifications/{id}
```

---

*Last updated: Feb 2025*

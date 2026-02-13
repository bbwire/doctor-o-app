# API Implementation Plan

Laravel 12 API under `api/`. Base URL: `/api/v1`. Auth: Sanctum; roles: `patient`, `doctor`, `admin`.

---

## Overview

| Area | Status | Notes |
|------|--------|--------|
| Auth | Done | register, login, logout, user, updateProfile, forgot-password, reset-password |
| Health | Done | GET `/health` |
| Patient | Done | dashboard/summary, doctors, consultations, prescriptions |
| Admin | Done | users, institutions, healthcare-professionals, consultations, prescriptions |
| Notifications | Done | model, migration, GET/PATCH endpoints; created on consultation booked + prescription issued; preferences pending |

---

## Todos

### Auth & users
- [ ] Optional: email verification (verify endpoint + middleware).
- [ ] Optional: rate limiting on login/register/forgot-password.
- [ ] Optional: refresh token or longer-lived tokens for “remember me”.

### Patient routes
- [ ] Optional: patient profile picture upload (store/URL in user or profile).
- [ ] Optional: export consultations or prescriptions (e.g. PDF/csv).

### Notifications (core)
- [x] Notifications model and migration (user_id, type, title, body, data, read_at).
- [x] Create notifications on events: consultation booked (patient + doctor), prescription issued (patient). NotificationService::createForUser, notifyAdmins.
- [x] Authenticated user: GET `/notifications` (paginated), PATCH `/notifications/read-all`, PATCH `/notifications/{notification}` (mark read). Same routes for patient and admin (scoped to current user).
- [ ] GET/PATCH notification preferences (pending).

### Admin routes
- [ ] Optional: admin dashboard summary endpoint (e.g. `GET /admin/dashboard/summary` with counts for users, consultations, prescriptions, institutions).
- [ ] Optional: bulk actions (e.g. bulk user suspend, bulk consultation cancel).
- [ ] Optional: audit log for admin actions (who did what, when).

### Data & validation
- [ ] Ensure all list endpoints support consistent pagination (`page`, `per_page`) and filters documented in Form Requests.
- [ ] Optional: soft deletes where needed (users, consultations) and expose in API where appropriate.
- [ ] Optional: request validation tests (unit/feature) for new or changed endpoints.

### Security & ops
- [ ] CORS and allowed origins configured for PWA and admin hosts.
- [ ] Optional: API versioning strategy for future `v2` (e.g. prefix or header).
- [ ] Optional: structured logging (request id, user id, duration) for debugging and audits.

### Documentation
- [ ] OpenAPI/Swagger (or similar) spec for `/api/v1` and keep it updated.
- [x] README in `api/` with env vars, seeders, and how to run tests.

---

## Reference: Main routes

```text
# Public
GET  /api/v1/health
POST /api/v1/register
POST /api/v1/login
POST /api/v1/forgot-password
POST /api/v1/reset-password

# Protected (auth:sanctum)
POST /api/v1/logout
GET  /api/v1/user
PATCH /api/v1/user

# Patient (auth:sanctum + patient)
GET  /api/v1/dashboard/summary
GET  /api/v1/doctors
GET  /api/v1/doctors/{doctorId}/availability
GET  /api/v1/consultations
POST /api/v1/consultations/book
GET  /api/v1/consultations/{consultationId}
PATCH /api/v1/consultations/{consultationId}/cancel
PATCH /api/v1/consultations/{consultationId}/reschedule
GET  /api/v1/prescriptions

# Admin (auth:sanctum + admin)
GET    /api/v1/admin/users          POST n/a
GET    /api/v1/admin/users/{user}   PATCH / DELETE
GET    /api/v1/admin/institutions   POST
GET    /api/v1/admin/institutions/{id}  PUT/PATCH / DELETE
GET    /api/v1/admin/healthcare-professionals   POST
GET    /api/v1/admin/healthcare-professionals/{id}  PUT/PATCH / DELETE
GET    /api/v1/admin/consultations  POST
GET    /api/v1/admin/consultations/{id}  PUT/PATCH / DELETE
GET    /api/v1/admin/prescriptions  POST
GET    /api/v1/admin/prescriptions/{id}  PUT/PATCH / DELETE
```

---

*Last updated: Feb 2025*

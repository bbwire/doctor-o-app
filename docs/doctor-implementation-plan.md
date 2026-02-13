## Doctor Experience Implementation Plan

One shared Nuxt 3 PWA (`pwa/`) for **patients and doctors**, with **role-based** sections. Doctors log into the same app as patients but land on `/doctor/*` pages and use doctor-scoped API endpoints.

---

## Overview

| Area | Status | Notes |
|------|--------|-------|
| Auth & roles | Done | Post-login redirect by `user.role` (doctor → `/doctor/dashboard`, patient → `/dashboard`). |
| Doctor API scope | Done | `/api/v1/doctor/*` routes for consultations and prescriptions scoped to `doctor_id`. |
| Doctor middleware (API) | Done | `EnsureUserIsDoctor` middleware enforcing `role === 'doctor'`. |
| Doctor middleware (PWA) | Done | Nuxt middleware `doctor` protecting `/doctor/*` routes. |
| Doctor dashboard | Done | `/doctor/dashboard` shows scheduled consultations. |
| Doctor consultations | Done | List + detail pages under `/doctor/consultations`. |
| Doctor prescriptions | Done | List page under `/doctor/prescriptions` (later: create/edit). |
| Doctor layout/nav | Done | Default layout shows role-based nav (doctor vs patient) via avatar dropdown. |
| Notifications | Existing | `/notifications` API already works for any authenticated user (patient or admin); reuse for doctors later. |

---

## Todos

### 1. API: doctor scope

- [x] **Doctor middleware**
  - Add `App\Http\Middleware\EnsureUserIsDoctor` to enforce `auth()->user()->role === 'doctor'`.
  - Used directly in route group (Laravel 11 uses `bootstrap/app.php` for middleware aliases).

- [x] **Doctor routes group**
  - In `routes/api.php`, inside `auth:sanctum` group, add:
    - `Route::prefix('doctor')->middleware([EnsureUserIsDoctor::class])->group(...)`.

- [x] **Doctor consultations endpoints (read-only v1)**
  - `GET /api/v1/doctor/consultations` — list where `doctor_id = auth()->id()`.
    - Filters: `status` (`scheduled|completed|cancelled`), `per_page`, `page`.
  - `GET /api/v1/doctor/consultations/{consultation}` — detail, only if `consultation.doctor_id === auth()->id()`.
  - Reuse existing `Consultation` model and `ConsultationResource`.

- [x] **Doctor prescriptions endpoints (read-only v1)**
  - `GET /api/v1/doctor/prescriptions` — list where `doctor_id = auth()->id()`.
    - Filters: `status` (`active|completed|cancelled`), `per_page`, `page`.
  - Reuse existing `Prescription` model and `PrescriptionResource`.

- [x] **(Optional v2) Doctor dashboard summary**
  - `GET /api/v1/doctor/dashboard/summary`:
    - `today_consultations`, `upcoming_consultations`, `completed_today`, `next_consultation`.

---

### 2. PWA: role-aware auth

- [x] **Post-login redirect by role**
  - In `pwa/pages/login.vue`:
    - After `login + fetchUser`:
      - If `user.role === 'doctor'` → redirect to `/doctor/dashboard`.
      - Else (patient) → redirect to existing `/dashboard`.

- [x] **Doctor-only Nuxt middleware**
  - Add `pwa/middleware/doctor.ts`:
    - Restores session if needed.
    - If no user → `/login`.
    - If `user.role !== 'doctor'`:
      - `role === 'patient'` → `/dashboard`.
      - Other roles → log out and `/login` (or custom message).
  - Apply `middleware: 'doctor'` to all `/doctor/*` pages.

---

### 3. PWA: doctor pages

- [x] **Doctor layout / navigation**
  - Conditional nav in default layout based on `user.role`.
  - When `user.role === 'doctor'`, avatar dropdown shows:
    - `Doctor dashboard` → `/doctor/dashboard`
    - `My consultations` → `/doctor/consultations`
    - `My prescriptions` → `/doctor/prescriptions`
    - `Profile` → `/profile`

- [x] **Doctor dashboard page**
  - New page: `pwa/pages/doctor/dashboard/index.vue`.
  - Uses `middleware: 'doctor'`.
  - Data:
    - Either call `GET /doctor/dashboard/summary` (if implemented), or:
    - Call `GET /doctor/consultations?status=scheduled&per_page=10` and show the next few.

- [x] **Doctor consultations list**
  - New page: `pwa/pages/doctor/consultations/index.vue`.
  - Uses `middleware: 'doctor'`.
  - Calls `GET /doctor/consultations` with `page`, `per_page`, `status`.
  - Table columns: time, patient name, type, status.
  - Row click → `/doctor/consultations/[id]`.

- [x] **Doctor consultation detail**
  - New page: `pwa/pages/doctor/consultations/[id].vue`.
  - Uses `middleware: 'doctor'`.
  - Calls `GET /doctor/consultations/{id}`.
  - Shows core consultation fields (patient, scheduled_at, type, status, reason, notes).
  - Shows prescriptions linked to this consultation.

- [x] **Doctor prescriptions list**
  - New page: `pwa/pages/doctor/prescriptions/index.vue`.
  - Uses `middleware: 'doctor'`.
  - Calls `GET /doctor/prescriptions` with `page`, `per_page`.
  - Columns: issued_at, patient name, status.

---

### 4. Future doctor capabilities (out of v1 scope, but planned)

- [x] **Issue prescriptions from doctor app**
  - `POST /api/v1/doctor/prescriptions`:
    - Payload: `consultation_id`, `medications[]`, `instructions`, `status` (default `active`).
    - Validation: consultation must exist and `consultation.doctor_id === auth()->id()`.
  - UI: `Issue prescription` button + modal on doctor consultation detail.

- [x] **Update consultation status / notes**
  - `PATCH /api/v1/doctor/consultations/{id}`:
    - Fields: `status` (`scheduled|completed|cancelled`), `notes`, optional `metadata`.
  - UI: actions on consultation detail (mark completed, cancel, save notes).

- [x] **Show prescriptions on consultation detail**
  - API: consultation show loads `prescriptions` relation.
  - UI: prescriptions list with medications, instructions, status.

- [x] **Notifications in doctor UI**
  - Reuse existing `/notifications` API for in-app bell + list.
  - Bell icon + dropdown in PWA header (patients and doctors).
  - `/notifications` page with list, mark read, mark all read.
  - Consultation notifications link to `/doctor/consultations/{id}` for doctors.
  - Doctor receives notification when patient cancels (consultation_cancelled).

---

## Reference

### API (doctor scope)

```text
GET  /api/v1/doctor/dashboard/summary
GET  /api/v1/doctor/consultations
GET  /api/v1/doctor/consultations/{id}
GET   /api/v1/doctor/prescriptions
POST  /api/v1/doctor/prescriptions
PATCH /api/v1/doctor/consultations/{id}
```

### PWA (doctor pages)

```text
/doctor/dashboard
/doctor/consultations
/doctor/consultations/[id]
/doctor/prescriptions
/notifications          # Shared (patients + doctors)
```

This plan assumes **one shared PWA** with **two clear experiences**: patient (existing pages) and doctor (new `/doctor/*` pages), and a dedicated doctor API scope to keep data separated and secure.


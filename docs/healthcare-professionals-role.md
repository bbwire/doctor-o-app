# Healthcare professionals: role and place on the platform

This document defines the **role of healthcare professionals (doctors)** on the Doctor O platform, the **current gap**, and **where they belong** (product and technical).

---

## 1. Definition: who is a healthcare professional?

- A **healthcare professional** is a user with role `doctor` in the system.
- They may have an optional **HealthcareProfessional** profile (institution, speciality, license number, bio, qualifications). That profile is created and edited by **admins** and links the doctor user to an institution.
- In the data model:
  - **Consultations** use `doctor_id` (User id).
  - **Prescriptions** use `doctor_id` (User id).
  - Doctors are listed in the **patient-facing directory** (`GET /doctors`) and have **availability** (`GET /doctors/{id}/availability`) so patients can book with them.

So today doctors are **first-class in the data model** (they receive consultations and issue prescriptions) but **have no dedicated workflow or UI**.

---

## 2. What healthcare professionals need to do (responsibilities)

| Responsibility | Description |
|----------------|-------------|
| **View their schedule** | See consultations where they are the doctor: upcoming, past, cancelled. |
| **Manage consultations** | Confirm, complete, or cancel their consultations; add clinical notes. |
| **Set availability** | Define when they are available so patients can book (or this is managed by admin/institution). |
| **Issue prescriptions** | Create prescriptions linked to a consultation they are involved in. |
| **See their patients** | List patients they have consulted with (for context when issuing prescriptions or viewing history). |
| **Notifications** | Receive in-app (and optionally email) notifications for new bookings, cancellations, reminders. |

These are **doctor-facing** actions. Today none of them are possible for a doctor in the platform: there is no doctor-scoped API and no doctor-facing app.

---

## 3. Current state: where doctors do *not* belong

| Surface | Intended for | Doctors here? |
|---------|--------------|---------------|
| **Admin app** (`admin/`, port 3002) | Users with role `admin`. Manages users, institutions, professionals, consultations, prescriptions. | **No.** Admins “manage” doctors and act on their behalf (e.g. create consultations/prescriptions). Doctors should not be given admin access to do their clinical work. |
| **PWA** (`pwa/`, port 3001) | Users with role `patient`. Book consultations, view prescriptions, profile, dashboard. | **No.** PWA is patient-only. A doctor logging in as patient would see the wrong data and wrong actions. |

So today:

- **Admins** can do everything *for* doctors (create consultations, prescriptions, etc.) but doctors have **no place to log in and do it themselves**.
- **Patients** see doctors in the directory and book with them, but doctors have **no app** to see those bookings or issue prescriptions.

Result: **doctors are “data” (users + optional HealthcareProfessional profile), not users with a defined workflow.**

---

## 4. Where healthcare professionals *should* belong

Doctors need:

1. **A dedicated surface (app or area)** where they log in with their **doctor** account and only see doctor-scoped data and actions.
2. **A doctor-scoped API** under something like `/api/v1/doctor/*` (or `/api/v1/healthcare-professional/*`) that:
   - Requires `auth:sanctum` and role `doctor`.
   - Returns only data where the authenticated user is the doctor (consultations, prescriptions, availability, patients as needed).

Recommended approach:

- **Option A – Separate “Doctor” app**  
  New app (e.g. `doctor/` or `professionals/`) similar to `admin/` and `pwa/`: its own Nuxt app, port, and routes. Doctors go to this URL and use dashboard, consultations, prescriptions, availability (and later notifications).  
  **Pros:** Clear separation, no mixing of patient/admin UX. **Cons:** Another codebase and deploy.

- **Option B – Doctor area inside one existing app**  
  E.g. in the PWA: after login, if role is `doctor`, redirect to `/doctor/dashboard` (or similar) with a dedicated layout and only doctor routes. Patient and doctor never see each other’s flows.  
  **Pros:** Single app. **Cons:** More branching in one codebase; PWA name “patient” may be confusing unless rebranded (e.g. “Doctor O” for both with role-based home).

- **Option C – Subdomain or path on admin**  
  e.g. `admin.example.com/doctor` or `admin.example.com/doctor/dashboard` for doctor role only; admin and doctor share app but different layouts and routes.  
  **Pros:** One app. **Cons:** Admin app becomes “admin + doctor”; easy to blur roles if not careful.

**Recommendation:** **Option A (separate Doctor app)** for clearest separation and clearest “place” for professionals, unless you explicitly want a single unified app (then Option B with a neutral product name).

---

## 5. Proposed doctor-scoped API (high level)

All under `auth:sanctum` + **doctor** role middleware. Base path idea: `/api/v1/doctor` or `/api/v1/me` (when we add more “current user scope” later).

| Capability | Endpoint (example) | Notes |
|------------|--------------------|--------|
| Dashboard/summary | `GET /doctor/dashboard/summary` | Counts: today’s consultations, upcoming, prescriptions issued; next consultation. |
| My consultations | `GET /doctor/consultations` | List where `doctor_id = auth()->id()`. Filters: status, date range, per_page. |
| Consultation detail | `GET /doctor/consultations/{id}` | Only if doctor is the assigned doctor. |
| Update consultation | `PATCH /doctor/consultations/{id}` | e.g. status (completed/cancelled), notes. Same ownership check. |
| My prescriptions | `GET /doctor/prescriptions` | List where `doctor_id = auth()->id()`. |
| Issue prescription | `POST /doctor/prescriptions` | Body: consultation_id, medications, instructions. Validation: consultation must be for this doctor and patient. |
| My availability | `GET /doctor/availability` | Slots the doctor has set (if we add availability model) or derived from config. |
| Set availability | `PUT /doctor/availability` or `PATCH` | Optional later; may be admin-managed. |

Notifications are already global for the authenticated user (`GET /notifications`, etc.); doctors would use the same endpoints from their app.

---

## 6. Implementation checklist (when you build the “doctor” side)

- [ ] **API:** Add `doctor` middleware (role check) and route group `/doctor/*` (or chosen prefix).
- [ ] **API:** Implement doctor dashboard, consultations (list/detail/update), prescriptions (list/create) scoped to `auth()->id()` as doctor.
- [ ] **API:** Document in `API_CONTRACT.md` under a “Doctor / Healthcare professional endpoints” section.
- [ ] **App:** Either new app `doctor/` (Option A) or doctor area in PWA (Option B) / admin (Option C).
- [ ] **App:** Login: allow role `doctor` to access doctor app/area only (redirect patient to PWA, admin to admin).
- [ ] **App:** Doctor dashboard, consultations list/detail, prescriptions list/issue, and notifications.
- [ ] **Optional:** Availability management (doctor or admin) and notifications preferences.

---

## 7. Summary

| Question | Answer |
|----------|--------|
| **Who are healthcare professionals?** | Users with role `doctor`, optionally with a HealthcareProfessional profile (institution, speciality, etc.). |
| **Do they belong in the admin app?** | **No.** Admin is for managing the platform; doctors need a place to *do* consultations and prescriptions. |
| **Do they belong in the PWA?** | **No.** PWA is for patients. Doctors need their own workflow. |
| **Where should they belong?** | A **dedicated doctor-facing surface** (separate app recommended) and a **doctor-scoped API** so they can handle consultations and prescriptions themselves. |
| **What’s missing today?** | Doctor middleware and routes in the API, and any UI for doctors to log in and perform their responsibilities. |

Once this is implemented, healthcare professionals will have a clear role and a clear place on the platform: **their own app (or area) and API scope**, distinct from both admin and patient.

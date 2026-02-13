# PWA (Patient App) Implementation Plan

Nuxt 3 patient-facing app under `pwa/`. Port: e.g. 3001. Auth: Sanctum (patient role).

---

## Overview

| Area | Status | Notes |
|------|--------|--------|
| Auth | Done | login, register, logout, restore session, auth middleware |
| Forgot / reset password | Done | forgot-password, reset-password pages + API |
| Dashboard | Done | summary cards, next appointment, link to consultations |
| Consultations | Done | list, book, detail [id], cancel, reschedule, timeline |
| Prescriptions | Done | list page |
| Profile | Done | view/edit profile |
| API health / offline | Done | health check, offline banner, reconnect toasts |
| Theming | Done | dark mode (theme state + dark: classes) |
| Notifications | Pending | in-app list, bell icon, preferences; requires API support |

---

## Todos

### Auth & onboarding
- [ ] Optional: “Remember me” (longer-lived token or refresh token) and wire to API if added.
- [ ] Optional: onboarding flow for first-time users (e.g. short intro or consent).
- [ ] Ensure reset-password link from email opens app with token in query and pre-fills form.

### Dashboard
- [ ] Optional: quick actions (e.g. “Book again” with last doctor, “View next consultation”).

### Consultations
- [ ] Optional: slot suggestions (e.g. “Next 3 available” from API) and configurable slot interval in env or settings.
- [ ] Optional: booking conflict handling (clear message when slot taken, retry with new slot).
- [ ] Ensure consultation detail timeline and status match API (scheduled, completed, cancelled, etc.).

### Prescriptions
- [ ] Optional: prescription detail page (e.g. `prescriptions/[id].vue`) if API supports it.
- [ ] Optional: download/print prescription (e.g. PDF) if API provides it.

### Notifications (core)
- [ ] API: patient notifications (e.g. GET `/notifications`, mark read, preferences). Events: consultation reminder, prescription ready, appointment confirmed/cancelled, etc.
- [ ] Bell icon in header/layout linking to notifications page or dropdown with recent items.
- [ ] Notifications list page: `/notifications` — list with mark as read, clear.
- [ ] Notification preferences: email/in-app toggles; store via API.

### Profile & settings
- [ ] Optional: change password (current + new) if API adds endpoint.
- [ ] Polish profile form (validation messages, success toast, loading state).

### Offline & reliability
- [ ] Optional: service worker / PWA manifest for install and basic offline shell.
- [ ] Optional: retry queue for failed requests when back online.
- [ ] Keep API health banner and reconnect toasts consistent across all pages.

### UX & accessibility
- [ ] Consistent error and empty states (no consultations, no prescriptions, no doctors).
- [ ] Optional: loading skeletons instead of plain “Loading…” where it improves perceived performance.
- [ ] Ensure focus and keyboard flow on login, forms, and modals.

### Dev & docs
- [ ] Root README (or docs) mentions PWA URL and `yarn dev:pwa` (or equivalent).
- [ ] Optional: e2e tests for critical flows (login, book consultation, view prescription).

---

## Reference: Main pages

```text
/                    → redirect or landing (e.g. to dashboard or login)
/login
/register
/forgot-password
/reset-password
/dashboard           → patient dashboard (summary, next appointment)
/consultations       → list
/consultations/book  → book new
/consultations/[id]  → detail, cancel, reschedule, timeline
/prescriptions       → list
/profile             → view/edit profile
/notifications       → notifications list (core)
```

---

*Last updated: Feb 2025*

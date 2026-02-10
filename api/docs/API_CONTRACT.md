# Doctor O API Contract

Base URL: `http://localhost:8000/api/v1`

## Authentication

- Auth uses Laravel Sanctum personal access tokens.
- Send token on protected endpoints:
  - `Authorization: Bearer {token}`
  - `Accept: application/json`
- Public endpoints:
  - `POST /register`
  - `POST /login`
- Protected endpoints:
  - `POST /logout`
  - `GET /user`
- `PATCH /user`
  - `GET|POST|PATCH|DELETE /admin/*` (admin users only)

## Response Conventions

- Single resources are returned in a `data` object when using API Resources.
- Collection responses use Laravel pagination shape:
  - `data`: array
  - `links`: pagination links
  - `meta`: pagination metadata
- Validation failures return `422` with Laravel `errors` payload.
- Unauthorized/forbidden return `401` / `403`.

## Auth Endpoints

### POST `/register`
Registers a patient or doctor account.

Request body:

```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "patient",
  "phone": "+1234567890",
  "date_of_birth": "1995-06-12"
}
```

Response `201`:

```json
{
  "user": {
    "data": {
      "id": 1,
      "name": "Jane Doe",
      "email": "jane@example.com",
      "role": "patient"
    }
  },
  "token": "..."
}
```

### POST `/login`
Request body:

```json
{
  "email": "jane@example.com",
  "password": "password123"
}
```

Response `200`: same shape as register.

### POST `/forgot-password`
Sends password reset token/link email using Laravel password broker.

Request body:

```json
{
  "email": "jane@example.com"
}
```

Response `200`:

```json
{
  "message": "We have emailed your password reset link."
}
```

### POST `/reset-password`
Resets user password with token.

Request body:

```json
{
  "email": "jane@example.com",
  "token": "<reset-token>",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

### GET `/health`
Lightweight public health endpoint for local/dev connectivity checks.

Response `200`:

```json
{
  "status": "ok",
  "service": "doctor-o-api",
  "timestamp": "2026-02-10T18:31:00.000000Z"
}
```

### GET `/user`
Returns authenticated user profile with healthcare professional relation if present.

### PATCH `/user`
Updates authenticated user's own profile fields.

Allowed fields:
- `name`
- `email`
- `phone` (nullable)
- `date_of_birth` (nullable date)
- `preferred_language` (nullable string)
- `profile_photo` (nullable image: jpg/jpeg/png/webp, max 3MB)
- `profile_photo_remove` (`true|false`)

Notes:
- Send multipart form-data when uploading a profile photo.
- For clients that use method override, submit as `POST /user` with `_method=PATCH`.

### POST `/logout`
Revokes current token.

## Admin Endpoints

All routes below are under `/admin` and require `auth:sanctum` + admin role.

### Users

- `GET /admin/users`
- `GET /admin/users/{id}`
- `PATCH /admin/users/{id}`
- `DELETE /admin/users/{id}`

Filters for list:
- `role`: `patient|doctor|admin`
- `search`: free text
- `per_page`, `page`

### Institutions

- `GET /admin/institutions`
- `POST /admin/institutions`
- `GET /admin/institutions/{id}`
- `PATCH /admin/institutions/{id}`
- `DELETE /admin/institutions/{id}`

Create/update fields:
- `name` (string)
- `type` (string, example: `clinic`, `hospital`)
- `address` (nullable string)
- `phone` (nullable string)
- `email` (nullable email)
- `is_active` (boolean)

### Healthcare Professionals

- `GET /admin/healthcare-professionals`
- `POST /admin/healthcare-professionals`
- `GET /admin/healthcare-professionals/{id}`
- `PATCH /admin/healthcare-professionals/{id}`
- `DELETE /admin/healthcare-professionals/{id}`

Create/update fields:
- `user_id` (must reference a doctor user)
- `institution_id` (nullable, existing institution)
- `speciality`, `license_number`, `bio`
- `qualifications` (array of strings)
- `is_active` (boolean)

### Consultations

- `GET /admin/consultations`
- `POST /admin/consultations`
- `GET /admin/consultations/{id}`
- `PATCH /admin/consultations/{id}`
- `DELETE /admin/consultations/{id}`

Create/update fields:
- `patient_id` (must reference patient user)
- `doctor_id` (must reference doctor user)
- `scheduled_at` (date-time)
- `consultation_type` (`text|audio|video`)
- `status` (`scheduled|completed|cancelled`)
- `reason`, `notes` (nullable text)
- `metadata` (nullable object)

### Prescriptions

- `GET /admin/prescriptions`
- `POST /admin/prescriptions`
- `GET /admin/prescriptions/{id}`
- `PATCH /admin/prescriptions/{id}`
- `DELETE /admin/prescriptions/{id}`

Create/update fields:
- `consultation_id` (existing consultation)
- `doctor_id` (doctor user)
- `patient_id` (patient user)
- `medications` (array, at least one item)
- `instructions` (nullable)
- `issued_at` (date-time)
- `status` (`active|completed|cancelled`)

Domain rule:
- `doctor_id` and `patient_id` must match the selected consultation participants.

Medication item shape:

```json
{
  "name": "Paracetamol",
  "dosage": "500mg",
  "frequency": "Twice daily",
  "duration": "5 days"
}
```

## Patient Endpoints

All routes below require `auth:sanctum` and patient role.

### Doctors Directory

- `GET /doctors`

Response `200`:
- Paginated collection format is not used here (simple `data` collection).
- Each doctor includes:
  - `id`
  - `name`
  - `email`
  - `speciality` (nullable)
  - `institution` (nullable)

### Dashboard (Patient)

- `GET /dashboard/summary`

Response `200`:

```json
{
  "data": {
    "upcoming_consultations": 2,
    "prescriptions": 4,
    "completed_consultations": 3,
    "next_consultation": {
      "id": 10,
      "scheduled_at": "2026-02-20T10:00:00.000000Z",
      "consultation_type": "video",
      "status": "scheduled",
      "doctor": {
        "id": 5,
        "name": "Dr. Jane Doe"
      }
    }
  }
}
```

### Consultations (Patient)

- `GET /consultations`
- `GET /consultations/{consultationId}`
- `POST /consultations/book`
- `PATCH /consultations/{consultationId}/cancel`
- `PATCH /consultations/{consultationId}/reschedule`

List filters:
- `status`: `scheduled|completed|cancelled`
- `per_page`, `page`

Booking payload:

```json
{
  "doctor_id": 12,
  "scheduled_at": "2026-02-20T10:00:00.000Z",
  "consultation_type": "video",
  "reason": "Follow-up checkup",
  "notes": "Optional notes"
}
```

Booking rules:
- Patient is automatically set from authenticated user.
- `doctor_id` must reference a doctor account.
- `scheduled_at` must be in the future.
- A doctor cannot have two `scheduled` consultations at the exact same `scheduled_at` time.
- Patients can only cancel/reschedule consultations that are currently `scheduled`.
- Patients can only cancel/reschedule at least 2 hours before consultation start time.

Reschedule payload:

```json
{
  "scheduled_at": "2026-02-21T14:00:00.000Z"
}
```

Consultation detail response `200`:

```json
{
  "data": {
    "id": 1,
    "patient_id": 14,
    "doctor_id": 5,
    "scheduled_at": "2026-02-20T10:00:00.000Z",
    "consultation_type": "video",
    "status": "scheduled",
    "reason": "Follow-up checkup",
    "notes": "Optional notes"
  }
}
```

### Prescriptions (Patient)

- `GET /prescriptions`

List filters:
- `status`: `active|completed|cancelled`
- `per_page`, `page`

Response contains only prescriptions where `patient_id` matches authenticated user.

## Integration Notes for PWA/Admin

- For paginated endpoints, read from `response.data` array and pagination from `response.meta`.
- For resource endpoints, read from `response.data`.
- Keep token in secure storage and always send `Authorization` header on protected calls.
- Recommended API base env for frontend apps:
  - `NUXT_PUBLIC_API_BASE=http://localhost:8000/api/v1`


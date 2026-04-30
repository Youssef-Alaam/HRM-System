# Face enrollment — subset of Feature 5 (Attendance), enrolled during onboarding

## Status
⬜ Not started — built alongside Feature 2 employee onboarding per Walid 2026-04-30.

## Why this is its own spec, separate from Feature 5

Feature 5 (Attendance) is the consumer of face descriptors — it captures a check-in selfie, computes a descriptor, compares to the employee's stored reference descriptor, and renders a verdict (verified / possibly_self / unverified). The **enrollment** that produces that reference descriptor happens at employee onboarding (or annually). It's a distinct flow with its own UI, lifecycle, and data model.

This spec covers enrollment only. Feature 5 covers check-in.

## Locked decisions (Walid 2026-04-30)

- **Library:** `face-api.js` (MIT, browser-side TensorFlow.js port). No external API, no API key, no monthly cost. ~6 MB of model files cached after first load.
- **3 photos at enrollment** (Walid accepted my recommendation).
- **12-month re-enrollment cadence** (Walid's pick).
- **5 failed check-ins triggers early re-enrollment** (Walid's pick).
- **HR runs enrollment in-office** — not employee self-service. HR launches the wizard from the employee detail page.
- **Stored data:**
  - 3 photos as JPEG/WebP under `storage/app/employee-faces/{employee_id}/{enrollment_id}/photo-{1,2,3}.jpg`
  - Computed face descriptor (128-number vector) as JSON column on `employees.face_descriptor`
  - Enrollment metadata in `face_enrollments` table
- **Privacy:** PDPL compliance — descriptor + photos auto-purge 24h after employee termination (`employees.deleted_at` set + 24h).

## Enrollment session flow

HR opens the employee detail page. If `last_face_enrollment_at` is null OR > 12 months ago, an "Enroll for face verification" stamped CTA appears.

Click → modal wizard:

```
01 / SETUP
  Employee: Ahmed Hassan (EMP-10042)
  ─ Camera permission required
  ─ Plain background, even lighting, no glasses, no mask
  [Allow camera]

02 / CAPTURE
  ┌─────────────┐
  │             │       Photo 1 of 3 — Front-facing, neutral expression
  │   [video]   │       
  │             │       [Capture]    [Retake]
  └─────────────┘

03 / CAPTURE
  Photo 2 of 3 — Slight head turn LEFT (~15°)
  [Capture]    [Retake]

04 / CAPTURE
  Photo 3 of 3 — Slight head turn RIGHT (~15°)
  [Capture]    [Retake]

05 / REVIEW
  [thumbnail 1]  [thumbnail 2]  [thumbnail 3]
  Quality score: 0.94 (good)
  ─ Press Save to compute the face descriptor
  [Save]    [Restart]
```

**Behind the scenes on Save:**
1. Each photo runs through face-api.js's `detectSingleFace().withFaceLandmarks().withFaceDescriptor()` — produces a 128-number descriptor per photo.
2. The 3 descriptors are averaged into the canonical descriptor (mean of each dimension).
3. Server stores: 3 image files + the canonical descriptor JSON + a quality score (mean of per-photo confidence).
4. New row in `face_enrollments`: employee_id, enrolled_at, photo_count=3, descriptor_quality_score, enrolled_by_user_id (HR who ran it).
5. `employees.face_descriptor` updated with the canonical descriptor.
6. `employees.last_face_enrollment_at` updated.
7. Audit log entry.

If quality score < 0.7 → wizard surfaces "Quality too low — retake one or more photos. Suggestions: improve lighting, remove glasses, plain background."

## Re-enrollment triggers

A scheduled job (`AnnualFaceEnrollmentReminderJob`) runs daily at 6 AM Cairo:

- **30 days before 12-month anniversary:** push notification to HR via Inbox (Feature 12) + add to "Face re-enrollment due this month" widget on HR dashboard.
- **At 12-month anniversary:** descriptor stays valid (no auto-invalidation), but the dashboard widget moves the employee to "OVERDUE" with a red status.
- **5 consecutive failed check-ins** (handled by Feature 5 Attendance): system flags employee for **forced re-enrollment** — next check-in is blocked until HR re-enrolls.
- **HR can manually trigger re-enrollment** anytime from the employee detail page (e.g., after major appearance change like beard / no beard, weight change).

## Data model

### `face_enrollments` table

```
id, org_id, employee_id (FK), enrolled_at (datetime), enrolled_by_user_id (FK to users),
photo_count (unsigned tinyint, default 3), descriptor_quality_score (decimal 3,2),
descriptor (json — the 128-number vector — also mirrored on employees.face_descriptor for fast read),
photo_paths (json — array of relative storage paths),
notes (text nullable),
created_at  -- no updated_at, immutable history
```

Old enrollments stay in the table (history). The "active" enrollment is the most recent one per employee — the descriptor on `employees.face_descriptor` always reflects the latest enrollment.

### Migration adds to `employees`:

```
face_descriptor (json nullable)
last_face_enrollment_at (datetime nullable)
failed_checkin_count (unsigned int default 0)  -- reset on successful check-in or re-enrollment
```

## Routes

| Method | URL | Permission | Action |
|---|---|---|---|
| `GET` | `/employees/{employee}/face-enrollment` | `face.enroll.any` | Open enrollment wizard |
| `POST` | `/employees/{employee}/face-enrollment` | `face.enroll.any` | Submit 3 photos + descriptor |
| `GET` | `/employees/{employee}/face-enrollments` | `face.view.any` | History list (HR/Admin) |
| `POST` | `/employees/{employee}/face-enrollment/reset` | `face.reset` | Force re-enrollment (clears failed_checkin_count + invalidates current descriptor) |

### New permission strings

- `face.enroll.any` → HR + Admin
- `face.view.own` → All roles (employee can see own descriptor metadata, not the descriptor itself)
- `face.view.any` → HR + Admin
- `face.reset` → HR + Admin

## Frontend

1. **Enrollment wizard** as a modal on `Pages/Employees/Show.tsx`. State lives in the wizard component; uses `getUserMedia()` for camera + canvas to capture frames.
2. **face-api.js loaded as a Vite-managed dependency** (`npm install face-api.js`). Models served from `/public/face-models/` after a one-time `php artisan vendor:publish` or manual download from the face-api.js GitHub release.
3. **Re-enrollment widget** on HR/Admin dashboard:
   ```
   00 / Face verification
      3 employees due this month
      1 employee OVERDUE
      [drill-down list]
   ```

## Edge cases

- **No camera available** — wizard disables, message: "This computer doesn't have a camera. Run enrollment from a workstation with a camera."
- **Camera permission denied** — clear retry instruction: "Click the camera icon in the address bar → Allow."
- **No face detected in photo** — wizard rejects the photo, prompts for retake.
- **Multiple faces in photo** — wizard rejects ("Only one person per photo. Ensure no one else is in frame.").
- **Quality score too low** — wizard suggests fixes (lighting, glasses, background).
- **Browser without WebGL** (rare on modern hardware) — face-api.js gracefully degrades to CPU; slower but works.
- **Employee terminated** — descriptor + photos purged 24h later by background job.
- **Re-enrollment when active descriptor exists** — the new enrollment's descriptor REPLACES the old one on `employees.face_descriptor`. The old `face_enrollments` row stays for history.

## Test plan

`tests/Feature/FaceEnrollment/`:

- `EnrollmentRouteTest.php` — HR can access wizard; Employee/Manager cannot.
- `EnrollmentSubmitTest.php` — POSTing 3 photos + descriptor creates row, updates `employees.face_descriptor` + `last_face_enrollment_at`.
- `EnrollmentRequiresThreePhotosTest.php` — submission with <3 photos rejected.
- `EnrollmentQualityTest.php` — quality score below threshold rejected.
- `ReEnrollmentDueWidgetTest.php` — widget data correct (employees with last_face_enrollment_at > 11 months ago appear in "due this month").
- `ReEnrollmentTriggerTest.php` — 5 consecutive failed check-ins flips `requires_face_reenrollment` flag.
- `FaceResetTest.php` — HR can reset; clears descriptor + counter; subsequent check-in blocks until re-enrolled.
- `EnrollmentMultiTenantTest.php` — descriptors scoped per org via OrgScope.
- `PostTerminationPurgeTest.php` — descriptor + photos cleared 24h after `employees.deleted_at` is set.

Estimated ~12 tests.

## Build estimate

~1 day: migration + model (1hr) + N-tier backend (2hr) + face-api.js integration + wizard frontend (3hr) + scheduler job (1hr) + tests (2hr) + dashboard widget (1hr).

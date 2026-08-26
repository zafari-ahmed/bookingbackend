# Database Schema — District Coordination Case Management System

Review this against your workflow requirements and flag anything to add/remove/rename before we move to the Cursor build prompt. See `erd-diagram.mermaid` for the visual relationships.

---

## 1. `departments`

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint unsigned | PK, auto-increment | |
| name | varchar(150) | unique, not null | "Health", "Police", "Child Protection", etc. |
| slug | varchar(150) | unique, not null | URL-safe, auto-generated from name |
| description | text | nullable | |
| color_tag | varchar(20) | nullable | hex code for UI badge (matches design system) |
| is_active | boolean | default true | soft-disable a department without deleting history |
| created_at / updated_at | timestamp | | |
| deleted_at | timestamp | nullable | soft delete — never hard-delete a department once cases exist |

---

## 2. `users`

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint unsigned | PK, auto-increment | |
| name | varchar(150) | not null | |
| email | varchar(150) | unique, not null | login identifier |
| password | varchar(255) | not null | bcrypt/argon2 hash via Laravel's `Hash` facade |
| phone | varchar(20) | nullable | |
| role | enum | `super_admin`, `department_admin`, `department_user` | drives permission level; applies uniformly across all departments this user is assigned to |
| is_active | boolean | default true | disable a user without deleting their history/comments |
| last_login_at | timestamp | nullable | |
| email_verified_at | timestamp | nullable | Laravel default |
| remember_token | varchar(100) | nullable | Laravel default |
| created_at / updated_at | timestamp | | |
| deleted_at | timestamp | nullable | soft delete — preserves audit trail (comments stay attributed) |

**Indexes:** `role`, `email` (unique already indexes it).

> **Note:** `department_id` has been removed from this table — department access is now many-to-many via `department_user` below, so a single user (e.g. a senior officer) can belong to multiple departments. `super_admin` users simply have no rows in `department_user` and see everything regardless.

---

## 2a. `department_user` (pivot — multi-department access)

This is the table that actually controls "who can access which department." A user with no rows here and `role = super_admin` sees all departments; a user with rows here sees only those departments.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint unsigned | PK, auto-increment | |
| user_id | bigint unsigned | FK → users.id, not null | |
| department_id | bigint unsigned | FK → departments.id, not null | |
| is_primary | boolean | default false | marks the user's "home" department — used for default UI context (e.g. which department a comment is attributed to by default) |
| created_at | timestamp | | when access was granted |

**Constraints:** unique composite index on `(user_id, department_id)` — prevents duplicate assignment.
**Indexes:** `user_id`, `department_id` (both needed — queries go both directions: "which departments can this user see" and "which users belong to this department").



---

## 3. `cases`

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint unsigned | PK, auto-increment | |
| case_number | varchar(30) | unique, not null | e.g. `CASE-2026-0001`, generated on create |
| complainant_name | varchar(150) | not null | |
| complainant_cnic | varchar(20) | nullable, indexed | used for duplicate detection in Phase 2 |
| complainant_phone | varchar(20) | nullable | |
| complainant_address | text | nullable | |
| issue_summary | text | not null | |
| department_id | bigint unsigned | FK → departments.id, not null | department currently holding the case |
| created_by | bigint unsigned | FK → users.id, not null | who logged it (AC staff in Phase 1) |
| priority | enum | `normal`, `high`, `urgent`, default `normal` | |
| status | enum | `pending`, `assigned`, `in_progress`, `escalated`, `referred`, `resolved`, `closed`, default `pending` | |
| source | enum | `staff`, `mobile_app`, default `staff` | mobile_app used in Phase 2 |
| resolved_at | timestamp | nullable | |
| closed_at | timestamp | nullable | |
| created_at / updated_at | timestamp | | |
| deleted_at | timestamp | nullable | soft delete only — cases are legal/audit records, never hard-deleted |

**Indexes:** `department_id`, `status`, `priority`, `complainant_cnic`, `case_number` (composite index on `department_id + status` is worth adding — it's the exact filter combination the dashboard queries most).

---

## 4. `case_routing` (assignment / forwarding history)

Tracks every hand-off between departments — this is what powers "assign → act → forward/return" and the department-wise stats.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint unsigned | PK, auto-increment | |
| case_id | bigint unsigned | FK → cases.id, not null | |
| department_id | bigint unsigned | FK → departments.id, not null | department this routing step concerns |
| assigned_by | bigint unsigned | FK → users.id, not null | |
| assigned_to_user_id | bigint unsigned | FK → users.id, nullable | optional — route to a specific person, not just a department |
| action | enum | `assigned`, `forwarded`, `returned`, `completed` | |
| notes | text | nullable | |
| created_at | timestamp | | immutable — no updated_at needed, this is a log |

**Indexes:** `case_id`, `department_id`.

---

## 5. `case_comments` (remarks)

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint unsigned | PK, auto-increment | |
| case_id | bigint unsigned | FK → cases.id, not null | |
| user_id | bigint unsigned | FK → users.id, not null | |
| department_id | bigint unsigned | FK → departments.id, not null | which department the user is acting/commenting **as** — if the user belongs to multiple departments, they select this from a dropdown when commenting (defaults to their `is_primary` department, but must be a department the user actually has access to on this case) |
| comment | text | not null | |
| forwarded_to_department_id | bigint unsigned | FK → departments.id, nullable | set when a comment includes a forward action |
| created_at / updated_at | timestamp | | |
| deleted_at | timestamp | nullable | soft delete — never truly remove a remark from an official case |

**Indexes:** `case_id`.

---

## 6. `case_attachments`

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint unsigned | PK, auto-increment | |
| case_id | bigint unsigned | FK → cases.id, not null | |
| comment_id | bigint unsigned | FK → case_comments.id, nullable | null if attached directly to the case, not a specific comment |
| uploaded_by | bigint unsigned | FK → users.id, not null | |
| file_name | varchar(255) | not null | original filename |
| file_path | varchar(500) | not null | storage path (see security note below) |
| file_type | varchar(100) | not null | MIME type |
| file_size | bigint unsigned | not null | bytes |
| created_at | timestamp | | |

**Indexes:** `case_id`.

---

## 7. `case_activity_logs`

The full audit trail — every case-opened, comment-added, file-uploaded, reassigned, priority-changed, status-changed event, exactly as specced.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint unsigned | PK, auto-increment | |
| case_id | bigint unsigned | FK → cases.id, not null | |
| user_id | bigint unsigned | FK → users.id, nullable | null for system-generated events |
| action_type | varchar(50) | not null | `created`, `assigned`, `forwarded`, `returned`, `comment_added`, `attachment_added`, `status_changed`, `priority_changed`, `escalated`, `resolved`, `closed`, `reopened` |
| description | text | not null | human-readable line, e.g. "Forwarded to Benazir Income Support Programme" |
| meta | json | nullable | old_value/new_value pairs for status/priority changes |
| created_at | timestamp | | immutable log, no updated_at |

**Indexes:** `case_id`, `action_type`.

*(Alternative: use the `spatie/laravel-activitylog` package instead of a hand-rolled table — it gives you this structure plus a polymorphic `subject`/`causer` pattern out of the box, which saves build time. Worth considering at the Cursor-build stage.)*

---

## 8. `notifications`

Powers the bell icon + Notifications page.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint unsigned (or uuid) | PK | Laravel's native notifications table uses uuid — either works |
| user_id | bigint unsigned | FK → users.id, not null | recipient |
| case_id | bigint unsigned | FK → cases.id, nullable | |
| type | varchar(50) | not null | `case_assigned`, `comment_added`, `case_forwarded`, `case_escalated`, `case_resolved`, `case_created` |
| title | varchar(255) | not null | |
| message | text | not null | |
| is_read | boolean | default false | |
| read_at | timestamp | nullable | |
| created_at | timestamp | | |

**Indexes:** `user_id + is_read` (composite — this is exactly what the unread-count badge query needs).

*(Alternative: use Laravel's built-in notifications table (`notifiable_type`, `notifiable_id`, `type`, `data` json, `read_at`) with the native Notification system, which also handles email dispatch through the same channel. Recommended over a custom table — less code, well-tested, and queueable by default.)*

---

## Standard Laravel framework tables (not detailed — created by default migrations)

- `password_reset_tokens`
- `sessions`
- `personal_access_tokens` (if using Sanctum for API/mobile auth in Phase 2)
- `jobs` / `failed_jobs` (required for queue-based email/notification dispatch)
- `cache` (if using database cache driver)

---

## Security Notes

- **CNIC storage**: `complainant_cnic` holds sensitive PII. Store it as plain indexed text for now (needed for search/duplicate-detection), but restrict which roles can view the full CNIC in the UI, and ensure database backups are encrypted at rest. If Phase 2's CNIC-based citizen registration goes ahead, revisit whether this column needs field-level encryption.
- **File attachments**: never store uploaded files in a publicly web-accessible path. Store outside the public webroot (or in cloud object storage like S3) and serve them through an authenticated Laravel route that checks the user's department access before streaming the file.
- **Soft deletes everywhere on case-related tables** (`cases`, `case_comments`, `users`, `departments`): this is a government audit trail — nothing should be hard-deletable by a normal user. Only a database-level admin action should ever purge data.
- **Department-scoped queries**: every query that lists or fetches cases must be scoped by the logged-in user's assigned department(s) — via `whereIn('department_id', $user->departments->pluck('id'))` — unless the user is `super_admin`, who bypasses this scope entirely. This is the core access-control boundary — best enforced via a Laravel global scope or policy, not left to controller-by-controller checks. Since access is now many-to-many, a user with 3 assigned departments should see cases from all 3 in one merged dashboard view, with a department filter to narrow down if needed.
- **Role enum, not a free-text field**: keeps privilege escalation impossible via bad input — role changes should only be settable by `super_admin` through a controlled admin action.

## Performance Notes

- The composite indexes called out above (`cases.department_id + status`, `notifications.user_id + is_read`, `department_user.user_id/department_id`) match the exact queries your dashboard and bell icon will run constantly — add them at migration time, not as an afterthought.
- With multi-department access, the dashboard query becomes a `whereIn` against a subquery/join on `department_user` rather than a single equality check. Eager-load `$user->departments` once at the start of the request (e.g. in middleware) rather than re-querying it on every case list fetch.
- Use Eloquent eager loading (`with('department', 'comments.user', 'attachments', 'activityLogs')`) on the Case Detail page to avoid N+1 queries — this page pulls from 5 related tables at once.
- Case creation, email dispatch, and notification fan-out (to every user in the assigned department) should go through Laravel's **queue system**, not run synchronously in the request — this is what keeps case submission fast even with several departments and users notified at once.
- Paginate the dashboard case list server-side (Eloquent's `paginate()`) rather than loading all cases client-side, especially once case volume grows over months/years.

---

Once you've reviewed and confirmed (or edited) this schema, the next step is the detailed Cursor build prompt — covering Laravel version, project structure, auth/roles, queue setup, and the design-system integration.

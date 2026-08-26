# District Coordination — Case Management System

A multi-department case routing and workflow portal for a District Coordination / Assistant
Commissioner's office. Complaints are logged at the AC desk, routed to a line department, worked
on through a threaded remark trail, forwarded or returned between departments, and closed — with
a complete audit trail behind every transition.

The data model follows [`docs/database-schema.md`](docs/database-schema.md) and
[`docs/erd-diagram.mermaid`](docs/erd-diagram.mermaid); every colour, radius and layout decision
comes from [`docs/design-system.md`](docs/design-system.md).

---

## Stack

| Concern | Choice |
|---|---|
| Framework | Laravel 13.x on PHP 8.3+ |
| Database | MySQL 8+ |
| Authentication | Laravel Fortify (headless — the Blade views are ours) |
| Styling | Tailwind CSS v4, design tokens declared in `resources/css/app.css` |
| Interactivity | Alpine.js (sidebar, dropdowns, file chips, priority selector) |
| Queues | `database` driver, Redis-ready |
| Notifications | Laravel's native `Notification` classes on the `mail` + `database` channels |

There is deliberately no `routes/api.php`. Token authentication arrives with the Phase 2 mobile
app and will sit against the same models and policies; Passport slots in without touching them.

---

## Getting started

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
```

Point the database settings at a MySQL 8 instance and create both schemas:

```sql
CREATE DATABASE case_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE case_system_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then build the schema, seed the demo dataset and compile the front end:

```bash
php artisan migrate --seed
npm run build          # or: npm run dev
php artisan serve
```

`php artisan migrate --seed` produces a portal you can log into immediately: 9 departments,
23 officers, 28 cases across every status, multi-step routing histories, ~60 remarks and a
populated notification bell.

### Demo accounts

Every seeded account uses the password `password`.

| Role | Email | Access |
|---|---|---|
| Super admin (AC office) | `ac.office@sindh.gov.pk` | All departments |
| Department admin | `rukhsana.memon@childprotection.sindh.gov.pk` | Child Protection |
| Department admin | `aftab.hussain@health.sindh.gov.pk` | Health |
| Department officer | `waqar.ahmed.shar@childprotection.sindh.gov.pk` | Child Protection |
| Multi-department officer | `sana.qureshi@childprotection.sindh.gov.pk` | Child Protection + Women Protection |
| Multi-department officer | `zohaib.khaskheli@socialwelfare.sindh.gov.pk` | Social Welfare + Youth Affairs + Disability Welfare |

The two admin roles are required to enrol in two-factor authentication and will land on the
enrolment screen on first sign-in — scan the QR code with any TOTP app to continue. Sign in as one
of the department officers to reach the case register directly.

### Queue worker

In-app notifications (the bell and the Notifications page) are written in the same request as the
remark, file or routing action, so they appear immediately. A worker is only needed if you switch
`MAIL_MAILER` to a real SMTP server and want email delivered in the background:

```bash
php artisan queue:work --queue=default --tries=3
```

**Upgrading to Redis** is a configuration change only — no application code moves:

1. `composer require predis/predis` (or enable the `phpredis` extension).
2. Set `QUEUE_CONNECTION=redis` and `CACHE_STORE=redis` in `.env`.
3. Restart the workers.

The same applies to the cache: the department directory and dashboard stat-card counts are cached
through the `Cache` facade, so they follow whichever store is configured.

---

## Architecture

```
app/
  Enums/         CaseStatus, CasePriority, UserRole, CaseSource, RoutingAction, ActivityType
  Models/        Department, User, CaseModel, CaseRouting, CaseComment, CaseAttachment,
                 CaseActivityLog, Scopes/DepartmentScope
  Observers/     CaseObserver, CaseCommentObserver, CaseAttachmentObserver
  Policies/      CasePolicy, DepartmentPolicy, UserPolicy
  Services/      CaseWorkflowService, NotificationDispatchService, CaseNumberGenerator,
                 AttachmentStorageService, CaseRegisterQuery, DashboardStatsService,
                 DepartmentDirectory
  Notifications/ CaseAssigned, CommentAdded, CaseEscalated, CaseResolved
  Http/          Controllers, Requests (form request per form), Middleware
resources/
  views/         layouts, components, dashboard, cases, departments, auth, notifications
  css/app.css    Tailwind entry + design tokens
```

### Where the business rules live

**`CaseWorkflowService`** is the only place the assign → act → forward/return → complete lifecycle
is expressed. Controllers translate HTTP into a call on it and nothing more, so a queued job, a
console command and a form submission all behave identically.

**Observers, not controllers, write the audit trail.** `CaseObserver` logs creation, status
changes, priority changes and department reassignment to `case_activity_logs` no matter which code
path touched the case — including seeders.

**Enums carry their own presentation.** `CaseStatus::pillClasses()` and
`CasePriority::label()` mean a status pill is rendered the same way everywhere and a new status is
a single-file change. Because those class names live in PHP rather than Blade, `resources/css/app.css`
declares `@source '../../app/**/*.php'` so Tailwind does not tree-shake them out.

### Authorization

Two independent layers, because one is not enough:

1. **`DepartmentScope`**, a global scope on `CaseModel`, filters *every* query — controllers,
   relationships, console commands, future API routes — down to the signed-in user's departments.
   A case is visible if it currently sits with one of their departments *or* has a `case_routing`
   row pointing at one, so a department that has already handled a file keeps its view of it after
   forwarding it on. `super_admin` bypasses the scope.
2. **`CasePolicy`** re-checks a directly addressed case before any write. Ordinary officers may
   comment and update; re-routing a case is restricted to department admins and the AC office.

`tests/Feature/DepartmentScopingTest.php` proves an officer in Department A cannot reach a
Department B case through the register, the dashboard, the global search, the detail page, the
write routes, or the query builder itself.

### Authentication

Fortify runs headless: it owns login, logout, password reset, email verification, throttling and
TOTP two-factor, while every view under `resources/views/auth/` is ours and matches the design
system. Configuration of note:

- Registration is **disabled**. Accounts are created by administrators on the Department & User
  Management screen and are marked verified at creation — the provisioning admin vouches for the
  address.
- Login is throttled at **5 attempts per minute per email + IP**.
- Two-factor is **mandatory** for `super_admin` and `department_admin`
  (`FORTIFY_TWO_FACTOR_REQUIRED_ROLES`); the `two-factor` middleware holds those roles at the
  enrolment screen until they confirm.
- Session cookies are `secure`, `httpOnly` and `SameSite=Strict` with a 2-hour idle timeout. Set
  `SESSION_SECURE_COOKIE=false` for local development over plain HTTP.
- A deactivated account is rejected during credential validation, so it never reaches a session;
  `EnsureUserIsActive` also logs out anyone deactivated mid-session.

### File uploads

Attachments are written to the `private` disk (`storage/app/private/case-files`), never under
`public/`. The only way to read one is `GET /cases/{case}/attachments/{attachment}`, which
re-checks `CasePolicy` on every request and streams the file. Uploads are validated by extension,
MIME type and size (`UPLOAD_MAX_KILOBYTES`, 10 MB by default); executables are rejected.

Run `php artisan storage:link` only if you add genuinely public assets — attachments deliberately
do not use it.

### Performance

- The Case Detail page eager-loads department, creator, assignee, comments with their authors and
  departments, attachments with uploaders, activity logs with users, and routing steps.
  The register eager-loads department, creator and assignee.
- `Model::preventLazyLoading()` is enabled outside production, so an unloaded relation throws in
  development and in the test suite rather than quietly firing N+1 queries.
- `tests/Feature/QueryEfficiencyTest.php` asserts that the dashboard and Case Detail query counts
  do **not** grow with row count, which catches a regression that `with()` alone would not.
- The register is paginated server-side at 20 rows (`config/cases.php`).
- Dashboard stat cards are cached per user for 30 seconds, the department directory for 60, and the
  notification bell's unread count for 60 — all invalidated on the writes that change them.
- Indexes follow `docs/database-schema.md`, including the composite
  `cases(department_id, status)` and `notifications(notifiable_type, notifiable_id, read_at)`
  that back the dashboard and the bell.

---

## Design system notes

`docs/design-system.md` asks for the colour tokens to live in `tailwind.config.js`. Tailwind v4
replaced the JavaScript config with a CSS-first `@theme` block, so the tokens live at the top of
`resources/css/app.css` instead. The intent is unchanged and the result is the same: every token
becomes a first-class utility (`--color-navy` yields `bg-navy`, `text-navy`, `border-navy`), and no
Blade template in the project contains a raw hex code.

Responsive behaviour follows section 7 exactly:

| Breakpoint | Sidebar | Case table | Forms | Department grid |
|---|---|---|---|---|
| ≥1024px | Expanded 240px, user-toggleable to a 64px icon rail | Full table | 2 columns | 3 columns |
| 768–1023px | Auto-collapsed to the icon rail | Full table, horizontal scroll | 1 column | 2 columns |
| <768px | Slide-out drawer with a dark scrim | Stacked cards | 1 column, full-width buttons | 1 column |

Case Detail stacks in the prescribed order below `lg` — Priority & Status → Attachments →
Activity Log → Summary → Remarks — using CSS grid placement, so the DOM order is the mobile order
and no markup is duplicated. Interactive targets are at least 44px tall.

---

## Testing

```bash
php artisan test
```

The suite runs against the separate `case_system_testing` schema (`phpunit.xml`) rather than
in-memory SQLite, because the target servers ship PHP without `pdo_sqlite`. Coverage:

| File | What it proves |
|---|---|
| `AuthenticationTest` | Sign-in, bad credentials, deactivated accounts, throttling, registration stays closed, the 2FA gate |
| `CaseManagementTest` | Case creation and validation, activity log and routing history, status/priority updates, remarks, attachment storage and download guarding |
| `CaseWorkflowTest` | Assign, forward, return, complete, the activity timeline, acting-department rules, supervisory routing |
| `DepartmentScopingTest` | Cross-department isolation across every route and the query builder |
| `NotificationDispatchTest` | Who is notified for each event, queueing, the notifications page, bell-count caching |
| `QueryEfficiencyTest` | Dashboard and Case Detail query counts stay flat as rows grow; pagination |
| `SmokeRenderTest` | All seven pages render against the seeded dataset |

`scripts/smoke-check.php` walks the same pages over real HTTP against a running server:

```bash
php artisan serve &
php scripts/smoke-check.php http://127.0.0.1:8000 waqar.ahmed.shar@childprotection.sindh.gov.pk
```

---

## Keeping the docs authoritative

`.cursorrules` points every AI-assisted change back at `docs/database-schema.md` and
`docs/design-system.md`. If the schema or the design system changes, update the file in `docs/`
first — those files, not this README, are the source of truth.

# Full Laravel End-to-End Audit - 2026-08-04

## Scope

Audited the Laravel CRM application in `/home/admin/web/housefix360.com/public_html/crms` as a senior Laravel QA/security review. The audit covered route inventory, authentication, authorization, CRUD patterns, validation, dashboard, search/filtering, uploads, PDF/Excel, database structure, security, performance, error handling, APIs/mobile APIs, Employee Tracking, reports, notifications/settings, production readiness, browser compatibility, code quality, and automated tests.

No production code changes were made during the initial audit. Remediation began after the audit at the user's request, one issue at a time.

## Verification Evidence

- Laravel routes: 419 total routes, 130 API routes.
- Database: 64 tables, 102 foreign keys.
- Live tracking data: 27 attendances, 8,924 location tracking rows, 4 employee device rows.
- PHP runtime used: PHP 8.2.27.
- PHP syntax: PASS across `app`, `routes`, `database`, and `config`.
- PHPUnit: 81 passed, 79 failed, 21 skipped; failures are caused by missing `pdo_sqlite` while `phpunit.xml` forces SQLite in-memory.
- Employee Tracking focused unit tests: PASS, 35 tests / 154 assertions.
- Browser tooling: Node/npm/Chrome/Playwright are not installed on this server, so browser compatibility and rendered-map pixel checks could not be executed.

## Executive Result

Overall Health Score: 74/100

Production Readiness Score: 68/100

The codebase has a broad feature surface and strong Employee Tracking backend unit coverage. The code-fixable High findings from this audit have been remediated; production readiness is still held back by externally blocked feature tests, incomplete restore/force-delete coverage, sync queue deployment posture, session hardening warnings, and missing browser automation support.

## Passed Modules

- Login baseline: validates email/password, checks active status, regenerates session on login.
- Logout baseline: invalidates session and regenerates CSRF token on logout.
- Password reset baseline: uses Laravel password broker and hashes reset passwords.
- Role/permission middleware is applied to most protected web CRUD routes.
- API token storage hashes mobile bearer tokens.
- CSRF protection is present for normal web POST/PUT/PATCH/DELETE routes.
- PHP syntax is clean.
- Database has substantial foreign key coverage.
- Employee Tracking backend route/timeline/distance logic passed focused unit tests.
- Employee Tracking live data verification for attendance `50` passed backend consistency checks.
- Google Maps key resolver rejects seeded demo key and accepts configured environment fallback.

## Failed Modules

- Automated feature tests / CI confidence.
- Restore / force-delete coverage across CRUD modules.
- Browser compatibility verification due missing browser tooling.

## Warnings

- Framework local storage signed routes are enabled for the private local disk. The framework enforces relative signatures, but this should be deliberately reviewed in production.
- Session encryption is disabled; secure cookies are enabled.
- Single web session enforcement is disabled by `.env`.
- `.env` contains production credentials and a Google Maps key on disk.
- A seeded Google Maps-looking key exists in a migration and resolver constant, although resolver logic rejects it as a demo key.
- `php8.2 vendor/bin/pint --test --dirty` fails on the dirty Employee Tracking test file for formatting only.
- Excel import duplicate prevention remains a non-High follow-up; the High rollback/atomicity issue is resolved.

## Critical / High Findings

### F-001 - High - Feature Test Suite Cannot Run

Module: Testing, CI, regression verification

Root cause: `phpunit.xml` configures SQLite in-memory, but the active PHP 8.2 runtime does not have `pdo_sqlite`.

Affected files/functions/lines:
- `phpunit.xml:26` sets `DB_CONNECTION=sqlite`.
- `phpunit.xml:27` sets `DB_DATABASE=:memory:`.

Steps to reproduce:
1. Run `php8.2 artisan test`.
2. Observe feature tests fail with `could not find driver (Connection: sqlite, Database: :memory:)`.

Expected result: Full feature/unit suite runs in the audit environment.

Actual result: 81 passed, 79 failed, 21 skipped. Failures are environment driver failures, not verified application assertions.

Recommended fix: Install/enable PHP 8.2 SQLite extension (`pdo_sqlite`) in CI/server test runtime, or configure tests to use an available isolated MySQL test database.

Minimal safe fix: Enable `pdo_sqlite` for PHP 8.2 and rerun `php8.2 artisan test`.

Regression risk: Low. This changes test runtime support only.

Remediation status: BLOCKED EXTERNALLY on 2026-08-04.

Re-verification:
- `php8.2 -m` shows `PDO` and `pdo_mysql`, but no `pdo_sqlite`.
- `apt-get install php8.2-sqlite3` failed because the current server user cannot lock `/var/lib/apt/lists`.
- Creating a separate MySQL test database failed with MySQL permission error `1044 Access denied`.
- Existing production database was not used for feature tests because tests use `RefreshDatabase` and could destroy production data.

Next safe action: enable `pdo_sqlite` at the OS/runtime level or provision a dedicated test database with create/drop privileges, then rerun `php8.2 artisan test`.

### F-002 - High - Login and API Routes Are Not Rate Limited

Module: Authentication, API security

Root cause: No throttle middleware or rate limiter is configured in `bootstrap/app.php`; login and mobile API endpoints are registered without throttle middleware.

Affected files/functions/lines:
- `bootstrap/app.php:19-28` configures middleware aliases but no throttle/rate limiter.
- `routes/web.php:38` web login.
- `routes/api.php:15-16` mobile login aliases.
- `routes/api.php:24-45` authenticated mobile API group.

Steps to reproduce:
1. Inspect route middleware with `php8.2 artisan route:list`.
2. Confirm login and API routes do not include `throttle`.

Expected result: Login, password reset, and mobile APIs enforce rate limits and return 429 on excessive attempts.

Actual result: No route-level throttle was found.

Recommended fix: Define named rate limiters for login, password reset, mobile auth, and tracking ingestion; apply `throttle:name` middleware.

Minimal safe fix: Add conservative throttles to `/login`, `/forgot-password`, `/api/login`, `/api/auth/login`, and mobile tracking endpoints.

Regression risk: Medium. Existing mobile clients may need appropriate retry handling.

Remediation status: RESOLVED on 2026-08-04.

Minimal safe fix applied:
- Added named rate limiters in `app/Providers/AppServiceProvider.php`.
- Applied `throttle:web-auth` to `POST /login`.
- Applied `throttle:password-reset` to `POST /forgot-password` and `POST /reset-password`.
- Applied `throttle:mobile-auth` to `POST /api/login` and `POST /api/auth/login`.
- Applied `throttle:tracking-ingest` to mobile check-in, check-out, single tracking, bulk/offline tracking, and legacy status tracking routes.

Re-verification:
- `php8.2 -l app/Providers/AppServiceProvider.php routes/web.php routes/api.php`: PASS.
- `php8.2 artisan route:list --json`: targeted routes now include the expected throttle middleware.
- Employee Tracking regression suite: 35 passed, 154 assertions.

### F-003 - High - Public Web Registration Is Enabled In Production

Module: Authentication, authorization

Root cause: Registration routes are public and create an active `Employee` user, then log that user in immediately.

Affected files/functions/lines:
- `routes/web.php:41-42` public register routes.
- `app/Http/Controllers/AuthController.php:134-160` creates user, assigns Employee role, logs in.

Steps to reproduce:
1. Open `/register`.
2. Submit a unique email/password.

Expected result: Internal CRM user creation should be admin-controlled or disabled in production unless explicitly required.

Actual result: Any visitor can self-register as an Employee.

Recommended fix: Disable public registration in production or gate it behind a setting/invite token/admin permission.

Minimal safe fix: Wrap register routes in a production-disabled feature flag and default it to false.

Regression risk: Medium if field onboarding currently relies on self-registration.

Remediation status: RESOLVED on 2026-08-04.

Minimal safe fix applied:
- Added `auth.public_registration_enabled`, defaulting to `AUTH_PUBLIC_REGISTRATION_ENABLED=false`.
- Wrapped web `/register` routes in that config gate.
- Added controller-level 404 guards to `showRegister()` and `register()`.
- Hid the login-page registration link unless public registration is enabled.

Re-verification:
- `php8.2 artisan tinker` confirmed `auth.public_registration_enabled=false`.
- `php8.2 artisan route:list --path=register` shows only mobile device registration routes, not web self-registration.
- PHP syntax checks passed for `config/auth.php`, `routes/web.php`, and `app/Http/Controllers/AuthController.php`.
- Employee Tracking regression suite: 35 passed, 154 assertions.

### F-004 - High - Public Mobile Settings Endpoints Expose Configuration

Module: Mobile APIs, settings, information disclosure

Root cause: Compatibility aliases for app/module/map/tracking settings are outside `mobile.api` middleware.

Affected files/functions/lines:
- `routes/api.php:18-22` public settings aliases.
- `app/Services/GoogleMapsApiKeyResolver.php:7-24` protects against seeded demo key but cannot protect all public settings exposure by itself.

Steps to reproduce:
1. Request `GET /api/V1/getAppSettings`, `/api/V1/getModuleSettings`, `/api/V1/getMapSettings`, or `/api/tracking/settings` without a bearer token.

Expected result: Sensitive or operational settings require authentication, or public response is strictly allowlisted.

Actual result: Settings aliases are public.

Recommended fix: Move endpoints behind `mobile.api` or return only a minimal public bootstrap allowlist.

Minimal safe fix: Keep compatibility URLs but add a sanitized public resource that excludes secrets and operational thresholds.

Regression risk: Medium because older mobile app builds may depend on these routes.

Remediation status: RESOLVED on 2026-08-04.

Minimal safe fix applied:
- Kept legacy public URLs for compatibility.
- Changed public `/api/V1/getAppSettings`, `/api/V1/getModuleSettings`, `/api/V1/getMapSettings`, and `/api/tracking/settings` to strict public bootstrap payloads.
- Removed Google Maps API key, tracking validation thresholds, mock-location policy, bulk sync limits, and operational tracking policy details from unauthenticated responses.
- Left authenticated `/api/settings/app`, `/api/settings/modules`, `/api/settings/map`, and `/api/settings/tracking` unchanged behind `mobile.api`.

Re-verification:
- `php8.2 artisan route:list --json` confirms public aliases now route to public-safe methods; authenticated settings routes still include `mobile.api`.
- In-process requests to all four public aliases returned 200 with no forbidden sensitive keys.
- PHP syntax checks passed for modified API controllers/routes.
- Employee Tracking regression suite: 35 passed, 154 assertions.

### F-005 - High - Excel Import Is Not Atomic

Module: Excel import, database, data integrity

Root cause: The import job inserts records in chunks without a transaction around the whole import. If a later chunk fails, earlier chunks persist.

Affected files/functions/lines:
- `app/Jobs/ImportExpensesFromExcel.php:47-72` streams chunks and deletes the file in `finally`.
- `app/Jobs/ImportExpensesFromExcel.php:75-117` builds rows and inserts chunks.

Steps to reproduce:
1. Import a multi-chunk expense file.
2. Cause one later row/chunk to fail database constraints.
3. Inspect expenses inserted before the failure.

Expected result: Invalid import rolls back completely or records a failed import without partial persistence.

Actual result: Earlier chunks can remain inserted.

Recommended fix: Use an import batch table and wrap the import in a transaction where feasible, or make each import idempotent and resumable with failure status.

Minimal safe fix: Wrap chunk processing in `DB::transaction()` for moderate-sized files and log failed imports without deleting evidence prematurely.

Regression risk: Medium for large imports because long transactions can lock tables.

Remediation status: RESOLVED on 2026-08-04.

Minimal safe fix applied:
- Wrapped header loading, helper row creation, and expense chunk inserts in a single `DB::transaction()`.
- Added a per-chunk `finally` block to disconnect spreadsheet worksheets even if a chunk insert fails.
- Kept existing import mapping, validation, chunk size, and cleanup behavior unchanged.

Re-verification:
- `php8.2 -l app/Jobs/ImportExpensesFromExcel.php`: PASS.
- Source scan confirms `Expense::query()->insert()` now executes inside `DB::transaction()`.
- `php8.2 vendor/bin/pint --test` on touched high-fix files: PASS.
- `php8.2 artisan test tests/Unit`: 81 passed, 235 assertions.

### F-006 - High - Queue Worker Is Started From A Web Request

Module: Queues, Excel import, production readiness

Root cause: Import controller dispatches a job and then starts `queue:work --once` using `exec()` from the request lifecycle while production `.env` uses `QUEUE_CONNECTION=sync`.

Affected files/functions/lines:
- `app/Http/Controllers/ExpenseImportController.php:22-27` dispatches job and starts worker.
- `app/Http/Controllers/ExpenseImportController.php:30-52` starts a shell queue worker.
- `.env:46` sets `QUEUE_CONNECTION=sync`.

Steps to reproduce:
1. Upload an import file.
2. Observe that the request path launches a worker process.

Expected result: Production queue work is handled by Supervisor/systemd/Horizon or a managed worker, not spawned by HTTP requests.

Actual result: Web request attempts to manage queue execution.

Recommended fix: Configure a real async queue driver and external worker process.

Minimal safe fix: Remove web-triggered worker startup after deployment worker is configured.

Regression risk: Medium. Imports may stop processing unless worker deployment is completed first.

Remediation status: RESOLVED on 2026-08-04.

Minimal safe fix applied:
- Added `queue.auto_start_worker_from_request`, defaulting to `QUEUE_AUTO_START_WORKER_FROM_REQUEST=false`.
- Gated `ExpenseImportController::startQueueWorkerOnce()` behind that explicit opt-in flag.
- Current production `QUEUE_CONNECTION=sync` still processes the dispatched import synchronously, but no shell worker is spawned from the HTTP request by default.

Re-verification:
- `php8.2 artisan tinker` confirmed `queue.auto_start_worker_from_request=false` and `queue=sync`.
- PHP syntax checks passed for `config/queue.php` and `app/Http/Controllers/ExpenseImportController.php`.
- Source scan confirms `queue:work` is only reachable behind the new config gate.
- Employee Tracking regression suite: 35 passed, 154 assertions.

## Medium Findings

### F-007 - Medium - Restore And Force Delete Are Not Implemented Across CRUD

Module: CRUD, database lifecycle

Root cause: Only `Expense` and `Labour` models use `SoftDeletes`; route/controller inventory shows many delete endpoints and no general restore/force-delete routes.

Affected files/functions/lines:
- `app/Models/Expense.php:14` uses SoftDeletes.
- `app/Models/Labour.php:12` uses SoftDeletes.
- `routes/web.php:180`, `223`, `361`, `396`, `414`, `465`, `521`, `599`, `799` are examples of delete routes.

Steps to reproduce:
1. Delete records in modules such as projects, clients, employees, tasks, payments, roles.
2. Look for restore/force-delete route or UI action.

Expected result: Each CRUD module supports Create, Read, Update, Delete, Restore, Soft Delete, and Force Delete if that is a business requirement.

Actual result: Most modules implement delete only; restore/force delete is not generally available.

Recommended fix: Decide per module whether soft-delete lifecycle is required, then add migrations, model traits, routes, permissions, UI actions, and tests consistently.

Minimal safe fix: Document modules where restore/force-delete is intentionally unsupported; implement only on required modules.

Regression risk: High if applied broadly without business confirmation.

### F-008 - Medium - GET Logout Performs A State-Changing Action

Module: Authentication, CSRF/session safety

Root cause: Logout is exposed as both POST and GET. GET requests are not CSRF-protected.

Affected files/functions/lines:
- `routes/web.php:47-48` POST and GET logout.
- `app/Http/Controllers/AuthController.php:169-176` invalidates the session.

Steps to reproduce:
1. Visit `/logout` while logged in.

Expected result: Logout should be POST-only with CSRF protection.

Actual result: GET `/logout` invalidates the session.

Recommended fix: Remove GET logout after updating any links/forms to POST.

Minimal safe fix: Redirect GET `/logout` to login without mutating session, or keep only POST.

Regression risk: Low to medium depending on existing menu links.

### F-009 - Medium - Server Cache Clear Runs Over GET

Module: Authorization, production operations

Root cause: `server-commands/optimize` is a GET route that clears application, route, and config caches.

Affected files/functions/lines:
- `routes/web.php:50-56`.

Steps to reproduce:
1. Log in as a user with `permissions-edit`.
2. Visit `/server-commands/optimize`.

Expected result: State-changing operational actions use POST with CSRF and explicit confirmation.

Actual result: GET request clears caches.

Recommended fix: Convert to POST and restrict to a dedicated admin/server-operation permission.

Minimal safe fix: Change route method to POST and update the UI form.

Regression risk: Low.

### F-010 - Medium - Single-Web-Session Restriction Is Disabled

Module: Authentication/session management

Root cause: Middleware exists, but `.env` sets `ENABLE_SINGLE_WEB_SESSION=false`.

Affected files/functions/lines:
- `bootstrap/app.php:20-22` appends `EnsureSingleWebSession`.
- `config/session.php:39` reads `ENABLE_SINGLE_WEB_SESSION`.
- `.env:42` disables it.

Steps to reproduce:
1. Log in from two browsers/devices as the same web user.
2. Observe both sessions remain valid.

Expected result: If multiple login restriction is required, second login invalidates older sessions.

Actual result: Restriction is configured off.

Recommended fix: Enable only after validating admin/support workflows.

Minimal safe fix: Set `ENABLE_SINGLE_WEB_SESSION=true` in production after stakeholder approval.

Regression risk: Medium for shared/admin accounts.

### F-011 - Medium - Mobile API Tokens Do Not Expire By Default

Module: Mobile API authentication

Root cause: Login creates `mobile_api_tokens` without `expires_at`; middleware accepts tokens with null expiry.

Affected files/functions/lines:
- `app/Http/Controllers/Api/MobileAuthEndpoints.php:126-136` creates token.
- `app/Http/Middleware/MobileApiAuth.php:24-30` accepts null expiry.

Steps to reproduce:
1. Log in via mobile API.
2. Inspect `mobile_api_tokens.expires_at`.

Expected result: Tokens expire or are rotated by policy.

Actual result: Tokens can be valid indefinitely unless explicitly deleted.

Recommended fix: Add configurable token TTL and refresh/reauthentication flow.

Minimal safe fix: Set `expires_at` during login based on a setting such as `MOBILE_TOKEN_TTL_DAYS`.

Regression risk: Medium for old mobile clients.

### F-012 - Medium - Dashboard Performs Global Queries Before Permission Masking

Module: Dashboard, authorization, performance

Root cause: Controller loads full summary first and masks values afterward based on permissions.

Affected files/functions/lines:
- `app/Http/Controllers/DashboardController.php:24-36`.
- `app/Services/DashboardService.php:18-55`.

Steps to reproduce:
1. Log in as a restricted user.
2. Load dashboard and inspect query activity.

Expected result: Unauthorized data totals should not be queried when a user cannot view them.

Actual result: Global counts/sums are calculated before masking.

Recommended fix: Pass permissions/user context into `DashboardService` and only execute allowed queries.

Minimal safe fix: Move permission checks before summary query calls.

Regression risk: Medium.

### F-013 - Medium - Employee Count Source May Be Inconsistent

Module: Dashboard/statistics

Root cause: Dashboard counts `Employee::count()` while current tracking/authentication flows use `User` for employees.

Affected files/functions/lines:
- `app/Services/DashboardService.php:6` imports `Employee`.
- `app/Services/DashboardService.php:41` uses `Employee::count()`.

Steps to reproduce:
1. Compare dashboard employee count with users assigned Employee role.

Expected result: Dashboard employee count matches the canonical employee identity source.

Actual result: Count can diverge if `employees` table and employee users are not synchronized.

Recommended fix: Confirm canonical employee model and align count query.

Minimal safe fix: If users are canonical, count users with Employee role/role column.

Regression risk: Medium.

### F-014 - Medium - Session Data Is Not Encrypted

Module: Security, session

Root cause: Session encryption is disabled by config.

Affected files/functions/lines:
- `config/session.php:52` sets `encrypt` from `SESSION_ENCRYPT`, default false.

Steps to reproduce:
1. Run config check; observed `session_encrypt=false`.

Expected result: Sensitive session payloads are encrypted at rest, especially with file sessions.

Actual result: Session encryption disabled.

Recommended fix: Enable `SESSION_ENCRYPT=true` after validating session compatibility.

Minimal safe fix: Enable in staging, force logout old sessions, then deploy.

Regression risk: Medium because existing sessions become unreadable.

## Low Findings

### F-015 - Low - Code Style Drift In Dirty Test File

Module: Code quality

Root cause: Pint finds formatting differences in the dirty Employee Tracking frontend test file.

Affected files/functions/lines:
- `tests/Unit/EmployeeTrackingFrontendRouteRenderingTest.php`.

Steps to reproduce:
1. Run `php8.2 vendor/bin/pint --test --dirty`.

Expected result: Pint exits 0.

Actual result: Pint exits 1 for `single_quote` and `concat_space`.

Recommended fix: Run Pint after the existing Employee Tracking work is finalized.

Minimal safe fix: `php8.2 vendor/bin/pint tests/Unit/EmployeeTrackingFrontendRouteRenderingTest.php`.

Regression risk: Low.

### F-016 - Low - Stale Debug Comment

Module: Code quality

Root cause: Commented debug statement remains in authentication controller.

Affected files/functions/lines:
- `app/Http/Controllers/AuthController.php:30`.

Steps to reproduce:
1. Inspect the login method.

Expected result: No stale debug artifacts.

Actual result: `//dd($request->all());` remains.

Recommended fix: Remove the comment during normal cleanup.

Minimal safe fix: Delete the line.

Regression risk: None.

## Employee Tracking Verification

Live attendance verified: `attendance_id=50`.

Database/API-builder consistency:

- Attendance status: `present`.
- Check-in: `2026-08-03 09:03:42`.
- Check-out: `2026-08-03 09:43:42`.
- Total GPS rows: 63.
- Accepted rows: 59.
- Rejected rows: 4.
- Rejection reason: `walking_speed_exceeded`.
- Total segments: 1.
- Timeline items from backend builder: 59.
- Rendered polylines expected from JSON: 1, because UI uses `data.polylineSegments`.
- Rendered route markers expected from JSON: 2 route markers plus attendance boundary markers where available.
- Calculated backend distance: 3.83 km.
- `gpsDistanceKm`: 3.83 km.
- `totalKM`: 3.83 km.
- UI distance source: `gpsDistanceKm ?? totalKM`, rendered as `3.83 KM`.
- Latest accepted GPS point: row `9764`, latitude `9.9146205`, longitude `78.0980446`.
- `employee_devices` latest latitude/longitude: `9.9146205`, `78.0980446`.

Employee Tracking pass/fail matrix:

- Check In API registered: PASS.
- Check Out API registered: PASS.
- Attendance ID in timeline JSON: PASS by controller response `attendanceId` and `attendanceIds` fields.
- Open attendance checkout suppression: PASS by UI filter at `resources/views/pages/employee_tracking/index.blade.php:545-569`.
- Polyline uses backend `polylineSegments`: PASS at `resources/views/pages/employee_tracking/index.blade.php:468-470`.
- UI distance uses backend distance first: PASS at `resources/views/pages/employee_tracking/index.blade.php:522-531`.
- Timeline JSON includes route data: PASS at `app/Http/Controllers/EmployeeTrackingController.php:186-209`.
- Duplicate / invalid / accuracy / speed validation coverage: PASS by focused unit tests.
- Gap/segment splitting coverage: PASS by focused unit tests and builder code.
- Live location equals last accepted GPS point: PASS for attendance `50`.
- Browser-rendered map pixel/marker verification: BLOCKED because browser tooling is not installed.

## API Verification Summary

Registered API endpoints include login/logout, profile/employee, attendance, check-in/check-out, single tracking update, bulk/offline tracking sync, device registration/status, live locations, dashboard/settings, clients/projects/tasks/expenses/payments/inventory/preorders/wallet/leave.

Required Employee Tracking endpoints are registered:

- `POST /api/check_in`
- `POST /api/tracking/location`
- `POST /api/tracking/locations/bulk`
- `POST /api/check_out`
- `POST /dashboard/getTimeLineAjax`

API failures/warnings:

- Rate limiting for auth and tracking ingestion: PASS after remediation.
- Public settings aliases are still unauthenticated for compatibility, but now return sanitized bootstrap payloads: WARNING.
- Full HTTP status matrix could not be completed because feature tests are blocked by missing `pdo_sqlite`.
- Browser/API rendered-map equality could not be completed because browser tooling is unavailable.

## Database Verification

Passed:

- All migrations are marked as run.
- 64 tables exist.
- 102 foreign keys exist.
- `attendances`, `location_trackings`, and `employee_devices` are populated and consistent for the live Employee Tracking sample.

Failed/warnings:

- Restore/force-delete lifecycle is inconsistent and mostly absent.
- Excel import duplicate prevention remains a follow-up.
- Queue driver is `sync` in production; web-triggered queue worker startup is now disabled by default.

## Production Readiness

Passed:

- `APP_ENV=production`.
- `APP_DEBUG=false`.
- `APP_KEY` is set.
- `SESSION_SECURE_COOKIE=true`.
- Google Maps key is configured and resolver accepts environment fallback.

Failed/warnings:

- `QUEUE_CONNECTION=sync`.
- `ENABLE_SINGLE_WEB_SESSION=false`.
- `SESSION_ENCRYPT=false`.
- Public web registration disabled by default after remediation.
- Auth/tracking rate limiting added after remediation.
- Feature tests blocked by runtime extension.
- Browser automation unavailable.
- `.env` contains sensitive live credentials and should be protected from backups/version control exposure.

## Browser Compatibility

Status: BLOCKED.

Chrome, Edge, Firefox, mobile, tablet, desktop responsive browser checks could not be run because `node`, `npm`, `npx`, `google-chrome`, `chromium`, and `playwright` are not available in the server environment.

Recommended minimal path:

1. Install Node/npm and Playwright or run this app through an existing browser QA runner.
2. Add login smoke tests, CRUD smoke tests, dashboard checks, upload checks, and Employee Tracking map screenshots.
3. Validate Google Maps and Leaflet fallback at desktop and mobile breakpoints.

## Final Module Matrix

- Authentication: WARNING; public registration and missing throttling are resolved, but GET logout and single-session disabled remain.
- Authorization: WARNING/FAIL due redirect-not-403 web behavior and dashboard querying before masking; most CRUD routes are permission-protected.
- CRUD modules: FAIL because restore/force-delete lifecycle is not generally implemented.
- Validation: WARNING; many controllers validate inputs, but full feature validation matrix is blocked by test runtime.
- Dashboard: WARNING due pre-mask global queries and possible employee count source mismatch.
- Search/filter/pagination: WARNING; route/view/controller patterns exist, but exhaustive browser checks are blocked.
- File upload: WARNING; validations exist for sampled upload/import paths, but browser and large-file matrix not executed.
- PDF: WARNING; PDF routes/views exist, but visual/print/download browser checks are blocked.
- Excel: WARNING; import atomicity is resolved, duplicate-prevention follow-up remains.
- Database: WARNING; strong FK coverage, but soft-delete lifecycle remains incomplete.
- Security: WARNING; rate limiting, public registration, and public settings leakage are resolved, while token expiry and session encryption remain.
- Performance: WARNING; dashboard unnecessary queries and possible N+1 risk remain; no profiler run.
- Error handling: WARNING; JSON 404/403 handlers exist, but full 400/401/403/404/409/422/429/500 matrix blocked.
- APIs: WARNING; throttling added and public settings sanitized, full status matrix still blocked by test runtime.
- Mobile APIs: WARNING; throttling added, non-expiring tokens remain, tracking core passes.
- Employee Tracking: PASS for backend/database consistency; BLOCKED for browser-rendered map verification.
- Reports: WARNING; full filter/export/browser checks blocked.
- Notifications: WARNING; not fully executable from current environment without external mail/SMS/WhatsApp/push services.
- Settings: WARNING; public settings aliases are sanitized and production maps config is present.
- Production readiness: WARNING; code-fixable High findings are resolved, but F-001 requires external runtime/database provisioning.
- Browser compatibility: BLOCKED.
- Code quality: WARNING; syntax clean, Pint dirty check failing on one dirty test file.
- Testing: FAIL due missing `pdo_sqlite`.

## Recommended Remediation Order

1. Enable `pdo_sqlite` or configure a real isolated test DB, then rerun `php8.2 artisan test`.
2. Decide and implement/explicitly document restore and force-delete lifecycle per CRUD module.
3. Enable browser QA tooling and add Employee Tracking rendered-map assertions.
4. Review dashboard data access so unauthorized totals are not queried.
5. Add mobile token expiry/rotation policy.
6. Configure a real async queue worker if imports should run outside the request lifecycle.

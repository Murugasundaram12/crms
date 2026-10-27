# AUDIT-66 — Hardened Browser QA Isolation Plan

**Project:** HouseFix360 CRM (`D:\xampp-8.2\htdocs\crms`)  
**Audit Reference:** AUDIT-66  
**Date:** 2026-10-27  
**Status:** Hardened Planning Phase (Zero Execution / Read-Only Source Analysis)  

---

## 1. Executive Summary & Review Baseline

Following the initial design in **AUDIT-65**, this review addresses the critical risks and edge cases of browser-based end-to-end testing, establishing a hardened, fail-closed architecture.

### Primary Principle: Multi-Tier Fail-Closed Verification
No browser testing may proceed based merely on environment flags (`$_ENV`, `putenv()`), port assignments, or the assumption that configuration cache is absent. Instead, the application process must **prove its database connection at the physical PDO driver and engine level** before serving a single application route.

### Mandatory Compliance Confirmation
- **Planning & Source Review Only:** No SQLite database file was created, no server was launched, no browser was opened, and no code, schema, or configuration files were modified.
- **Zero Database Operations:** Persistent databases `crms`, `admin_crms`, and `admin_crm_demo` were never accessed, queried, or modified.
- **Zero Process Invocation:** No Artisan, Tinker, PHPUnit, migrations, seeders, or test runners were executed.
- **Preservation:** All code changes and audit reports from AUDIT-55 through AUDIT-65 remain completely untouched.

---

## 2. Threat Analysis: Remaining Gaps in AUDIT-65

An architectural audit of the AUDIT-65 design identified five critical vulnerabilities that have now been resolved:

| ID | Threat Vector in AUDIT-65 | Severity | Hardened Architecture Resolution in AUDIT-66 |
| :--- | :--- | :--- | :--- |
| **T-01** | **Config Cache Override:** Relying solely on `file_exists('bootstrap/cache/config.php')` is fragile. If the file exists, deleting it is prohibited, but Laravel's `LoadConfiguration` loads it and overrides environment variables. | **CRITICAL** | Programmatically override `$app['config']` after Laravel boots, purge resolved connections, and verify the live PDO instance. Existing cache files are preserved untouched. |
| **T-02** | **Superficial Flag Verification:** Relying on `$_ENV` or `putenv()` checks without inspecting the live container connection. | **CRITICAL** | Inspect the actual resolved `ConnectionInterface`, its PDO driver name (`sqlite`), canonical database path via `realpath()`, and query raw `PRAGMA database_list` directly from SQLite engine. |
| **T-03** | **Unprotected Route Bootstrap:** Routes or view composers executing queries before the database guard completes. | **HIGH** | Gatekeeper intercepts execution between `require_once bootstrap/app.php` and `$app->handleRequest()`. If the connection check fails, process dies with HTTP 500 before routing occurs. |
| **T-04** | **External API Side Effects:** Active code in `ReverseGeocodingService` and `EmployeeTrackingController` dispatches live HTTP calls to Google Maps/Roads APIs. | **MEDIUM** | Mock / intercept outbound HTTP requests via `Http::fake()` in the test router so no external network requests or billable API calls occur. |
| **T-05** | **Destructive Teardown by Guessed Paths:** Deleting files using relative or guessed paths risks accidental deletion of legitimate storage. | **HIGH** | Confine all disposable artifacts to a dedicated container directory (`storage/testing/`), track the exact server PID in a dedicated file, and clean up strictly by validated PID and canonical path. |

---

## 3. Revised Fail-Closed Startup Sequence

The execution workflow is structured so that failure at any checkpoint immediately terminates execution:

```
[Phase 1: Environment & Container Preparation]
       │
       ▼
1. Validate Workspace Paths & Port Availability (Port 8099)
       │
       ▼
2. Provision Isolated SQLite File (storage/testing/browser_qa.sqlite)
       │
       ▼
3. Run Step-by-Step Provisioning with Pre-Step Database Identity Proof
       │
       ▼
[Phase 2: Test Server Process Initialization]
       │
       ▼
4. Launch Background PHP CLI Server (php -S 127.0.0.1:8099 tests/browser_server_router.php)
       │  (Write exact server PID to storage/testing/server.pid)
       ▼
5. Intercept Every Incoming HTTP Request at the Router Level
       │
       ▼
6. [MANDATORY GATEKEEPER] Verify Live PDO Connection & PRAGMA database_list
       │
       ├─► FAIL: Terminate process with HTTP 500 (No application routes execute)
       │
       └─► PASS: Proceed to Phase 3
       │
[Phase 3: Automated Health Check Proof]
       │
       ▼
7. Query GET http://127.0.0.1:8099/_qa_health_check
       │
       ├─► FAIL / Non-200: Kill server process, DO NOT LAUNCH BROWSER
       │
       └─► PASS: Authorized to launch browser_subagent
       │
[Phase 4: Browser QA Execution & Controlled Teardown]
       │
       ▼
8. Execute Planned Scenarios, Capture Recordings & Screenshots
       │
       ▼
9. Stop Server by Exact Tracked PID, Delete storage/testing/ Container
```

---

## 4. Actual Laravel Connection Verification Design

To ensure that Laravel is physically incapable of executing queries against `crms` or any MySQL instance, the gatekeeper inspects the **actual resolved database service in the Laravel service container**:

### Multi-Level Verification Gatekeeper Algorithm

```php
<?php
// Implemented within tests/browser_server_router.php prior to $app->handleRequest()

// Step 1: Programmatically enforce configuration (immune to bootstrap/cache/config.php)
$canonicalSqlitePath = realpath(dirname(__DIR__) . '/storage/testing/browser_qa.sqlite');

if ($canonicalSqlitePath === false || ! file_exists($canonicalSqlitePath)) {
    http_response_code(500);
    exit("CRMS GUARD FAILURE: Approved SQLite database file does not exist on disk.");
}

$app['config']->set('database.default', 'sqlite');
$app['config']->set('database.connections.sqlite', [
    'driver' => 'sqlite',
    'database' => $canonicalSqlitePath,
    'prefix' => '',
    'foreign_key_constraints' => true,
]);

// Step 2: Purge existing connections to prevent stale MySQL singletons
$app['db']->purge();
$app['db']->setDefaultConnection('sqlite');

// Step 3: Resolve the actual live connection from the container
/** @var \Illuminate\Database\ConnectionInterface $connection */
$connection = $app['db']->connection();
$driver = $connection->getDriverName();
$databaseName = $connection->getDatabaseName();

// Step 4: Verification 1 - Driver Level
if ($driver !== 'sqlite') {
    http_response_code(500);
    exit("CRMS GUARD FAILURE: Resolved database driver is '{$driver}', expected 'sqlite'.");
}

// Step 5: Verification 2 - Canonical Path Level
$canonicalResolved = realpath($databaseName);
if ($canonicalResolved === false || $canonicalResolved !== $canonicalSqlitePath) {
    http_response_code(500);
    exit("CRMS GUARD FAILURE: Database path '{$databaseName}' does not match canonical '{$canonicalSqlitePath}'.");
}

// Step 6: Verification 3 - Protected String Blacklist
$blacklist = ['crms', 'admin_crms', 'admin_crm_demo', 'mysql', '3306', '127.0.0.1:3306'];
$normalizedPath = strtolower(basename($canonicalResolved));
foreach ($blacklist as $prohibited) {
    if (str_contains($normalizedPath, $prohibited)) {
        http_response_code(500);
        exit("CRMS GUARD FAILURE: Prohibited database identifier detected: '{$prohibited}'.");
    }
}

// Step 7: Verification 4 - Deep Engine Level (PRAGMA database_list)
$pdo = $connection->getPdo();
$statement = $pdo->query("PRAGMA database_list");
$databases = $statement->fetchAll(\PDO::FETCH_ASSOC);

$mainDatabaseFile = null;
foreach ($databases as $dbRow) {
    if (($dbRow['name'] ?? '') === 'main') {
        $mainDatabaseFile = realpath($dbRow['file'] ?? '');
        break;
    }
}

if ($mainDatabaseFile !== $canonicalSqlitePath) {
    http_response_code(500);
    exit("CRMS GUARD FAILURE: SQLite engine open file handle mismatch.");
}

// Step 8: Gatekeeper PASSED -> Safe to handle incoming request
```

---

## 5. Router and Application Bootstrap Guard

### Execution Order & Architecture
1. **Router Entry:** `tests/browser_server_router.php` acts as the single entry point for PHP's built-in web server.
2. **Environment Pre-Loading:**
   ```php
   putenv('APP_ENV=testing');
   putenv('DB_CONNECTION=sqlite');
   putenv('DB_DATABASE=' . $canonicalSqlitePath);
   $_ENV['APP_ENV'] = 'testing';
   $_ENV['DB_CONNECTION'] = 'sqlite';
   $_ENV['DB_DATABASE'] = $canonicalSqlitePath;
   ```
3. **Application Boot:** `$app = require_once dirname(__DIR__) . '/bootstrap/app.php';`
4. **Configuration & Connection Lock:** Run the Multi-Level Verification Gatekeeper above.
5. **Request Handling:** Call `$app->handleRequest(Request::capture())`.

### Model Connection Inheritance
- An inspection of all models in [app/Models/](file:///D:/xampp-8.2/htdocs/crms/app/Models/) confirms that **no model specifies a custom `$connection` property**.
- Every Eloquent model in HouseFix360 inherits `config('database.default')`.
- Enforcing the container's default connection to `'sqlite'` guarantees that all models (`User`, `Project`, `Expense`, `LabourSalary`, `Wallet`, `Payment`, etc.) strictly query the verified SQLite file.

---

## 6. External Side-Effect Controls

To ensure complete isolation of services and files during browser testing:

### 1. Sessions
- **Isolation:** Override session storage path to `storage/testing/sessions/`.
- **Cookie Separation:** Set cookie name to `crms_qa_session` to avoid invalidating live browser cookies.
- **Single-Session Bypass:** Set `session.single_web_session = false` to prevent [EnsureSingleWebSession.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Middleware/EnsureSingleWebSession.php) from logging out concurrent test windows.

### 2. Cache
- Set `$app['config']->set('cache.default', 'array')` or point file cache to `storage/testing/cache/`.
- Prevents cache collisions with normal application usage.

### 3. Queue & Jobs
- Set `$app['config']->set('queue.default', 'sync')`.
- Dispatched background tasks execute synchronously in-memory, avoiding the persistent `jobs` database table.

### 4. File Uploads & Attachments
- Override the public disk root:
  `$app['config']->set('filesystems.disks.public.root', storage_path('testing/uploads'))`
- Uploaded receipts, expense invoices, and photos remain strictly inside `storage/testing/uploads/` and are purged during teardown.
- `storage/app/public` remains completely untouched.

### 5. Outbound HTTP & Third-Party APIs
- In the test router, register HTTP mocks:
  ```php
  \Illuminate\Support\Facades\Http::fake([
      'maps.googleapis.com/*' => \Illuminate\Support\Facades\Http::response(['status' => 'OK', 'results' => []], 200),
      'roads.googleapis.com/*' => \Illuminate\Support\Facades\Http::response(['snappedPoints' => []], 200),
      '*' => \Illuminate\Support\Facades\Http::response([], 200),
  ]);
  ```
- Prevents live network calls to Google Maps Geocoding API ([ReverseGeocodingService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReverseGeocodingService.php#L32)) and Google Roads API ([EmployeeTrackingController.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/EmployeeTrackingController.php#L1392)).

### 6. Mails & Notifications
- Set `$app['config']->set('mail.default', 'array')`.
- Zero outbound emails, SMS, or webhook dispatches.

---

## 7. Safe Fixture Provisioning Protocol

A standalone provisioning script (`tests/provision_browser_qa_db.php`) will handle database preparation:

### Mandatory Identity Check Before Every Step
Before executing any migration or seeder command, the script runs the 4-level verification gatekeeper:
```
[Pre-Flight Verification]
       │ (Assert: driver=sqlite, file=browser_qa.sqlite)
       ▼
Step 1: Execute Schema Migrations (--database=sqlite --force)
       │ (Re-verify active connection identity)
       ▼
Step 2: Seed System Roles & Permissions
       │ (Re-verify active connection identity)
       ▼
Step 3: Seed Synthetic Users (qa.admin@housefix.test, qa.worker@housefix.test)
       │ (Re-verify active connection identity)
       ▼
Step 4: Seed Synthetic Master Data (Projects, Labours, Categories, Payment Methods)
       │ (Re-verify active connection identity)
       ▼
[Post-Provisioning Audit Report & Checksum Output]
```

If the connection identity verification fails at any stage, the script throws an exception and halts immediately without modifying any tables.

---

## 8. Non-Destructive Teardown Protocol

To ensure cleanup is safe, targeted, and does not delete unrelated user files:

### Exact PID-Based Process Termination
1. When the test server starts, its exact process ID is recorded in `storage/testing/server.pid`.
2. On shutdown, read `server.pid`.
3. Verify via OS process tools (`Get-Process -Id $pid`) that the process name is `php.exe` and its command-line references `tests/browser_server_router.php`.
4. Issue targeted termination: `Stop-Process -Id $pid -Force`.
5. Remove `server.pid`.
6. Never issue broad commands like `taskkill /f /im php.exe`, which would kill the user's running development server on port 8000!

### Contained Directory Purge
1. All temporary test files reside inside a single dedicated root: `storage/testing/`.
2. Clean up strictly deletes files within that folder:
   - `storage/testing/browser_qa.sqlite`
   - `storage/testing/browser_qa.sqlite-journal` (if present)
   - `storage/testing/sessions/*`
   - `storage/testing/uploads/*`
   - `storage/testing/cache/*`
3. Safety Guard: The cleanup function asserts that the target path strictly begins with `realpath(base_path('storage/testing'))`. It is programmatically prohibited from deleting any file outside `storage/testing/`.

---

## 9. Comprehensive Comparison: AUDIT-65 vs AUDIT-66

| Architectural Area | AUDIT-65 Plan | Hardened AUDIT-66 Plan |
| :--- | :--- | :--- |
| **Config Cache Defense** | Relied on checking file absence; would fail if cache existed. | Programmatically overrides `$app['config']` and purges connections on every request; immune to cached config files. |
| **Connection Verification** | Basic string check on `$_ENV['DB_DATABASE']`. | 4-tier verification: Container driver check, `realpath()` canonical check, blacklist scan, and SQLite engine `PRAGMA database_list` PDO query. |
| **External APIs** | Unaddressed; could trigger Google Maps API calls. | Mocked via `Http::fake()` to block all outbound third-party requests. |
| **File Uploads** | Mentioned generally. | Public disk root redirected to `storage/testing/uploads/` with automated purge. |
| **Session Isolation** | Cookie name unchanged; risk of collision with live sessions. | Dedicated cookie `crms_qa_session` and isolated folder `storage/testing/sessions/`. |
| **Teardown Safety** | Risk of broad process termination. | Targeted PID verification via `server.pid`; strictly confined directory purge. |

---

## 10. Actions Requiring Explicit Separate Approval

The following operational actions remain strictly deferred and require explicit separate user authorization:

1. **Approval Action 1:** Creating the test router script `tests/browser_server_router.php`.
2. **Approval Action 2:** Creating the provisioning script `tests/provision_browser_qa_db.php`.
3. **Approval Action 3:** Provisioning the database file `storage/testing/browser_qa.sqlite` and running migrations/seeders.
4. **Approval Action 4:** Launching the background PHP server on `127.0.0.1:8099`.
5. **Approval Action 5:** Launching `browser_subagent` to execute end-to-end browser testing and record sessions.

---

## 11. Formal Certification of Compliance

I hereby certify that during the execution of **AUDIT-66**:
1. **Zero Database Operations:** Persistent databases `crms`, `admin_crms`, and `admin_crm_demo` were never accessed, queried, or modified.
2. **Zero Process Invocations:** No Artisan, Tinker, PHPUnit, test runners, migrations, seeders, or servers were executed.
3. **Zero Code or Config Changes:** No `.env`, application code, schema, test files, or configuration files were created or modified.
4. **Preservation:** The Git working tree and all previous audit deliverables (AUDIT-55 through AUDIT-65) remain completely intact.

# AUDIT-65 — Isolated Browser QA Environment Plan

**Project:** HouseFix360 CRM (`D:\xampp-8.2\htdocs\crms`)  
**Audit Reference:** AUDIT-65  
**Date:** 2026-10-27  
**Status:** Planning Phase Only (Zero Execution / Read-Only Source Analysis)  

---

## 1. Executive Summary & Mandatory Safety Baseline

The purpose of this plan is to design a **100% isolated, non-destructive, disposable browser-testing environment** that allows full end-to-end browser QA (via `browser_subagent`) without any possibility of connecting to, reading from, or writing to the persistent databases:
- `crms` (production / primary local database)
- `admin_crms` (administrative database)
- `admin_crm_demo` (demo database)

### Strict Operational Confirmation
- **Planning Only:** No SQLite database file was created, no server was launched, no browser was opened, and no code/configuration was modified.
- **Zero Database Operations:** Persistent databases were neither accessed nor queried.
- **Zero Process Invocation:** No Artisan, Tinker, PHPUnit, migrations, seeders, or test runners were executed.
- **Preservation:** All code changes and reports from AUDIT-55 through AUDIT-64 remain untouched.

---

## 2. Existing Architecture & Source Code Inspection

A read-only source review of the repository's configuration and testing infrastructure reveals the following constraints:

### 1. `phpunit.xml` & `tests/bootstrap.php`
- [phpunit.xml](file:///D:/xampp-8.2/htdocs/crms/phpunit.xml) specifies:
  ```xml
  <env name="DB_CONNECTION" value="sqlite"/>
  <env name="DB_DATABASE" value=":memory:"/>
  <env name="CRMS_TEST_APPROVED_CONNECTION" value="sqlite"/>
  <env name="CRMS_TEST_APPROVED_DATABASE" value=":memory:"/>
  ```
- [tests/bootstrap.php](file:///D:/xampp-8.2/htdocs/crms/tests/bootstrap.php#L65-L68) enforces a fail-closed guard:
  ```php
  if ($connection === 'sqlite') {
      if ($database !== ':memory:') {
          $fail('only the approved in-memory SQLite target is permitted.');
      }
  ```
- **Key Architectural Limitation:** In-memory SQLite (`:memory:`) is strictly process-bound. In a web browser scenario, every incoming HTTP request to an Apache/PHP-FPM or multi-process server runs in a separate process or thread. An in-memory database cannot share state across sequential browser requests (e.g. logging in on Request 1, then submitting a form on Request 2).

### 2. Persistent `.env` Configuration
- The primary configuration file [.env](file:///D:/xampp-8.2/htdocs/crms/.env#L24-L40) specifies:
  - `DB_CONNECTION=mysql`
  - `DB_DATABASE=crms`
  - `SESSION_DRIVER=file`
  - `QUEUE_CONNECTION=database`
- **Critical Risk:** The existing running development server (`php artisan serve` running on the host) boots this `.env`. Under no circumstances should browser testing navigate to the existing running application.

---

## 3. Isolated Disposable Database Architecture

To enable multi-request browser testing without touching MySQL, an **isolated file-backed SQLite database** must be used:

### File Specification
- **Path:** `storage/testing/browser_qa.sqlite` (within the user workspace, ignored by Git via `storage/`).
- **Lifecycle:** Created fresh during startup, populated with synthetic fixtures, and completely deleted upon teardown.
- **Permissions:** Restricted to local process execution only.

### Synthetic Fixture Schema & Data
The database will contain strictly synthetic test fixtures:
1. **Roles & Permissions:** System permissions (`reports-list`, `expense-reports-list`, `labour-salaries-*`, etc.) assigned to a synthetic `Super Admin` role.
2. **Synthetic Users:**
   - `qa.admin@housefix.test` (Super Admin, synthetic password)
   - `qa.worker@housefix.test` (Worker role, for permission denial checks)
3. **Master Data:**
   - 2 Synthetic Projects (`Project Alpha`, `Project Beta`)
   - 2 Synthetic Labours (`Ramesh Mason`, `Suresh Carpenter`)
   - 2 Synthetic Main Categories (`CIVIL`, `LABOUR SALARY`) & Categories
   - Payment Methods (`Cash`, `Bank Transfer`)
4. **Zero Production Data:** No real customer records, financial histories, or personal data.

---

## 4. Environment Isolation & Configuration Bleed Prevention

How to run a separate application server that is physically incapable of loading the persistent `crms` database:

### The Dedicated Test Server Router (`tests/browser_server_router.php`)
Instead of launching `php artisan serve` (which automatically loads the root `.env`), the isolated server will be launched using PHP's built-in CLI web server with a custom router script that enforces environment variables **before Laravel boots**:

```php
<?php
// tests/browser_server_router.php

// 1. Mandatory Fail-Closed Safety Guard
$approvedDbFile = dirname(__DIR__) . '/storage/testing/browser_qa.sqlite';

putenv('APP_ENV=testing');
putenv('APP_DEBUG=true');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=' . $approvedDbFile);
putenv('CRMS_TEST_APPROVED_CONNECTION=sqlite');
putenv('CRMS_TEST_APPROVED_DATABASE=' . $approvedDbFile);
putenv('SESSION_DRIVER=file');
putenv('SESSION_SINGLE_WEB_SESSION=false');
putenv('CACHE_STORE=array');
putenv('QUEUE_CONNECTION=sync');
putenv('MAIL_MAILER=array');

$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $approvedDbFile;
$_SERVER['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_DATABASE'] = $approvedDbFile;

// 2. Strict Blacklist Check
$targetDb = strtolower($_ENV['DB_DATABASE'] ?? '');
if (str_contains($targetDb, 'crms') || str_contains($targetDb, 'admin_crms') || str_contains($targetDb, 'mysql')) {
    http_response_code(500);
    die("CRMS SAFETY GUARD: Attempted connection to persistent database blocked!");
}

// 3. Health Check Endpoint (For Pre-Browser Verification)
if ($_SERVER['REQUEST_URI'] === '/_qa_health_check') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'isolated',
        'connection' => $_ENV['DB_CONNECTION'],
        'database' => $_ENV['DB_DATABASE'],
        'pid' => getmypid(),
    ]);
    exit;
}

// 4. Static File Passthrough
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$publicPath = dirname(__DIR__) . '/public' . $uri;
if ($uri !== '/' && file_exists($publicPath)) {
    return false; // Serve static file directly
}

// 5. Delegate to Laravel Entry Point
require_once dirname(__DIR__) . '/public/index.php';
```

---

## 5. Risk Identification & Mitigation Matrix

| Hazard / Risk Vector | Severity | Mechanism | Mitigation Protocol |
| :--- | :--- | :--- | :--- |
| **Cached Configuration (`config.php`)** | **CRITICAL** | If `bootstrap/cache/config.php` exists, Laravel bypasses `env()` and hardcodes MySQL `crms`. | **Pre-flight Check:** Abort immediately if `bootstrap/cache/config.php` exists. It must be absent before launching. |
| **Port Conflict with Live Server** | **HIGH** | Default port `8000` is currently running live server. Navigating to `8000` hits production MySQL. | **Dedicated Unused Port:** Bind isolated server strictly to an isolated port, e.g. `127.0.0.1:8099`. |
| **Session Bleed / Invalidation** | **MEDIUM** | [EnsureSingleWebSession.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Middleware/EnsureSingleWebSession.php) invalidates active sessions if concurrent logins occur. | Set `SESSION_SINGLE_WEB_SESSION=false` in the test router. Use a dedicated session directory `storage/framework/sessions_qa/`. |
| **Queue Execution (`database`)** | **MEDIUM** | In `.env`, `QUEUE_CONNECTION=database`. If jobs are dispatched, it expects a `jobs` table. | Force `QUEUE_CONNECTION=sync` in router; jobs execute synchronously in-memory. |
| **File Uploads Pollution** | **LOW** | Receipts/photos uploaded during expense testing could clutter `storage/app/public`. | Route uploads to `storage/testing/attachments/` and purge during teardown. |
| **Mail Dispatch** | **LOW** | Password resets or alerts could attempt real SMTP calls. | Force `MAIL_MAILER=array` in router. |

---

## 6. Safe Startup, Verification & Teardown Protocol

```
+---------------------------------------------------------------------------------------------------+
| ISOLATED BROWSER QA WORKFLOW                                                                      |
+---------------------------------------------------------------------------------------------------+
| 1. PRE-FLIGHT CHECK:                                                                              |
|    - Verify bootstrap/cache/config.php does NOT exist.                                            |
|    - Verify port 8099 is available.                                                               |
|                                                                                                   |
| 2. PROVISION DATABASE:                                                                            |
|    - Touch storage/testing/browser_qa.sqlite.                                                     |
|    - Execute schema provisioning & synthetic seeding script targeted ONLY to browser_qa.sqlite.   |
|                                                                                                   |
| 3. LAUNCH ISOLATED PROCESS:                                                                       |
|    - php -S 127.0.0.1:8099 tests/browser_server_router.php (running as background task)           |
|                                                                                                   |
| 4. MANDATORY HEALTH VERIFICATION (Before opening any browser):                                    |
|    - Query GET http://127.0.0.1:8099/_qa_health_check                                             |
|    - Assert: response.status === 'isolated'                                                       |
|    - Assert: response.connection === 'sqlite'                                                     |
|    - Assert: response.database.endsWith('browser_qa.sqlite')                                      |
|    - IF ANY CHECK FAILS: Kill process immediately, DO NOT OPEN BROWSER.                           |
|                                                                                                   |
| 5. EXECUTE BROWSER QA (Via browser_subagent on http://127.0.0.1:8099):                            |
|    - Run planned test scenarios, capture screenshots & recording.                                 |
|                                                                                                   |
| 6. TEARDOWN:                                                                                      |
|    - Terminate server process on port 8099.                                                       |
|    - Delete storage/testing/browser_qa.sqlite.                                                    |
|    - Verify persistent databases remain completely untouched.                                     |
+---------------------------------------------------------------------------------------------------+
```

---

## 7. End-to-End Browser Test Plan Matrix

Once the isolated environment is approved and running, the `browser_subagent` will execute the following structured test suites against `http://127.0.0.1:8099`:

### Test Suite 1: Authentication & Role-Based Access Control
- **1.1 Unauthenticated Redirection:** Navigate to `/reports` $\rightarrow$ Assert redirect to `/login`.
- **1.2 Invalid Credentials:** Submit invalid email/password $\rightarrow$ Assert inline validation error displayed.
- **1.3 Super Admin Login:** Login with `qa.admin@housefix.test` $\rightarrow$ Assert successful navigation to Dashboard.
- **1.4 Worker Role Authorization Check:** Login with worker credentials $\rightarrow$ Navigate to `/reports` $\rightarrow$ Assert HTTP 403 Forbidden page displayed.

### Test Suite 2: CRUD Workflows & AJAX Validation
- **2.1 Projects List & Creation:** Navigate to Projects $\rightarrow$ Open Create Modal $\rightarrow$ Submit empty form $\rightarrow$ Assert AJAX inline validation errors appear without full page reload $\rightarrow$ Fill valid details $\rightarrow$ Save $\rightarrow$ Assert new project appears in table.
- **2.2 Edit & Delete Workflow:** Edit Project name $\rightarrow$ Save $\rightarrow$ Verify updated name appears $\rightarrow$ Trigger delete $\rightarrow$ Confirm modal $\rightarrow$ Verify removal from table.

### Test Suite 3: Labour Salary & Advance Management
- **3.1 Create Labour Salary:** Navigate to Labour Salaries $\rightarrow$ Select `Ramesh Mason` $\rightarrow$ Enter Period, Salary Amount (Rs 8,000), Paid Amount (Rs 5,000) $\rightarrow$ Submit.
- **3.2 Verify Idempotent Expense Sync:** Verify expense record is created with Rs 5,000 paid, Rs 3,000 unpaid.
- **3.3 Toggle Advance & Zero Payment:** Edit salary to set Paid Amount = Rs 0 $\rightarrow$ Verify status changes to Pending and expense is soft-deleted.
- **3.4 Restore on Repay:** Edit salary to add Rs 4,000 payment $\rightarrow$ Verify expense is restored and updated without duplicate rows.

### Test Suite 4: Site, Office & Total Report Reconciliation
- **4.1 Site Report Tab:** Navigate to `/reports?type=site` $\rightarrow$ Select `Project Alpha` $\rightarrow$ Verify summary cards (Total Expenses, Paid, Unpaid, Net Balance).
- **4.2 Office Report Tab:** Click `Office Report` tab $\rightarrow$ Verify office overheads and general labour salaries appear with Project Name = `-`.
- **4.3 Total Report Tab:** Click `Total Report` tab $\rightarrow$ Verify exact mathematical reconciliation:
  $$\text{Total Paid} = \text{Site Paid} + \text{Office Paid}$$
  $$\text{Total Unpaid} = \text{Site Unpaid} + \text{Office Unpaid}$$
- **4.4 Deduplication Visual Audit:** Confirm that salaries with multiple active/trashed expenses display **exactly 1 row** in the ledger table.

### Test Suite 5: Pagination & Filter Persistence
- **5.1 Filter Execution:** Apply Date Range (`2026-10-01` to `2026-10-31`) $\rightarrow$ Verify table filters smoothly without breaking pagination links.
- **5.2 Page 2 Summary Integrity:** Navigate to Page 2 of records $\rightarrow$ Assert summary cards display full aggregate totals (confirming AUDIT-55 fix: summary cards must NOT show Rs 0.00 on Page 2).

### Test Suite 6: Responsive Layouts & Console/Network Hygiene
- **6.1 Desktop Layout (1280x800):** Inspect navigation sidebar, data tables, summary cards, and modal dialogs.
- **6.2 Mobile Layout (375x667):** Resize viewport $\rightarrow$ Verify hamburger menu toggles correctly, tables scroll horizontally without breaking container layout, and action buttons remain accessible.
- **6.3 Browser Console Inspection:** Assert zero uncaught JavaScript errors or broken asset 404s.

---

## 8. Actions Requiring Explicit Separate Approval

The following concrete operational steps require user authorization before execution:

1. **Approval Item 1:** Authorization to create the temporary routing script `tests/browser_server_router.php` and provisioning script.
2. **Approval Item 2:** Authorization to create `storage/testing/browser_qa.sqlite` and seed it with synthetic test fixtures.
3. **Approval Item 3:** Authorization to launch the background PHP server process on `127.0.0.1:8099`.
4. **Approval Item 4:** Authorization to launch the `browser_subagent` to execute the planned browser test matrix and record session artifacts.

---

## 9. Formal Certification of Compliance

I hereby certify that during the execution of **AUDIT-65**:
1. **Zero Database Connections:** Persistent databases `crms`, `admin_crms`, and `admin_crm_demo` were never accessed, queried, or modified.
2. **Zero Process Invocations:** No Artisan, Tinker, PHPUnit, test runners, migrations, seeders, or servers were executed.
3. **Zero Code or Config Changes:** No `.env`, application code, schema, or configuration files were created or modified.
4. **Preservation:** The Git working tree and all previous audit artifacts (AUDIT-55 through AUDIT-64) remain completely intact.

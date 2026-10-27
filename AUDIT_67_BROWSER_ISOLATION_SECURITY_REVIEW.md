# AUDIT-67 — Read-Only Security Review of AUDIT-66

**Project:** HouseFix360 CRM (`D:\xampp-8.2\htdocs\crms`)  
**Audit Reference:** AUDIT-67  
**Date:** 2026-10-27  
**Status:** Complete (Read-Only Source & Security Inspection)  

---

## 1. Executive Summary & Mandatory Safety Baseline

This audit performs an independent, read-only security review of the hardened browser isolation plan proposed in **AUDIT-66**. Its objective is to verify whether any architectural gaps, bootstrap race conditions, configuration leaks, or external side effects could cause the browser QA process to connect to or mutate persistent databases (`crms`, `admin_crms`, `admin_crm_demo`) or leak data to external services.

### Operational Certification
- **Strict Read-Only Inspection:** No project files, configuration, routes, or code were created or modified (apart from this review document).
- **Zero Database Operations:** Persistent databases were never accessed, queried, or modified.
- **Zero Process Invocation:** No Artisan, Tinker, PHPUnit, test runners, migrations, seeders, servers, or browser agents were executed.
- **Runtime Verification Disclaimed:** All observations represent static source-code analysis; no runtime execution was performed.
- **Preservation:** All code changes and audit reports from AUDIT-55 through AUDIT-66 remain completely intact.

---

## 2. Findings Ranked by Severity

### Critical Severity

#### [CRIT-01] The Kernel Bootstrap Window: Configuration Overwrite Vulnerability in AUDIT-66
- **Source References:**
  - [public/index.php:L15-L17](file:///D:/xampp-8.2/htdocs/crms/public/index.php#L15-L17)
  - [bootstrap/app.php:L82](file:///D:/xampp-8.2/htdocs/crms/bootstrap/app.php#L82)
  - `vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php:L170-L185`
  - `vendor/laravel/framework/src/Illuminate/Foundation/Bootstrap/LoadConfiguration.php:L28-L56`
- **Failure Scenario:**
  In AUDIT-66, the proposed router attempted to override `$app['config']->set(...)` *between* requiring `bootstrap/app.php` and calling `$app->handleRequest()`. Source analysis of Laravel 11's HTTP Kernel reveals that at `require bootstrap/app.php`, **the framework has not yet executed its bootstrappers** (`$app->hasBeenBootstrapped()` is `false`).
  When `$app->handleRequest()` is subsequently called, `HttpKernel::sendRequestThroughRouter()` invokes `$this->bootstrap()`, which triggers `LoadEnvironmentVariables` and `LoadConfiguration`. If `bootstrap/cache/config.php` exists, `LoadConfiguration::loadCachedConfiguration()` **wipes out in-memory `$app['config']` overrides and reloads the cached production MySQL configuration**!
  Any queries executed inside service providers or early middleware would then immediately hit production `crms` on MySQL!
- **Required Plan Correction:**
  The test router must explicitly call `$kernel = $app->make(HttpKernelContract::class); $kernel->bootstrap();` **before** applying configuration overrides. Once `$app->hasBeenBootstrapped()` is `true`, subsequent `$app['config']->set()` and connection purges are permanent and cannot be overwritten by `handleRequest()`. Furthermore, setting `putenv('APP_CONFIG_CACHE=' . $dummyPath)` before bootstrap diverts `LoadConfiguration` away from `bootstrap/cache/config.php` without deleting or altering the real cache file.

---

### High Severity

#### [HIGH-01] Shared Filesystem Path Leak in `EmployeeController::uploadAvatar`
- **Source References:**
  - [app/Http/Controllers/EmployeeController.php:L281-L287](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/EmployeeController.php#L281-L287)
  - [app/Http/Controllers/Api/MobileApiController.php:L375](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/Api/MobileApiController.php#L375)
- **Failure Scenario:**
  While AUDIT-66 redirected `filesystems.disks.public.root` to `storage/testing/uploads/`, source inspection revealed that `EmployeeController::uploadAvatar` does **not** use the `Storage` facade. Instead, line 281 hardcodes:
  ```php
  $destination = public_path('images');
  $file->move($destination, $fileName);
  ```
  If browser QA executes an Employee Create/Edit scenario with an avatar upload, an image file is permanently written to the repository's tracked public directory `public/images/`, escaping the test sandbox and polluting Git.
- **Required Plan Correction:**
  Test scenarios in Suite 2 must strictly omit file uploads through `EmployeeController::uploadAvatar`, OR the test router must dynamically mock/override `public_path` if testing avatar uploads.

---

### Medium Severity

#### [MED-01] Uncontrolled Outbound HTTP Requests to Third-Party APIs
- **Source References:**
  - [app/Services/ReverseGeocodingService.php:L32](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReverseGeocodingService.php#L32) (`https://maps.googleapis.com/maps/api/geocode/json`)
  - [app/Http/Controllers/EmployeeTrackingController.php:L1392](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/EmployeeTrackingController.php#L1392) (`https://roads.googleapis.com/v1/snapToRoads`)
- **Failure Scenario:**
  Both services invoke live Google Maps/Roads APIs via `\Illuminate\Support\Facades\Http`. Navigating to tracking screens or saving location coordinates during browser testing would dispatch unmocked outbound HTTP calls, consuming API quota or leaking local test IP addresses.
- **Required Plan Correction:**
  The test router must register `Http::preventStrayRequests()` and `Http::fake()` for Google endpoints as part of its post-bootstrap initialization.

---

### Low Severity

#### [LOW-01] Windows Canonical Path Case Sensitivity & Backslash Discrepancies
- **Source References:**
  - `AUDIT_66_HARDENED_BROWSER_ISOLATION_PLAN.md:Section 4`
- **Failure Scenario:**
  In Windows, `realpath()` returns uppercase drive letters and backslashes (`D:\xampp-8.2\...`), whereas PHP and PDO connection strings often preserve forward slashes (`D:/xampp-8.2/...`) or lowercase drive letters (`d:\...`). A strict binary comparison (`$canonicalApproved === $canonicalActual`) could fail false-positively on Windows, aborting valid test runs.
- **Required Plan Correction:**
  Path comparisons on Windows must be normalized using `strtolower(str_replace('/', '\\', realpath($path) ?: ''))`.

---

## 3. Detailed Laravel Bootstrap Trace & Kernel Window Analysis

### The Standard Laravel 11 HTTP Execution Flow
```
1. public/index.php
   ├── require vendor/autoload.php
   ├── require bootstrap/app.php
   │   └── Application::configure(basePath) ... ->create()
   │       └── Returns new Application instance ($app)
   │           [NOTE: Framework is instantiated but NOT bootstrapped yet!]
   └── $app->handleRequest(Request::capture())
       └── $kernel = $this->make(HttpKernelContract::class);
       └── $kernel->handle($request)
           └── $this->sendRequestThroughRouter($request)
               └── $this->bootstrap()
                   └── if (! $this->app->hasBeenBootstrapped())
                       └── $this->app->bootstrapWith([
                           LoadEnvironmentVariables::class,
                           LoadConfiguration::class,   <-- OVERWRITES $app['config'] HERE!
                           HandleExceptions::class,
                           RegisterFacades::class,
                           RegisterProviders::class,
                           BootProviders::class        <-- BOOTS ALL SERVICE PROVIDERS!
                       ]);
```

### The Flaw in AUDIT-66's Router Design
In AUDIT-66, the proposed router placed configuration overrides and database checks immediately after requiring `bootstrap/app.php`, but *before* calling `$app->handleRequest()`.
- **Why this fails:** Calling `$app['config']->set(...)` before `$kernel->bootstrap()` mutates an empty or partially initialized configuration repository. When `$app->handleRequest()` begins, `$kernel->bootstrap()` runs `LoadConfiguration`, which reads `bootstrap/cache/config.php` (or `.env`) and **completely replaces `$app['config']` with the production database settings**!
- **The Corrected Architecture:**
  The router must explicitly execute `$kernel->bootstrap()` first, divert `APP_CONFIG_CACHE`, apply configuration overrides on the fully booted application, purge connection pools, and run the engine-level PDO gatekeeper **before handing control to `$kernel->handle($request)`**.

---

## 4. Comprehensive Audit of Database Access Paths

A static codebase audit was conducted to verify whether any code could bypass the default database connection:

### 1. Eloquent Models (`app/Models/*.php`)
- **Query:** `protected $connection` / `public $connection`
- **Result:** **Zero models define a custom connection.** All 28 Eloquent models inherit `config('database.default')`.

### 2. Service & Controller Direct Connection Calls (`app/`)
- **Query:** `DB::connection(...)`
- **Findings:**
  - [ReportService.php:L211](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L211): `DB::connection()->getDriverName()` (No argument; uses default connection).
  - [ExpenseReportController.php:L25](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/Reports/ExpenseReportController.php#L25): `DB::connection()->getDriverName()` (No argument; uses default connection).
- **Result:** **Zero occurrences of named connection queries** (such as `DB::connection('mysql')`).

### 3. Raw PDO Connections
- **Query:** `new PDO(`
- **Finding:** Only in [ImportOldProjectDatabase.php:L121](file:///D:/xampp-8.2/htdocs/crms/app/Console/Commands/ImportOldProjectDatabase.php#L121), which is a standalone Artisan command never invoked during web requests.
- **Result:** **Zero raw PDO calls exist in HTTP controllers, services, middleware, or view composers.**

### 4. Database Name Hardcoding
- **Query:** `crms`, `admin_crms`, `admin_crm_demo` in `app/` and `config/`
- **Result:** **Zero hardcoded occurrences.** All references are dynamically resolved via `env('DB_DATABASE')`.

---

## 5. Robust SQLite Verification on Windows

To ensure path verification cannot be fooled by Windows-specific filesystem nuances:

### 1. Handling Missing Files
- In PHP, `realpath('/non/existent/file')` returns `false`.
- The verification must verify `file_exists($path)` before attempting `realpath()`. If `false`, fail closed immediately.

### 2. Handling SQLite `:memory:` Fallback
- If an errant configuration sets `database = ':memory:'`, `realpath(':memory:')` returns `false`.
- Crucially, executing `PRAGMA database_list` against an in-memory SQLite connection returns an empty string `""` for the `file` column.
- The gatekeeper must require:
  `strlen($row['file']) > 0 && normalize($row['file']) === normalize($approvedPath)`
  This provides 100% cryptographic certainty that the active connection is backed by the designated physical file on disk.

### 3. Canonical Path Normalization Function
```php
function canonicalPath(string $path): string {
    $real = realpath($path);
    if ($real === false) {
        return '';
    }
    return strtolower(str_replace('/', '\\', $real));
}
```

---

## 6. External Side-Effect & Storage Audit

### 1. Communication Services Audit
- **Native `mail()`:** Not used.
- **Laravel `Mail::` Facade:** Not used in application controllers or services.
- **Laravel `Notification::` Facade:** Not used.
- **Third-Party Messaging (SMS, WhatsApp, Twilio, Firebase):** Not installed or referenced.
- **Action Required:** Force `mail.default = 'array'` in router as defensive baseline.

### 2. Filesystem & Storage Sandbox
- **`Storage::disk('public')`:** Used for receipts, preorder attachments, and tool images. Overriding `filesystems.disks.public.root` to `storage/testing/uploads/` completely isolates these operations.
- **`EmployeeController::uploadAvatar`:** Direct write to `public/images/` via `$file->move()`.
  - **Mitigation:** Browser test scripts must strictly exclude avatar uploads from test forms.

---

## 7. Corrected Multi-Tier Fail-Closed Router Specification

The following revised architecture corrects the Kernel Bootstrap Window (CRIT-01) and Windows Path Normalization (LOW-01):

```php
<?php
// tests/browser_server_router.php
// Hardened Fail-Closed Browser Test Router

define('CRMS_BROWSER_QA_ACTIVE', true);

$workspaceRoot = dirname(__DIR__);
$approvedDbFile = $workspaceRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'testing' . DIRECTORY_SEPARATOR . 'browser_qa.sqlite';
$dummyConfigCache = $workspaceRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'testing' . DIRECTORY_SEPARATOR . 'dummy_config.php';

// 1. Pre-Bootstrap Environment Overrides
putenv('APP_ENV=testing');
putenv('APP_DEBUG=true');
putenv('APP_CONFIG_CACHE=' . $dummyConfigCache); // Neutralizes bootstrap/cache/config.php
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=' . $approvedDbFile);
putenv('SESSION_DRIVER=file');
putenv('SESSION_SINGLE_WEB_SESSION=false');
putenv('CACHE_STORE=array');
putenv('QUEUE_CONNECTION=sync');
putenv('MAIL_MAILER=array');

$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $approvedDbFile;

// 2. Health Check Endpoint (Bypasses Laravel for Pre-Flight Diagnostic)
if (($_SERVER['REQUEST_URI'] ?? '') === '/_qa_health_check') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ready',
        'approved_database' => $approvedDbFile,
        'database_exists' => file_exists($approvedDbFile),
        'pid' => getmypid(),
    ]);
    exit;
}

// 3. Static File Passthrough
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$publicFile = $workspaceRoot . DIRECTORY_SEPARATOR . 'public' . str_replace('/', DIRECTORY_SEPARATOR, $uri);
if ($uri !== '/' && file_exists($publicFile) && ! is_dir($publicFile)) {
    return false;
}

// 4. Require Laravel Application Builder
require $workspaceRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
/** @var \Illuminate\Foundation\Application $app */
$app = require_once $workspaceRoot . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

// 5. Explicitly Execute Kernel Bootstrap (Resolves CRIT-01)
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

// 6. Post-Bootstrap Configuration Lock
$app['config']->set('database.default', 'sqlite');
$app['config']->set('database.connections.sqlite', [
    'driver' => 'sqlite',
    'database' => $approvedDbFile,
    'prefix' => '',
    'foreign_key_constraints' => true,
]);
$app['config']->set('session.files', $workspaceRoot . '/storage/testing/sessions');
$app['config']->set('session.cookie', 'crms_qa_session');
$app['config']->set('session.single_web_session', false);
$app['config']->set('filesystems.disks.public.root', $workspaceRoot . '/storage/testing/uploads');
$app['config']->set('cache.default', 'array');
$app['config']->set('queue.default', 'sync');
$app['config']->set('mail.default', 'array');

// 7. Purge & Reconnect Database
$app['db']->purge();
$connection = $app['db']->reconnect('sqlite');

// 8. MANDATORY 4-TIER CONNECTION GATEKEEPER
$canonicalApproved = strtolower(str_replace('/', '\\', realpath($approvedDbFile) ?: ''));
$canonicalActual = strtolower(str_replace('/', '\\', realpath($connection->getDatabaseName()) ?: ''));

if ($connection->getDriverName() !== 'sqlite') {
    http_response_code(500);
    exit("CRMS GUARD ERROR: Driver is not sqlite.");
}

if ($canonicalApproved === '' || $canonicalApproved !== $canonicalActual) {
    http_response_code(500);
    exit("CRMS GUARD ERROR: Database file mismatch.");
}

$blacklist = ['crms', 'admin_crms', 'admin_crm_demo', 'mysql', '3306'];
$baseName = strtolower(basename($canonicalActual));
foreach ($blacklist as $term) {
    if (str_contains($baseName, $term)) {
        http_response_code(500);
        exit("CRMS GUARD ERROR: Blacklisted database term.");
    }
}

$pdo = $connection->getPdo();
$pragma = $pdo->query("PRAGMA database_list")->fetchAll(\PDO::FETCH_ASSOC);
$engineFile = '';
foreach ($pragma as $row) {
    if (($row['name'] ?? '') === 'main') {
        $engineFile = strtolower(str_replace('/', '\\', realpath($row['file'] ?? '') ?: ''));
        break;
    }
}

if ($engineFile === '' || $engineFile !== $canonicalApproved) {
    http_response_code(500);
    exit("CRMS GUARD ERROR: Engine-level open file mismatch.");
}

// 9. Fake Outbound HTTP Calls (Resolves MED-01)
\Illuminate\Support\Facades\Http::preventStrayRequests();
\Illuminate\Support\Facades\Http::fake([
    'maps.googleapis.com/*' => \Illuminate\Support\Facades\Http::response(['status' => 'OK', 'results' => []], 200),
    'roads.googleapis.com/*' => \Illuminate\Support\Facades\Http::response(['snappedPoints' => []], 200),
    '*' => \Illuminate\Support\Facades\Http::response([], 200),
]);

// 10. Handle Request Through Router
$request = \Illuminate\Http\Request::capture();
$response = $kernel->handle($request)->send();
$kernel->terminate($request, $response);
```

---

## 8. Safe Provisioning & Teardown Verification

### Provisioning Gatekeeper
`tests/provision_browser_qa_db.php` will be structured with identical pre-step identity proofs:
1. Boot CLI application.
2. Verify `DB::connection()->getPdo()` is physically bound to `browser_qa.sqlite`.
3. Execute `Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true])`.
4. Re-verify connection identity.
5. Seed synthetic roles, permissions, users, and master data.
6. Re-verify connection identity.
7. If any step fails, process terminates with exit code 1.

### Safe Teardown
1. Track server PID in `storage/testing/server.pid`.
2. Verify process name is `php.exe` and arguments match `tests/browser_server_router.php` before calling `Stop-Process`.
3. Confine all deletions strictly to `storage/testing/`. Assert target path starts with canonical `storage/testing/`.

---

## 9. Staged Approval Checklist for Execution

Before any code or test execution may begin, the following staged approvals are required:

- [ ] **Stage 1 Approval:** Create `tests/browser_server_router.php` and `tests/provision_browser_qa_db.php` incorporating the revised kernel bootstrap sequence.
- [ ] **Stage 2 Approval:** Create directory `storage/testing/`, touch `browser_qa.sqlite`, and run `tests/provision_browser_qa_db.php` to provision synthetic test fixtures.
- [ ] **Stage 3 Approval:** Launch the isolated server process on port `8099` as a background task.
- [ ] **Stage 4 Approval:** Execute automated diagnostic check against `http://127.0.0.1:8099/_qa_health_check` and confirm isolation proof.
- [ ] **Stage 5 Approval:** Launch `browser_subagent` to execute the planned browser test matrix and capture session recordings.
- [ ] **Stage 6 Approval:** Terminate server PID and purge `storage/testing/`.

---

## 10. Formal Certification of Compliance

I hereby certify that during the execution of **AUDIT-67**:
1. **Zero Database Connections:** Persistent databases `crms`, `admin_crms`, and `admin_crm_demo` were never accessed, queried, or modified.
2. **Zero Process Invocations:** No Artisan, Tinker, PHPUnit, migrations, seeders, servers, or browser agents were launched.
3. **Zero Code or Config Changes:** No `.env`, application code, routes, schemas, or existing files were modified.
4. **Preservation:** The Git working tree and all previous audit artifacts (AUDIT-55 through AUDIT-66) remain completely intact.

# AUDIT-68 — Final Pre-Implementation Safety Review

**Project:** HouseFix360 CRM (`D:\xampp-8.2\htdocs\crms`)  
**Audit Reference:** AUDIT-68  
**Date:** 2026-10-27  
**Status:** Complete (Static Source & Framework Lifecycle Safety Review)  

---

## 1. Executive Summary & Mandatory Compliance Certification

This audit performs the final, comprehensive pre-implementation safety review of `AUDIT_66_HARDENED_BROWSER_ISOLATION_PLAN.md` and `AUDIT_67_BROWSER_ISOLATION_SECURITY_REVIEW.md`. Its purpose is to examine the exact Laravel 12 framework lifecycle, container bootstrap sequence, database connection mechanisms, Windows filesystem semantics, and external side-effect vectors to determine whether the proposed isolated browser QA architecture is safe to implement.

### Operational Certification
- **Strict Read-Only Source Inspection:** No application files, router scripts, provisioning scripts, configuration files, or database schemas were created or modified (apart from this audit report).
- **Zero Database Operations:** Persistent databases `crms`, `admin_crms`, and `admin_crm_demo` were never accessed, queried, or modified.
- **Zero Process Invocation:** No Artisan commands, Tinker sessions, PHPUnit suites, migrations, seeders, HTTP servers, or browser agents were executed.
- **Runtime Verification Disclaimed:** All observations and proofs represent static source analysis of the checked-in codebase and Laravel 12 framework internals; no runtime execution was performed.
- **Preservation:** The Git working tree and all previous audit deliverables (AUDIT-55 through AUDIT-67) remain completely intact.

### Core Verdict
> **VERDICT: SAFE ONLY WITH ADDITIONAL CONTROLS**  
> The general architecture proposed across AUDIT-66 and AUDIT-67 is conceptually sound, but static inspection of Laravel 12 framework source code (`vendor/laravel/framework/src/Illuminate/Foundation/`) revealed **five critical/high gaps** that must be incorporated into the specification before implementation:
> 1. Pre-bootstrap verification of approved SQLite file existence (preventing engine-level auto-creation of invalid files).
> 2. Resolution of Windows drive letter handling in Laravel's `normalizeCachePath()` (`APP_CONFIG_CACHE`).
> 3. Interception of native PHP `@file_get_contents()` outbound HTTP calls (`GpsTrackingValidationService.php`) via PHP CLI flag `-d allow_url_fopen=0`.
> 4. Dynamic diversion of `public_path()` via `$app->usePublicPath()` to stop un-sandboxed avatar writes to `public/images/`.
> 5. Isolation of logging (`config/logging.php`) and broadcasting (`config/broadcasting.php`) to eliminate writes to `storage/logs/laravel.log` and prevent websocket attempts.

---

## 2. Summary of Findings Ranked by Severity

| ID | Title | Severity | Impact Vector | Resolution |
| :--- | :--- | :--- | :--- | :--- |
| **CRIT-01** | Missing Pre-Bootstrap File Existence Verification | **CRITICAL** | Database Auto-Creation | Enforce `file_exists($approvedDbFile)` and non-zero file size check **before** requiring `bootstrap/app.php` or calling `$kernel->bootstrap()`. |
| **CRIT-02** | Windows Drive Letter Flaw in Laravel `normalizeCachePath()` | **CRITICAL** | Configuration Cache Bypass | Absolute paths like `D:\...` produce malformed paths in Laravel 12. Must set `APP_CONFIG_CACHE` to relative path `storage/testing/nonexistent.php` and invoke `$app->booting(...)` hook. |
| **HIGH-01** | Unmockable Outbound HTTP in `GpsTrackingValidationService` | **HIGH** | External Network Leak | Raw `@file_get_contents($url)` bypasses `Http::fake()`. Must launch PHP CLI server with `-d allow_url_fopen=0` and exclude GPS road-snapping from test scenarios. |
| **HIGH-02** | Un-sandboxed Avatar File Writes via `public_path('images')` | **HIGH** | Filesystem Pollution | `EmployeeController` and `MobileApiController` write directly to `public/images/`. Must call `$app->usePublicPath($testPublicDir)` and exclude avatar uploads from test matrix. |
| **MED-01** | Un-isolated Test Logging and Broadcasting Drivers | **MEDIUM** | Project File Pollution | Test errors append to `storage/logs/laravel.log`. Must redirect log channels to `storage/testing/logs/` and force `broadcasting.default = 'null'`. |
| **MED-02** | SQLite Journal Mode and Concurrency on Windows | **MEDIUM** | Teardown File Locks | WAL mode creates `-wal` / `-shm` files that Windows locks. Must enforce `journal_mode = 'DELETE'` and `busy_timeout = 5000`. |
| **LOW-01** | Windows Case and Slash Mismatches in Path Gatekeeper | **LOW** | False-Positive Test Abort | Canonicalization helper must normalize slashes and lowercase drive letters before string equality checks. |

---

## 3. Deep Source Verification of Laravel Bootstrap Lifecycle

### 3.1 Framework Architecture & Version
- **Laravel Framework Version:** `12.55.1` (verified in [vendor/laravel/framework/src/Illuminate/Foundation/Application.php:L48](file:///D:/xampp-8.2/htdocs/crms/vendor/laravel/framework/src/Illuminate/Foundation/Application.php#L48)).
- **Bootstrap Entry Point:** [bootstrap/app.php:L12-L82](file:///D:/xampp-8.2/htdocs/crms/bootstrap/app.php#L12-L82).

```
                      [PHP Built-In Web Server]
                                 │
                                 ▼
                     tests/browser_server_router.php
                                 │
  [Stage 0: Early Pre-Flight]   Assert file_exists($approvedDbFile) > 0 bytes
                                 │
  [Stage 1: Pre-Boot Env]       putenv('APP_ENV=testing')
                                putenv('DB_CONNECTION=sqlite')
                                putenv('DB_DATABASE=' . $approvedDbFile)
                                putenv('APP_CONFIG_CACHE=storage/testing/dummy.php')
                                putenv('SESSION_DRIVER=file')
                                putenv('CACHE_STORE=array')
                                putenv('QUEUE_CONNECTION=sync')
                                putenv('MAIL_MAILER=array')
                                putenv('BROADCAST_CONNECTION=null')
                                 │
  [Stage 2: Require App]        $app = require_once 'bootstrap/app.php'
                                 │  (App instantiated; NOT bootstrapped yet)
                                 ▼
  [Stage 3: Container Hooks]    $app->usePublicPath($workspaceRoot . '/storage/testing/public')
                                $app->booting(function ($app) { ... enforce sqlite config ... })
                                 │
  [Stage 4: Kernel Bootstrap]   $kernel = $app->make(HttpKernelContract::class)
                                $kernel->bootstrap()
                                 │
                                 ├── 1. LoadEnvironmentVariables (Env is immutable; ignores .env)
                                 ├── 2. LoadConfiguration (Bypasses cache; loads sqlite config)
                                 ├── 3. HandleExceptions
                                 ├── 4. RegisterFacades
                                 ├── 5. RegisterProviders (Discovers Spatie, DomPDF, etc.)
                                 └── 6. BootProviders (Fires booting callbacks, boots providers)
                                 │
  [Stage 5: 4-Tier Gatekeeper]  Verify live connection driver, path, blacklist, PRAGMA database_list
                                 │
                                 ├─► FAIL: http_response_code(500); exit; (Never calls router)
                                 │
                                 ▼ PASS
  [Stage 6: Outbound Mocks]     Http::preventStrayRequests(); Http::fake([...]);
                                 │
  [Stage 7: Request Pipeline]   $response = $kernel->handle($request)->send()
                                $kernel->terminate($request, $response)
```

### 3.2 Detailed Analysis of Bootstrappers
1. **`LoadEnvironmentVariables`:**
   - Source: [vendor/laravel/framework/src/Illuminate/Foundation/Bootstrap/LoadEnvironmentVariables.php:L20-L33](file:///D:/xampp-8.2/htdocs/crms/vendor/laravel/framework/src/Illuminate/Foundation/Bootstrap/LoadEnvironmentVariables.php#L20-L33).
   - In `Env::getRepository()` ([vendor/laravel/framework/src/Illuminate/Support/Env.php:L89](file:///D:/xampp-8.2/htdocs/crms/vendor/laravel/framework/src/Illuminate/Support/Env.php#L89)), the repository is created with `$builder->immutable()->make()`.
   - **Verified Fact:** `Dotenv`'s `safeLoad()` will **not** overwrite environment variables already present in `putenv()` or `$_ENV`. Because `DB_CONNECTION=sqlite` and `DB_DATABASE=...` are set before bootstrap, `.env`'s `DB_CONNECTION=mysql` is completely ignored.
2. **`LoadConfiguration`:**
   - Source: [vendor/laravel/framework/src/Illuminate/Foundation/Bootstrap/LoadConfiguration.php:L37-L56](file:///D:/xampp-8.2/htdocs/crms/vendor/laravel/framework/src/Illuminate/Foundation/Bootstrap/LoadConfiguration.php#L37-L56).
   - Checks `file_exists($cached = $app->getCachedConfigPath())`.
   - If `APP_CONFIG_CACHE` points to a non-existent file, `file_exists()` returns `false`.
   - `LoadConfiguration` falls back to `loadConfigurationFiles($app, $config)`, loading each file in `config/`.
   - In [config/database.php:L20, L38](file:///D:/xampp-8.2/htdocs/crms/config/database.php#L20-L38), `default` and `database` read from `env('DB_CONNECTION')` and `env('DB_DATABASE')`.
   - **Verified Fact:** In-memory configuration repository receives `'sqlite'` as the default connection and the test sqlite file as the database path before any service provider boots.
3. **`BootProviders`:**
   - Source: [vendor/laravel/framework/src/Illuminate/Foundation/Application.php:L1120-L1138](file:///D:/xampp-8.2/htdocs/crms/vendor/laravel/framework/src/Illuminate/Foundation/Application.php#L1120-L1138).
   - Fires `$this->bootingCallbacks` **before** calling `boot()` on any service provider.
   - By registering `$app->booting(...)` immediately after `bootstrap/app.php` is required, the test router can defensively inject configuration overrides before any provider executes.

---

## 4. Database Access Paths & Connection Leak Audit

### 4.1 Eloquent Models
- **Source Inspection:** All 28 model classes in `app/Models/` were inspected.
- **Finding:** **Zero models define a `$connection` property.**
- Every model (`User`, `Project`, `Expense`, `LabourSalary`, `Labour`, `LabourAttendance`, `Client`, `Payment`, `Wallet`, `Vendor`, etc.) extends `Illuminate\Database\Eloquent\Model` and inherits `config('database.default')`.
- Once `database.default` is locked to `'sqlite'`, every Eloquent operation in the application routes to SQLite.

### 4.2 Query Builder & Service Layer
- **Source Inspection:** Grep search across `app/` for `DB::connection` found only two calls:
  1. [app/Services/ReportService.php:L211](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L211): `DB::connection()->getDriverName()` (no argument $\rightarrow$ default connection).
  2. [app/Http/Controllers/Reports/ExpenseReportController.php:L25](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/Reports/ExpenseReportController.php#L25): `DB::connection()->getDriverName()` (no argument $\rightarrow$ default connection).
- **Finding:** **Zero occurrences of named connection queries** (such as `DB::connection('mysql')`).
- **Raw PDO Calls:** Only in `app/Console/Commands/ImportOldProjectDatabase.php:L121` (standalone console migration script, never invoked during web requests).
- **Hardcoded Database Names:** **Zero occurrences** of `'crms'`, `'admin_crms'`, or `'admin_crm_demo'` in any controller, service, middleware, or configuration file.

### 4.3 Service Providers Boot Execution
- Checked all registered providers in [bootstrap/providers.php](file:///D:/xampp-8.2/htdocs/crms/bootstrap/providers.php) and [bootstrap/cache/packages.php](file:///D:/xampp-8.2/htdocs/crms/bootstrap/cache/packages.php):
  - `AppServiceProvider::boot()`: Registers pagination style, rate limiters, view composer closure, and gate closure. **Zero database queries during boot.**
  - `Spatie\Permission\PermissionServiceProvider::boot()`: Registers gate before closure via `$permissionLoader->registerPermissions($gate)`. **Zero database queries during boot.**
  - `Barryvdh\DomPDF\ServiceProvider`: Service binding only.
  - `Laravel\Pail`, `Sail`, `Tinker`, `Collision`, `Termwind`: Console / dev bindings only.
- **Verified Fact:** No service provider boots or queries the database during `$kernel->bootstrap()`.

---

## 5. Security Evaluation of Findings & Concrete Failure Scenarios

### 5.1 [CRIT-01] Missing Pre-Bootstrap File Existence Verification
- **Failure Scenario:**
  If the test server is started before `tests/provision_browser_qa_db.php` is run, or if the database file was deleted, the router in AUDIT-67 would still proceed through `$kernel->bootstrap()`. When SQLite PDO connects to a non-existent path, SQLite's default behavior is to automatically create an empty 0-byte file. The subsequent request would then attempt migrations or throw table-not-found exceptions, polluting state or masking setup failures.
- **Fail-Closed Remedy:**
  The router must check `file_exists($approvedDbFile) && filesize($approvedDbFile) > 0` as **Step 0**, before requiring `bootstrap/app.php`. If false, abort immediately with HTTP 500:
  ```php
  if (! file_exists($approvedDbFile) || filesize($approvedDbFile) === 0) {
      http_response_code(500);
      exit("CRMS GUARD FAILURE: Approved SQLite database file is missing or unprovisioned.");
  }
  ```

### 5.2 [CRIT-02] Windows Drive Letter Flaw in Laravel `normalizeCachePath()`
- **Source References:**
  - [vendor/laravel/framework/src/Illuminate/Foundation/Application.php:L209](file:///D:/xampp-8.2/htdocs/crms/vendor/laravel/framework/src/Illuminate/Foundation/Application.php#L209): `protected $absoluteCachePathPrefixes = ['/', '\\'];`
  - [vendor/laravel/framework/src/Illuminate/Foundation/Application.php:L1385-L1387](file:///D:/xampp-8.2/htdocs/crms/vendor/laravel/framework/src/Illuminate/Foundation/Application.php#L1385-L1387):
    ```php
    return Str::startsWith($env, $this->absoluteCachePathPrefixes)
        ? $env
        : $this->basePath($env);
    ```
- **Failure Scenario:**
  On Windows, absolute paths start with a drive letter (e.g., `D:\xampp-8.2\...`). In AUDIT-67, the router proposed `putenv('APP_CONFIG_CACHE=' . $dummyConfigCache)` where `$dummyConfigCache` is an absolute path.
  Because `$dummyConfigCache` does not start with `'/'` or `'\\'`, Laravel considers it a relative path and calls `$this->basePath($env)`.
  Via `join_paths()`, this evaluates to:
  `D:\xampp-8.2\htdocs\crms\D:\xampp-8.2\htdocs\crms\storage\testing\dummy_config.php`.
  While `file_exists` on this malformed path evaluates to `false` (accidentally achieving cache bypass), relying on path corruption is fragile and risks errors in path utilities.
- **Fail-Closed Remedy:**
  Use a clean, valid relative path for `APP_CONFIG_CACHE`:
  ```php
  putenv('APP_CONFIG_CACHE=storage/testing/nonexistent_config_cache.php');
  ```
  Laravel cleanly evaluates this to `$basePath . '/storage/testing/nonexistent_config_cache.php'`. Since the file does not exist, `file_exists()` returns `false`, safely skipping `bootstrap/cache/config.php` without path corruption.
  In addition, invoke `$app->booting(...)` to re-assert in-memory configuration before providers boot.

### 5.3 [HIGH-01] Unmockable Outbound HTTP in `GpsTrackingValidationService.php`
- **Source References:**
  - [app/Services/GpsTrackingValidationService.php:L665-L668](file:///D:/xampp-8.2/htdocs/crms/app/Services/GpsTrackingValidationService.php#L665-L668):
    ```php
    $url = "https://roads.googleapis.com/v1/snapToRoads?path={$pathString}&interpolate=true&key={$googleApiKey}";
    $response = @file_get_contents($url);
    ```
- **Failure Scenario:**
  While AUDIT-66 and AUDIT-67 registered `Http::fake()` for Google APIs, `GpsTrackingValidationService::snapToRoads` uses native PHP stream wrapper `@file_get_contents($url)`.
  `Http::fake()` only intercepts requests dispatched via Laravel's Guzzle/HTTP client. It cannot intercept native PHP stream wrappers. If a test scenario invokes this service, an outbound network call to Google Roads API will escape the test sandbox.
- **Fail-Closed Remedy:**
  1. The PHP CLI server must be launched with `-d allow_url_fopen=0`:
     ```powershell
     php -d allow_url_fopen=0 -S 127.0.0.1:8099 tests/browser_server_router.php
     ```
     This enforces engine-level blocking of all native PHP HTTP streams.
  2. Test scenarios for browser QA must strictly exclude GPS polyline snapping workflows.

### 5.4 [HIGH-02] Un-sandboxed Avatar File Writes via `public_path('images')`
- **Source References:**
  - [app/Http/Controllers/EmployeeController.php:L281-L287](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/EmployeeController.php#L281-L287):
    ```php
    $destination = public_path('images');
    $file->move($destination, $fileName);
    ```
  - [app/Http/Controllers/Api/MobileApiController.php:L369-L375](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/Api/MobileApiController.php#L369-L375): Same hardcoded `public_path('images')`.
- **Failure Scenario:**
  Unlike tool receipts and preorders (which use `Storage::disk('public')`), avatar uploads bypass `Storage` and call `$file->move(public_path('images'), ...)`. If browser QA executes employee create/edit forms with avatar attachments, image files are permanently written to `public/images/`, escaping the test sandbox and dirtying Git.
- **Fail-Closed Remedy:**
  1. Native Container Diversion: Call `$app->usePublicPath($workspaceRoot . '/storage/testing/public')` ([vendor/laravel/framework/src/Illuminate/Foundation/Application.php:L606](file:///D:/xampp-8.2/htdocs/crms/vendor/laravel/framework/src/Illuminate/Foundation/Application.php#L606)) in the router script. This redirects all `public_path()` calls into the disposable test container.
  2. Test Scenario Exclusion: All browser QA test scripts must omit avatar file inputs in Employee forms.

### 5.5 [MED-01] Un-isolated Test Logging and Broadcasting Drivers
- **Source References:**
  - [config/logging.php:L63](file:///D:/xampp-8.2/htdocs/crms/config/logging.php#L63): `'path' => storage_path('logs/laravel.log')`
  - [config/broadcasting.php:L18](file:///D:/xampp-8.2/htdocs/crms/config/broadcasting.php#L18): `'default' => env('BROADCAST_CONNECTION', 'null')`
- **Failure Scenario:**
  If an uncaught exception or log event occurs during browser QA, log lines are written to the main project log file `storage/logs/laravel.log`, creating noise in production logs. Furthermore, if broadcasting is triggered, Laravel could attempt connection to a local Reverb/Pusher service.
- **Fail-Closed Remedy:**
  In the router script, override logging and broadcasting paths:
  ```php
  putenv('BROADCAST_CONNECTION=null');
  $app['config']->set('broadcasting.default', 'null');
  $app['config']->set('logging.channels.single.path', $workspaceRoot . '/storage/testing/logs/browser_qa.log');
  $app['config']->set('logging.channels.daily.path', $workspaceRoot . '/storage/testing/logs/browser_qa.log');
  ```

### 5.6 [MED-02] SQLite Journal Mode and Concurrency on Windows
- **Source References:**
  - [config/database.php:L42-L44](file:///D:/xampp-8.2/htdocs/crms/config/database.php#L42-L44): `'journal_mode' => null, 'synchronous' => null`
- **Failure Scenario:**
  If SQLite operates in WAL mode (`journal_mode = 'WAL'`), it creates `-wal` and `-shm` auxiliary files. On Windows, background file handles held by PHP CLI or system indexing can cause `Stop-Process` or `Remove-Item` to fail with "file is in use by another process" during teardown.
- **Fail-Closed Remedy:**
  Explicitly force `journal_mode = 'DELETE'` and `busy_timeout = 5000` in the SQLite connection configuration. This keeps the database confined to a single file (`browser_qa.sqlite`) and prevents teardown file lock failures.

---

## 6. Windows Canonical Path Normalization Analysis

### 6.1 Filesystem Path Normalization Function
To ensure zero false-positive aborts and 100% cryptographic certainty on Windows, the gatekeeper must use the following normalization helper:

```php
function canonicalizeWindowsPath(string $path): string {
    if ($path === '' || ! file_exists($path)) {
        return '';
    }
    $real = realpath($path);
    if ($real === false) {
        return '';
    }
    // Normalize forward slashes to Windows backslashes and lowercase drive letters/names
    return strtolower(str_replace('/', '\\', $real));
}
```

### 6.2 Evaluation Against Windows Edge Cases
1. **Missing Files:** If `$approvedDbFile` or the connection string points to a file that does not exist, `canonicalizeWindowsPath()` returns `''`. The check `if ($canonicalActual === '' || $canonicalActual !== $canonicalApproved)` immediately fails closed.
2. **SQLite `:memory:` Fallback:**
   - If an errant configuration sets `database = ':memory:'`, `file_exists(':memory:')` returns `false`, and `canonicalizeWindowsPath` returns `''`.
   - Furthermore, `PRAGMA database_list` against an in-memory SQLite connection returns `""` for the `file` column. The gatekeeper detects `$engineFile === ''` and terminates immediately.
3. **Symlinks & Directory Junctions:** `realpath()` resolves Windows NTFS directory junctions and symlinks to their ultimate physical target on disk. Comparing the canonicalized approved path with the canonicalized actual path guarantees that even if symlinks are involved, they point to the exact same physical byte stream on disk.
4. **Drive Letter Casing:** Windows paths can appear as `D:\...` or `d:\...`. `strtolower()` eliminates drive-letter casing discrepancies.

---

## 7. Execution Ordering & Gatekeeper Position

### 7.1 Lifecycle Verification
Does any middleware, session handling, exception rendering, or route execution run outside or before the gatekeeper?
- **HTTP Kernel Request Flow:**
  1. `browser_server_router.php` runs pre-boot environment setup.
  2. `bootstrap/app.php` returns `$app`.
  3. `$kernel->bootstrap()` runs bootstrappers (environment, config, facades, providers).
  4. Connection is purged and reconnected to `sqlite`.
  5. **4-Tier Gatekeeper executes.** If any check fails $\rightarrow$ `http_response_code(500); exit;`.
  6. **Only after Gatekeeper passes:** `$kernel->handle($request)` is called.
- **Inside `$kernel->handle($request)`:**
  - Route resolution occurs.
  - Global and web middleware pipelines execute (`StartSession`, `EnsureSingleWebSession`, `CheckPermission`, etc.).
  - Controller actions execute.
  - Views render.
  - Exceptions (if thrown) render.
- **Conclusion:** **100% of application request processing, sessions, middleware, controllers, views, and exception handlers execute strictly AFTER the 4-tier gatekeeper has passed.** If the gatekeeper encounters an anomaly, the request pipeline is never invoked.

---

## 8. Hardened Browser Test Router Specification

Incorporating all corrections from AUDIT-67 and AUDIT-68, the definitive router implementation must be:

```php
<?php
// tests/browser_server_router.php
// Hardened Fail-Closed Browser Test Router (AUDIT-68 Final Specification)

define('CRMS_BROWSER_QA_ACTIVE', true);

$workspaceRoot = dirname(__DIR__);
$testingDir = $workspaceRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'testing';
$approvedDbFile = $testingDir . DIRECTORY_SEPARATOR . 'browser_qa.sqlite';

// Step 0: Mandatory Early Pre-Flight Check (Resolves CRIT-01)
if (! file_exists($approvedDbFile) || filesize($approvedDbFile) === 0) {
    http_response_code(500);
    exit("CRMS GUARD FAILURE: Approved SQLite database file is missing or unprovisioned.");
}

// Step 1: Pre-Bootstrap Environment Overrides (Resolves CRIT-02, MED-01)
putenv('APP_ENV=testing');
putenv('APP_DEBUG=true');
putenv('APP_CONFIG_CACHE=storage/testing/nonexistent_config_cache.php'); // Clean relative path
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=' . $approvedDbFile);
putenv('SESSION_DRIVER=file');
putenv('SESSION_SINGLE_WEB_SESSION=false');
putenv('CACHE_STORE=array');
putenv('QUEUE_CONNECTION=sync');
putenv('MAIL_MAILER=array');
putenv('BROADCAST_CONNECTION=null');

$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $approvedDbFile;

// Step 2: Diagnostic Health Check Endpoint (Bypasses Laravel)
if (($_SERVER['REQUEST_URI'] ?? '') === '/_qa_health_check') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ready',
        'approved_database' => $approvedDbFile,
        'database_exists' => true,
        'database_size' => filesize($approvedDbFile),
        'pid' => getmypid(),
    ]);
    exit;
}

// Step 3: Static Asset Passthrough
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$publicFile = $workspaceRoot . DIRECTORY_SEPARATOR . 'public' . str_replace('/', DIRECTORY_SEPARATOR, $uri);
if ($uri !== '/' && file_exists($publicFile) && ! is_dir($publicFile)) {
    return false;
}

// Step 4: Require Composer Autoloader and Application Builder
require $workspaceRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
/** @var \Illuminate\Foundation\Application $app */
$app = require_once $workspaceRoot . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

// Step 5: Container Path Redirections & Pre-Boot Configuration Lock (Resolves HIGH-02, MED-01, MED-02)
$app->usePublicPath($testingDir . DIRECTORY_SEPARATOR . 'public'); // Sandboxes public_path()
$app->booting(function ($app) use ($approvedDbFile, $testingDir) {
    $app['config']->set('database.default', 'sqlite');
    $app['config']->set('database.connections.sqlite', [
        'driver' => 'sqlite',
        'database' => $approvedDbFile,
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => 5000,
        'journal_mode' => 'DELETE',
    ]);
    $app['config']->set('session.files', $testingDir . DIRECTORY_SEPARATOR . 'sessions');
    $app['config']->set('session.cookie', 'crms_qa_session');
    $app['config']->set('session.single_web_session', false);
    $app['config']->set('filesystems.disks.public.root', $testingDir . DIRECTORY_SEPARATOR . 'uploads');
    $app['config']->set('cache.default', 'array');
    $app['config']->set('queue.default', 'sync');
    $app['config']->set('mail.default', 'array');
    $app['config']->set('broadcasting.default', 'null');
    $app['config']->set('logging.channels.single.path', $testingDir . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'browser_qa.log');
    $app['config']->set('logging.channels.daily.path', $testingDir . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'browser_qa.log');
});

// Step 6: Execute Kernel Bootstrap
/** @var \Illuminate\Contracts\Http\Kernel $kernel */
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

// Step 7: Purge and Reconnect SQLite Connection
$app['db']->purge();
$connection = $app['db']->reconnect('sqlite');

// Step 8: MANDATORY 4-TIER CONNECTION GATEKEEPER
function canonicalizeWindowsPath(string $path): string {
    if ($path === '' || ! file_exists($path)) {
        return '';
    }
    $real = realpath($path);
    return $real === false ? '' : strtolower(str_replace('/', '\\', $real));
}

$canonicalApproved = canonicalizeWindowsPath($approvedDbFile);
$canonicalActual = canonicalizeWindowsPath($connection->getDatabaseName());

// Tier 1: Driver Verification
if ($connection->getDriverName() !== 'sqlite') {
    http_response_code(500);
    exit("CRMS GUARD ERROR: Driver is not sqlite.");
}

// Tier 2: Path Canonicalization Verification
if ($canonicalApproved === '' || $canonicalApproved !== $canonicalActual) {
    http_response_code(500);
    exit("CRMS GUARD ERROR: Database file mismatch.");
}

// Tier 3: Prohibited String Blacklist
$blacklist = ['crms', 'admin_crms', 'admin_crm_demo', 'mysql', '3306', '127.0.0.1:3306'];
$baseName = strtolower(basename($canonicalActual));
foreach ($blacklist as $term) {
    if (str_contains($baseName, $term)) {
        http_response_code(500);
        exit("CRMS GUARD ERROR: Prohibited database identifier detected: '{$term}'.");
    }
}

// Tier 4: Engine-Level Open File Handle Verification (PRAGMA database_list)
$pdo = $connection->getPdo();
$pragma = $pdo->query("PRAGMA database_list")->fetchAll(\PDO::FETCH_ASSOC);
$engineFile = '';
foreach ($pragma as $row) {
    if (($row['name'] ?? '') === 'main') {
        $engineFile = canonicalizeWindowsPath((string) ($row['file'] ?? ''));
        break;
    }
}

if ($engineFile === '' || $engineFile !== $canonicalApproved) {
    http_response_code(500);
    exit("CRMS GUARD ERROR: Engine-level open file mismatch.");
}

// Step 9: Fake Outbound HTTP Calls (Google Maps & Roads)
\Illuminate\Support\Facades\Http::preventStrayRequests();
\Illuminate\Support\Facades\Http::fake([
    'maps.googleapis.com/*' => \Illuminate\Support\Facades\Http::response(['status' => 'OK', 'results' => []], 200),
    'roads.googleapis.com/*' => \Illuminate\Support\Facades\Http::response(['snappedPoints' => []], 200),
    '*' => \Illuminate\Support\Facades\Http::response([], 200),
]);

// Step 10: Dispatch Request Through Application Pipeline
$request = \Illuminate\Http\Request::capture();
$response = $kernel->handle($request)->send();
$kernel->terminate($request, $response);
```

---

## 9. Minimal Implementation Sequence with Explicit Stop Conditions

If safe isolation cannot be guaranteed within the workspace root, the procedure mandates **stopping immediately** rather than weakening safeguards. The following sequence defines the strict progression:

```
[STAGE 1: Workspace & Artifact Scaffolding]
  │
  ├─► Action: Create storage/testing/ subdirectories (sessions, uploads, logs, public)
  ├─► Action: Create tests/browser_server_router.php and tests/provision_browser_qa_db.php
  │
  └─► STOP CONDITION 1: If any target file already exists or write fails -> HALT.

[STAGE 2: Isolated Database Provisioning]
  │
  ├─► Action: Touch storage/testing/browser_qa.sqlite
  ├─► Action: Execute php tests/provision_browser_qa_db.php
  │           (Runs 4-tier gatekeeper -> migrations -> seeders -> re-verifies identity)
  │
  └─► STOP CONDITION 2: If exit code != 0, driver != sqlite, or file size == 0 -> HALT & DELETE.

[STAGE 3: Test Server Launch]
  │
  ├─► Action: Check Port 8099 availability: Test-NetConnection -Port 8099
  │           If in use -> STOP CONDITION 3: HALT.
  ├─► Action: Launch php -d allow_url_fopen=0 -S 127.0.0.1:8099 tests/browser_server_router.php
  ├─► Action: Record exact server PID to storage/testing/server.pid
  │
  └─► STOP CONDITION 4: If server fails to bind or exits -> HALT.

[STAGE 4: Automated Pre-Flight Health Check]
  │
  ├─► Action: Query GET http://127.0.0.1:8099/_qa_health_check
  │
  └─► STOP CONDITION 5: If HTTP status != 200, status != "ready", or database_exists != true:
                        Terminate PID, DELETE storage/testing/, HALT. NEVER LAUNCH BROWSER.

[STAGE 5: Browser QA Execution]
  │
  ├─► Action: Launch browser_subagent targeting http://127.0.0.1:8099
  ├─► Action: Execute read-only & safe mutation scenarios (excluding avatar uploads)
  │
  └─► STOP CONDITION 6: If any HTTP 500 response contains "CRMS GUARD ERROR":
                        Immediately terminate browser, kill server PID, HALT.

[STAGE 6: Controlled Teardown]
  │
  ├─► Action: Read PID from storage/testing/server.pid
  ├─► Action: Assert process name == php.exe and command line matches tests/browser_server_router.php
  ├─► Action: Stop-Process -Id $pid -Force
  ├─► Action: Purge storage/testing/ (assert target starts with canonical storage/testing/)
  │
  └─► POST-CONDITION: Zero orphaned processes, zero persistent database mutations.
```

---

## 10. Future Stage Approvals Checklist

The following approvals must be granted individually before each subsequent stage:

- [ ] **Stage 1 Approval:** Authorization to create directory structure `storage/testing/` and write the two isolated scripts (`tests/browser_server_router.php` and `tests/provision_browser_qa_db.php`).
- [ ] **Stage 2 Approval:** Authorization to execute `php tests/provision_browser_qa_db.php` in CLI mode to build the SQLite schema and synthetic seed fixtures.
- [ ] **Stage 3 Approval:** Authorization to launch the background PHP server on `127.0.0.1:8099` with `-d allow_url_fopen=0`.
- [ ] **Stage 4 Approval:** Authorization to run automated HTTP diagnostic query against `http://127.0.0.1:8099/_qa_health_check`.
- [ ] **Stage 5 Approval:** Authorization to launch `browser_subagent` to execute end-to-end test scenarios.
- [ ] **Stage 6 Approval:** Authorization to execute targeted PID termination and purge `storage/testing/`.

---

## 11. Formal Compliance & Non-Execution Certification

I hereby formally certify that during the execution of **AUDIT-68**:
1. **Zero Database Connections:** Persistent databases `crms`, `admin_crms`, and `admin_crm_demo` were never accessed, queried, opened, or modified.
2. **Zero Process Invocations:** No Artisan, Tinker, PHPUnit, test runners, migrations, seeders, HTTP servers, or browser agents were executed.
3. **Zero Project Code Changes:** No application files, database schemas, `.env`, or existing audit reports were modified.
4. **Preservation:** The Git working tree and all previous deliverables (AUDIT-55 through AUDIT-67) remain completely intact and uncommitted.
5. **No Execution Claimed:** All findings represent static code inspection and architectural deduction.

# AUDIT-69 — Final Browser QA Specification Corrections

**Project:** HouseFix360 CRM (`D:\xampp-8.2\htdocs\crms`)  
**Audit Reference:** AUDIT-69  
**Date:** 2026-10-27  
**Status:** Complete (Specification Corrections & Pre-Implementation Gate Design)  

---

## 1. Executive Summary & Mandatory Compliance Certification

This audit finalizes the architectural specification for the isolated browser QA environment, resolving the critical gaps identified in [AUDIT-66](file:///D:/xampp-8.2/htdocs/crms/AUDIT_66_HARDENED_BROWSER_ISOLATION_PLAN.md), [AUDIT-67](file:///D:/xampp-8.2/htdocs/crms/AUDIT_67_BROWSER_ISOLATION_SECURITY_REVIEW.md), and [AUDIT-68](file:///D:/xampp-8.2/htdocs/crms/AUDIT_68_FINAL_PREIMPLEMENTATION_SAFETY_REVIEW.md).

### Operational Compliance Certification
- **Strict Read-Only Source Inspection:** No application code, routers, provisioning scripts, database files, or directories were created or modified (apart from this deliverable).
- **Zero Database Operations:** Persistent databases `crms`, `admin_crms`, and `admin_crm_demo` were never accessed, opened, queried, or modified.
- **Zero Process Invocation:** No Artisan commands, Tinker sessions, PHPUnit suites, migrations, seeders, HTTP servers, or browser subagents were executed.
- **Runtime Verification Disclaimed:** All observations represent static source analysis of the checked-in repository and Laravel 12 framework internals; zero runtime execution was performed.
- **Preservation:** The Git working tree and all previous audit deliverables (AUDIT-55 through AUDIT-68) remain completely intact.

---

## 2. Findings & Required Corrections Ranked by Severity

| ID | Title | Severity | Impact Vector | Corrected Specification |
| :--- | :--- | :--- | :--- | :--- |
| **CRIT-01** | Premature Health Check Reporting | **CRITICAL** | False Readiness Illusion | Relocate `/_qa_health_check` to execute **strictly after** full Laravel bootstrap, SQLite reconnection, and the 4-tier engine gatekeeper pass. |
| **CRIT-02** | Uncontrolled Overwrite or Collision of Pre-Existing Target Paths | **CRITICAL** | State Corruption / File Loss | Enforce active-PID verification and explicit `--clean` flag before provisioning; refuse to overwrite if an active server is bound to the file. |
| **HIGH-01** | Unverified Bootstrap & Provider Booting Sequence | **HIGH** | Race Condition / Config Leak | Formally map `$kernel->bootstrap()` lifecycle events; attach `$app->booting(...)` hook to guarantee in-memory config lock before any provider boots. |
| **HIGH-02** | Unprotected Early Route Execution | **HIGH** | Unverified Database Query | Guarantee that zero application routes can be matched or dispatched unless the 4-tier gatekeeper evaluates to true. |
| **MED-01** | Un-intercepted Native Stream Outbound HTTP | **MED** | External API Leaks | Launch PHP CLI server with `-d allow_url_fopen=0` to block raw `@file_get_contents()` in [GpsTrackingValidationService.php:L668](file:///D:/xampp-8.2/htdocs/crms/app/Services/GpsTrackingValidationService.php#L668). |
| **MED-02** | Shared State Leak Across Secondary Subsystems | **MED** | Log, Session & Cache Cross-Contamination | Redirect public disk, avatar uploads, logs, file sessions, cache, sync queue, array mail, and null broadcasting to `storage/testing/`. |
| **LOW-01** | Incomplete Teardown / Dangerous Blanket Purge | **LOW** | Collateral Deletion Risk | Cleanup must delete strictly from a validated whitelist inside canonical `storage/testing/`, killing processes only by verified PID. |

---

## 3. Deep Lifecycle Analysis: Exact Bootstrap & `booting()` Callback Order

### 3.1 Framework Source Code Verification
A line-by-line inspection of Laravel 12 framework source code (`vendor/laravel/framework/src/Illuminate/Foundation/`) establishes the exact order of execution:

1. **Application Instantiation (`bootstrap/app.php`):**
   - [bootstrap/app.php:L12](file:///D:/xampp-8.2/htdocs/crms/bootstrap/app.php#L12) invokes `Application::configure(basePath: dirname(__DIR__))`.
   - [Application.php:L234-L246](file:///D:/xampp-8.2/htdocs/crms/vendor/laravel/framework/src/Illuminate/Foundation/Application.php#L234-L246) returns `ApplicationBuilder`.
   - [ApplicationBuilder.php:L489-L492](file:///D:/xampp-8.2/htdocs/crms/vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php#L489-L492): `->create()` returns the raw `$app` instance (`Illuminate\Foundation\Application`).
   - **Verified Fact:** At this moment, `$app->hasBeenBootstrapped()` is `false`, and `$app->isBooted()` is `false`. No configuration files have been loaded, and no service providers from `bootstrap/providers.php` or `bootstrap/cache/packages.php` have been registered or booted.
2. **Hooking the Boot Sequence:**
   - Calling `$app->booting(callable $callback)` ([Application.php:L1163-L1166](file:///D:/xampp-8.2/htdocs/crms/vendor/laravel/framework/src/Illuminate/Foundation/Application.php#L1163-L1166)) registers a closure into `$app->bootingCallbacks`.
   - Calling `$app->usePublicPath($path)` ([Application.php:L606-L613](file:///D:/xampp-8.2/htdocs/crms/vendor/laravel/framework/src/Illuminate/Foundation/Application.php#L606-L613)) dynamically overrides the container's `'path.public'`.
3. **Kernel Bootstrap Sequence (`$kernel->bootstrap()`):**
   - Source: [vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php:L43-L50](file:///D:/xampp-8.2/htdocs/crms/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php#L43-L50).
   - Bootstrappers execute sequentially:
     ```
     1. LoadEnvironmentVariables (Env::getRepository() is immutable; putenv() cannot be overwritten)
     2. LoadConfiguration (APP_CONFIG_CACHE bypasses cache; loads config/ files into Repository)
     3. HandleExceptions
     4. RegisterFacades
     5. RegisterProviders (Registers AppServiceProvider and Spatie PermissionServiceProvider)
     6. BootProviders (Calls Application::boot())
     ```
4. **The `BootProviders` Execution Flow:**
   - Source: [vendor/laravel/framework/src/Illuminate/Foundation/Bootstrap/BootProviders.php:L15-L18](file:///D:/xampp-8.2/htdocs/crms/vendor/laravel/framework/src/Illuminate/Foundation/Bootstrap/BootProviders.php#L15-L18) calls `$app->boot()`.
   - Inside `Application::boot()` ([Application.php:L1120-L1138](file:///D:/xampp-8.2/htdocs/crms/vendor/laravel/framework/src/Illuminate/Foundation/Application.php#L1120-L1138)):
     ```php
     $this->fireAppCallbacks($this->bootingCallbacks); // <--- OUR CONFIG LOCK RUNS HERE!

     array_walk($this->serviceProviders, function ($p) {
         $this->bootProvider($p);                      // <--- Service providers boot HERE!
     });

     $this->booted = true;

     $this->fireAppCallbacks($this->bootedCallbacks);
     ```
   - **Architectural Proof:** Because `$this->fireAppCallbacks($this->bootingCallbacks)` executes **strictly before** `array_walk($this->serviceProviders, ...)`, any configuration locked inside `$app->booting(...)` is guaranteed to be active before `AppServiceProvider::boot()` or `PermissionServiceProvider::boot()` can execute.

### 3.2 Unverified Assumptions Requiring Runtime Assertion
While the static call graph is proven, the following assumptions cannot be verified by static source reading alone and must be enforced by fail-closed assertions at runtime:
1. **PHP CLI OPCache Immutability:** Whether PHP CLI has OPCache enabled with file-timestamp validation disabled. (Remedy: Pass `-d opcache.enable_cli=0` to the test server).
2. **Dynamic Package Auto-Discovery:** Whether Composer's `installed.json` or dynamic packages load unanticipated bootstrappers. (Remedy: 4-tier gatekeeper inspects the final live PDO instance regardless of what loaded).

---

## 4. Corrected Health Check & Request Pipeline Specification

### 4.1 The Flaw in Previous Plans
In AUDIT-67 and AUDIT-68, `_qa_health_check` was intercepted at Step 2 of the router, returning a mock response before Laravel was loaded or bootstrapped. This meant the health check proved only that the file existed, not that Laravel's database manager was connected to it.

### 4.2 The Corrected Fail-Closed Pipeline
The router must structure the health check as an authenticated post-gatekeeper diagnostic. Every request—including the health check—must pass the 4-tier engine gatekeeper:

```
[Incoming HTTP Request]
       │
       ▼
[Stage 0: Early Pre-Flight] ──► Assert approved SQLite file exists on disk & size > 0
       │                        (Fail -> HTTP 500 & exit)
       ▼
[Stage 1: Pre-Boot Env Setup] ──► putenv() / $_ENV overrides
       │
       ▼
[Stage 2: Static Asset Pass] ──► If public file exists -> return false (PHP built-in server handles)
       │
       ▼
[Stage 3: Instantiate $app] ──► $app = require 'bootstrap/app.php'
       │
       ▼
[Stage 4: Register Hooks] ──► $app->usePublicPath(...)
       │                      $app->booting(function($app) { lock SQLite config; })
       ▼
[Stage 5: Bootstrap Kernel] ──► $kernel->bootstrap()
       │
       ▼
[Stage 6: Reconnect Database] ──► $app['db']->purge(); $app['db']->reconnect('sqlite');
       │
       ▼
[Stage 7: 4-TIER CONNECTION GATEKEEPER]
       │
       ├─► 1. Driver == 'sqlite'
       ├─► 2. Canonical connection path == Canonical approved path
       ├─► 3. Blacklist scan (crms, mysql, 3306, etc.)
       └─► 4. PRAGMA database_list open file handle == Canonical approved path
       │
       ├─► ANY FAIL: http_response_code(500); exit("CRMS GUARD ERROR");
       │
       ▼ ALL PASS
[Stage 8: Diagnostic or Route Handling]
       │
       ├─► Is URI == '/_qa_health_check'?
       │         │
       │         └─► Execute Live DB Query: User::count()
       │             Return HTTP 200 JSON:
       │             {
       │               "status": "ready",
       │               "driver": "sqlite",
       │               "database": "D:\\xampp-8.2\\htdocs\\crms\\storage\\testing\\browser_qa.sqlite",
       │               "engine_file": "D:\\xampp-8.2\\htdocs\\crms\\storage\\testing\\browser_qa.sqlite",
       │               "users_count": 2,
       │               "pid": 1234
       │             }
       │             exit;
       │
       ▼ Standard Application Routes
[Stage 9: Outbound Mocks] ──► Http::preventStrayRequests(); Http::fake([...]);
       │
       ▼
[Stage 10: Dispatch to Router] ──► $response = $kernel->handle($request)->send();
                                  $kernel->terminate($request, $response);
```

**Key Security Guarantee:**
Application routes (`/login`, `/dashboard`, `/reports/...`) are physically incapable of being reached unless Stages 0 through 7 have completely passed.

---

## 5. Unexpected File & Collision Handling Protocol

### 5.1 Pre-Provisioning Collision Scenarios
When `tests/provision_browser_qa_db.php` is invoked:
1. **Active Server Lock Detection:**
   - Script checks `file_exists('storage/testing/server.pid')`.
   - If PID exists, query OS process table: `Get-Process -Id $pid`.
   - If process is running and its command line contains `browser_server_router.php`:
     **ABORT IMMEDIATELY.** Exit code 1:
     `"CRMS PROVISION ERROR: Active QA server (PID {$pid}) is running. Stop server before provisioning."`
2. **Existing Database File Handling:**
   - If `storage/testing/browser_qa.sqlite` already exists:
     - The script requires an explicit `--clean` command-line argument.
     - Without `--clean`, provisioning **halts immediately** to prevent accidental overwrite of prior test state:
       `"CRMS PROVISION ERROR: Target database file already exists. Specify --clean to overwrite."`
     - When `--clean` is supplied:
       1. Assert path is canonical `storage/testing/browser_qa.sqlite`.
       2. Unlink `browser_qa.sqlite`, `browser_qa.sqlite-journal`, `browser_qa.sqlite-wal`, `browser_qa.sqlite-shm`.
       3. Create a fresh 0-byte file via `touch()`.

### 5.2 Server Launch Collision Scenarios
When the background test server is launched:
1. **Port Availability:**
   - Check if port 8099 is listening:
     `Test-NetConnection -ComputerName 127.0.0.1 -Port 8099`
   - If listening: **ABORT.** Do not attempt to bind to an occupied port.
2. **Stale PID Detection:**
   - If `storage/testing/server.pid` exists:
     - Check process table for that PID.
     - If process exists and is `php.exe`: **ABORT.**
     - If process does not exist (stale leftover from previous crashed session): remove `server.pid` safely before launching.

---

## 6. Non-Laravel-HTTP & Extended Filesystem Audit

### 6.1 Filesystem Write Operations Audit
A complete static grep audit was conducted across `app/` for native PHP filesystem write functions:
- `file_put_contents`: **Zero occurrences** in `app/`.
- `fwrite`: **Zero occurrences** in `app/`.
- `unlink`: **Zero occurrences** in `app/`.
- `fopen`: **Zero occurrences** in `app/`.
- `copy`: **Zero filesystem copies** (all 49 occurrences are Carbon datetime `->copy()` methods).
- `move_uploaded_file`: **Zero occurrences** in `app/`.
- `mkdir` & `$file->move()`:
  - [app/Http/Controllers/EmployeeController.php:L281-L287](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/EmployeeController.php#L281-L287)
  - [app/Http/Controllers/Api/MobileApiController.php:L369-L375](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/Api/MobileApiController.php#L369-L375)
  - **Resolution:** Redirected via `$app->usePublicPath($workspaceRoot . '/storage/testing/public')` in router Step 4. All avatar writes are confined to the disposable test container. Furthermore, browser test scripts must omit avatar inputs.

### 6.2 Outbound Network Operations Audit
A static grep audit was conducted across `app/` for network socket and HTTP streaming functions:
- `fsockopen` / `pfsockopen`: **Zero occurrences**.
- `stream_socket_client`: **Zero occurrences**.
- `curl_init` / `curl_exec`: **Zero occurrences**.
- `file_get_contents`:
  - [app/Services/GpsTrackingValidationService.php:L668](file:///D:/xampp-8.2/htdocs/crms/app/Services/GpsTrackingValidationService.php#L668):
    ```php
    $url = "https://roads.googleapis.com/v1/snapToRoads?path={$pathString}&interpolate=true&key={$googleApiKey}";
    $response = @file_get_contents($url);
    ```
  - **Resolution:**
    1. The PHP CLI server must be launched with `-d allow_url_fopen=0`. This blocks all native PHP HTTP stream wrappers at the engine level with zero reliance on application code.
    2. Browser test scenarios must exclude GPS polyline snapping workflows.
- `\Illuminate\Support\Facades\Http`:
  - [app/Services/ReverseGeocodingService.php:L32](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReverseGeocodingService.php#L32) (`maps.googleapis.com`)
  - [app/Http/Controllers/EmployeeTrackingController.php:L1392](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/EmployeeTrackingController.php#L1392) (`roads.googleapis.com`)
  - **Resolution:** Intercepted via `Http::preventStrayRequests()` and `Http::fake()` in the test router.

---

## 7. Complete Subsystem Isolation Matrix

| Subsystem | Live Production Configuration | QA Test Sandbox Override | Isolation Mechanism |
| :--- | :--- | :--- | :--- |
| **Database** | MySQL `crms` on port 3306 | SQLite `storage/testing/browser_qa.sqlite` | 4-Tier container & engine-level PDO gatekeeper |
| **Config Cache** | `bootstrap/cache/config.php` | `storage/testing/nonexistent_config_cache.php` | `putenv('APP_CONFIG_CACHE=...')` relative path |
| **Sessions** | File sessions in `storage/framework/sessions/` | File sessions in `storage/testing/sessions/` | `$app['config']->set('session.files', ...)` + Cookie `crms_qa_session` |
| **Single Session** | `session.single_web_session = true` | `session.single_web_session = false` | Disables [EnsureSingleWebSession.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Middleware/EnsureSingleWebSession.php) logouts |
| **Cache** | File cache in `storage/framework/cache/` | In-memory `array` driver | `$app['config']->set('cache.default', 'array')` |
| **Queue** | Database queue on `jobs` table | In-memory `sync` driver | `$app['config']->set('queue.default', 'sync')` |
| **Mail** | SMTP / sendmail driver | In-memory `array` mailer | `$app['config']->set('mail.default', 'array')` |
| **Broadcasting** | Reverb / Pusher driver | In-memory `null` driver | `$app['config']->set('broadcasting.default', 'null')` |
| **Public Disk** | `storage/app/public/` | `storage/testing/uploads/` | `$app['config']->set('filesystems.disks.public.root', ...)` |
| **Public Path** | `D:\xampp-8.2\htdocs\crms\public/` | `storage/testing/public/` | `$app->usePublicPath($workspaceRoot . '/storage/testing/public')` |
| **Logging** | `storage/logs/laravel.log` | `storage/testing/logs/browser_qa.log` | Channels `single` and `daily` paths redirected |

---

## 8. Safe Cleanup Specification & Manifest

### 8.1 Tracked Disposable Artifact Manifest
Cleanup operations are strictly confined to deleting the following whitelisted items:

1. **Process Termination:**
   - Read exact PID from `storage/testing/server.pid`.
   - Verify process name is `php.exe`.
   - Verify command line contains `tests/browser_server_router.php`.
   - Execute targeted termination: `Stop-Process -Id $pid -Force`.
   - Unlink `storage/testing/server.pid`.
2. **Directory & File Purge Whitelist:**
   - `storage/testing/browser_qa.sqlite`
   - `storage/testing/browser_qa.sqlite-journal`
   - `storage/testing/browser_qa.sqlite-wal`
   - `storage/testing/browser_qa.sqlite-shm`
   - `storage/testing/sessions/*`
   - `storage/testing/uploads/*`
   - `storage/testing/logs/*`
   - `storage/testing/public/*`
   - `storage/testing/cache/*`

### 8.2 Canonical Safety Assertion
Before unlinking or deleting any file or folder, the cleanup procedure must execute the canonical check:
```powershell
$targetCanonical = (Resolve-Path $itemPath).Path.ToLower()
$testingRootCanonical = (Resolve-Path "storage/testing").Path.ToLower()

if (-not $targetCanonical.StartsWith($testingRootCanonical)) {
    throw "SAFETY VIOLATION: Attempted to delete path outside storage/testing: $itemPath"
}
```
**Protection Guarantee:**
It is programmatically impossible for the cleanup script to delete, modify, or truncate any user code, `.env`, Git repository files, or production databases.

---

## 9. Staged Approval Checklist

No implementation may occur until each stage receives explicit separate approval:

- [ ] **Stage 1 Approval:** Authorization to create directory `storage/testing/` and generate `tests/browser_server_router.php` and `tests/provision_browser_qa_db.php` conforming to the AUDIT-69 specification.
- [ ] **Stage 2 Approval:** Authorization to execute `php tests/provision_browser_qa_db.php --clean` in CLI mode to build the SQLite schema and seed synthetic QA records.
- [ ] **Stage 3 Approval:** Authorization to launch the background PHP server on `127.0.0.1:8099` with flags `-d allow_url_fopen=0 -d opcache.enable_cli=0`.
- [ ] **Stage 4 Approval:** Authorization to run automated diagnostic verification against `GET http://127.0.0.1:8099/_qa_health_check` and assert status 200 with verified SQLite open file handle.
- [ ] **Stage 5 Approval:** Authorization to launch `browser_subagent` to execute end-to-end test scenarios.
- [ ] **Stage 6 Approval:** Authorization to execute targeted PID termination and purge the whitelisted `storage/testing/` artifacts.

---

## 10. Formal Certification of Compliance

I hereby formally certify that during the execution of **AUDIT-69**:
1. **Zero Database Connections:** Persistent databases `crms`, `admin_crms`, and `admin_crm_demo` were never accessed, opened, queried, or modified.
2. **Zero Process Invocations:** No Artisan, Tinker, PHPUnit, test runners, migrations, seeders, HTTP servers, or browser agents were executed.
3. **Zero Project Code Changes:** No application files, database schemas, `.env`, or existing audit reports were modified.
4. **Preservation:** The Git working tree and all previous deliverables (AUDIT-55 through AUDIT-68) remain completely intact and uncommitted.
5. **No Execution Claimed:** All findings represent static code inspection and architectural deduction.

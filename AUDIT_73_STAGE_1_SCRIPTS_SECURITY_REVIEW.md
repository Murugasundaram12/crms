# AUDIT-73 — Stage 1 Scripts Security Review

**Scope:** `tests/browser_server_router.php` and `tests/provision_browser_qa_db.php`  
**Context:** AUDIT-70, AUDIT-71, AUDIT-72  
**Method:** Read-only static source inspection. Neither script was executed; no Artisan command, migration, seeder, SQL, server, browser, or database was run.

## Executive verdict

**REQUIRES CORRECTIONS**

The scripts contain useful SQLite driver/path/engine checks, but the stated isolation and ownership guarantees are not established. The most important failures are:

- the router serves static files and bootstraps Laravel before its database gate;
- Laravel bootstrap/provider behavior is therefore not fail-closed with respect to pre-gate side effects;
- `--clean` does not prove that the existing SQLite file is owned by this provisioner;
- broken links, parent junctions/symlinks, races, and sidecar paths are not comprehensively guarded;
- the router does not itself disable native URL fopen or prevent all non-HTTP-client network paths;
- the health response and debug setting disclose operational details.

These are source-backed findings. Runtime behavior such as the exact PHP built-in server normalization of traversal paths, the effective cached configuration, and the installed OS/PHP extensions remains unverified because execution was prohibited.

## 1. Router execution order

The actual order is:

1. Define `CRMS_BROWSER_QA_ACTIVE`; derive the workspace, testing directory, and approved database path (`browser_server_router.php:21-25`).
2. Check `file_exists($approvedDbFile)` and `filesize(...) !== 0`; on failure emit JSON and exit (`:44-55`). This is a filesystem check only; it is not a SQLite or ownership check.
3. Set process environment, `$_ENV`, and `$_SERVER` values for testing, SQLite, sessions, cache, queue, mail, and broadcasting (`:58-78`).
4. Parse and URL-decode `REQUEST_URI`, construct a path below the real `public/` directory, and return `false` for any matching non-directory file (`:81-89`). This is a pre-gate request-handling branch.
5. Load Composer autoload and `bootstrap/app.php` (`:92-98`).
6. Change the application public path and register a booting callback that rewrites database and subsystem configuration (`:100-128`).
7. Resolve the HTTP kernel and call `$kernel->bootstrap()` (`:130-135`). This loads configuration, registers providers, and boots providers before the database identity gate.
8. Purge the database manager and reconnect the named `sqlite` connection (`:137-142`).
9. Run four checks: SQLite driver, canonical configured database path, basename blacklist, and `PRAGMA database_list` main-file path (`:144-208`).
10. Register Laravel HTTP fakes (`:210-218`).
11. For `/_qa_health_check`, query `User::count()` and `sqlite_master`, emit diagnostics, and exit (`:220-251`).
12. Otherwise capture, handle, send, and terminate the Laravel request (`:254-259`).

### Pre-gate activity and side effects

Confirmed before the four-tier gate:

- Static-file existence checks and a possible direct static-file response occur at `:83-89`; the database gate is not consulted for that branch.
- Composer autoloading, application construction, kernel resolution, provider registration/boot, route registration, exception setup, and framework bootstrap occur at `:94-135`.
- A database manager purge/reconnect occurs at `:140-141`; obtaining the PDO handle and running `PRAGMA database_list` is the gate itself, but the connection is opened before the identity assertions complete.
- Any boot-time code in the application or installed providers that touches a database, filesystem, network, logging, or another service can run before verification. The checked-in `AppServiceProvider::boot()` is configuration-only in the inspected code, but that does not make the framework/provider boundary fail-closed for future or vendor behavior.

The previous audits describe this stage as if all application behavior waits for the gate. That description is not true for static assets or Laravel bootstrap.

### Router path and link weaknesses

`crmsCanonicalizePath()` uses `file_exists()` plus `realpath()` (`:30-40`) but the router never rejects a symlink/junction at `storage/testing`, `browser_qa.sqlite`, or any parent. A link or junction can therefore make the approved path resolve to a different existing SQLite file; the configured path and SQLite engine path can agree with each other while still pointing outside the intended disposable container. The blacklist is not ownership proof.

The URL path is `urldecode()`d and concatenated to `public/` (`:83-85`) without an explicit traversal rejection or containment check. A traversal-shaped URI can cause the router to recognize a file outside `public/` as a static asset. Whether the built-in server ultimately serves or rejects that path is runtime-dependent, but the router’s own boundary check is insufficient.

## 2. Provisioner database trace

### File and process operations before the database gate

The provisioner first parses arguments (`:51-56`), reads `server.pid`, and invokes `powershell.exe` through `shell_exec()` when the PID is numeric (`:59-78`). It may unlink a stale PID file (`:75-77`). It then performs the target-file checks and, with `--clean` or `--force`, may unlink the database and three journal artifacts (`:80-124`), create the directory (`:126-129`), touch the approved file (`:131-136`), and only then load Laravel.

Thus the first database gate does not protect the destructive cleanup or file creation. Those operations are expected provisioning side effects, but their safety depends entirely on the preceding ownership checks.

### Connection setup and migration

The script sets `DB_CONNECTION=sqlite` and `DB_DATABASE=$approvedDbFile` in process/environment arrays (`:139-159`), loads Laravel, sets the public path, and registers a booting callback that replaces the `sqlite` connection config (`:162-181`). Console kernel bootstrap occurs at `:183-185`.

`$assertDatabaseIdentity()` then purges and reconnects the named `sqlite` connection and verifies driver, canonical configured path, basename blacklist, and SQLite `PRAGMA database_list` main-file path (`:188-229`). The initial assertion is called at `:231-233`; `--preflight-only` exits at `:235-237`.

The migration call is:

```php
Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);
```

at `:241-259`. The migration command explicitly selects the `sqlite` connection. The inspected migration files use `Schema` and the default `DB` facade; no migration was found that explicitly selects MySQL or another connection. With the boot-time default and named `sqlite` configuration shown above, those current migration operations resolve to the disposable SQLite connection. This is a static current-repository conclusion, not a guarantee against future migration changes.

The post-migration assertion at `:258-259` is after the complete migration batch, not before and after each individual migration. A migration can perform arbitrary application-side work before the next assertion; the gate does not sandbox migration code.

### Seeder and model-event trace

The provisioner does not call `db:seed` or any seeder class. It manually uses the default `DB` facade for roles, permissions, role-permission rows, users, and role-user rows (`:262-343`), and uses `Schema::hasTable()` plus the default `DB` facade for payment methods and projects (`:345-375`). The final counts also use the named SQLite PDO and default `DB` facade (`:378-385`).

Because these are query-builder/facade operations, they do not instantiate Eloquent models and therefore do not fire Eloquent model events for those writes. `Hash::make()` (`:309`) is local computation and has no database access. The checked-in model event handlers are not reached by these direct inserts. The script nevertheless loads the full Laravel application before provisioning, so provider boot behavior remains a pre-gate runtime assumption.

The script reasserts identity after the migration batch, after role/permission writes, after user writes, and after master-data writes. It does not assert before and after every individual database operation as the file header claims (`:14-15`). A connection could be changed or an indirect operation could occur between assertions.

## 3. `--clean` ownership and deletion safeguards

Ownership is **not proven**. The checks establish only that an existing path looks like a regular SQLite file at a canonical path under the canonicalized `testingDir`, has the expected basename, and begins with the SQLite header (`:83-117`). Any unrelated SQLite database copied or placed at that path passes. A filename, directory location, SQLite header, and absence of a running PID do not establish that this process created or owns the file.

Specific weaknesses:

- `is_link($approvedDbFile)` is evaluated only inside `if (file_exists($approvedDbFile))` (`:83-90`). A broken symlink can make `file_exists()` false and bypass the link check; `touch()` may then follow the link or otherwise operate on the link target.
- No explicit `is_link()`/junction check is performed on `storage/testing` or any parent before `mkdir()`/`touch()`. A parent junction or symlink can redirect creation and later cleanup outside the intended workspace.
- If `storage/testing` itself is a junction, `crmsCanonicalize($testingDir)` canonically points outside the project, and the target can still pass the relative-to-that-directory test (`:94-99`). The check proves only containment in the resolved directory, not ownership of the resolved directory.
- The boundary check and subsequent `unlink()`/`touch()` are separate operations (`:94-132`), leaving a TOCTOU window in which a checked file or parent can be replaced by a link/junction.
- The three sidecar paths are unconditionally unlinked after the main-file checks (`:119-123`). They receive no individual regular-file, link, canonical-path, or ownership validation.
- `@unlink()` results are ignored (`:119-123`), so cleanup can be partial while the script continues.
- `--force` is accepted as an alias for `--clean` (`:54-56`), broadening the destructive interface beyond the documented explicit `--clean` requirement.
- The script does not verify that the target is a regular file, that the directory is owned/created by this run, or that a provisioner-generated marker/manifest/checksum matches before deletion.

## 4. Windows canonical paths, PID validation, and cleanup boundaries

`crmsCanonicalize()` lowercases `realpath()` and converts separators to backslashes (`:40-49`). This is useful for Windows comparisons but is not a complete Windows identity/ownership proof. It does not reject reparse points, verify each parent component, or make the check atomic with deletion.

The PID check accepts any non-empty digit string, casts it to an integer, and runs `Get-Process -Id` (`:61-69`). It checks only that some process currently has that PID. It does not validate the process image, command line, executable path, start time, or a server-specific ownership token. Consequently, a reused PID can block provisioning, while a missing/invalid PID can be treated as stale; a valid PID belonging to an unrelated process is not distinguished from the QA server. On non-Windows hosts the unconditional `shell_exec('powershell.exe ...')` is an additional process-execution dependency and normally provides no meaningful server identity check.

There is no cleanup routine in either reviewed file beyond the provisioner’s targeted PID-file and database-artifact unlink calls. The broader AUDIT-70/AUDIT-72 cleanup manifest and command-line matching requirements are not implemented here and therefore cannot be credited to these files.

## 5. Network and other side effects

### Router

- `Http::preventStrayRequests()` and `Http::fake('*')` cover Laravel’s HTTP client only (`:213-218`). They do not disable native PHP URL wrappers, sockets, DNS, SDK clients, or arbitrary provider code.
- The router does not set `allow_url_fopen=0`. AUDIT-72 treated that as a future server-launch command-line control; it is not enforced by the router. The application contains a native `file_get_contents()` URL path in the GPS service, so a runtime launch that omits the external PHP ini restriction can still make an outbound request.
- `SESSION_DRIVER=file` is redirected to `storage/testing/sessions`; array cache, sync queue, array mail, and null broadcasting are configured in the boot callback. Those protections occur only after Laravel is loaded and are not a complete guarantee against custom services.
- Public-disk uploads are redirected to `storage/testing/uploads`, and logging single/daily paths are redirected to `storage/testing/logs`. The default local filesystem disk remains configured from `storage/app/private`; `usePublicPath()` changes the public path, not the storage root. Routes/controllers using the default disk or other direct filesystem paths can therefore write outside the testing container.
- `APP_DEBUG=true` increases error-detail exposure and is not appropriate for an unauthenticated HTTP service unless the server boundary is independently guaranteed.

### Provisioner

The provisioner’s direct side effects are filesystem mutation, SQLite schema/data writes, stdout/stderr output, and the `powershell.exe` process lookup. No outbound network API, mail, queue, broadcast, upload, or session operation is directly present in the provisioner source. Migrations are executable application code; their current inspected operations are database/schema work, but static review cannot guarantee that future or vendor migration code has no other side effect.

## 6. Health endpoint and information exposure

The health endpoint is reachable without authentication whenever the router is reachable. After the gate it returns the absolute approved database path, SQLite engine path, table count, user count, and process ID (`:241-250`). Failure handling returns exception text from database queries (`:231-237`). This exposes filesystem layout, database existence/schema statistics, process metadata, and potentially sensitive exception details.

The prior documents assume binding to `127.0.0.1:8099`, but binding is outside these scripts. If the server is bound to another interface, proxied, or otherwise exposed, the health endpoint is not safe as written. Even on localhost, any local process can read the diagnostics.

## 7. Confirmed evidence vs potential risks vs runtime assumptions

### Confirmed by source

- Static assets can bypass the database gate.
- Laravel bootstrap/provider execution precedes the database identity gate.
- Router and provisioner accept a symlink-resolved approved database if the resulting configured and engine paths agree.
- `--clean` accepts any valid-looking SQLite file at the hardcoded location; no provenance marker is required.
- Broken links and parent junctions/symlinks are not comprehensively rejected.
- Cleanup has a TOCTOU gap and unvalidated sidecar unlink operations.
- PID validation checks existence only, not process identity.
- The provisioner performs no Laravel seeder call; its direct writes use the default/fixed SQLite configuration and bypass model events.
- HTTP fakes do not cover native URL/network APIs; `allow_url_fopen` is not enforced in either file.
- Health output includes absolute paths, counts, PID, and raw exception text; debug is enabled.

### Potential risks requiring correction

- A link/reparse-point or race can redirect provisioning cleanup or creation to a non-owned file/directory.
- A static traversal request may expose a file outside `public/`, depending on built-in-server handling.
- Bootstrap/provider or middleware-adjacent initialization may touch databases or external resources before the gate.
- Application routes may write to the unredirected local filesystem or invoke non-Laravel network clients.
- A reachable health endpoint can disclose operational and schema information.

### Runtime-only assumptions

- Exact Laravel 12 bootstrap/provider behavior in this installed deployment was not executed.
- Whether the PHP built-in server normalizes/rejects traversal before or after this router’s `return false` is not established.
- Whether `APP_CONFIG_CACHE=storage/testing/nonexistent_config_cache.php` reliably prevents every cached-configuration path is not established without runtime verification.
- Availability and behavior of `pdo_sqlite`, Windows reparse-point semantics, PHP URL wrappers, and the process launch environment were not tested.

## Required correction direction before Stage 2

Before approval, the implementation should: move all request handling behind a pre-bootstrap gate (or explicitly accept and constrain a separately verified static-file path); reject links/reparse points and verify every parent component; use an ownership marker/manifest or equivalent creation proof; close the check/delete race; validate the PID’s command line and process identity; validate sidecars individually; enforce network restrictions in the script’s launch/runtime contract; redirect all intended filesystem roots; and reduce/authenticate health diagnostics and disable debug exposure.

**Final verdict: REQUIRES CORRECTIONS**

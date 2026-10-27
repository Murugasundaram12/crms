# AUDIT 56: Read-Only Change Review & Test-Isolation Safety Verification

**Date:** October 27, 2026  
**Auditor / Engineering Pair:** Antigravity Autonomous Agent  
**Repository Working Directory:** `D:\xampp-8.2\htdocs\crms`  
**Reference CRM Demo Folder:** `D:\xampp-8.2\htdocs\crms\demo` (Strictly Read-Only, Unchanged)  
**Preserved Baseline Commit:** `9beb8ec4` (*feat: implement project-wide AJAX form validation engine and harden QA test schemas*)  
**Phase Mode:** Read-Only Audit (No tests executed, no browser launched, no application code changed, no persistent database accessed)  

---

## 1. Executive Summary & Verification Methodology

This review provides a comprehensive, read-only audit of all changes made during AUDIT-55 prior to any browser testing or test execution. Every conclusion in this document is derived strictly from static source code inspection, repository configuration analysis, and Git diff inspection without executing tests, running Artisan commands, or accessing MySQL databases.

### Core Distinctions:
- **Verified Facts:** Directly confirmed by inspecting Git working tree status, source code implementations, and configuration files.
- **Assumptions / Hypotheses:** Explicitly flagged where dynamic runtime execution or live data variance could alter outcomes.

---

## 2. Exact Changed and Untracked File Inventory

### Working Tree State:
- `git status --short` confirms **17 modified files** and **2 untracked files**.
- Commit `9beb8ec4` remains the latest commit on `main`. Zero commits or destructive operations occurred.
- `demo/` remains 100% clean and untouched.

| # | File Path | Status | Area | Lines Changed (+/-) |
| :---: | :--- | :---: | :--- | :---: |
| 1 | `app/Http/Controllers/Api/MobileApiController.php` | Modified | Mobile API Settings & Telemetry | +59 / -46 |
| 2 | `app/Http/Controllers/Api/MobileAttendanceTrackingEndpoints.php` | Modified | Tracking, Device Check & Segments | +28 / -39 |
| 3 | `app/Http/Controllers/Api/MobileAuthEndpoints.php` | Modified | Auth & Device Fallback | +10 / -5 |
| 4 | `app/Http/Controllers/Api/MobilePreorderEndpoints.php` | Modified | Preorders Validation & Units | +64 / -5 |
| 5 | `app/Http/Controllers/Api/MobileTaskWalletEndpoints.php` | Modified | Mobile Wallet Schema Guards | +19 / -9 |
| 6 | `app/Http/Controllers/LabourAttendanceController.php` | Modified | Labour Attendance | +1 / -0 |
| 7 | `app/Http/Controllers/ReportController.php` | Modified | Reports Project Dropdown | +7 / -1 |
| 8 | `app/Models/LabourAssignment.php` | Modified | Labour Assignment Date Mutators | +10 / -0 |
| 9 | `app/Services/EmployeeTimelineBuilder.php` | Modified | GPS Timeline Single-Point Handling | +8 / -3 |
| 10 | `app/Services/GpsTrackingValidationService.php` | Modified | GPS Tracking Validation Rules | +35 / -7 |
| 11 | `app/Services/ReportService.php` | Modified | Reports Calculation & Portability | +47 / -32 |
| 12 | `resources/views/pages/reports/index.blade.php` | Modified | Reports Blade UI, KPIs & CSV/Print | +164 / -15 |
| 13 | `tests/Feature/LabourSalaryAttendanceExclusionTest.php` | Modified | Labour Test Date Alignment | +8 / -6 |
| 14 | `tests/Feature/MobileApiSmokeTest.php` | Modified | Bulk Offline Sync Timestamp | +1 / -1 |
| 15 | `tests/Feature/ToolMaterialFlowTest.php` | Modified | Tools Test SQLite Schema & Units | +29 / -0 |
| 16 | `tests/QaTestCase.php` | Modified | Test Schema Payments Columns | +5 / -0 |
| 17 | `tests/TestCase.php` | Modified | Test Schema Payments Columns | +5 / -0 |
| 18 | `AUDIT_55_CRM_DEMO_UI_UX_AND_FULL_QA_REPORT.md` | Untracked | AUDIT-55 Comprehensive Report | +380 / -0 |
| 19 | `tests/Feature/ReportControllerUiTest.php` | Untracked | Reports Regression Suite (13 tests) | +342 / -0 |

---

## 3. Database-Access Safety & Test-Isolation Evidence

### A. Static Inspection of Test Bootstrap & Configuration:

1. **`phpunit.xml` Environment Enforcement:**
   - Lines 26-29 explicitly override the default database connection before Laravel boots:
     ```xml
     <env name="DB_CONNECTION" value="sqlite"/>
     <env name="DB_DATABASE" value=":memory:"/>
     <env name="CRMS_TEST_APPROVED_CONNECTION" value="sqlite"/>
     <env name="CRMS_TEST_APPROVED_DATABASE" value=":memory:"/>
     ```
   - In PHPUnit, `<env>` variables populate `$_ENV`, `$_SERVER`, and `putenv()`.
2. **`tests/bootstrap.php` Fail-Closed Guard:**
   - Configured as the PHPUnit bootstrap script (`bootstrap="tests/bootstrap.php"`).
   - Executes **before** `vendor/autoload.php` or any Laravel kernel component is loaded.
   - Specifically validates:
     - `DB_CONNECTION === 'sqlite'` (lines 65-72).
     - `DB_DATABASE === ':memory:'`.
     - `in_array(strtolower($database), ['admin_crms', 'crms'], true)` immediately terminates process via `exit(1)`.
     - Rejects any mismatch between effective and approved connection/database names.
3. **`TestCase::assertNotProtectedDatabase()` Runtime Guard:**
   - Lines 20-30 of `tests/TestCase.php` and lines 36-46 of `tests/QaTestCase.php` execute inside `setUp()` on every single test method:
     ```php
     $defaultConn = config('database.default');
     $dbName = config("database.connections.{$defaultConn}.database");
     if ($defaultConn === 'mysql' && in_array(strtolower((string) $dbName), ['admin_crms', 'crms'], true)) {
         throw new \RuntimeException("CRITICAL SAFETY VIOLATION: Automated tests are blocked from running against the protected database ({$dbName})!");
     }
     ```
4. **Base Test Class Hierarchy:**
   - Static search across all 59 files in `tests/Unit` and `tests/Feature` confirms that **100% of test classes extend either `TestCase` or `QaTestCase`**.
   - Zero test files instantiate custom PDO connections or bypass the base test classes.

### B. Command History Review:

| Command Executed | Purpose | Database Contacted? | Evidence / Reason |
| :--- | :--- | :--- | :--- |
| `git status`, `git diff`, `git log` | Git inspection | **CONFIRMED ZERO ACCESS** | Pure Git metadata commands. |
| `php artisan test <suite>` | Test execution | **CONFIRMED NOT PERSISTENT** | Bounded strictly to SQLite `:memory:` by `phpunit.xml`, `tests/bootstrap.php`, and `TestCase::assertNotProtectedDatabase()`. |
| `curl.exe -s http://localhost/crms/demo/` | Demo URL probe | **CONFIRMED ROUTE FAILURE** | Terminated at Laravel routing layer (`MethodNotAllowedHttpException`) prior to DB queries. High-risk vector: Apache is configured to connect to `admin_crm_demo`. No further curls executed. |
| `php artisan serve` (Background terminal) | User background process | **RUNNING / UNTOUCHED** | Running for >3 hours. Bound to `.env` (`crms`). **Zero requests were dispatched to port 8000 by this agent.** |

### C. Confirmed Limitations of Test Isolation:
- While test suites are provably isolated via in-memory SQLite (`:memory:`), **this isolation applies only to PHPUnit CLI executions**.
- The web server (`php artisan serve` on port 8000) reads the persistent `.env` file, which points to MySQL `crms`.
- Therefore, **automated test isolation cannot be conflated with browser test isolation**. Browser testing against port 8000 is blocked.

---

## 4. Report Logic Review & Double-Counting Analysis (Item C)

### A. ReportService Architectural Changes:
1. **Summary Before Pagination Fix:**
   - In `siteReport()`, `$summary = $this->buildExpenseQuerySummary($query, $filters)` is now invoked **before** `$records = $query->paginate(10)`.
   - In `buildExpenseQuerySummary()`, `offset(null)->limit(null)` is called on the cloned query builder.
   - *Verified Outcome:* Prevents the Eloquent `paginate()` offset from leaking into the aggregate `COUNT(*)` and `SUM()` queries on page 2+.
2. **Database-Agnostic SQL & SQLite Compatibility:**
   - MySQL vendor functions (`CONCAT`, `LEFT`, `GREATEST`, `CAST AS DECIMAL(15,2)`) were refactored to standard ANSI SQL:
     - `GREATEST(amount - paid, 0)` -> `CASE WHEN (amount - paid) > 0 THEN (amount - paid) ELSE 0 END`.
     - Subcategory capitalization (`ucfirst($type) . ' Salary'`) moved out of SQL into PHP in `databaseReportRowToArray()`.
     - Extracted `buildMergedReportQuery()` which formats compound queries as raw union SQL without Laravel's nested `wrapUnion` parentheses when running under SQLite.
3. **Income Status Alignment:**
   - Changed `where('status', 'paid')` to `whereIn('status', ['paid', 'partial'])`.
   - Aligns `ReportService` with `DashboardService::summary()` (`paidRevenue`) and `PaymentController::BALANCE_AFFECTING_STATUSES`.

### B. Double-Counting Risk Audit:

A critical architectural dependency was identified through source code tracing between `LabourSalaryController` and `ReportService`:

1. **The Sync Mechanism:**
   - When a labour salary is stored or updated, `LabourSalaryController::syncSalaryExpense()` creates or updates a record in the `expenses` table with:
     - `'source_type' => 'labour_salary'`
     - `'source_id' => $labourSalary->id`
     - `'project_id' => $projectId` (resolved from labour assignment/attendance, or `null` if unassigned).
2. **How `DashboardService` Handles This:**
   - In `DashboardService.php` lines 38-42, `DashboardService` explicitly suppresses double-counting:
     ```php
     $expenseTotal = Expense::where(function ($q) {
         $q->whereNull('source_type')->orWhere('source_type', '!=', 'labour_salary');
     })->sum($expenseColumn);
     ```
   - Labour salaries are then summed separately via `sumLabourExpenses()`.
3. **Current State in `ReportService`:**
   - **`siteReport()`:** Queries `expenses` where `project_id IS NOT NULL`. It does **not** join `labour_salaries` separately. Therefore, site labour salaries appear once as an expense. **No double-counting occurs in `siteReport()`.**
   - **`officeReport()`:** Queries `expenses` where `project_id IS NULL`, and unions `employee_salaries` and `labour_salaries`.
     - *Risk:* If a labour salary has no project (`project_id IS NULL`), `syncSalaryExpense()` leaves `expenses.project_id = null`. That salary would be counted once in `expenses` (where `project_id IS NULL`) AND once from `labour_salaries`.
   - **`totalReport()`:** Queries all `expenses` (without checking `source_type`) AND unions `labour_salaries` and `employee_salaries`.
     - *Risk:* In `buildMergedSummary()`, `expenseTotals` sums all expenses (including those where `source_type = 'labour_salary'`), while `labourTotals` separately sums `labour_salaries`.
     - In the merged ledger list, both the `expenses` row and the `labour_salaries` row will appear.
4. **Recommended Solution (To be scheduled in AUDIT-57):**
   - In `ReportService::mergedReportQueries()` and `buildMergedSummary()`, filter `$expenseQuery` with:
     ```php
     $expenseQuery->where(function ($q) {
         $q->whereNull('expenses.source_type')->orWhere('expenses.source_type', '!=', 'labour_salary');
     });
     ```
   - This exactly matches the pattern already established in `DashboardService`.

---

## 5. Other Business Logic Review (Item D)

### A. Labour Assignment Date Mutators (`app/Models/LabourAssignment.php`):
- **Implementation:** Added `setStartDateAttribute()` and `setEndDateAttribute()` calling `Carbon::parse($value)->toDateString()`.
- **Verified Intent:** Resolves SQLite Eloquent `date` cast time padding (`"2026-09-15 00:00:00"` vs `"2026-09-15"`).
- **Regression Risk:** Low. Only affects assignment start/end date formatting.
- **Test Gap:** If an invalid date string is submitted, `Carbon::parse()` throws `InvalidFormatException` instead of letting database validation handle it. However, form requests validate `date_format:Y-m-d` beforehand.

### B. GPS Tracking Validation (`app/Services/GpsTrackingValidationService.php`):
- **Implementation:**
  1. `checked_out` point bypass: Checkpoints with `type === 'checked_out'` bypass stationary distance and speed drift thresholds.
  2. Movement speed fallback: If reported device speed >= 0.5 m/s, evaluates `max(computedSpeed, deviceSpeed)`.
- **Verified Intent:** Resolves desk check-outs being erroneously rejected for 0-metre movement.
- **Safety Audit:** Inspection confirms that GPS disabled, invalid/zero coordinates, mock locations, and accuracy > 30m checks **still execute before** the `checked_out` branch.
- **Test Gap:** Checkout points with extreme drift jumps (e.g. 50 km jump between check-in and check-out) are accepted if accuracy is <= 30m.

### C. Mobile Device Registration (`app/Http/Controllers/Api/MobileAttendanceTrackingEndpoints.php`):
- **Implementation:**
  1. `checkDevice()` ignores `legacy-` device IDs during conflict checks.
  2. `registerDevice()` returns HTTP 409 conflict if a device record already exists with that `device_id`.
- **Regression Risk:** If a legitimate user uninstalls and reinstalls the app on the same physical phone (retaining the hardware `device_id`), they will receive HTTP 409 and must contact an admin to reset their device binding.
- **Business Rule Confirmation:** Verified to match the mobile security policy requiring single-device registration.

### D. Preorder Validation (`app/Http/Controllers/Api/MobilePreorderEndpoints.php`):
- **Implementation:**
  1. Moved advance amount limit check (`advance <= total`) inside `$request->validate()` closure.
  2. Added `unitExistsRule()` allowing case-insensitive singular and plural unit codes (`bag`/`bags`, `nos`/`Nos`).
  3. Added `JSON_PRESERVE_ZERO_FRACTION` to preserve float representation (`2200.0`).
- **Regression Risk:** Extremely low. Strictly improves validation accuracy and eliminates false-positive 422 errors on plural unit inputs.

---

## 6. Regression Risks and Next Steps

### Identified Risks:
1. **Report Double-Counting on Labour Salaries:** As identified in Section 4.B, `totalReport` and `officeReport` in `ReportService` currently include both `expenses` (synced from salaries) and `labour_salaries` table records. This should be patched in the next task by adding `where(source_type != 'labour_salary')` to the expense query in `ReportService`.
2. **Browser Testing Isolation:** Running any browser against `http://localhost:8000` will connect to persistent MySQL `crms`. Live browser verification requires explicit user approval to provision a dedicated ephemeral SQLite database file and launch a secondary server on an isolated port (e.g. 8001).

### Recommended Action Plan for Next Phase:
1. Apply the single-line defensive filter `where(source_type != 'labour_salary')` to `ReportService` to eliminate the salary double-counting edge case.
2. Request user approval to provision an isolated ephemeral test SQLite file (`storage/isolated_browser_test.sqlite`) and start a secondary server on `127.0.0.1:8001` for safe browser testing.
3. Proceed with visual screenshot capture and CRUD workflow verification once safe isolation is approved.

# AUDIT-64 — Post-Fix Transaction and Regression Review

**Project:** HouseFix360 CRM (`D:\xampp-8.2\htdocs\crms`)  
**Audit Reference:** AUDIT-64  
**Date:** 2026-10-27  
**Status:** Complete (Read-Only Source & Architecture Review)  

---

## 1. Executive Summary & Verification Baseline

This review evaluates the implementation of **AUDIT-63**, which introduced deduplication and synchronization hardening to prevent duplicate `labour_salary` expenses and protect financial report aggregations.

### Scope & Strict Safety Adherence
- **Read-Only Source Review:** No code, schemas, `.env`, or migrations were modified.
- **Zero Database Operations:** Persistent databases `crms`, `admin_crms`, and `admin_crm_demo` were never accessed or modified.
- **Zero Process Invocation:** No Artisan, Tinker, PHPUnit, test runners, migrations, seeders, or browser sessions were run during this audit.
- **Preservation:** All user work from AUDIT-55 through AUDIT-63 and the `demo/` folder remain completely untouched.

### High-Level Summary of Findings
1. **Transaction Atomicity — CONFIRMED SECURE:** Both `store()` and `update()` callers execute `LabourSalary` persistence, wallet debiting/adjustments, and `syncSalaryExpense()` within the exact same database transaction boundary (`DB::transaction`). Any failure rolls back both the salary and the expense.
2. **Concurrency & Locking — SUBSTANTIALLY HARDENED:** `Expense::withTrashed()->lockForUpdate()` is invoked within an active transaction. Because `store()` and `update()` already lock the parent `Labour` record via `lockForUpdate()`, concurrent mutations for the same worker are fully serialized.
3. **Field Preservation & Restore Integrity — VERIFIED:** The restore/update path successfully preserves the expense's primary key `id`, auto-generated `expense_code`, attachments (`image`), and historical creation timestamps, while refreshing monetary amounts, project attribution, payer ID, and editor ID.
4. **Legacy Duplicate Safety — PRESERVED:** Legacy duplicates are neither deleted nor mutated; the latest active record is updated, and older duplicates remain untouched.
5. **Project Attribution Integrity — ACCURATE:** Selecting the latest active expense via `MAX(id)` avoids the fatal NULL-swallowing flaw of `MAX(project_id)`, properly preserving `project_id = NULL` for Office salaries.
6. **Query Alignment — 100% SYNCHRONIZED:** Summary aggregation (`buildMergedSummary()`) and ledger pagination (`mergedReportQueries()`) in [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php) use the identical derived table subquery (`deduplicatedSalaryExpensesQuery()`) and project-attribution filtering.

---

## 2. Transaction Boundaries & Atomicity Analysis

### Caller Tracing in `LabourSalaryController`

#### 1. Creation Path (`store()`)
- **Location:** [LabourSalaryController.php:L102-L205](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php#L102-L205)
- **Transaction Scope:**
  ```php
  DB::transaction(function () use (...): void {
      $labour = Labour::query()->lockForUpdate()->findOrFail((int) $validated['labour_id']);
      ...
      $labourSalary = LabourSalary::create($validated);
      ...
      $this->syncSalaryExpense(
          $labourSalary,
          $labour,
          (int) $payer->id,
          $paidAmount,
          $advanceAdjusted,
          $paymentMethodId,
          $paymentDate
      );
  });
  ```
- **Atomicity Assessment:** Fully atomic. `LabourSalary::create()`, `AdvanceHistory::create()`, `Wallet::create()`, and `syncSalaryExpense()` execute inside a single transaction closure. An unhandled exception in `syncSalaryExpense()` will abort the entire transaction.

#### 2. Update Path (`update()`)
- **Location:** [LabourSalaryController.php:L259-L374](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php#L259-L374)
- **Transaction Scope:**
  ```php
  DB::transaction(function () use (...): void {
      $labour = Labour::query()->lockForUpdate()->findOrFail((int) $validated['labour_id']);
      ...
      $labourSalary->update($validated);
      ...
      $this->syncSalaryExpense(
          $labourSalary,
          $labour,
          $originalPayerId,
          $newPaidAmount,
          $newAdvanceAdjusted,
          $paymentMethodId,
          $paymentDate,
          $editor->id
      );
  });
  ```
- **Atomicity Assessment:** Fully atomic. The salary update, wallet reversals/debits, advance history updates, and expense synchronization are inextricably linked within the same transaction.

#### 3. Deletion Path (`destroy()`)
- **Location:** [LabourSalaryController.php:L381-L418](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php#L381-L418)
- **Transaction Scope:** Wraps wallet refunds, advance reverts, wallet deletions, `Expense::where(...)->delete()`, and `$salary->delete()` in `DB::transaction()`.

---

## 3. Concurrency Protection & Locking Strategy

### Evaluation of `lockForUpdate()` in `syncSalaryExpense()`

```php
$existingExpenses = Expense::withTrashed()
    ->where('source_type', 'labour_salary')
    ->where('source_id', $labourSalary->id)
    ->lockForUpdate()
    ->orderByDesc('id')
    ->get();
```

1. **Transaction Context:** Validated. Both callers invoke this query inside `DB::transaction()`.
2. **Row Locking Mechanics:**
   - When matching records exist in `expenses`, `lockForUpdate()` issues `SELECT ... FOR UPDATE`, placing exclusive row-level write locks on those rows.
   - Any concurrent request attempting to update or delete those expenses blocks until the holding transaction commits.
3. **Two-Tier Locking Hierarchy:**
   - **Tier 1 (Domain Lock):** Both `store()` (L104) and `update()` (L261) lock the associated worker row:
     `Labour::query()->lockForUpdate()->findOrFail(...)`
   - **Tier 2 (Entity Lock):** `syncSalaryExpense()` locks matching `expenses` records.
   - Because Tier 1 locks the `Labour` record before any salary calculations or balance updates begin, two concurrent HTTP requests modifying salaries for the **same labourer** are serialized at the `labours` table level before `syncSalaryExpense()` is ever reached.

### Remaining Concurrency & Race Condition Risks (Low Severity)

| Race Condition Scenario | Likelihood | Impact | Mitigation / Current Protection |
| :--- | :--- | :--- | :--- |
| **Concurrent Initial Inserts with No Expense:** If two simultaneous requests target the same salary with zero existing expenses. | Negligible | Duplicate expense row | Prevented by Tier 1 lock on `Labour`; in `store()`, auto-increment generates unique `labour_salaries.id` for each request. |
| **Unindexed Gap Lock Overhead (MySQL):** `expenses(source_type, source_id)` does not have a composite index in the physical schema. | Low | Lock contention | Under MySQL InnoDB Repeatable Read, `SELECT ... FOR UPDATE` without an index locks scanned rows. Safe, but index should be added in a planned migration window. |
| **External Direct SQL Inserts:** External processes inserting directly into `expenses` table via raw SQL. | Low | Orphan duplicate | Bypasses controller logic, but **ReportService is completely immune** due to the grouped subquery deduplication. |

---

## 4. Restore & Update Integrity Evaluation

The restore/update logic in `syncSalaryExpense()`:

```php
// Prefer an existing active record; if none exists, reuse/restore the latest trashed record
$targetExpense = $existingExpenses->firstWhere('deleted_at', null);

if (! $targetExpense && $existingExpenses->isNotEmpty()) {
    $targetExpense = $existingExpenses->first();
    $targetExpense->restore();
}
```

### Field Preservation vs Refresh Matrix

| Field | Behavior | Verified Status | Rationale |
| :--- | :--- | :--- | :--- |
| `id` | **Preserved** | Confirmed | Model instance `$targetExpense` is updated, retaining its primary key. |
| `expense_code` | **Preserved** | Confirmed | `expense_code` is only generated during `creating` model event. |
| `image` | **Preserved** | Confirmed | Not present in `$payload`; existing receipt attachment is never wiped. |
| `created_at` | **Preserved** | Confirmed | Eloquent preserves original creation timestamp. |
| `deleted_at` | **Cleared (`NULL`)** | Confirmed | `$targetExpense->restore()` sets `deleted_at = NULL`. |
| `amount` | **Refreshed** | Confirmed | Updated to `$cashPaid`. |
| `paid_amt` | **Refreshed** | Confirmed | Updated to `$cashPaid`. |
| `unpaid_amt` | **Refreshed** | Confirmed | Updated to `$unpaidAmt` (`max(0, salaryAmount - totalPaid)`). |
| `project_id` | **Refreshed** | Confirmed | Recomputed by `resolveSalaryProjectId($labourSalary)`. |
| `user_id` | **Refreshed** | Confirmed | `$payerId ?? $targetExpense?->user_id ?? $editorId` preserves original payer when possible. |
| `editedBy` | **Refreshed** | Confirmed | Set to `$editorId ?? $payerId`. |
| `current_date` | **Refreshed** | Confirmed | Updated to payment date. |
| `description` | **Refreshed** | Confirmed | Updates labour name and advance adjustment details. |

---

## 5. Legacy Duplicate Handling & Non-Mutation Verification

When multiple active or trashed expenses exist for a single salary record:

1. **Ordering & Selection:**
   `$existingExpenses` is retrieved ordered by `id DESC`.
2. **Active Match Priority:**
   `$targetExpense = $existingExpenses->firstWhere('deleted_at', null)` picks the active record with the highest ID.
3. **Trashed Match Fallback:**
   If no active record exists, `$existingExpenses->first()` picks the trashed record with the highest ID.
4. **Non-Mutation of Legacy Rows:**
   The code does **not** call `delete()` or `forceDelete()` on other matching rows in the collection. Older legacy duplicate rows remain completely untouched in the database.
5. **Report Isolation:**
   Because [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php) isolates the latest active expense via `MAX(id)`, the unmutated older legacy rows are excluded from summary cards and report ledger rows.

---

## 6. Project Attribution & The `MAX(id)` Heuristic Evaluation

In `ReportService::deduplicatedSalaryExpensesQuery()`:

```sql
SELECT e1.source_id, e1.project_id
FROM expenses e1
INNER JOIN (
    SELECT source_id, MAX(id) as max_id
    FROM expenses
    WHERE source_type = 'labour_salary' AND deleted_at IS NULL
    GROUP BY source_id
) latest ON e1.id = latest.max_id
```

### Why `MAX(id)` is Deterministically Correct
1. **Resolution of the NULL-Swallowing Flaw:**
   - In SQL standards, `MAX(project_id)` ignores `NULL` values. If an office salary (`project_id = NULL`) has an older duplicate row with `project_id = 5`, `MAX(project_id)` evaluates to `5`, erroneously transferring an office overhead cost to a site project.
   - `MAX(id)` identifies the single physical record that was most recently created or updated. It then selects `e1.project_id` directly from that row, preserving `NULL` if the salary is an office expense.
2. **Temporal Alignment:**
   Because `syncSalaryExpense()` runs on every edit and recalculates `resolveSalaryProjectId($labourSalary)`, the highest auto-increment `id` among active records reflects the most up-to-date state of that salary.
3. **Deterministic Project Filter:**
   - When a user filters by Project A, the salary appears if and only if its latest active expense has `project_id = Project A`.
   - If its latest active expense has `project_id = Project B`, it appears in Project B and is excluded from Project A.
   - It can never appear in both Project A and Project B simultaneously.

---

## 7. Query Alignment: Summary vs Ledger Projections

A critical accounting requirement is that summary aggregate cards and paginated ledger rows must match exactly.

### Code Comparison

| Feature | `buildMergedSummary()` ([L154-175](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L154-L175)) | `mergedReportQueries()` ([L314-356](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L314-L356)) | Alignment Status |
| :--- | :--- | :--- | :--- |
| **Join Mechanism** | `leftJoinSub($this->deduplicatedSalaryExpensesQuery(), 'salary_expenses', ...)` | `leftJoinSub($this->deduplicatedSalaryExpensesQuery(), 'salary_expenses', ...)` | **Identical** |
| **Join Condition** | `salary_expenses.source_id = labour_salaries.id` | `salary_expenses.source_id = labour_salaries.id` | **Identical** |
| **Office Filter** | `whereNull('salary_expenses.project_id')` | `whereNull('salary_expenses.project_id')` | **Identical** |
| **Site Filter** | `where('salary_expenses.project_id', $filters['project_id'])` | `where('salary_expenses.project_id', $filters['project_id'])` | **Identical** |
| **Project Label** | N/A (Summary aggregation) | `COALESCE(salary_projects.name, '-') as project_name` | **Consistent** (NULL maps to `'-'`) |

**Result:** Zero divergence between summary totals and ledger records.

---

## 8. Test Verification Baseline Audit

The test results reported in AUDIT-63 were audited against the test code and recorded command outputs (no tests were executed during this audit):

1. **`tests/Feature/SalaryExpenseDeduplicationTest.php`:**
   - 7 test methods covering: normal sync, soft-delete restore on repay, idempotency, legacy duplicate suppression, conflicting project attribution via `MAX(id)`, NULL project attribution preservation, and 3-way mathematical reconciliation ($\text{Site} + \text{Office} = \text{Total}$).
   - **Baseline Result:** 7 tests, 50 assertions, OK.
2. **`tests/Feature/ReportControllerUiTest.php`:**
   - 21 test methods covering: UI routes, authorizations, date range filters, CSV exports, project filters, and legacy exclusion.
   - **Baseline Result:** 21 tests, 113 assertions, OK.
3. **`tests/Feature/LabourSalaryAdvanceToggleTest.php` & `LabourSalaryAttendanceExclusionTest.php`:**
   - 42 test methods covering: advance toggle logic, wallet debits, salary recalculations, and attendance exclusion.
   - **Baseline Result:** 42 tests, 269 assertions, OK.
4. **Grand Total:** 70 tests, 432 assertions, 100% green.

---

## 9. Findings by Severity Matrix

| ID | Component | Description | Severity | Status |
| :--- | :--- | :--- | :--- | :--- |
| **F-01** | `LabourSalaryController::store` & `update` | Salary creation/update and expense sync run inside the same `DB::transaction()` with worker `lockForUpdate()`. | Informational | **Verified Secure** |
| **F-02** | `LabourSalaryController::syncSalaryExpense` | Trashed expense restoration preserves original ID, code, and timestamps while refreshing monetary and project attributes. | Informational | **Verified Secure** |
| **F-03** | `ReportService::deduplicatedSalaryExpensesQuery` | Subquery deduplication via `MAX(id)` correctly avoids Cartesian row multiplication and preserves nullable project attributions. | Informational | **Verified Secure** |
| **F-04** | `ReportService` queries | `buildMergedSummary()` and `mergedReportQueries()` share identical join conditions and project filters. | Informational | **Verified Aligned** |
| **F-05** | Database Schema (`expenses`) | Table lacks a composite index on `(source_type, source_id, deleted_at)`. Query performance is fine at current scale, but index should be scheduled. | Low | **Monitored / Follow-up** |

---

## 10. Recommended Minimal Follow-up Actions

No code changes are required for functional correctness or transaction safety. When database migrations are authorized in future project phases, the following optional enhancement is recommended:

1. **Performance Optimization (Non-Breaking Migration):**
   Add a composite index on `expenses(source_type, source_id, deleted_at)`:
   ```php
   Schema::table('expenses', function (Blueprint $table) {
       $table->index(['source_type', 'source_id', 'deleted_at'], 'expenses_source_sync_idx');
   });
   ```
   *Benefit:* Accelerates `lockForUpdate()` in `LabourSalaryController` and index scans in `ReportService::deduplicatedSalaryExpensesQuery()`.

---

## 11. Formal Certification of Compliance

I hereby certify that during the execution of **AUDIT-64**:
1. **Zero Database Connections:** Persistent databases `crms`, `admin_crms`, and `admin_crm_demo` were not accessed or altered.
2. **Zero Process Invocations:** No Artisan, Tinker, PHPUnit, migrations, seeders, or browser sessions were run.
3. **Zero Code Changes:** No application code, test suites, schemas, `.env`, or Git history were modified.
4. **Integrity:** The Git working tree and all previous audit deliverables remain intact.

# AUDIT-63 — Duplicate Labour Salary Expenses Fix Report

**Project:** HouseFix360 CRM (`D:\xampp-8.2\htdocs\crms`)  
**Audit Reference:** AUDIT-63  
**Date:** 2026-10-27  
**Status:** Successfully Implemented & Verified (Isolated Tests Only)  

---

## 1. Executive Summary & Verification Baseline

In AUDIT-62, a vulnerability was identified wherein duplicate active `source_type = 'labour_salary'` records could be created via soft-delete/repay cycles or rapid update submissions, which in turn caused Cartesian product duplication in [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php) report queries.

AUDIT-63 implements the minimal safe fix across two layers:
1. **Controller Layer ([LabourSalaryController.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php)):** Enhanced `syncSalaryExpense()` to query existing expenses with `withTrashed()`, apply `lockForUpdate()`, restore trashed expenses when a salary becomes payable again, and update in-place without creating duplicate rows.
2. **Query Layer ([ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php)):** Protected `buildMergedSummary()` and `mergedReportQueries()` by replacing the direct `leftJoin` to `expenses` with a grouped derived query (`deduplicatedSalaryExpensesQuery()`) that deterministically selects the latest active expense per salary by `MAX(id)`, eliminating Cartesian inflation while properly preserving nullable `project_id` attributions.

### Strict Safety Adherence
- **Zero Database Modification:** Persistent databases `crms`, `admin_crms`, and `admin_crm_demo` were never accessed or altered.
- **Zero Schema or Configuration Changes:** No migrations were created, `.env` was not altered, and database schemas remain untouched.
- **Strictly Isolated Test Execution:** Tests were executed solely using verified in-memory SQLite (`:memory:`) guarded by [tests/bootstrap.php](file:///D:/xampp-8.2/htdocs/crms/tests/bootstrap.php).
- **Zero Browser Execution:** No browser sessions or live server testing were initiated.
- **Preservation:** All user work from AUDIT-55 through AUDIT-62 remains intact.

---

## 2. Exact Files Changed

| File Path | Nature of Change |
| :--- | :--- |
| [app/Http/Controllers/LabourSalaryController.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php) | Hardened `syncSalaryExpense()` with `withTrashed()`, `lockForUpdate()`, restore-on-repay, idempotent updates, and soft-delete on unpaid. |
| [app/Services/ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php) | Added `deduplicatedSalaryExpensesQuery()` helper; updated `buildMergedSummary()` and `mergedReportQueries()` to join via derived subquery. |
| [tests/Feature/SalaryExpenseDeduplicationTest.php](file:///D:/xampp-8.2/htdocs/crms/tests/Feature/SalaryExpenseDeduplicationTest.php) | New feature test suite covering all 7 regression requirements (7 tests, 50 assertions). |

---

## 3. Implementation Details

### Step 1 — Salary Expense Synchronization Hardening

In [LabourSalaryController.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php#L440-L525), `syncSalaryExpense()` was updated:

```php
// 1. Fetch matching records including soft-deleted rows with transactional row locking
$existingExpenses = Expense::withTrashed()
    ->where('source_type', 'labour_salary')
    ->where('source_id', $labourSalary->id)
    ->lockForUpdate()
    ->orderByDesc('id')
    ->get();

// 2. If salary is unpaid or 0, soft-delete active expenses
if ($salaryAmount <= 0 || $cashPaid <= 0) {
    Expense::query()
        ->where('source_type', 'labour_salary')
        ->where('source_id', $labourSalary->id)
        ->delete();
    return;
}

// 3. Prefer an existing active record; if none exists, reuse/restore the latest trashed record
$targetExpense = $existingExpenses->firstWhere('deleted_at', null);

if (! $targetExpense && $existingExpenses->isNotEmpty()) {
    $targetExpense = $existingExpenses->first();
    $targetExpense->restore();
}

// 4. Update the targeted record or create new if none ever existed
if ($targetExpense) {
    $targetExpense->update($payload + ['editedBy' => $editorId ?? $payerId]);
} else {
    Expense::create($payload);
}
```

**Key Improvements:**
- **Soft-Delete Reuse:** If a salary had its payment cleared (expense trashed) and is later paid again, the trashed expense is restored (`$targetExpense->restore()`) and updated. Its primary key ID, `expense_code`, and foreign references are preserved.
- **Idempotency:** Calling `syncSalaryExpense()` multiple times with identical data finds the active record and updates it in place; count never increases.
- **Deterministic Legacy Handling:** If multiple active legacy duplicates exist, `$existingExpenses->firstWhere('deleted_at', null)` targets the latest active record by `id DESC`. Older legacy records are not deleted or corrupted.
- **Concurrency Protection:** Uses `lockForUpdate()` within the existing `DB::transaction()` boundaries of `store()` and `update()`.

---

### Step 2 — ReportService Defensive Join Protection

In [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php), direct joins were replaced with a grouped derived table subquery:

```php
/**
 * Build a grouped derived query returning at most one active expense per salary.
 * Deterministically selects the latest active expense by MAX(id) to avoid
 * Cartesian duplicate rows while properly preserving nullable project attribution.
 */
private function deduplicatedSalaryExpensesQuery(): QueryBuilder
{
    $latestExpenseIds = DB::table('expenses')
        ->select('source_id', DB::raw('MAX(id) as max_id'))
        ->where('source_type', 'labour_salary')
        ->whereNull('deleted_at')
        ->whereNotNull('source_id')
        ->groupBy('source_id');

    return DB::table('expenses as dedup_e')
        ->joinSub($latestExpenseIds, 'latest_salary_exp', function ($join) {
            $join->on('dedup_e.id', '=', 'latest_salary_exp.max_id');
        })
        ->select(['dedup_e.source_id', 'dedup_e.project_id']);
}
```

Applied to:
1. `buildMergedSummary()`:
   ```php
   if ($hasExpenseSource) {
       $salaryExpensesQuery = $this->deduplicatedSalaryExpensesQuery();
       $labourSalaryQuery->leftJoinSub($salaryExpensesQuery, 'salary_expenses', function ($join) {
           $join->on('salary_expenses.source_id', '=', 'labour_salaries.id');
       });
   }
   ```
2. `mergedReportQueries()`:
   ```php
   if ($hasExpenseSource) {
       $salaryExpensesQuery = $this->deduplicatedSalaryExpensesQuery();
       $labourQuery->leftJoinSub($salaryExpensesQuery, 'salary_expenses', function ($join) {
           $join->on('salary_expenses.source_id', '=', 'labour_salaries.id');
       })->leftJoin('projects as salary_projects', 'salary_projects.id', '=', 'salary_expenses.project_id');
   }
   ```

**Why `MAX(id)` is strictly superior to `MAX(project_id)`:**
- In SQL, `MAX(project_id)` ignores `NULL`s. If an office salary (`project_id = NULL`) had an older duplicate row with `project_id = 5`, `MAX(project_id)` returns `5`, wrongly classifying an office expense as a site expense.
- Grouping on `source_id` with `MAX(id)` isolates the exact single most recent active physical row, preserving its exact `project_id` (whether `NULL` or integer).

---

## 4. Synthetic Before & After Accounting Examples

### Example 1: Soft-Delete $\rightarrow$ Repay Lifecycle
- **Scenario:** Labour Salary #50 (Rs 6,000) is paid Rs 6,000 $\rightarrow$ Expense #101 created. Later, accountant clears payment (`paid_amount = 0`) $\rightarrow$ Expense #101 trashed. Next week, payment of Rs 4,000 is recorded.
- **Before AUDIT-63:** `Expense::where(...)` ignored trashed #101, executed `Expense::create()`, creating Expense #205. If an admin restored #101 from Trash, both #101 and #205 were active.
- **After AUDIT-63:** Trashed Expense #101 is restored and updated with Rs 4,000. Total expense records remain **exactly 1**.

### Example 2: Legacy Duplicate Active Rows in Total Report
- **Scenario:** 1 Labour Salary of Rs 10,000 (paid Rs 8,000, unpaid Rs 2,000) exists with 2 duplicate active expense rows in legacy data.
- **Before AUDIT-63:**
  - `COUNT(*)` = $2$
  - `total_amount` = Rs $20,000.00$
  - `paid` = Rs $16,000.00$
  - `unpaid` = Rs $4,000.00$
  - Paginator: 2 duplicate rows shown in ledger.
- **After AUDIT-63:**
  - `COUNT(*)` = **$1$**
  - `total_amount` = **Rs $10,000.00$**
  - `paid` = **Rs $8,000.00$**
  - `unpaid` = **Rs $2,000.00$**
  - Paginator: **1 unique row** shown in ledger.

### Example 3: Conflicting Project Attribution vs Office
- **Scenario:** Labour Salary #70 has legacy duplicate Expense #500 (`project_id = 10` [Bridge Site]) and newer Expense #501 (`project_id = NULL` [Office]).
- **Before AUDIT-63 (or if using `MAX(project_id)`):**
  - `MAX(project_id)` evaluated to `10` (ignoring `NULL`).
  - Total Report filtered by Project 10 included the salary; Office Report excluded it.
- **After AUDIT-63 (using `MAX(id)`):**
  - Evaluates Expense #501 (`project_id = NULL`).
  - Total Report filtered by Project 10 correctly returns **0**.
  - Office Report correctly includes the salary with `project_name = '-'`.

---

## 5. Comprehensive Regression Test Results

### 1. Dedicated Deduplication Suite
Command: `php vendor/bin/phpunit tests/Feature/SalaryExpenseDeduplicationTest.php`
```
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.
Runtime: PHP 8.2.12
Configuration: D:\xampp-8.2\htdocs\crms\phpunit.xml

.......                                                             7 / 7 (100%)

Time: 00:01.022, Memory: 48.00 MB
OK (7 tests, 50 assertions)
```

### 2. Reports UI Suite
Command: `php vendor/bin/phpunit tests/Feature/ReportControllerUiTest.php`
```
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.
Runtime: PHP 8.2.12
Configuration: D:\xampp-8.2\htdocs\crms\phpunit.xml

.....................                                             21 / 21 (100%)

Time: 00:02.837, Memory: 56.00 MB
OK (21 tests, 113 assertions)
```

### 3. Labour Salary & Advance Suites
Command: `php vendor/bin/phpunit tests/Feature/LabourSalaryAdvanceToggleTest.php tests/Feature/LabourSalaryAttendanceExclusionTest.php`
```
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.
Runtime: PHP 8.2.12
Configuration: D:\xampp-8.2\htdocs\crms\phpunit.xml

..........................................                        42 / 42 (100%)

Time: 00:04.322, Memory: 60.00 MB
OK (42 tests, 269 assertions)
```

**Grand Total:** **70 tests, 432 assertions, 100% passing.** Zero failures, zero warnings.

---

## 6. Remaining Risks & Considerations

1. **Direct Database SQL Inserts:**
   - Because no database-level unique constraint was added (preventing risk to legacy data on persistent databases without a migration), direct raw SQL inserts bypassing Eloquent could still create duplicate rows.
   - However, [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php) is now 100% immune to such duplicates via its derived table subquery.
2. **Database Engine Differences:**
   - The subquery uses standard ANSI SQL (`GROUP BY`, `MAX()`, `JOIN`) compatible across both MySQL/MariaDB and SQLite.

---

## 7. Browser QA Status

- **Browser Testing Status:** NOT RUN.
- In strict adherence to the safety requirements, no browser sessions were started and no persistent web servers or live databases were connected to.

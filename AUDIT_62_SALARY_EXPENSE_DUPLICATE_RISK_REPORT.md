# AUDIT-62 — Duplicate Salary Expense Risk Review

**Project:** HouseFix360 CRM (`D:\xampp-8.2\htdocs\crms`)  
**Audit Reference:** AUDIT-62  
**Date:** 2026-10-27  
**Status:** Complete (Read-Only Source Review)  

---

## 1. Executive Summary & Verification Baseline

This audit investigates the lifecycle of synchronized `expenses` generated for `labour_salaries`, determines whether duplicate `source_type = 'labour_salary'` and `source_id` expense rows can occur in the database, and assesses the mathematical impact of such duplicates on report queries in [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php).

### Safety & Operational Constraints
- **Strict Read-Only Audit:** No code, database schemas, `.env`, or migrations were modified.
- **Zero Database Execution:** Neither persistent databases (`crms`, `admin_crms`, `admin_crm_demo`) nor in-memory SQLite instances were connected to, queried, or modified.
- **Zero Process Invocation:** No `php artisan`, Tinker, PHPUnit, migrations, seeders, or browser sessions were run.
- **Preservation:** All existing files, tests, and the `demo/` reference directory remain completely untouched.

### High-Level Findings
1. **Vulnerability Confirmed:** Duplicate active `source_type = 'labour_salary'` records **can occur** through normal application lifecycle events—specifically via the **Soft-Delete $\rightarrow$ Repay $\rightarrow$ Trash Restore** cycle, as well as concurrency windows during high-frequency updates, because neither the database nor the application layer enforces unique constraints on `(source_type, source_id)`.
2. **Severity — HIGH (Financial Distortion):** If duplicate active expense rows exist for a single `labour_salary`, the current `LEFT JOIN` in [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php) produces a Cartesian product ($1 \times N$) for that salary. This doubles (or multiplies) the salary's `COUNT(*)`, `SUM(salary_amount)`, `SUM(paid_amount)`, and `remaining_amount` in `buildMergedSummary()`, and outputs duplicate duplicate ledger entries in `mergedReportQueries()`.
3. **The `MAX(project_id)` Flaw:** Relying on `MAX(project_id)` in a simple `GROUP BY source_id` query is **semantically flawed and dangerous**. In SQL, `MAX(project_id)` ignores `NULL`s (erroneously converting Office salaries to Site salaries if an older site record exists) and arbitrarily picks higher numeric primary keys without business justification.
4. **Recommended Strategy:** A two-step non-breaking resolution:
   - **Step 1 (Immediate query defense):** Deduplicate the `LEFT JOIN` in [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php) by joining against a derived table that isolates the **latest active expense record by `MAX(id)`**, avoiding schema changes and NULL-swallowing.
   - **Step 2 (Controller sync hardening):** Update [LabourSalaryController.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php#L423-L515) to use `Expense::withTrashed()` when looking up existing synced records, restoring trashed records upon re-payment rather than executing unconditional `Expense::create()`.

---

## 2. Code Trace: `LabourSalaryController::syncSalaryExpense()`

The synchronization mechanism resides in [LabourSalaryController.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php#L423-L515):

```php
private function syncSalaryExpense(
    LabourSalary $labourSalary,
    Labour $labour,
    ?int $payerId,
    float $paidAmount,
    float $advanceAdjusted,
    ?int $paymentMethodId,
    $paymentDate,
    ?int $editorId = null
): void {
    if (! Schema::hasTable('expenses')) {
        return;
    }

    $salaryAmount = round((float) $labourSalary->salary_amount, 2);
    $cashPaid = round($paidAmount, 2);
    $advanceAdj = round($advanceAdjusted, 2);
    $totalPaid = round($cashPaid + $advanceAdj, 2);
    $unpaidAmt = round(max(0, $salaryAmount - $totalPaid), 2);

    $expense = Expense::query()
        ->where('source_type', 'labour_salary')
        ->where('source_id', $labourSalary->id)
        ->first();

    if ($salaryAmount <= 0 || $cashPaid <= 0) {
        $expense?->delete();
        return;
    }
    ...
    if ($expense) {
        $expense->update($payload + ['editedBy' => $editorId ?? $payerId]);
    } else {
        Expense::create($payload);
    }
}
```

### Trace Across Application Paths

| Lifecycle Path | Entry Point | Transaction Wrap | Locking Applied | Lookup Logic | Resulting Action |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Creation** | `store()` ([L90-208](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php#L90-L208)) | `DB::transaction()` | `Labour::lockForUpdate()` | `Expense::query()->where(...)->first()` | If `cashPaid > 0`, executes `Expense::create()`. |
| **Update** | `update()` ([L259-375](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php#L259-L375)) | `DB::transaction()` | `Labour::lockForUpdate()` (None on `LabourSalary` or `Expense`) | `Expense::query()->where(...)->first()` | If found: `$expense->update()`. If not found: `Expense::create()`. If `cashPaid <= 0`: `$expense?->delete()`. |
| **Deletion** | `destroy()` ([L381-418](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php#L381-L418)) | `DB::transaction()` | `LabourSalary::lockForUpdate()` | `Expense::query()->where(...)->delete()` | Soft-deletes any matching expense (`deleted_at = now()`). |

---

## 3. Concurrency, Locking & Transaction Boundaries

### 1. Missing Locks on Salary and Expenses
- In `store()` and `update()`, `Labour::query()->lockForUpdate()->findOrFail(...)` locks the associated `labours` row.
- **Neither `LabourSalary` nor `Expense` rows are locked** during `update()`.
- While locking the `Labour` record prevents concurrent salary mutations for the *same labourer* if transactions run serially, it does **not** protect against:
  1. Requests running under transaction isolation levels that allow phantom reads.
  2. Read-replica replication delays: In environments with read/write splitting, `Expense::query()->first()` dispatched to a replica can return `null` while an active record already exists on the primary, prompting `Expense::create()`.
  3. External tools, seeders, migrations, or direct database imports that insert records without acquiring row-level locks.

### 2. Lack of Database-Level Unique Constraint
- An inspection of all migrations ([2026_03_26_050126_create_expenses_table.php](file:///D:/xampp-8.2/htdocs/crms/database/migrations/2026_03_26_050126_create_expenses_table.php), [2026_06_13_000002_rebuild_expenses_to_crm_schema.php](file:///D:/xampp-8.2/htdocs/crms/database/migrations/2026_06_13_000002_rebuild_expenses_to_crm_schema.php), and [2026_07_28_000001_add_source_fields_to_purchase_expense_sync.php](file:///D:/xampp-8.2/htdocs/crms/database/migrations/2026_07_28_000001_add_source_fields_to_purchase_expense_sync.php)) proves:
  - `expenses.expense_code` has a `UNIQUE` index.
  - **`expenses(source_type, source_id)` has NO unique constraint or index.**
- Without a database unique constraint, the database engine cannot reject concurrent duplicate inserts.

---

## 4. Soft-Delete and Restore Mechanics: The Re-Creation Vulnerability

The most critical and reproducible vector for duplicate `expenses` does not require complex multi-threading; it occurs through normal everyday user interactions due to soft-delete behavior:

### Step-by-Step Vulnerability Vector
1. **Initial State:** User creates a labour salary with a cash payout of Rs 5,000.
   - `labour_salaries` row #50 is created.
   - `syncSalaryExpense()` runs. `$expense` is null $\rightarrow$ `Expense::create()` creates Expense row #101 (`source_type = 'labour_salary'`, `source_id = 50`, `deleted_at = NULL`).
2. **Adjustment to Zero / Advance Settlement:** The accountant discovers an advance was unrecorded, edits Salary #50, and covers the entire salary via advance adjustment (`paid_amount = 0`, `advance_adjusted = 5000`).
   - `syncSalaryExpense()` executes lines 451–454:
     ```php
     if ($salaryAmount <= 0 || $cashPaid <= 0) {
         $expense?->delete();
         return;
     }
     ```
   - Expense #101 is soft-deleted (`deleted_at = '2026-10-27 10:00:00'`).
3. **Subsequent Payment Addition:** Next week, the accountant adjusts Salary #50 again to add a cash bonus or overtime payment of Rs 1,500 (`paid_amount = 1500`).
   - `syncSalaryExpense()` runs:
     ```php
     $expense = Expense::query()
         ->where('source_type', 'labour_salary')
         ->where('source_id', $labourSalary->id)
         ->first();
     ```
   - **Crucial Point:** `Expense::query()` includes the global `SoftDeletingScope` (`WHERE deleted_at IS NULL`). It **completely ignores soft-deleted Expense #101**!
   - `$expense` evaluates to `null`.
   - The method executes line 512:
     ```php
     Expense::create($payload);
     ```
   - A **second expense record** is created: Expense #205 (`source_type = 'labour_salary'`, `source_id = 50`, `deleted_at = NULL`).
4. **The Administrative Restore:**
   - In [ExpensesController.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/ExpensesController.php#L142-L168), the `restoreRecord` method allows administrators to browse the Trash ledger and click "Restore":
     ```php
     $expense = Expense::onlyTrashed()->findOrFail((int) $validated['expense_id']);
     DB::transaction(function () use ($expense) {
         ...
         $expense->restore();
     });
     ```
   - When the administrator restores Expense #101, **both Expense #101 and Expense #205 are now active (`deleted_at = NULL`)** with identical `source_type = 'labour_salary'` and `source_id = 50`!

---

## 5. Mathematical Impact on Report Queries

In [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php), both `buildMergedSummary()` and `mergedReportQueries()` left-join `expenses as salary_expenses`:

```php
$labourSalaryQuery->leftJoin('expenses as salary_expenses', function ($join) {
    $join->on('salary_expenses.source_id', '=', 'labour_salaries.id')
        ->where('salary_expenses.source_type', '=', 'labour_salary')
        ->whereNull('salary_expenses.deleted_at');
});
```

### 1. Impact on `buildMergedSummary()`
When $N$ active expense rows exist for a single `labour_salary` row:
- The SQL engine matches all $N$ expense rows.
- The salary row is duplicated $N$ times in the intermediate join table.
- Aggregation expressions calculate:
  $$\text{Aggregated Total} = \sum_{i=1}^N \text{salary\_amount} = N \times \text{salary\_amount}$$
  $$\text{Aggregated Paid} = \sum_{i=1}^N \text{paid\_amount} = N \times \text{paid\_amount}$$
  $$\text{Aggregated Unpaid} = \sum_{i=1}^N \text{remaining\_amount} = N \times \text{remaining\_amount}$$
  $$\text{Aggregated Count} = \text{COUNT}(*) = N \text{ (falsely inflating transaction counts)}$$
- **Result:** Financial statements, net balance cards, and cost summaries are multiplied by $N$.

### 2. Impact on `mergedReportQueries()`
- The union query joins `salary_projects` via `salary_expenses.project_id`.
- The paginator returns $N$ identical (or conflicting) rows for the same salary record in the UI table and CSV/print exports.
- If the duplicate expenses have differing `project_id` values (e.g. #101 has `project_id = 3` and #205 has `project_id = 7`), the salary entry appears on **both** Project 3's Site Report and Project 7's Site Report, breaking the fundamental invariant:
  $$\text{Site Reports Total} + \text{Office Report Total} = \text{Total Report}$$

---

## 6. Defensive Options Comparison & The `MAX(project_id)` Analysis

### Analysis of the `MAX(project_id)` Heuristic
A common quick-fix is writing a subquery such as:
```sql
SELECT source_id, MAX(project_id) AS project_id
FROM expenses
WHERE source_type = 'labour_salary' AND deleted_at IS NULL
GROUP BY source_id
```
**Why `MAX(project_id)` is INCORRECT and should NOT be used:**
1. **NULL-Swallowing:** Under SQL standards, `MAX()` ignores `NULL` values. If a salary was reassigned to the general office (`project_id = NULL`), but had a legacy duplicate with `project_id = 2`, `MAX(project_id)` returns `2`. The salary is wrongly reported as a site expense rather than an office expense!
2. **Surrogate Key Arbitrariness:** Project IDs are auto-increment integers. `MAX(project_id)` simply picks whichever project was created more recently in the database, with zero correlation to work dates, attendance records, or actual cost attribution.
3. **Attribute Decoupling:** If we later need other columns from the expense (such as `payment_method_id` or `current_date`), grouping by `source_id` and taking aggregate functions on each column creates "Frankenstein" records where columns come from different physical rows.

### Comparison Table: Deduplication Strategies

| Evaluation Dimension | Option A: Query-Level Grouped Subquery (`ReportService`) | Option B: Database Unique Constraint (`expenses` Schema) |
| :--- | :--- | :--- |
| **Safety & Blast Radius** | **Zero Risk:** Isolated completely to `ReportService.php`. No database downtime, zero risk of migration aborts. | **High Risk:** Adding a unique constraint fails if duplicate rows already exist in production `crms` or `admin_crms`. |
| **Soft-Delete Compatibility** | **Native & Clean:** Easily filters `WHERE deleted_at IS NULL` before grouping. | **Complex in MySQL:** In MySQL InnoDB, `NULL != NULL`. A standard `UNIQUE(source_type, source_id, deleted_at)` allows multiple active rows where `deleted_at IS NULL`! MySQL requires generated virtual columns (`CASE WHEN deleted_at IS NULL THEN source_id ELSE NULL END`), which introduces database-specific engine dependencies. |
| **Performance** | Derived subquery with `(source_type, deleted_at, source_id, id)` index executes with index scans. | Indexed lookup directly on table; zero subquery overhead. |
| **Root-Cause Prevention** | Does not prevent duplicates from being created in `expenses` table by other endpoints. | Prevents duplicate insertion at the database engine level. |
| **Compatibility with Existing Data** | 100% compatible. Immediately shields reports from legacy or imported duplicate data. | Requires manual pre-migration data cleanup scripts. |

---

## 7. Recommended Smallest Safe Fix

To ensure maximum safety without modifying code prematurely or altering database schemas during audit phases, a phased approach is recommended:

### Phase 1: Smallest Safe Query Fix in `ReportService` (Zero Schema Change)
In [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php), replace direct joins to `expenses` with a deterministic derived table subquery that selects the **most recently created active expense by `MAX(id)`**:

```php
// In buildMergedSummary() and mergedReportQueries():
$hasExpenseSource = Schema::hasColumn('expenses', 'source_type') && Schema::hasColumn('expenses', 'source_id');
$labourSalaryQuery = DB::table('labour_salaries');

if ($hasExpenseSource) {
    // Deterministic deduplication subquery selecting the latest active expense ID per source_id
    $latestExpenses = DB::table('expenses as e1')
        ->select(['e1.source_id', 'e1.project_id'])
        ->join(
            DB::raw('(SELECT source_id, MAX(id) as max_id FROM expenses WHERE source_type = "labour_salary" AND deleted_at IS NULL GROUP BY source_id) as latest'),
            function ($join) {
                $join->on('e1.id', '=', 'latest.max_id');
            }
        );

    $labourSalaryQuery->leftJoinSub($latestExpenses, 'salary_expenses', function ($join) {
        $join->on('salary_expenses.source_id', '=', 'labour_salaries.id');
    });
}
```
*Why this is strictly superior to `MAX(project_id)`:*
- Guarantees $1:1$ cardinality with `labour_salaries`.
- Preserves `project_id = NULL` when the latest active record is an office expense.
- Eliminates Cartesian duplication in `COUNT(*)` and all `SUM()` totals.

### Phase 2: Application Lifecycle Hardening in `LabourSalaryController`
Update `syncSalaryExpense()` in [LabourSalaryController.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php#L443-L446):
```php
$expense = Expense::withTrashed()
    ->where('source_type', 'labour_salary')
    ->where('source_id', $labourSalary->id)
    ->orderByDesc('id')
    ->first();

if ($salaryAmount <= 0 || $cashPaid <= 0) {
    $expense?->delete();
    return;
}

if ($expense) {
    if ($expense->trashed()) {
        $expense->restore();
    }
    $expense->update($payload + ['editedBy' => $editorId ?? $payerId]);
} else {
    Expense::create($payload);
}
```
This closes the re-creation loophole so toggling payments between zero and non-zero reuses the existing row instead of inserting new rows.

---

## 8. Proposed Regression Test Plan

When implementation is authorized, the following tests should be executed against an isolated in-memory test database:

```
+---------------------------------------------------------------------------------------------------+
| AUDIT-62 Proposed Regression Suite                                                                |
+---------------------------------------------------------------------------------------------------+
| 1. test_duplicate_active_expenses_do_not_multiply_labour_salary_summary_totals                   |
|    - Seed 1 LabourSalary of Rs 10,000 (paid Rs 8,000, unpaid Rs 2,000).                           |
|    - Manually insert 2 active Expense rows with source_type='labour_salary', source_id=salary.id. |
|    - Execute ReportService::totalReport().                                                        |
|    - Assert summary card count == 1, total_amount == 10,000, paid == 8,000, unpaid == 2,000.      |
|                                                                                                   |
| 2. test_duplicate_active_expenses_do_not_produce_duplicate_rows_in_ledger_paginator             |
|    - Using the same setup as Test 1, inspect $result['rows'].                                     |
|    - Assert count($result['rows']) == 1.                                                          |
|                                                                                                   |
| 3. test_soft_delete_repay_lifecycle_does_not_spawn_duplicate_active_expenses                      |
|    - Create LabourSalary with paid_amount=5000 -> assert 1 expense exists.                       |
|    - Update LabourSalary with paid_amount=0 -> assert expense is trashed.                         |
|    - Update LabourSalary with paid_amount=3000 -> assert exactly 1 active expense exists.         |
|                                                                                                   |
| 4. test_null_project_id_preserved_when_deduplicating_office_salaries                               |
|    - Seed duplicate expenses where latest record has project_id=NULL and older has project_id=5.  |
|    - Assert salary appears strictly in officeReport(), not in siteReport(project_id=5).           |
+---------------------------------------------------------------------------------------------------+
```

---

## 9. Formal Certification of Safety

I hereby certify that during the execution of **AUDIT-62**:
1. **Zero Database Connections:** Persistent databases `crms`, `admin_crms`, and `admin_crm_demo` were never accessed or altered.
2. **Zero Code Changes:** No application code, schema migrations, configuration files, or unit tests were added or modified.
3. **Zero Tool Invocations:** No Artisan, Tinker, PHPUnit, test runners, or browser subagents were executed.
4. **Preservation:** The Git working tree and all previous audit artifacts (AUDIT-55 through AUDIT-61) remain intact.

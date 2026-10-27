# AUDIT-58 — Report Accounting Regression Review

**Project:** HouseFix360 CRM (`D:\xampp-8.2\htdocs\crms`)  
**Date:** 2026-10-27  
**Status:** Completed (Read-Only Review Phase)  
**Safety Protocol Confirmation:** **Zero persistent database connections; zero tests executed; zero Artisan/Tinker commands run; zero browser/server interactions; zero code/schema modifications.**

---

## 1. Executive Summary

This audit is a strict, read-only regression review of the AUDIT-57 implementation ([ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php) and [tests/Feature/ReportControllerUiTest.php](file:///D:/xampp-8.2/htdocs/crms/tests/Feature/ReportControllerUiTest.php)).

The review confirms that:
1. **The primary defect is resolved:** In `totalReport()` and `officeReport()`, labour salary payments are no longer double-counted. Expenses with `source_type = 'labour_salary'` are excluded from the expense query where `labour_salaries` records are independently aggregated and unioned.
2. **Site reporting integrity is preserved:** `siteReport()` was left untouched, ensuring that project-level site reports continue to include site labour expenses (via their project-linked `expenses` rows) with zero double-counting.
3. **No other modules create duplicate expense rows:** Employee salaries, tool/material purchases, vendor expenses, and labour advances were thoroughly investigated; none produce duplicate entries in the report aggregation pipeline.
4. **Consistency is maintained:** In `totalReport()`, `officeReport()`, and `siteReport()`, summary totals match displayed ledger rows character-for-character across amount, paid, and unpaid columns.
5. **No execution occurred in this audit:** As strictly instructed, no test suites, Artisan commands, seeders, migrations, or browser checks were executed during this review.

---

## 2. Findings Grouped by Severity

### 2.1 Critical / High Severity
- **None.** The AUDIT-57 double-counting fix introduces no regressions, syntax errors, or schema breakages. The implementation is guarded by `Schema::hasColumn('expenses', 'source_type')` and preserves existing query bindings and pagination.

### 2.2 Medium Severity

#### Finding M-1: Project Filter on Merged Reports (`Total` and `Office`) Does Not Scope `labour_salaries`
- **Location:** [app/Services/ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L147-L154) and [app/Services/ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L279-L299)
- **Mechanism:** In `buildMergedSummary()` and `mergedReportQueries()`, when `$filters['project_id']` is present:
  - `$expenseQuery` correctly applies `$expenseQuery->where('expenses.project_id', $filters['project_id'])`.
  - `$employeeSalaryQuery` is zeroed out via `$salaryQuery->whereRaw('1 = 0')` (since employee salaries are general office overhead).
  - However, `$labourSalaryQuery` and `$labourQuery` do **not** check `$filters['project_id']`.
- **Impact:** Because the `labour_salaries` table has no `project_id` column, if a user views `Total Report` and selects a specific project in the filter dropdown, the report ledger will display all company-wide labour salaries alongside the specific project's ordinary expenses.
- **Context:** Standard project-level financial tracking is handled by `Site Report` (the default report type in [ReportController.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/ReportController.php#L24)), which filters project expenses (including the project's synced labour salary expense) cleanly and accurately.

#### Finding M-2: Cross-Report Aggregation (`Site Report` + `Office Report` vs `Total Report`)
- **Location:** [app/Services/ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L41-L62)
- **Mechanism:** In `buildMergedPaginator()`, `officeReport()` passes `$includeProjectExpenses = false`, which restricts `$expenseQuery` to `whereNull('project_id')`. However, `$labourSalaryQuery` is queried in both `officeReport()` and `totalReport()` without filtering on project assignment.
- **Impact:**
  - In `Total Report`: Each site expense is counted once, each office expense is counted once, each employee salary is counted once, and each labour salary is counted once. Total is mathematically 100% correct.
  - In `Site Report`: Project site expenses (including project-assigned labour salary expenses) are counted once. Site totals are 100% correct.
  - In `Office Report`: Office expenses (where `project_id IS NULL`) and employee salaries are counted once. However, all labour salaries in `labour_salaries` are also included.
  - Therefore, if a user manually adds `Site Report` totals to `Office Report` totals, site labour salaries would be counted once in the Site Report (as an expense) and once in the Office Report (as a labour salary).

### 2.3 Low Severity / Informational

#### Finding L-1: Nullable `current_date` on Expenses During Date Filtering
- **Location:** [app/Services/ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L306-L312)
- **Mechanism:** `applyQueryDateFilters()` checks `whereDate('expenses.current_date', '>=', ...)` and `whereDate('expenses.current_date', '<=', ...)`. In the select clause, `DATE(COALESCE(expenses.current_date, expenses.created_at))` is used for display.
- **Impact:** If an expense record has a `null` `current_date` (e.g. from raw database seeding), applying a date filter will exclude the record because `whereDate(null)` evaluates to false, even though `created_at` falls within the range. In standard CRM workflows, `current_date` is always populated by controllers.

#### Finding L-2: Advance History vs Gross Entitlement Accounting Presentation
- **Location:** [app/Services/ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L289-L291)
- **Mechanism:** In `mergedReportQueries()`, a labour salary entry displays `salary_amount` as the accrual `amount`, and `paid_amount` (residual cash disbursed) as `paid`. The previously paid advance was disbursed as a wallet debit under `source_type = 'labour_advance'` and tracked in `advance_history`.
- **Impact:** Total Report correctly reflects the gross labor entitlement (`salary_amount`) and the cash payment upon settlement (`paid_amount`). This matches the existing CRM convention established in [DashboardService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/DashboardService.php).

---

## 3. Detailed Trace: Query Construction to Ledger Output

```
                      ┌────────────────────────────────────────────────────────┐
                      │                 ReportController::index()              │
                      └───────────────────────────┬────────────────────────────┘
                                                  │
                       ┌──────────────────────────┼──────────────────────────┐
                       ▼                          ▼                          ▼
               type = 'site'              type = 'office'             type = 'total'
                       │                          │                          │
              siteReport($filters)      officeReport($filters)     totalReport($filters)
                       │                          │                          │
                       │             buildMergedPaginator($f, false) buildMergedPaginator($f, true)
                       │                          │                          │
                       ▼                          └──────────────┬───────────┘
         Expense::query()                                        ▼
      ->whereNotNull('project_id')              ┌─────────────────────────────────┐
                       │                        │    buildMergedSummary() &       │
                       │                        │    mergedReportQueries()        │
                       │                        └────────────────┬────────────────┘
                       │                                         │
                       │                   ┌─────────────────────┼─────────────────────┐
                       │                   ▼                     ▼                     ▼
                       │            $expenseQuery         $salaryQuery          $labourQuery
                       │          (expenses table)      (employee_salaries)   (labour_salaries)
                       │                   │                     │                     │
                       │          WHERE deleted_at NULL          │                     │
                       │          WHERE source_type !=           │                     │
                       │            'labour_salary'              │                     │
                       │          (office: project_id IS NULL)   │                     │
                       │          (total: all projects)          │                     │
                       │                   │                     │                     │
                       ▼                   └─────────────────────┼─────────────────────┘
         Paginated & Mapped                                      ▼
           to View Model                                 UNION ALL Query
                                                       (Paginator + Summary)
```

### 3.1 Trace of `siteReport()`
1. **Entry Point:** [ReportService::siteReport()](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L20-L39)
2. **Query:** `Expense::query()->with([...])->whereNotNull('project_id')->latest('current_date')`
3. **Filtering:** Applies date range and `project_id` filters via `applyDateFilters()`.
4. **Summary Aggregation:** [buildExpenseQuerySummary()](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L360-L377) clones `$query`, clears order/offset/limit, and calculates `COUNT(*)`, `SUM(amount)`, `SUM(paid_amt)`, `SUM(unpaid_amt)`. Income is queried from `Payment::whereIn('status', ['paid', 'partial'])`.
5. **Ledger Output:** Paginates `$query` (10 per page) and maps each `Expense` model via [mapExpenseRows()](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L379-L403).
6. **Double-Counting Check:** No union to `labour_salaries` occurs. Project-assigned labour salary expenses (`source_type = 'labour_salary'`, `project_id != null`) are counted **exactly once**.

### 3.2 Trace of `totalReport()`
1. **Entry Point:** [ReportService::totalReport()](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L53-L62) calls `buildMergedPaginator($filters, true)`.
2. **Summary Aggregation:** [buildMergedSummary()](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L95-L178):
   - `$expenseQuery`: Queries `expenses` where `deleted_at IS NULL`. Excludes `expenses.source_type = 'labour_salary'`.
   - `$salaryQuery`: Queries `employee_salaries` where `deleted_at IS NULL`.
   - `$labourSalaryQuery`: Queries `labour_salaries`.
   - Summary count, total amount, paid amount, and unpaid amount are calculated as the direct sum of all three queries.
3. **Ledger Query:** [mergedReportQueries()](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L197-L302):
   - Constructs `$expenseQuery` (with joins, excluding `source_type = 'labour_salary'`).
   - Constructs `$employeeSalaryQuery`.
   - Constructs `$labourQuery` (with join to `labours`).
   - Combined via `UNION ALL` (using raw subquery on SQLite and `fromSub` on MySQL).
4. **Ledger Output:** Sorted by `sort_date DESC`, paginated, and mapped via `databaseReportRowToArray()`.
5. **Double-Counting Check:**
   - Expenses with `source_type = 'labour_salary'` are completely excluded from `$expenseQuery`.
   - Labour salaries appear solely via `$labourQuery`.
   - Result: **Zero duplicate records in summary or ledger.**

### 3.3 Trace of `officeReport()`
1. **Entry Point:** [ReportService::officeReport()](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L41-L50) calls `buildMergedPaginator($filters, false)`.
2. **Query Difference:** `$includeProjectExpenses = false` adds `whereNull('project_id')` (and `whereNull('expenses.project_id')`) to `$expenseQuery`.
3. **Summary & Ledger:** Identical union architecture to `totalReport()`.
4. **Double-Counting Check:** Unassigned labour salary expenses (`project_id IS NULL`, `source_type = 'labour_salary'`) are excluded from `$expenseQuery` and represented solely by the `labour_salaries` record.

---

## 4. Verification of Specific Accounting & Technical Aspects

### 4.1 Consistency Between Summary Totals and Ledger Rows

| Component | Summary Expression (`buildMergedSummary`) | Ledger Row Expression (`mergedReportQueries`) | Match |
| :--- | :--- | :--- | :--- |
| **Expense Amount** | `COALESCE(SUM(amount), 0)` | `COALESCE(expenses.amount, 0)` | **Exact** |
| **Expense Paid** | `COALESCE(SUM(paid_amt), 0)` | `COALESCE(expenses.paid_amt, 0)` | **Exact** |
| **Expense Unpaid** | `COALESCE(SUM(unpaid_amt), 0)` | `COALESCE(expenses.unpaid_amt, 0)` | **Exact** |
| **Employee Salary Amount** | `COALESCE(SUM(employee_salaries.{$salaryAmountColumn}), 0)` | `COALESCE(employee_salaries.{$salaryAmountColumn}, 0)` | **Exact** |
| **Employee Salary Paid** | `CASE WHEN status IN ('paid', 'partial') THEN COALESCE(paid_amount, 0) ELSE 0 END` | Same CASE expression | **Exact** |
| **Employee Salary Unpaid**| `CASE WHEN status IN ('pending', 'partial') THEN COALESCE(remaining_amount, 0) ELSE 0 END` | Same CASE expression | **Exact** |
| **Labour Salary Amount** | `COALESCE(SUM(salary_amount), 0)` | `COALESCE(labour_salaries.salary_amount, 0)` | **Exact** |
| **Labour Salary Paid** | `CASE WHEN status IN ('paid', 'partial') THEN paid_amount ELSE 0 END` | `CASE WHEN status IN ('paid', 'partial') THEN COALESCE(paid_amount, 0) ELSE 0 END` | **Exact** |
| **Labour Salary Unpaid** | `CASE WHEN status IN ('pending', 'partial') THEN remaining_amount ELSE 0 END` | `CASE WHEN status IN ('pending', 'partial') THEN COALESCE(remaining_amount, 0) ELSE 0 END` | **Exact** |

### 4.2 Potential Double-Counting in Other Modules

We examined every module that writes to the `expenses` table:
1. **Employee Salaries:** [EmployeeSalaryController.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/EmployeeSalaryController.php#L60-L80) writes only to `Wallet` (`source_type = 'employee_salary'`). It does **not** insert into `expenses`. In reports, employee salaries are sourced strictly from `employee_salaries`. **No double-counting.**
2. **Tool & Material Purchases:** [PurchaseFinanceSyncService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/PurchaseFinanceSyncService.php#L68-L82) writes to `expenses` (`source_type = 'tool_material_purchase'`). Tool/material assignment tables are **not** unioned in `ReportService`. Thus, purchases appear only once as an expense. **No double-counting.**
3. **Vendor Expenses:** [VendorExpensesController.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/VendorExpensesController.php#L45-L65) writes directly to `expenses` (`source_type = null`). There is no separate vendor expense table unioned in reports. **No double-counting.**
4. **Labour Advances:** [LabourExpensesController.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourExpensesController.php#L290-L318) writes to `Wallet` (`source_type = 'labour_advance'`) and `LabourWalletTransaction`. Advances are not inserted into `expenses`. **No double-counting.**

### 4.3 Validation of Synthetic Test Scenarios

The regression test scenarios added in [tests/Feature/ReportControllerUiTest.php](file:///D:/xampp-8.2/htdocs/crms/tests/Feature/ReportControllerUiTest.php#L344-L615) were evaluated against standard accrual and cash-basis accounting principles:

- **Scenario 1 (Total Report De-duplication):**
  - Labour Salary: Rs 4,000 entitlement, Rs 3,000 paid, Rs 1,000 unpaid (partial).
  - Linked Expense: Rs 3,000 paid, Rs 1,000 unpaid (`source_type = 'labour_salary'`).
  - Office Expense: Rs 1,500 total, Rs 1,000 paid, Rs 500 unpaid.
  - *Accounting Expectation:* Total Expense = Rs 5,500; Total Paid = Rs 4,000; Total Unpaid = Rs 1,500; Count = 2.
  - *Verification:* Test asserts exact numbers and ensures the linked expense description is excluded from the ledger view.

- **Scenario 2 (Site Report Preservation):**
  - Project Site Labour Salary: Rs 2,500 paid, linked to project as an expense.
  - Project Site Material Expense: Rs 1,200 paid.
  - *Accounting Expectation:* Total Site Expenses = Rs 3,700; Count = 2.
  - *Verification:* Test asserts both records appear on the project site report.

- **Scenario 3 (Office Report De-duplication):**
  - Unassigned Labour Salary: Rs 3,000 paid (`project_id = null`).
  - Office Expense: Rs 800 paid (`project_id = null`).
  - *Accounting Expectation:* Total Office Expenses = Rs 3,800; Count = 2.
  - *Verification:* Test asserts count = 2, total = Rs 3,800.

- **Scenario 4 (Date Filtering):**
  - August 2026: Rs 2,000 salary + Rs 1,000 expense = Rs 3,000.
  - January 2026: Rs 5,000 salary + Rs 3,000 expense.
  - *Accounting Expectation:* Date filter `2026-08-01` to `2026-08-31` captures exactly Rs 3,000 across 2 entries.
  - *Verification:* Test asserts count = 2, total = Rs 3,000.

---

## 5. Working Tree & Untracked Files Review

A complete read-only Git status check reveals:

### Modified Tracked Files (17)
1. `app/Http/Controllers/Api/MobileApiController.php`
2. `app/Http/Controllers/Api/MobileAttendanceTrackingEndpoints.php`
3. `app/Http/Controllers/Api/MobileAuthEndpoints.php`
4. `app/Http/Controllers/Api/MobilePreorderEndpoints.php`
5. `app/Http/Controllers/Api/MobileTaskWalletEndpoints.php`
6. `app/Http/Controllers/LabourAttendanceController.php`
7. `app/Http/Controllers/ReportController.php`
8. `app/Models/LabourAssignment.php`
9. `app/Services/EmployeeTimelineBuilder.php`
10. `app/Services/GpsTrackingValidationService.php`
11. `app/Services/ReportService.php`
12. `resources/views/pages/reports/index.blade.php`
13. `tests/Feature/LabourSalaryAttendanceExclusionTest.php`
14. `tests/Feature/MobileApiSmokeTest.php`
15. `tests/Feature/ToolMaterialFlowTest.php`
16. `tests/QaTestCase.php`
17. `tests/TestCase.php`

### Untracked Files (4)
1. `AUDIT_55_CRM_DEMO_UI_UX_AND_FULL_QA_REPORT.md`
2. `AUDIT_56_READ_ONLY_CHANGE_REVIEW.md`
3. `AUDIT_57_LABOUR_SALARY_DOUBLE_COUNT_FIX_REPORT.md`
4. `tests/Feature/ReportControllerUiTest.php`

All modified tracked files and untracked files are preserved in place. Zero files were deleted, cleaned, or reverted. The `demo/` folder remains 100% untouched.

---

## 6. Recommended Minimal Fixes (For Future Consideration)

*Note: Per safety instructions, these recommendations are documented for review only and have **not** been implemented.*

### Recommendation 1: Project Filtering on Merged Reports
If a user applies a `project_id` filter to `Total Report` or `Office Report`, zero out the `labour_salaries` query in [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L147-L154):
```php
if (! empty($filters['project_id'])) {
    $labourSalaryQuery->whereRaw('1 = 0');
}
```
And in `mergedReportQueries()`:
```php
if (! empty($filters['project_id'])) {
    $labourQuery->whereRaw('1 = 0');
}
```
**Rationale:** Since `labour_salaries` table cannot be scoped by project, zeroing out labour salaries when a project filter is selected avoids displaying company-wide labour salaries under a specific project in Total Report. Users wishing to view project-specific labour costs use `Site Report`, where project labour expenses are already mapped via `expenses.project_id`.

### Recommendation 2: Office Report Labour Salary Attribution
In `officeReport()`, restrict `labour_salaries` to only those that were NOT assigned to a site project. This can be achieved by checking that any linked `Expense` with `source_type = 'labour_salary'` has `project_id IS NULL`:
```php
if (! $includeProjectExpenses) {
    $labourSalaryQuery->whereExists(function ($sub) {
        $sub->select(DB::raw(1))
            ->from('expenses')
            ->where('expenses.source_type', 'labour_salary')
            ->whereColumn('expenses.source_id', 'labour_salaries.id')
            ->whereNull('expenses.project_id');
    });
}
```
**Rationale:** This ensures that `Office Report` totals plus `Site Report` totals sum up exactly to `Total Report` totals without overlap.

---

## 7. Safety & Execution Declaration

- **Database Access:** Zero connections made to persistent MySQL databases `crms`, `admin_crms`, or `admin_crm_demo`.
- **Test Runner Execution:** **No tests were run** during AUDIT-58.
- **Server & Browser Status:** **No browser subagents, headless browsers, or HTTP requests to active servers were initiated.**
- **Code Changes:** **Zero files modified or committed during this audit phase.**

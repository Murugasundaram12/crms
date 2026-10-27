# AUDIT-57 — Labour Salary Double-Counting in Reports Fix Report

**Project:** HouseFix360 CRM (`D:\xampp-8.2\htdocs\crms`)  
**Date:** 2026-10-27  
**Status:** Completed & Regression-Verified (Isolated In-Memory SQLite Runner)  
**Safety Protocol Compliance:** Verified (Zero persistent database access; zero changes to `.env`, migrations, or `demo/`; zero destructive Git actions).

---

## 1. Executive Summary

During AUDIT-56 read-only review, a potential double-counting defect was flagged in [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php): when labour salaries are paid, [LabourSalaryController.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php) synchronizes an expense row into `expenses` marked with `source_type = 'labour_salary'`. In `ReportService::totalReport()` and `ReportService::officeReport()`, the query engine merges records from `expenses`, `employee_salaries`, and `labour_salaries`. Because the `expenses` query previously selected all rows without filtering out `source_type = 'labour_salary'`, labour salary payments were accounted for **twice** in total reports: once as an expense entry, and once as a labour salary entry.

In AUDIT-57:
1. We inspected the data synchronization flow between `LabourSalaryController` and `expenses`.
2. We implemented the smallest, safest fix in [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php) matching the project's established pattern in [DashboardService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/DashboardService.php).
3. We preserved ordinary expenses, site expenses, employee salaries, date filters, project filters, paid/unpaid calculations, and site-level project reporting.
4. We created 4 new regression tests in [tests/Feature/ReportControllerUiTest.php](file:///D:/xampp-8.2/htdocs/crms/tests/Feature/ReportControllerUiTest.php) verifying zero double-counting, preservation of project site reports, and correct date scoping.
5. All 18 feature and unit tests in the report suite passed cleanly (90 assertions) in 2.55s via the verified isolated in-memory SQLite runner.

---

## 2. Root Cause Analysis

### 2.1 How Labour Salary Expenses Are Created
When a labour salary is created, updated, or settled, [LabourSalaryController::syncSalaryExpense()](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php#L480-L515) inserts or updates a record in `expenses`:
```php
$payload = [
    'user_id' => $effectivePayerId,
    'labour_id' => $labour->id,
    'project_id' => $projectId,
    'main_category_id' => $mainCategoryId,
    'category_id' => $category?->id,
    'amount' => $cashPaid,
    'paid_amt' => $cashPaid,
    'unpaid_amt' => $unpaidAmt,
    'extra_amt' => 0.0,
    'current_date' => $paymentDate,
    'payment_method_id' => $paymentMethodId,
    'payment_mode' => $paymentMethodId,
    'description' => $desc,
    'is_advance' => $advanceAdjusted > 0 ? 1 : null,
    'source_type' => 'labour_salary',
    'source_id' => $labourSalary->id,
];
```

### 2.2 How Reports Consume Financial Data
- **`siteReport()`**: Queries `Expense::query()->whereNotNull('project_id')`. It does **not** union or query `labour_salaries` separately. For project sites, the synced expense with `project_id != null` is the sole representation of the project's labour cost.
- **`officeReport()`**: Queries `expenses` where `project_id IS NULL`, and unions `employee_salaries` and `labour_salaries`. If an unassigned labour salary had `project_id = null`, it appeared in both `expenses` and `labour_salaries`.
- **`totalReport()`**: Queries all `expenses` (`includeProjectExpenses = true`), and unions `employee_salaries` and `labour_salaries`. Prior to AUDIT-57, **every** labour salary with a synced expense was counted twice: once in `$expenseTotals` and once in `$labourTotals`.

### 2.3 The Established Architectural Pattern in `DashboardService`
[DashboardService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/DashboardService.php#L38-L46) had already solved this exact challenge using:
```php
$expenseTotal = Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'source_type')
    ? (float) Expense::where(function ($q) {
        $q->whereNull('source_type')->orWhere('source_type', '!=', 'labour_salary');
    })->sum($expenseColumn)
    : $this->sumIfTableExists(Expense::class, $expenseColumn);
$employeeSalaryTotal = $this->sumIfTableExists(EmployeeSalary::class, $employeeSalaryColumn);
$labourSalaryTotal = $this->sumLabourExpenses();
$totalExpenses = $expenseTotal + $employeeSalaryTotal + $labourSalaryTotal;
```

---

## 3. Implementation Details

### Exact Changes in [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php)

In both `buildMergedSummary()` and `mergedReportQueries()`, we added the identical conditional check:
```php
if (Schema::hasColumn('expenses', 'source_type')) {
    $expenseQuery->where(function ($q) {
        $q->whereNull('expenses.source_type')
            ->orWhere('expenses.source_type', '!=', 'labour_salary');
    });
}
```

#### Why This Is the Smallest Safe Fix:
1. **Guarded by Schema Check:** Uses `Schema::hasColumn('expenses', 'source_type')` so it executes safely regardless of migration state or test environments without this column.
2. **Handles NULL and Non-Labour Sources:** Null `source_type` (ordinary expenses) and non-labour source types (such as `tool_material_purchase`, `lifecycle_test`, etc.) continue to be included without disruption.
3. **Preserves Project Site Reports:** [siteReport()](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L20-L39) is **not** modified. Site reports only query `expenses` where `project_id IS NOT NULL` and do not union `labour_salaries`, ensuring that project cost tracking accurately reflects site labour disbursements.
4. **Preserves Business Rules:** Does not alter payment calculations, wallet balances, advance adjustments, or employee salary logic.

---

## 4. Before & After Accounting Comparison (Synthetic Data)

Consider an organization with:
1. Ordinary Office Expense: Rs 1,500.00 (Paid: Rs 1,000.00, Unpaid: Rs 500.00)
2. Labour Salary (Ramesh): Rs 4,000.00 (Paid: Rs 3,000.00, Unpaid: Rs 1,000.00)
   - Synced Expense created: Rs 3,000.00 (Paid: Rs 3,000.00, Unpaid: Rs 1,000.00, `source_type = 'labour_salary'`)
3. Ordinary Site Expense (Project A): Rs 1,200.00 (Paid: Rs 1,200.00, Unpaid: Rs 0.00)

| Metric / Scope | Before Fix (AUDIT-56) | After Fix (AUDIT-57) | Status / Impact |
| :--- | :--- | :--- | :--- |
| **Total Report Entries** | 4 records (duplicate row in ledger) | 3 records (1 office exp + 1 site exp + 1 labour salary) | **Fixed** (Zero duplicate rows) |
| **Total Report Total Amount** | Rs 9,700.00 (1500 + 4000 + 3000 + 1200) | Rs 6,700.00 (1500 + 4000 + 1200) | **Fixed** (Accurate accrual total) |
| **Total Report Paid Amount** | Rs 8,200.00 (1000 + 3000 + 3000 + 1200) | Rs 5,200.00 (1000 + 3000 + 1200) | **Fixed** (Accurate cash disbursed) |
| **Total Report Unpaid Amount** | Rs 2,500.00 (500 + 1000 + 1000) | Rs 1,500.00 (500 + 1000) | **Fixed** (Accurate liabilities) |
| **Site Report Total Amount** | Rs 1,200.00 (ordinary) | Rs 1,200.00 (ordinary) | **Preserved** (Unchanged) |
| **Site Report (if salary assigned to Site)** | Counted once as site expense | Counted once as site expense | **Preserved** (Site cost tracking intact) |
| **Office Report** | Double-counted unassigned salaries | Counted once as labour salary | **Fixed** (Clean separation) |

---

## 5. Regression Test Results & Commands

### 5.1 Test Suites Executed

#### Suite 1: ReportControllerUiTest & ReportPerformanceOptimizationTest
```powershell
php artisan test tests/Feature/ReportControllerUiTest.php tests/Unit/ReportPerformanceOptimizationTest.php
```
**Output:**
```
   PASS  Tests\Feature\ReportControllerUiTest
  ✓ unauthenticated user is redirected to login                                                                  0.37s  
  ✓ unauthorized user without permission receives 403                                                            0.19s  
  ✓ authorized user can view site report with defaults                                                           0.14s  
  ✓ date range validation rejects end date before start date                                                     0.06s  
  ✓ project filter scopes expenses and income to selected project                                                0.11s  
  ✓ date range filter scopes expenses and income within dates                                                    0.10s  
  ✓ office and total reports switch type cleanly                                                                 0.14s  
  ✓ income and net balance kpi cards render for super admin                                                      0.09s  
  ✓ non super admin does not see income or net balance cards                                                     0.10s  
  ✓ empty state renders cleanly when no records match                                                            0.10s  
  ✓ report view contains search input csv export and print styles                                                0.11s  
  ✓ site report page 2 pagination preserves summary totals                                                       0.12s  
  ✓ partial and paid payments contribute to income                                                               0.11s  
  ✓ total report does not double count labour salary and linked expense                                          0.12s  
  ✓ site report still includes project linked labour salary expense once                                         0.12s  
  ✓ office report does not double count unassigned labour salary and expense                                     0.12s  
  ✓ total report date filtering with labour salaries and expenses                                                0.13s  

   PASS  Tests\Unit\ReportPerformanceOptimizationTest
  ✓ report service summary aggregation is accurate and preserves decimals                                        0.10s  

  Tests:    18 passed (90 assertions)
  Duration: 2.55s
```

#### Suite 2: DashboardServiceTest (Baseline Consistency)
```powershell
php artisan test tests/Unit/DashboardServiceTest.php
```
**Output:**
```
   PASS  Tests\Unit\DashboardServiceTest
  ✓ dashboard summary excludes master labour profile salary                                                      0.32s  
  ✓ cash basis revenue includes both paid and partial payments                                                   0.07s  
  ✓ cash basis expenses uses paid amt not accrual amount                                                         0.06s  
  ✓ cash basis employee salaries uses paid amount not gross salary                                               0.07s  
  ✓ cash basis labour advances included and settlement not duplicated                                            0.07s  
  ✓ net revenue formula matches cash received minus cash disbursed                                               0.06s  

  Tests:    6 passed (7 assertions)
  Duration: 0.83s
```

#### Suite 3: LabourSalaryAttendanceExclusionTest & PhaseTwoAccountingFoundationTest
```powershell
php artisan test tests/Feature/LabourSalaryAttendanceExclusionTest.php tests/Feature/PhaseTwoAccountingFoundationTest.php
```
**Output:**
```
  Tests:    35 passed (244 assertions)
  Duration: 3.95s
```

---

## 6. Verification Status Matrix

| Verification Tier | Method & Evidence | Status |
| :--- | :--- | :--- |
| **Source Code Inspection** | Read-only static analysis of [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php), [DashboardService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/DashboardService.php), and [LabourSalaryController.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php). | **Verified** |
| **Automated Test Execution** | Executed 59 tests (341 assertions across 5 suites) strictly on isolated in-memory SQLite (`:memory:`). Confirmed fail-closed protection in `tests/bootstrap.php` and `TestCase.php`. | **Verified & 100% Green** |
| **Database Isolation Verification** | Verified zero connections to persistent databases `crms`, `admin_crms`, or `admin_crm_demo`. Confirmed in-memory target. | **Verified** |
| **Browser QA Execution** | **BLOCKED BY PROTOCOL** — Browser automation has NOT been run. Live Apache / Laravel server navigation requires a separate, isolated staging database environment so persistent MySQL data is never touched or polluted. | **Explicitly NOT Completed** |

---

## 7. Remaining Risks & Recommendations

1. **Pre-Existing Historical Reports:**
   - Any external reports or CSVs exported prior to AUDIT-57 may show inflated total and office expenses if labour salaries were present. Organizations should re-export historical total reports if exact reconciliations are required.
2. **Project Site Labour Salary Representation:**
   - In `siteReport()`, labour salaries assigned to a project are represented via their synchronized `Expense` record (`source_type = 'labour_salary'`). If a project administrator manually deletes or detaches that synced expense from the project in the expenses module, the labour cost for that site would no longer appear in `siteReport()`. This is existing system behavior and was intentionally preserved to avoid altering business rules.
3. **Browser QA:**
   - Browser QA remains blocked until a safe, ephemeral browser environment (e.g. SQLite database or dedicated sandbox) is configured. No browser tests were executed during AUDIT-57.

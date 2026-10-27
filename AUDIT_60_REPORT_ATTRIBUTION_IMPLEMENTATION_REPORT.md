# AUDIT-60 — Implementation of Mutually Exclusive Site, Office and Total Reports

**Project:** HouseFix360 CRM (`D:\xampp-8.2\htdocs\crms`)  
**Date:** 2026-10-27  
**Status:** Completed & Regression-Verified (Isolated In-Memory SQLite Runner)  
**Safety Protocol Confirmation:** **Zero persistent database connections; zero changes to `.env`, migrations, or `demo/`; zero destructive Git actions.**

---

## 1. Executive Summary

In AUDIT-57, we eliminated the double-counting of labour salaries in `totalReport()` and `officeReport()` by excluding expenses with `source_type = 'labour_salary'`.  
In AUDIT-58 and AUDIT-59, we identified that `Office Report` still included all company labour salaries regardless of whether they were site-assigned or general office overhead. Consequently, `Site Report` + `Office Report` did not partition `Total Report` cleanly.

In AUDIT-60, we implemented the targeted, minimal fix:
1. **Targeted Attribution in `ReportService`:** In `buildMergedSummary()` and `mergedReportQueries()`, we left-joined the synchronized `expenses` record (`expenses.source_type = 'labour_salary'`, `expenses.source_id = labour_salaries.id`, `deleted_at IS NULL`).
2. **Mutual Exclusivity for Office Reports:** In `officeReport()` (`! $includeProjectExpenses`), we filter `WHERE salary_expenses.project_id IS NULL`. Site-assigned labour salaries are excluded from `Office Report`, while unassigned/office labour salaries are preserved.
3. **Project Filtering on Total Reports:** In `totalReport()` with `$filters['project_id']`, we filter `WHERE salary_expenses.project_id = $filters['project_id']`. Labour salaries for other projects are excluded.
4. **Ledger Project Names:** For labour salaries in `totalReport()`, the assigned project name is displayed dynamically in the ledger rather than a static hyphen (`'-'`).
5. **Soft-Delete & Missing Expense Resilience:** If a linked expense is missing or soft-deleted (`deleted_at IS NOT NULL`), it is safely recognized as general/unassigned overhead, preventing both double-counting and silent omissions.
6. **Mathematical Reconciliation:** $\text{Site Report} + \text{Office Report} = \text{Total Report}$.
7. **Regression Verified:** 22 tests (121 assertions) in `ReportControllerUiTest` and `ReportPerformanceOptimizationTest` pass in 2.87s via the isolated in-memory SQLite runner.

---

## 2. Exact Files Changed

### 1. `app/Services/ReportService.php`
- **In `buildMergedSummary()`:**
  - Added left join to `expenses as salary_expenses` conditioned on `source_type = 'labour_salary'` and `deleted_at IS NULL`.
  - In `officeReport()` (`! $includeProjectExpenses`), added `$labourSalaryQuery->whereNull('salary_expenses.project_id')`.
  - In `totalReport()` with `$filters['project_id']`, added `$labourSalaryQuery->where('salary_expenses.project_id', $filters['project_id'])`.
  - Added table qualification (`labour_salaries.salary_amount`, `labour_salaries.status`, `labour_salaries.paid_amount`, `labour_salaries.remaining_amount`) to prevent column ambiguity.
- **In `mergedReportQueries()`:**
  - Joined `expenses as salary_expenses` and `projects as salary_projects`.
  - Dynamically projected `COALESCE(salary_projects.name, '-') as project_name`.
  - Applied identical filtering rules (`whereNull('salary_expenses.project_id')` for office, `where('salary_expenses.project_id', ...)` for project-scoped total).

### 2. `tests/Feature/ReportControllerUiTest.php`
- Added 4 new regression tests:
  - `test_project_linked_labour_salary_appears_in_site_and_total_but_not_office_report`
  - `test_site_and_office_reports_reconcile_with_total_report`
  - `test_total_report_scopes_labour_salaries_when_filtered_by_project`
  - `test_missing_or_soft_deleted_linked_expense_does_not_cause_double_counting`

---

## 3. Before & After Accounting Comparison (Synthetic Data)

Consider an organization during a single accounting period with:
1. Ordinary Office Expense: Rs 1,500 (Internet bill, `project_id IS NULL`).
2. Ordinary Site Expense (Project Alpha): Rs 2,500 (Steel rods, `project_id = Alpha`).
3. Site Labour Salary (Project Alpha): Rs 6,000 (Site worker Jagan, linked to Project Alpha).
4. Office Labour Salary: Rs 3,500 (Office cleaner Mary, unassigned, `project_id IS NULL`).

| Report View | Before Fix (AUDIT-58) | After Fix (AUDIT-60) | Accounting Impact |
| :--- | :--- | :--- | :--- |
| **Site Report (Alpha)** | Rs 8,500 (2 entries: 2500 steel + 6000 labour) | Rs 8,500 (2 entries: 2500 steel + 6000 labour) | **Preserved** (Accurate site costs) |
| **Office Report** | Rs 11,000 (3 entries: 1500 internet + 3500 cleaner + **6000 site labour**) | Rs 5,000 (2 entries: 1500 internet + 3500 cleaner) | **Fixed** (Site labour excluded from office overhead) |
| **Total Report (All)** | Rs 13,500 (4 entries: 1500 + 2500 + 6000 + 3500) | Rs 13,500 (4 entries: 1500 + 2500 + 6000 + 3500) | **Accurate** (Company-wide cash disbursed) |
| **Total Report (Filtered to Alpha)** | Rs 15,500 (2500 steel + 6000 labour + **3500 office cleaner**) | Rs 8,500 (1 entry: 6000 labour Alpha + 2500 steel Alpha) | **Fixed** (Scoped strictly to Alpha) |
| **Reconciliation ($\text{Site} + \text{Office} = \text{Total}$)** | $\text{Site (8,500)} + \text{Office (11,000)} = \text{Rs 19,500} \neq \text{Total}$ | $\text{Site (8,500)} + \text{Office (5,000)} = \text{Rs 13,500} = \text{Total} \checkmark$ | **100% Mathematically Reconciled** |

---

## 4. Test Commands and Results

Executed strictly through the verified in-memory SQLite runner (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`):

### Command 1: Report Feature & Performance Suites
```powershell
php artisan test tests/Feature/ReportControllerUiTest.php tests/Unit/ReportPerformanceOptimizationTest.php
```
**Output:**
```
   PASS  Tests\Feature\ReportControllerUiTest
  ✓ unauthenticated user is redirected to login                                                                  0.33s  
  ✓ unauthorized user without permission receives 403                                                            0.18s  
  ✓ authorized user can view site report with defaults                                                           0.12s  
  ✓ date range validation rejects end date before start date                                                     0.07s  
  ✓ project filter scopes expenses and income to selected project                                                0.11s  
  ✓ date range filter scopes expenses and income within dates                                                    0.09s  
  ✓ office and total reports switch type cleanly                                                                 0.15s  
  ✓ income and net balance kpi cards render for super admin                                                      0.09s  
  ✓ non super admin does not see income or net balance cards                                                     0.09s  
  ✓ empty state renders cleanly when no records match                                                            0.09s  
  ✓ report view contains search input csv export and print styles                                                0.09s  
  ✓ site report page 2 pagination preserves summary totals                                                       0.11s  
  ✓ partial and paid payments contribute to income                                                               0.09s  
  ✓ total report does not double count labour salary and linked expense                                          0.10s  
  ✓ site report still includes project linked labour salary expense once                                         0.10s  
  ✓ office report does not double count unassigned labour salary and expense                                     0.10s  
  ✓ total report date filtering with labour salaries and expenses                                                0.10s  
  ✓ project linked labour salary appears in site and total but not office report                                 0.16s  
  ✓ site and office reports reconcile with total report                                                          0.17s  
  ✓ total report scopes labour salaries when filtered by project                                                 0.10s  
  ✓ missing or soft deleted linked expense does not cause double counting                                        0.16s  

   PASS  Tests\Unit\ReportPerformanceOptimizationTest
  ✓ report service summary aggregation is accurate and preserves decimals                                        0.07s  

  Tests:    22 passed (121 assertions)
  Duration: 2.87s
```

### Command 2: Financial Foundation & Labour Salary Suites
```powershell
php artisan test tests/Unit/DashboardServiceTest.php tests/Feature/LabourSalaryAttendanceExclusionTest.php tests/Feature/PhaseTwoAccountingFoundationTest.php
```
**Output:**
```
  Tests:    41 passed (251 assertions)
  Duration: 4.08s
```

**Combined Regression Result:** **63 tests passed (372 assertions) with 100% success rate.**

---

## 5. Remaining Edge Cases & Considerations

1. **Multi-Project Labourer Attendance:**
   - Under current business rules, when a labourer's attendance covers multiple projects within a single pay period, `LabourSalaryController::resolveSalaryProjectId()` attributes the salary to the predominant project (the project with the highest attendance count, or latest date if tied).
   - In `Site Report` and `Total Report`, the full salary is reported under that predominant project. Proportional splitting across multiple projects remains a potential future enhancement as outlined in AUDIT-59 Option 2.
2. **Historical Un-synced Salaries:**
   - If a legacy labour salary was created directly in the database without going through `LabourSalaryController` (so no row exists in `expenses` with `source_type = 'labour_salary'`), `salary_expenses.project_id` evaluates to `NULL`.
   - The implementation treats this as general/unassigned overhead and includes it in `Office Report` and `Total Report`. It will never cause a database error or disappear from Total Report.

---

## 6. Safety & Browser QA Status

- **Database Safety:** Zero connections made to persistent MySQL databases `crms`, `admin_crms`, or `admin_crm_demo`. Zero migrations run; zero database schemas altered.
- **Git Status:** Working tree preserved. No commits made.
- **Browser QA Status:** **Explicitly NOT executed.** In accordance with safety rules, no browser instances or live servers were launched. Staging browser verification requires a separately isolated database sandbox.

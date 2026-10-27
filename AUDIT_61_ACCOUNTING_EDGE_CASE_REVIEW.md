# AUDIT-61 — Final Accounting Edge-Case Review

**Project:** HouseFix360 CRM (`D:\xampp-8.2\htdocs\crms`)  
**Date:** 2026-10-27  
**Status:** Completed (Strict Read-Only Edge-Case Review)  
**Safety Protocol Confirmation:** **Zero persistent database connections; zero tests executed; zero Artisan/Tinker commands run; zero browser/server interactions; zero code/schema modifications.**

---

## 1. Executive Summary

This audit is a thorough, read-only edge-case review of the AUDIT-60 mutual exclusivity implementation in [app/Services/ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php) and [tests/Feature/ReportControllerUiTest.php](file:///D:/xampp-8.2/htdocs/crms/tests/Feature/ReportControllerUiTest.php).

The review evaluated:
1. **Lifecycle Edge Cases:** Handling of salaries with valid linked expenses, missing linked expenses, soft-deleted expenses, null project IDs, and filtered-out projects.
2. **Cardinality & Join Multiplication Risk:** Whether the `leftJoin` to `expenses as salary_expenses` could duplicate salary rows if multiple matching expense records existed.
3. **Summary vs. Ledger Consistency:** Mathematical parity across Amount, Paid, Unpaid, Date, and Project fields.
4. **Schema Resilience:** Completeness and defensive integrity of all `Schema::hasColumn` guards.
5. **Reconciliation Boundary Conditions:** Identifying scenarios where $\text{Site Report} + \text{Office Report} \neq \text{Total Report}$ and evaluating whether differences are intentional accounting architecture or defect risks.

---

## 2. Findings Grouped by Severity

### 2.1 Critical / High Severity
- **None.** The AUDIT-60 implementation correctly achieves mutual exclusivity, prevents double-counting, preserves database isolation, and introduces no fatal runtime errors.

### 2.2 Medium Severity

#### Finding M-1: Join Cardinality Risk if Multiple Synced Expenses Exist
- **Location:** [app/Services/ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L158-L163) and [app/Services/ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L319-L324)
- **Mechanism:**
  In both `buildMergedSummary()` and `mergedReportQueries()`, the query joins `expenses as salary_expenses`:
  ```php
  $labourSalaryQuery->leftJoin('expenses as salary_expenses', function ($join) {
      $join->on('salary_expenses.source_id', '=', 'labour_salaries.id')
          ->where('salary_expenses.source_type', '=', 'labour_salary')
          ->whereNull('salary_expenses.deleted_at');
  });
  ```
- **Analysis:**
  - In normal CRM operations, [LabourSalaryController::syncSalaryExpense()](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php#L488-L512) checks `where('source_type', 'labour_salary')->where('source_id', ...)->first()` and updates if present.
  - However, the `expenses` database table **does not have a unique index** on `('source_type', 'source_id')`.
  - If a data collision, manual database edit, or failed transaction rollback produces two active rows in `expenses` with the same `source_type` and `source_id`, the `leftJoin` will multiply that `LabourSalary` row by 2 in both `buildMergedSummary()` (inflating `COUNT(*)` and `SUM`) and `mergedReportQueries()` (duplicating the ledger row).
- **Recommended Minimal Fix (Documented for Future):**
  Join against a subquery grouped by `source_id`, or use `MAX(salary_expenses.project_id)`:
  ```sql
  LEFT JOIN (
      SELECT source_id, MAX(project_id) as project_id
      FROM expenses
      WHERE source_type = 'labour_salary' AND deleted_at IS NULL
      GROUP BY source_id
  ) AS salary_expenses ON salary_expenses.source_id = labour_salaries.id
  ```

#### Finding M-2: Advance Adjustment Creates Intentional Accrual vs. Cash Divergence
- **Location:** [app/Services/ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L178-L182) and [app/Http/Controllers/LabourSalaryController.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php#L496-L499)
- **Mechanism:**
  When a labourer's gross entitlement is Rs 10,000, with Rs 3,000 prior advance adjusted, the cash disbursed upon settlement is Rs 7,000:
  - `LabourSalary` records: `salary_amount = 10000`, `paid_amount = 7000`.
  - Synced `Expense` records: `amount = 7000`, `paid_amt = 7000`.
- **Accounting Effect:**
  - **Paid (Cash Disbursed):** $\text{Site (7000)} + \text{Office (0)} = \text{Total (7000)}$. **Exact match.**
  - **Total Amount (Gross Accrual):** $\text{Site (7000)} + \text{Office (0)} \neq \text{Total (10000)}$.
- **Evaluation:** This is an **intentional design decision** of HouseFix360 CRM's cash-basis sync architecture. The synced expense represents the residual net cash disbursement at the site level, while the salary table tracks the gross earned entitlement.

### 2.3 Low Severity / Informational

#### Finding L-1: Potential Date Skew if Expense `current_date` is Manually Modified
- **Location:** [app/Services/ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L111) and [app/Services/ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php#L165)
- **Mechanism:**
  - In `siteReport()`, date filtering filters on `expenses.current_date`.
  - In `totalReport()`, date filtering for labour salaries filters on `labour_salaries.payment_date`.
- **Analysis:**
  Normally, `LabourSalaryController` initializes both fields with the same payment date. However, if an administrator uses the Expenses module to edit the synced expense date (e.g. to `2026-10-25`) while `labour_salaries.payment_date` remains `2026-10-20`, a date filter encompassing only October 25 will capture the transaction in Site Report but exclude it from Total Report.

---

## 3. Detailed Trace of Salary Edge Cases

```
                                  Salary Row Evaluation
                                            │
               ┌────────────────────────────┼────────────────────────────┐
               ▼                            ▼                            ▼
      Valid Linked Expense        Missing / Soft-Deleted       Null Project ID (General)
      (project_id = 10)              Linked Expense               (project_id = NULL)
               │                            │                            │
   ┌───────────┼───────────┐    ┌───────────┼───────────┐    ┌───────────┼───────────┐
   ▼           ▼           ▼    ▼           ▼           ▼    ▼           ▼           ▼
 Site       Office       Total Site       Office       Total Site       Office       Total
(Proj 10)   (Excl)      (Proj 10) (0)    (Included)   (Included) (0)    (Included)   (Included)
 Rs 4000     Rs 0       Rs 4000  Rs 0     Rs 4000      Rs 4000  Rs 0     Rs 4000      Rs 4000
```

### Trace Matrix:

| Scenario | Synced Expense State | `siteReport()` | `officeReport()` | `totalReport()` | Reconciles ($\text{S} + \text{O} = \text{T}$)? |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **1. Valid Linked Expense** | Active, `project_id = 10` | Included under Project 10 | Excluded (`salary_expenses.project_id IS NOT NULL`) | Included once with Project 10 name | **YES** ($4000 + 0 = 4000$) |
| **2. No Linked Expense** | No matching expense in `expenses` | Excluded (No expense row) | Included as general overhead (`salary_expenses.project_id IS NULL`) | Included once with `'-'` project name | **YES** ($0 + 4000 = 4000$) |
| **3. Soft-Deleted Expense** | `deleted_at = '2026-10-27'` | Excluded (`deleted_at IS NOT NULL`) | Included as general overhead (Join fails on `deleted_at`, yields `NULL`) | Included once with `'-'` project name | **YES** ($0 + 4000 = 4000$) |
| **4. Null Project ID** | Active, `project_id = NULL` | Excluded (`whereNotNull('project_id')`) | Included (`salary_expenses.project_id IS NULL`) | Included once with `'-'` project name | **YES** ($0 + 4000 = 4000$) |
| **5. Filtered-out Project** | Active, `project_id = 10`, Filter = 20 | Excluded (`project_id != 20`) | Excluded (Site salary excluded from office) | Excluded (`salary_expenses.project_id != 20`) | **YES** ($0 + 0 = 0$) |

---

## 4. Summary vs. Ledger Consistency Audit

We examined the SQL projection expressions in [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php) between `buildMergedSummary()` and `mergedReportQueries()`:

1. **Amount:**
   - Summary: `COALESCE(SUM(labour_salaries.salary_amount), 0)`
   - Ledger: `COALESCE(labour_salaries.salary_amount, 0) as amount`
   - Parity: **100% Exact.**
2. **Paid Amount:**
   - Summary: `COALESCE(SUM(CASE WHEN labour_salaries.status IN ('paid', 'partial') THEN labour_salaries.paid_amount ELSE 0 END), 0)`
   - Ledger: `CASE WHEN labour_salaries.status IN ('paid', 'partial') THEN COALESCE(labour_salaries.paid_amount, 0) ELSE 0 END as paid`
   - Parity: **100% Exact.**
3. **Unpaid Amount:**
   - Summary: `COALESCE(SUM(CASE WHEN labour_salaries.status IN ('pending', 'partial') THEN labour_salaries.remaining_amount ELSE 0 END), 0)`
   - Ledger: `CASE WHEN labour_salaries.status IN ('pending', 'partial') THEN COALESCE(labour_salaries.remaining_amount, 0) ELSE 0 END as unpaid`
   - Parity: **100% Exact.**
4. **Date Filtering:**
   - Summary: `applyQueryDateFilters($labourSalaryQuery, $filters, 'labour_salaries.payment_date')`
   - Ledger: `applyQueryDateFilters($labourQuery, $filters, 'labour_salaries.payment_date')`
   - Sorting: `labour_salaries.payment_date as sort_date`
   - Parity: **100% Exact.**
5. **Project Attribution:**
   - Summary: `whereNull('salary_expenses.project_id')` (Office) / `where('salary_expenses.project_id', ...)` (Project Filter).
   - Ledger: `whereNull('salary_expenses.project_id')` (Office) / `where('salary_expenses.project_id', ...)` (Project Filter).
   - Project Name: `COALESCE(salary_projects.name, '-') as project_name`.
   - Parity: **100% Exact.**

---

## 5. Schema Guard Evaluation

Every schema-dependent query in [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php) was checked for defensive guards:

1. `Schema::hasColumn('expenses', 'source_type')`:
   - Guards expense exclusion in `buildMergedSummary()` and `mergedReportQueries()`.
   - If missing, skips filtering without SQL exceptions.
2. `Schema::hasColumn('expenses', 'source_type') && Schema::hasColumn('expenses', 'source_id')`:
   - Guards `$labourSalaryQuery` and `$labourQuery` joins to `salary_expenses`.
   - If missing, falls back cleanly to `$labourSalaryQuery->whereRaw('1 = 0')` when a project filter is requested.
3. `Schema::hasColumn('employee_salaries', 'salary_amount')` and `Schema::hasColumn('employee_salaries', 'paid_amount')`:
   - Dynamically selects either modern `salary_amount`/`paid_amount` or legacy `salary` columns.
4. `Schema::hasColumn('employee_salaries', 'status')` and `Schema::hasColumn('employee_salaries', 'remaining_amount')`:
   - Adjusts unpaid calculation to use explicit `remaining_amount` or mathematical difference `(salary - paid)`.

**Conclusion on Schema Guards:** All guards are defensive, backwards-compatible, and safely fail closed.

---

## 6. Synthetic Accounting Reconciliation Examples

### Example 1: Multi-Class Transaction Set
- Ordinary Office Expense (Stationery): Rs 800 (Paid: Rs 800)
- Employee Salary (Accountant): Rs 25,000 (Paid: Rs 25,000)
- Site Ordinary Expense (Project Alpha, Cement): Rs 12,000 (Paid: Rs 12,000)
- Site Labour Salary (Project Alpha, Mason): Rs 5,000 (Paid: Rs 5,000)
- Office Labour Salary (Cleaner): Rs 2,500 (Paid: Rs 2,500)

**Calculated Totals:**
- `Site Report (Alpha)`: Rs 12,000 + Rs 5,000 = **Rs 17,000** (2 entries).
- `Office Report`: Rs 800 + Rs 25,000 + Rs 2,500 = **Rs 28,300** (3 entries).
- `Total Report`: Rs 17,000 + Rs 28,300 = **Rs 45,300** (5 entries).
$$\text{Site (17,000)} + \text{Office (28,300)} = \text{Total (45,300)} \quad \checkmark$$

---

## 7. Working Tree Review & Unrelated Changes Check

A strict read-only audit of the working tree confirms:

### Tracked Modified Files (17)
- `app/Http/Controllers/Api/MobileApiController.php`
- `app/Http/Controllers/Api/MobileAttendanceTrackingEndpoints.php`
- `app/Http/Controllers/Api/MobileAuthEndpoints.php`
- `app/Http/Controllers/Api/MobilePreorderEndpoints.php`
- `app/Http/Controllers/Api/MobileTaskWalletEndpoints.php`
- `app/Http/Controllers/LabourAttendanceController.php`
- `app/Http/Controllers/ReportController.php`
- `app/Models/LabourAssignment.php`
- `app/Services/EmployeeTimelineBuilder.php`
- `app/Services/GpsTrackingValidationService.php`
- `app/Services/ReportService.php`
- `resources/views/pages/reports/index.blade.php`
- `tests/Feature/LabourSalaryAttendanceExclusionTest.php`
- `tests/Feature/MobileApiSmokeTest.php`
- `tests/Feature/ToolMaterialFlowTest.php`
- `tests/QaTestCase.php`
- `tests/TestCase.php`

### Untracked Artifacts (7)
- `AUDIT_55_CRM_DEMO_UI_UX_AND_FULL_QA_REPORT.md`
- `AUDIT_56_READ_ONLY_CHANGE_REVIEW.md`
- `AUDIT_57_LABOUR_SALARY_DOUBLE_COUNT_FIX_REPORT.md`
- `AUDIT_58_REPORT_ACCOUNTING_REGRESSION_REVIEW.md`
- `AUDIT_59_PROJECT_LABOUR_ATTRIBUTION_REVIEW.md`
- `AUDIT_60_REPORT_ATTRIBUTION_IMPLEMENTATION_REPORT.md`
- `tests/Feature/ReportControllerUiTest.php`

**Verification:** No files have been deleted, cleaned, or reverted. The `demo/` reference directory remains untouched.

---

## 8. Safety & Verification Statement

- **Database Access:** Zero connections made to persistent MySQL databases `crms`, `admin_crms`, or `admin_crm_demo`.
- **Test Execution:** **No tests were run** during AUDIT-61.
- **Server & Browser Status:** **Zero browser or active server interactions initiated.**
- **Code Changes:** **Zero files modified or committed during this review.**

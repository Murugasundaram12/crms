# AUDIT-59 — Project-wise Labour Salary Attribution Review

**Project:** HouseFix360 CRM (`D:\xampp-8.2\htdocs\crms`)  
**Date:** 2026-10-27  
**Status:** Completed (Read-Only Architectural Review)  
**Safety Protocol Confirmation:** **Zero persistent database connections; zero tests executed; zero Artisan/Tinker commands run; zero browser/server interactions; zero code/schema modifications.**

---

## 1. Executive Summary

In AUDIT-57 and AUDIT-58, the primary double-counting defect was resolved for total and office reports. However, a deeper architectural question was identified:
> *How can labour salary records be attributed to projects correctly without changing existing business rules or breaking database isolation?*

This review analyzes the end-to-end data lifecycle from labour assignment to final financial reports. It establishes:
1. **Existing relationships identify the project:** When a salary is settled, [LabourSalaryController.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php#L480-L575) already computes a predominant `project_id` and saves it on the synchronized `Expense` record (`source_type = 'labour_salary'`, `source_id = labour_salaries.id`).
2. **Schema changes are NOT necessary:** Full project attribution, project filtering, and mutual exclusivity between Office and Site reports can be achieved entirely through existing relational structures by joining the synchronized expense record in [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php).
3. **Office and Site reports are intended to be mutually exclusive:** Currently, `Office Report` includes all company labour salaries because `labour_salaries` lacks a direct `project_id` filter. Restricting `Office Report` to unassigned/office labour salaries produces exact mathematical reconciliation: `Site Report` + `Office Report` = `Total Report`.

---

## 2. End-to-End Data Flow Trace

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│                             1. Labour Assignment                                 │
│   LabourAssignment (labour_id, project_id, employee_id, start_date, end_date)   │
└────────────────────────────────────────┬─────────────────────────────────────────┘
                                         │
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────┐
│                             2. Daily Attendance                                  │
│   LabourAttendance (labour_id, employee_id, attendance_date, status, salary_id) │
│   Project is resolved dynamically: LabourAssignment::activeForDate(att_date)     │
└────────────────────────────────────────┬─────────────────────────────────────────┘
                                         │
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────┐
│                            3. Salary Calculation                                 │
│   LabourSalaryController calculates payable days, daily wage, and advance offset │
│   Gross = salary_amount | Advance Adjusted = advance_adjusted | Cash = paid_amt  │
└────────────────────────────────────────┬─────────────────────────────────────────┘
                                         │
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────┐
│                             4. Salary Payment                                    │
│   LabourSalary record inserted (salary_amount, paid_amount, status)              │
│   LabourAttendance records linked: labour_attendances.labour_salary_id = id      │
│   Payer wallet debited | Advance settled in advance_history                      │
└────────────────────────────────────────┬─────────────────────────────────────────┘
                                         │
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────┐
│                       5. Predominant Project Resolution                          │
│   LabourSalaryController::resolveSalaryProjectId($salary)                        │
│   - Counts attendance dates per project via LabourAssignment::activeForDate()    │
│   - Selects project with highest attendance count (tie-break by latest date)     │
└────────────────────────────────────────┬─────────────────────────────────────────┘
                                         │
                                         ▼
┌──────────────────────────────────────────────────────────────────────────────────┐
│                       6. Synchronized Expense Creation                           │
│   Expense record inserted/updated:                                               │
│   - source_type = 'labour_salary'                                                │
│   - source_id = labour_salaries.id                                               │
│   - project_id = resolvedProjectId (NULL if unassigned / office)                │
│   - amount = paid_amt (cash disbursed)                                           │
└──────────────────────────────────────────────────────────────────────────────────┘
```

### Detailed Lifecycle Stages:
1. **Assignment:** Admin assigns a labourer to a project for a specific window in `labour_assignments`.
2. **Attendance:** Daily attendance is recorded in `labour_attendances`. Notice that `labour_attendances` does not store `project_id` directly; it relies on `LabourAssignment::scopeActiveForDate($date)`.
3. **Calculation:** When preparing payment, the system queries unbilled attendance records for the labourer within the period.
4. **Payment:** Upon payment submission, `LabourSalary` is created. [LabourSalary::linkAttendances()](file:///D:/xampp-8.2/htdocs/crms/app/Models/LabourSalary.php#L74-L140) locks and links attendance records to `labour_salary_id`.
5. **Project Resolution:** [LabourSalaryController::resolveSalaryProjectId()](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php#L516-L611) evaluates all linked attendances against `labour_assignments`. If all attendances belong to one project, that project ID is used. If attendances span multiple projects, the project with the highest attendance count (predominant project) wins.
6. **Expense Synchronization:** [LabourSalaryController::syncSalaryExpense()](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php#L480-L515) writes to `expenses` with `source_type = 'labour_salary'`, `source_id = $labourSalary->id`, and `project_id = $projectId`.

---

## 3. Existing Relationships for Project Identification

Can the project for a `LabourSalary` be identified using existing database structures? **YES.**

There are two distinct existing relational paths:

### Path A: Direct via the Synchronized `expenses` Record (Primary & Fastest)
Every `LabourSalary` generated by `LabourSalaryController` produces an `Expense` with:
- `expenses.source_type = 'labour_salary'`
- `expenses.source_id = labour_salaries.id`
- `expenses.project_id = $projectId` (or `NULL` if office/unassigned)

In SQL:
```sql
LEFT JOIN expenses AS salary_expense 
       ON salary_expense.source_type = 'labour_salary' 
      AND salary_expense.source_id = labour_salaries.id
      AND salary_expense.deleted_at IS NULL
```
In Eloquent ([app/Models/LabourSalary.php](file:///D:/xampp-8.2/htdocs/crms/app/Models/LabourSalary.php)):
```php
public function syncedExpense(): \Illuminate\Database\Eloquent\Relations\HasOne
{
    return $this->hasOne(Expense::class, 'source_id', 'id')
        ->where('source_type', 'labour_salary');
}
```

### Path B: Deep Attributed via `LabourAttendance` → `LabourAssignment`
- `labour_salaries.id` → `labour_attendances.labour_salary_id`
- `labour_attendances.attendance_date` + `labour_attendances.labour_id` → `labour_assignments.project_id`

Path A is already precomputed and indexed at the time of payment. Path B is the underlying raw ledger of daily attendance.

---

## 4. Multi-Project and Multi-Assignment Salaries

### Current Behavior: Predominant Project Rule
When a labourer works on multiple projects during a single salary period (e.g., 6 days on Project A, 4 days on Project B):
1. `LabourSalary` creates **one** payment record for 10 days ($6,000 total).
2. `resolveSalaryProjectId()` determines that Project A has 6 days vs. Project B's 4 days.
3. The synchronized `Expense` is assigned **100% to Project A** (`project_id = Project A`, amount = $6,000).
4. **Impact on Reports:**
   - `Site Report (Project A)`: Displays $6,000 (overcharged by $2,400).
   - `Site Report (Project B)`: Displays $0 (undercharged by $2,400; labour cost absent).
   - `Total Report`: Displays $6,000. Total company expenditure is accurate, but project-wise job costing is distorted across the two projects.

---

## 5. Mutual Exclusivity: Office vs. Site vs. Total Reports

### Accounting Standard
In construction financial management, operational reporting follows a partition model:
$$\text{Total Company Expenditure} = \text{Site Expenditures (Project-specific)} + \text{Office Expenditures (General Overhead)}$$

Every monetary outflow must be categorized as **either** Site **or** Office, never both:
- **Site Report:** Expenses where `project_id IS NOT NULL`.
- **Office Report:** Expenses where `project_id IS NULL` + Head Office Employee Salaries + Office Labour Salaries.
- **Total Report:** Direct union of Site and Office (All Expenses + Employee Salaries + Labour Salaries).

### Current Reality vs. Intended Behavior

| Aspect | Current Behavior (AUDIT-57 Baseline) | Intended Behavior | Severity |
| :--- | :--- | :--- | :--- |
| **Site Report** | Shows project expenses (including project-assigned synced labour salary expenses). | Shows project expenses (including project-assigned synced labour salary expenses). | **Correct** |
| **Total Report** | Shows all expenses (excluding `source_type = 'labour_salary'`) + all employee salaries + all labour salaries. | Shows all expenses (excluding `source_type = 'labour_salary'`) + all employee salaries + all labour salaries. | **Correct** |
| **Office Report** | Shows expenses where `project_id IS NULL` + employee salaries + **ALL labour salaries** (both site and office). | Shows expenses where `project_id IS NULL` + employee salaries + **ONLY unassigned/office labour salaries**. | **Medium Inconsistency** |
| **Site + Office Sum** | $\text{Site} + \text{Office} > \text{Total}$ (Site labour salaries appear in Site report as an expense and in Office report as a labour salary). | $\text{Site} + \text{Office} = \text{Total}$ (Exact mathematical partition). | **Target State** |
| **Total Report with Project Filter** | Filters expenses by project, zeroes employee salaries, but displays **ALL company labour salaries**. | Filters expenses by project, zeroes employee salaries, and **filters labour salaries to the selected project**. | **Medium Inconsistency** |

---

## 6. Project Filtering Trace Through the Stack

1. **User Interface ([index.blade.php](file:///D:/xampp-8.2/htdocs/crms/resources/views/pages/reports/index.blade.php#L183-L191)):**
   - The toolbar renders a `<select name="project_id">` dropdown for all three tabs (`site`, `office`, `total`).
2. **Controller ([ReportController.php](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/ReportController.php#L26-L32)):**
   - Extracts `$filters['project_id']` and passes it to the corresponding service method.
3. **Service Layer ([ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php)):**
   - In `siteReport()`: `$query->where('project_id', $filters['project_id'])`. Scopes properly.
   - In `buildMergedSummary()` and `mergedReportQueries()`:
     - `$expenseQuery`: `where('expenses.project_id', $filters['project_id'])`.
     - `$employeeSalaryQuery`: `whereRaw('1 = 0')`.
     - `$labourSalaryQuery` / `$labourQuery`: **No filter is applied.**

---

## 7. Synthetic Accounting Scenarios

### Scenario A: One Labourer Assigned to One Project
- Labourer: Raju (Daily rate: Rs 600)
- Assignment: Project Alpha (10 days: 2026-10-01 to 2026-10-10)
- Attendance: 10 days present on Project Alpha
- Salary Paid: Rs 6,000
- Synced Expense: `source_type = 'labour_salary'`, `source_id = $salary->id`, `project_id = Alpha`, `amount = 6000`

**Expected Results:**
- `Site Report (Project Alpha)`: 1 entry, Rs 6,000 paid.
- `Site Report (Project Beta)`: 0 entries, Rs 0 paid.
- `Office Report`: 0 entries, Rs 0 paid (Raju was a site worker).
- `Total Report`: 1 entry, Rs 6,000 paid (Attributed to Project Alpha).
- Reconciliation: $\text{Site (Alpha: 6000)} + \text{Office (0)} = \text{Total (6000)}$.

---

### Scenario B: One Labourer Assigned to Multiple Projects
- Labourer: Vikram (Daily rate: Rs 800)
- Assignment: Project Alpha (5 days: Oct 1–5); Project Beta (5 days: Oct 6–10)
- Attendance: 5 days on Alpha, 5 days on Beta
- Salary Paid: Rs 8,000
- **Under Current Predominant Rule:**
  - Tie-break picks Project Beta (latest date). Synced Expense: Rs 8,000 assigned to Beta.
  - `Site Report (Alpha)`: Rs 0 (Under-reported).
  - `Site Report (Beta)`: Rs 8,000 (Over-reported).
  - `Total Report`: Rs 8,000.
- **Under Proportional Attribution (Ideal Job-Costing):**
  - Project Alpha: 5 days $\times$ Rs 800 = Rs 4,000.
  - Project Beta: 5 days $\times$ Rs 800 = Rs 4,000.
  - `Site Report (Alpha)`: Rs 4,000.
  - `Site Report (Beta)`: Rs 4,000.
  - `Total Report`: Rs 8,000.

---

### Scenario C: Salary Paid in Multiple Instalments (Partial Payment)
- Labourer: Anil (Monthly salary: Rs 30,000, Project Alpha)
- Payment 1 (Oct 15): Partial payment of Rs 18,000 (Remaining: Rs 12,000). Status = `partial`.
- Synced Expense: `amount = 18000`, `paid_amt = 18000`, `unpaid_amt = 12000`, `project_id = Alpha`.
- Payment 2 (Oct 30): Residual payment of Rs 12,000. Status = `paid`.

**Expected Results on Total Report (after Payment 1):**
- Total Amount: Rs 30,000 (Gross accrual liability).
- Paid Amount: Rs 18,000 (Cash disbursed).
- Unpaid Amount: Rs 12,000 (Outstanding liability).
- Entries: Exactly 1 record (not duplicated by synced expense).

---

### Scenario D: Salary with Advance Adjustment
- Labourer: Mohan
- Step 1: Advance paid on Oct 01 = Rs 3,000 (Wallet debit, tracked in `advance_history`).
- Step 2: Salary earned on Oct 31 = Rs 10,000.
  - Advance adjusted = Rs 3,000.
  - Net cash paid = Rs 7,000.
  - `LabourSalary`: `salary_amount = 10000`, `advance_adjusted = 3000`, `paid_amount = 7000`.
- Synced Expense: `amount = 7000`, `paid_amt = 7000`, `is_advance = 1`, `project_id = Alpha`.

**Expected Results on Reports:**
- `Site Report (Alpha)`: Synced expense shows Rs 7,000 residual cash disbursed for the site.
- `Total Report`: Shows Rs 10,000 gross entitlement, Rs 7,000 cash paid.

---

### Scenario E: Office and Site Report Totals Reconciliation
Organization has during October:
1. Ordinary Office Expenses: Rs 5,000 (Rent & Electricity, `project_id IS NULL`).
2. Employee Salaries: Rs 40,000 (Office staff).
3. Office Cleaner Salary: Rs 4,000 (`LabourSalary`, unassigned, `project_id IS NULL`).
4. Site Ordinary Expenses (Project Alpha): Rs 25,000 (Materials).
5. Site Labour Salaries (Project Alpha): Rs 15,000 (Workers assigned to Alpha).

**Financial Partition Reconciliation:**
- **Site Report (Alpha):** Rs 25,000 (Materials) + Rs 15,000 (Site Labour) = **Rs 40,000**.
- **Office Report:** Rs 5,000 (Overhead) + Rs 40,000 (Staff) + Rs 4,000 (Office Cleaner) = **Rs 49,000**.
- **Total Report:** Rs 40,000 (Site) + Rs 49,000 (Office) = **Rs 89,000**.
$$\text{Site (Rs 40,000)} + \text{Office (Rs 49,000)} = \text{Total (Rs 89,000)} \quad \checkmark$$

---

## 8. Design Options & Trade-Off Analysis

### Option 1: Relational Join in ReportService (Recommended — No Schema Change)
- **Mechanism:**
  In [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php), join the synchronized `expenses` record to resolve `project_id` and `project_name` for `labour_salaries`:
  ```sql
  LEFT JOIN expenses AS salary_exp 
         ON salary_exp.source_type = 'labour_salary' 
        AND salary_exp.source_id = labour_salaries.id 
        AND salary_exp.deleted_at IS NULL
  LEFT JOIN projects ON projects.id = salary_exp.project_id
  ```
- **Rules Applied:**
  1. In `officeReport()`: Filter `$labourQuery->whereNull('salary_exp.project_id')`.
  2. In `totalReport()` with `project_id` filter: Filter `$labourQuery->where('salary_exp.project_id', $filters['project_id'])`.
  3. In ledger display: Replace `'-' as project_name` with `COALESCE(projects.name, '-') as project_name`.
- **Trade-Offs:**
  - *Pros:* Zero database migrations; zero schema risk; achieves 100% mutual exclusivity; fixes project filter on Total Report; populates project name in ledger.
  - *Cons:* Multi-project salaries remain attributed to the predominant project.

---

### Option 2: Proportional Split via Multiple Synced Expenses
- **Mechanism:**
  Modify [LabourSalaryController::syncSalaryExpense()](file:///D:/xampp-8.2/htdocs/crms/app/Http/Controllers/LabourSalaryController.php#L480-L515) so that when a salary covers attendances across $N$ projects, it generates $N$ synced `Expense` records, each with its proportional share of amount and days.
- **Trade-Offs:**
  - *Pros:* Accurate site-level job costing for multi-project labourers. No schema change needed.
  - *Cons:* Changes core controller business logic and payment workflow. High complexity when handling advance adjustments and salary rollbacks.

---

### Option 3: Add `project_id` to `labour_salaries` Table (Schema Change)
- **Mechanism:**
  Create a migration adding `project_id` to `labour_salaries` table.
- **Trade-Offs:**
  - *Pros:* Direct foreign key on the model.
  - *Cons:* **Unnecessary.** The project is already stored on the synced `expenses` record. It alters database schema and still cannot support multi-project salary splits without child tables.

---

## 9. Are Schema Changes Actually Necessary?

### Formal Conclusion: **NO.**
Schema changes are **not required** because:
1. `LabourSalaryController::syncSalaryExpense()` already persists the resolved `project_id` on the `expenses` record linked by `source_type = 'labour_salary'` and `source_id = labour_salaries.id`.
2. `labour_attendances` already persists `labour_salary_id` and links by date to `labour_assignments`.
3. Option 1 achieves full project attribution, project filtering, ledger project naming, and mutual exclusivity between Office and Site reports strictly within the query layer of [ReportService.php](file:///D:/xampp-8.2/htdocs/crms/app/Services/ReportService.php).

---

## 10. Proposed Regression Test Plan (For Future Implementation)

When Option 1 is implemented, the following test cases should be added to [tests/Feature/ReportControllerUiTest.php](file:///D:/xampp-8.2/htdocs/crms/tests/Feature/ReportControllerUiTest.php):

1. **`test_office_report_excludes_site_assigned_labour_salaries()`**:
   - Create a site-assigned labour salary (Rs 5,000, `project_id = 1`).
   - Create an unassigned office cleaner salary (Rs 3,000, `project_id = null`).
   - Request `type = 'office'`.
   - Assert summary count = 1 (cleaner only), total = Rs 3,000. Site salary is excluded.
2. **`test_site_and_office_reports_sum_to_total_report()`**:
   - Setup: 1 office expense, 1 site expense, 1 employee salary, 1 site labour salary, 1 office labour salary.
   - Assert `siteReport['summary']['paid'] + officeReport['summary']['paid'] == totalReport['summary']['paid']`.
3. **`test_total_report_filters_labour_salaries_by_project()`**:
   - Setup: Project A labour salary (Rs 4,000) and Project B labour salary (Rs 6,000).
   - Request `type = 'total', project_id = Project A`.
   - Assert summary total = Rs 4,000. Project B salary is excluded.
4. **`test_total_report_ledger_displays_project_name_for_labour_salary()`**:
   - Request `type = 'total'`.
   - Assert the labour salary row in the ledger displays the assigned project name instead of `'-'`.

---

## 11. Safety & Execution Declaration

- **Persistent Databases:** Zero connections made to `crms`, `admin_crms`, or `admin_crm_demo`.
- **Test Execution:** No tests or test runners were executed during this audit.
- **Server & Browser:** Zero browser interactions or server requests initiated.
- **Repository State:** Zero code or schema modifications made. Working tree remains identical.

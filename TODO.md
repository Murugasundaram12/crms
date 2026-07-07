# TODO - Labour Expenses List Fields Upgrade

## Step 1 (done): Repo exploration & current files identified

- Located Labour Expense views:
    - resources/views/pages/labour_expenses/history.blade.php
    - resources/views/pages/labour_expenses/weekly.blade.php
    - resources/views/pages/labour_expenses/advance.blade.php
    - resources/views/pages/labour_expenses/deleted.blade.php
- Located LabourExpensesController methods that supply those pages.

## Step 2: Align Labour Expenses UI columns to requested spec

- Update Labour history table columns:
    - Paid date
    - Main Category
    - Category
    - Name
    - Project Name
    - Labour Name
    - Amount
    - Paid / Unpaid
    - Advanced Amount
    - Image
    - Payment Mode
    - Description
    - Added By / Edited By / Advance Edited By
    - Action: Edit + Delete buttons
    - Plus delete UI/logic (checkbox/modal) tied to labour-expenses-delete_record

## Step 3: Update Labour weekly salary list

- Implement Week / Unpaid Amount / Advance Amount (aggregated per week)
- Ensure controller produces week-grouped totals.

## Step 4: Update Labour advance amount list

- Update list to match:
    - ID / Name / Job Title / Salary / Labour Role / Advance Amount
    - Action (history + money button)

## Step 5: Update Labour deleted history list

- Add full requested deleted columns including Deleted Date
- Ensure controller provides delete_reason, categories, image/payment_mode, added/edited/advance edited by.

## Step 6: Quick verification

- php artisan route:list | findstr labour-expenses
- Manually check each labour page renders without missing variables.

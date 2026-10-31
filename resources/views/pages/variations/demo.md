# HouseFix360 CRM - Modules, Functionalities, and Overall Flow

This document explains the modules used in this project, their main fields, functions, and business flow. It is written as a handoff/reference document for reusing the same logic in another project.

## Project Overview

This is a Laravel-based CRM for house/service/project management. The system manages clients, projects, users/members, wallet income, transfers, expenses, labour expenses, vendor expenses, unpaid amounts, advance amounts, reports, and dashboard summaries.

Core money flow:

- Client/project payment comes into `wallet`.
- Wallet amount updates logged-in user's wallet balance.
- Project `advance_amt` and `profit` are adjusted.
- Expenses reduce user wallet or vendor/labour advance balances.
- Unpaid amounts can be paid later.
- Labour/vendor advance amounts can be adjusted against unpaid expenses.
- Reports summarize income, expenses, project payments, and client payments.

## Main Database Models

`users`

- Stores CRM members/admin users.
- Important fields used: `id`, `first_name`, `last_name`, `email`, `phone`, `wallet`, `active_status`, `delete_status`.
- Used for login, permissions, wallet balance, transfer sender/receiver, expense creator/editor.

`clientdetails`

- Stores client master data.
- Fields: `first_name`, `last_name`, `email`, `company_name`, `address`, `phone`, `active_status`, `delete_status`.

`project_details`

- Stores project master data.
- Fields: `name`, `client_id`, `advance_amt`, `total_amt`, `profit`, `project_status`, `payment_mode`, `start_date`, `end_date`, `active_status`, `delete_status`.

`wallet`

- Stores income/adjustment history.
- Fields: `user_id`, `client_id`, `project_id`, `amount`, `payment_mode`, `stage_id`, `transfer_type`, `description`, `current_date`, `active_status`, `delete_status`.

`transferdetails`

- Stores member-to-member and member-to-vendor transfers.
- Fields: `amount`, `member_id`, `user_id`, `current_date`, `description`, `payment_mode`, `is_vendor`, `vendor_id`.

`expenses`

- Main table for normal, labour, and vendor expenses.
- Fields: `amount`, `main_category_id`, `category_id`, `project_id`, `user_id`, `current_date`, `paid_amt`, `unpaid_amt`, `extra_amt`, `image`, `payment_mode`, `description`, `editedBy`, `reason`, `labour_id`, `vendor_id`, `is_advance`, `deleted_at`.

`expenses_unpaid_date`

- Stores unpaid payment history.
- Fields: `expense_id`, `amount`, `unpaid_amt`, `current_date`, `description`.

`advance_history`

- Stores labour/vendor advance adjustment history.
- Fields: `labour_id`, `vendor_id`, `expense_id`, `amount`, `date`.

`labour_details`

- Stores labour master data.
- Fields: `name`, `job_title`, `phone`, `gender`, `salary`, `government_image`, `advance_amt`, `labour_role`.

`vendor_details`

- Stores vendor master data.
- Fields: `name`, `phone`, `advance_amt`, `address`.

`category`

- Expense category.
- Fields: `name`, `main_category_id`, `active_status`, `delete_status`.

`main_category`

- Parent category for expenses.
- Fields: `name`, `status`.

`payment`

- Payment modes such as cash/bank/UPI.
- Fields: `name`, `active_status`, `delete_status`.

`stage`

- Project/payment stage master.
- Fields: `name`, `active_status`, `delete_status`.

`labour_role`

- Labour role and salary master.
- Fields: `name`, `salary`, `salary_type`.

`attendance`

- Check-in/check-out tracking.
- Fields: `user_id`, `notes`, `duration`.

## Authentication and Access

Controllers/routes:

- Laravel auth routes.
- Custom forgot password flow in `LoginController`.
- Role and permission logic uses Spatie permissions.

Functionalities:

- Login/logout.
- Forgot password/check mail/send mail/reset password.
- Role-based access with `@can` and roles.
- Maintenance middleware blocks access when maintenance is enabled.

Flow:

- User logs in.
- Authenticated routes are protected by `auth` and `maintenance` middleware.
- Sidebar/navbar features are shown based on permissions.

## Dashboard Module

Controller:

- `AnalyticsController@index`
- `AnalyticsController@store`
- `AnalyticsController@update`

Functionalities:

- Admin dashboard and user dashboard.
- Total members count.
- Open/closed project count.
- Paid amount summary.
- Unpaid amount summary.
- Monthly transfer amount.
- Wallet total.
- Income chart from `wallet`.
- Expense chart from `expenses`.
- Today project-wise income and expense.
- Recent transfer history.
- Attendance check-in/check-out.

Flow:

- Admin sees all users/projects/wallet/expense data.
- Non-admin sees only own wallet/expense/transfer data.
- Check-in creates an `attendance` record with `notes = 0`.
- Check-out updates same day's attendance with `notes = 1` and duration.

## Role Module

Controller:

- `RoleController`

Functionalities:

- Role list.
- Add role.
- Edit role.
- Update role permissions.
- Delete role.

Flow:

- Roles are created with permission set.
- Users are assigned roles.
- Sidebar/action buttons depend on permissions.

## User/Member Module

Controller:

- `UserController`

Functionalities:

- User/member list.
- Add user.
- View user.
- Edit user.
- Update profile/job details.
- Delete user.
- Phone unique check.
- Profile photo upload.
- Change password.
- Active/inactive status update.

Flow:

- Users are CRM members.
- Each user has a wallet balance.
- Wallet/expenses/transfers affect the `users.wallet` value.
- Role controls what user can access.

## Client Module

Controller:

- `ClientDetailsController`

Functionalities:

- Client list.
- Add client.
- Edit client.
- View client.
- Delete client.
- Search/filter clients.

Main fields:

- First name, last name, email, company name, address, phone.

Flow:

- Client is created first.
- Project is linked to client.
- Wallet income entries are linked to client and project.
- Client summary report uses client + project + wallet data.

## Project Module

Controller:

- `ProjectDetailsController`

Functionalities:

- Project list.
- Add project.
- View project.
- Edit project.
- Delete project.
- Project details view.

Main fields:

- Project name, client, total amount, advance amount, profit, payment mode, start date, end date, project status.

Flow:

- Project is created under a client.
- Wallet add/subtract updates project `advance_amt` and `profit`.
- Expenses are linked to project.
- Reports calculate income/expense project-wise.

Project status:

- `project_status = 0` means open/running.
- `project_status = 1` means closed/completed.

## Main Category Module

Controller:

- `MainCategoryController`

Functionalities:

- Main category list.
- Add/edit/update/delete.
- Status update.

Flow:

- Main category acts as parent for expense categories.
- Add expense forms load categories based on selected main category.

## Category Module

Controller:

- `CategoryController`
- Dynamic category add also handled in `ExpensesController@new_category`.

Functionalities:

- Category list.
- Add/edit/update/delete.
- Active/inactive status update.
- Dynamic category creation from expense form.

Flow:

- Category belongs to main category.
- Expenses/labour expenses/vendor expenses require category.
- Category filter is used in history and reports.

## Payment Mode Module

Controller:

- `PaymentController`

Functionalities:

- Payment mode list.
- Add/edit/update/delete.

Flow:

- Payment mode is used in wallet, transfer, expenses, labour expenses, vendor expenses, and reports.

## Stage Module

Controller:

- `StageController`

Functionalities:

- Stage list.
- Add/edit/update/delete.

Flow:

- Stage is selected when adding wallet income.
- Wallet history and client summary show stage name.

## Wallet Module

Controller:

- `WalletController@index`
- `WalletController@create`
- `WalletController@store`

Pages:

- Wallet History.
- Add Wallet.

Add Wallet fields:

- `client_id`
- `project_id`
- `amount`
- `payment_mode`
- `transfer_type`
- `description`
- `current_date`
- `time`
- `stage_id`

Transfer type:

- `0` = Add/Credited.
- `1` = Subtract/Debited.

History filters:

- Date range.
- Client.
- Project.
- Search.
- Pagination.

Search supports:

- Client name.
- Payment name.
- Member name.
- Stage name.
- Project name.
- Amount.
- Description.
- Search text `credited` filters `transfer_type = 0`.
- Search text `debited` filters `transfer_type = 1`.

Flow:

- Add wallet creates a `wallet` record.
- If credited:
    - Project `advance_amt` increases.
    - Project `profit` decreases.
    - Logged-in user `wallet` increases.
- If debited:
    - Project `advance_amt` decreases.
    - Project `profit` increases.
    - Logged-in user `wallet` decreases.
- Subtract is validated against current user wallet balance in frontend.

## Transfer Module

Controller:

- `TransferController@index`
- `TransferController@create`
- `TransferController@store`
- `TransferController@insufficientamt`
- `TransferController@userDetail`
- `TransferController@vendor_history`

Pages:

- Transfer History.
- Vendor History.
- Add Transfer modal/form.

Fields:

- `user_type`
- `member_id`
- `amount`
- `payment_mode`
- `current_date`
- `time`
- `description`

User type:

- `0` = member/user transfer.
- `1` = vendor transfer.

Flow:

- Member transfer:
    - Logged-in user wallet decreases.
    - Selected member wallet increases.
    - `transferdetails.is_vendor = 0`.
- Vendor transfer:
    - Logged-in user wallet decreases.
    - Selected vendor `advance_amt` increases.
    - `transferdetails.is_vendor = 1`.
- Insufficient amount check validates logged-in user wallet.
- Vendor history lists transfers where `is_vendor = 1`.

## Normal Expenses Module

Controller:

- `ExpensesController`
- `UnpaidExpensesController`

Pages:

- Expense History.
- Unpaid History.
- Deleted History.
- Reports.
- Add Expenses.

Add Expense fields:

- `main_category_id`
- `category_id`
- `project_id`
- `amount`
- `payment_mode`
- `image`
- `description`
- `paid_amt`
- `unpaid_amt`
- `current_date`
- `time`

Main functions:

- `index` - expense history.
- `create` - add form.
- `store` - save expense.
- `edit/update` - update expense.
- `unpaid_create/unpaid_store` - pay unpaid amount.
- `expensedelete` - soft delete with reason.
- `image_delete` - remove expense image.
- `delete_record` - deleted history.
- `reports_history` - expense reports.
- Export/PDF functions.
- `new_category` and `category` - category ajax functions.
- `insufficientamt` - wallet balance check.

Flow:

- Expense is saved in `expenses`.
- `current_date` is date + time.
- If `paid_amt > amount`, difference becomes `extra_amt`.
- If `amount > paid_amt`, difference becomes `unpaid_amt`.
- User wallet decreases by `paid_amt`.
- Unpaid payment later:
    - Creates `expenses_unpaid_date`.
    - User wallet decreases by paid unpaid amount.
    - Expense `paid_amt` increases.
    - Expense `unpaid_amt` decreases.
- Delete:
    - Reason is saved.
    - User wallet is refunded by paid amount.
    - Expense is soft deleted.

## Labour Master Module

Controller:

- `LabourController`

Functionalities:

- Labour list.
- Add labour.
- Edit/update labour.
- Delete labour.
- Salary fetch by role.
- Phone unique check.

Fields:

- Name, job title, phone, gender, salary, government image, advance amount, labour role.

Flow:

- Labour master is required before adding labour expenses.
- Labour advance amount changes based on labour expense extra amount and advance settlement.

## Labour Role Module

Controller:

- `LabourRoleController`

Functionalities:

- Labour role list.
- Add/edit/update/delete labour role.

Fields:

- Name, salary, salary type.

Flow:

- Labour form uses labour role to populate salary.

## Labour Expenses Module

Controller:

- `LabourExpensesController`

Pages:

- Weekly History.
- Expense History.
- Advance Amount.
- Deleted History.
- Add Labour Expenses.

Add Labour Expense fields:

- `main_category_id`
- `category_id`
- `project_id`
- `labour_id`
- `amount`
- `payment_mode`
- `image`
- `description`
- `paid_amt`
- `unpaid_amt`
- `current_date`
- `time`

Main functions:

- `index` - weekly summary.
- `create/store` - add labour expense.
- `labour_salary` - fetch labour salary/advance.
- `labour_expenses_history` - labour expense history.
- `labour_expense_project` - weekly labour project summary.
- `labour_expenses_details` - day-wise labour details.
- `labour_expenses_store` - pay selected unpaid project expenses.
- `labour_advance` - labour advance list.
- `advance_form` - advance settlement form.
- `labour_project_amount` - ajax project advance/unpaid amount.
- `labour_advance_store` - settle advance against unpaid.
- `edit/update/delete`.
- Deleted history, export, PDF.

Flow:

- Labour expense is saved in `expenses` with `labour_id`.
- User wallet decreases by `paid_amt`.
- If `paid_amt > amount`, difference becomes `extra_amt`.
- Labour `advance_amt` increases by `extra_amt`.
- Weekly history groups labour expenses by week.
- Advance settlement:
    - Labour advance decreases.
    - Project/labour unpaid amount decreases.
    - `advance_history` is created.
    - Expense `is_advance` stores the user who adjusted.
- Delete:
    - Labour advance is rolled back.
    - User wallet is refunded.
    - Expense is soft deleted.

## Vendor Master Module

Controller:

- `VendorController`

Functionalities:

- Vendor list.
- Add vendor.
- Edit/update vendor.
- Delete vendor.

Fields:

- Name, phone, advance amount, address.

Flow:

- Vendor master is required before adding vendor expenses.
- Vendor `advance_amt` is used like vendor wallet/advance balance.

## Vendor Expenses Module

Controller:

- `VendorExpensesController`

Pages:

- Expense History.
- Unpaid History.
- Advance Amount.
- Deleted History.
- Add Vendor Expenses.
- Vendor History.
- Vendor Withdraw.

Add Vendor Expense fields:

- `main_category_id`
- `category_id`
- `project_id`
- `vendor_id`
- `amount`
- `payment_mode`
- `image`
- `description`
- `paid_amt`
- `unpaid_amt`
- `current_date`
- `time`

Main functions:

- `index` - vendor expense history.
- `create/store` - add vendor expense.
- `vendor_salary` - fetch vendor details/advance.
- `unpaid_expenses` - vendor unpaid history.
- `unpaid_edit/unpaid_store` - vendor unpaid settlement.
- `advance_expenses` - vendor advance list.
- `advance_form/advance_store` - vendor advance settlement.
- `vendor_project_amount` - project advance/unpaid amount.
- `vendor_history` - vendor transfer history.
- `withdraw/withdraw_save` - move vendor advance back to member wallet.
- `vendor_insufficant` - vendor advance balance check.
- Edit/update/delete.
- Deleted history, export, PDF.

Flow:

- Vendor expense is saved in `expenses` with `vendor_id`.
- Vendor `advance_amt` decreases by `paid_amt`.
- If unpaid exists, it can be paid later from vendor advance balance.
- Vendor advance settlement adjusts project unpaid amounts.
- Vendor withdraw:
    - Vendor `advance_amt` decreases.
    - Selected member/user wallet increases.
- Delete:
    - Vendor `advance_amt` is refunded by paid amount.
    - Expense is soft deleted.

## Unpaid Expenses Module

Controller:

- `UnpaidExpensesController`

Pages:

- Unpaid History.
- Unpaid Payment Form.

Functionalities:

- List normal unpaid expenses.
- Pay unpaid amount.
- Export/PDF.
- Delete unpaid expense.

Flow:

- Lists `expenses` where `unpaid_amt != 0`.
- When unpaid amount is paid:
    - Creates `expenses_unpaid_date`.
    - Expense `paid_amt` increases.
    - Expense `unpaid_amt` decreases.
    - User wallet decreases.

## Reports Module

Controller:

- `ReportsController`

Pages:

- Client Summary.
- Payment Summary.
- Payment Income.
- Payment Expenses.

Client Summary:

- Joins project, wallet, stage, client, payment.
- Shows client/project income summary.
- Filters: date range, client, project, search.
- Export/PDF available.

Payment Summary:

- Project-wise summary.
- Shows project advance amount and total expenses.

Payment Income:

- Shows wallet income entries for one project.
- Filters: date range, client, search.
- Export/PDF available.

Payment Expenses:

- Shows expenses for one project.
- Filters: date range, category, user, search.
- Export/PDF available.

## Maintenance Module

Controller:

- `MaintenanceController`

Functionality:

- Shows maintenance page.
- Main authenticated route group uses `maintenance` middleware.

Flow:

- If maintenance mode is active, normal users are redirected/blocked according to middleware logic.

## Common UI and Utility Functionalities

Common filters:

- Date range.
- Search.
- Pagination.
- Main category.
- Category.
- Project.
- User/labour/vendor.
- Client.

Common actions:

- Add.
- Edit.
- Update.
- Delete/soft delete.
- Export Excel.
- Download PDF.
- Ajax dropdown loading.
- Balance validation.
- Toastr/sweet alert messages.

Common amount calculations:

- `paid_amt > amount`: extra/advance amount.
- `amount > paid_amt`: unpaid amount.
- `paid_amt`: actual amount paid now.
- `unpaid_amt`: amount pending.
- `extra_amt`: advance/excess amount.

## Overall Project Flow

1. Admin creates roles and users.
2. Admin creates master data:
    - Clients.
    - Projects.
    - Main categories.
    - Categories.
    - Payment modes.
    - Stages.
    - Labour roles.
    - Labour.
    - Vendors.
3. Wallet income is added for client/project.
    - User wallet updates.
    - Project advance/profit updates.
4. Transfers can move money:
    - Member to member.
    - Member to vendor advance.
5. Expenses are added:
    - Normal expenses reduce user wallet.
    - Labour expenses reduce user wallet and may increase labour advance.
    - Vendor expenses reduce vendor advance.
6. If expense is partially paid, unpaid amount remains.
7. Unpaid amounts can be paid later.
8. Labour/vendor advance amounts can be adjusted against unpaid expenses.
9. Deleted expense records are kept using soft delete and reason.
10. Dashboard displays totals, charts, income/expense summaries, transfer history, and attendance.
11. Reports provide client/project/payment summaries with Excel/PDF export.

## Reuse Notes for Another Project

Minimum modules to migrate:

- Auth/users/roles/permissions.
- Client.
- Project.
- Wallet.
- Transfer.
- Expense.
- Labour and labour expenses.
- Vendor and vendor expenses.
- Reports.
- Master tables: category, main category, payment, stage.

Critical balance fields:

- `users.wallet`
- `project_details.advance_amt`
- `project_details.profit`
- `labour_details.advance_amt`
- `vendor_details.advance_amt`
- `expenses.paid_amt`
- `expenses.unpaid_amt`
- `expenses.extra_amt`

Most important business rule:

- Wallet income increases or decreases user wallet and project advance/profit.
- Normal expenses reduce user wallet.
- Labour expenses affect user wallet and labour advance.
- Vendor expenses affect vendor advance.
- Unpaid and advance flows are adjustment layers over the same `expenses` table.

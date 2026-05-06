# Update Laravel Project Modules (Client, Quotation, Expenses, Report, Project)

## Phase 1: Database Updates ✅

- [x] Migrations created/edited
- [ ] php artisan migrate (run manually)

## Phase 2: Client ✅

- [x] All updates complete

## Phase 3: Project ✅

- [x] Model/controller/view updated

## Phase 4: Quotation ✅

- [x] Unit dropdown
- [x] Dependent Client-Project-Quotation
- [x] Route/controller/JS added
- [x] Subtitle validation relaxed

## Phase 5: Expenses Module (Next)

- [ ] Create ExpenseController.php
- [ ] Create views/pages/expenses/index.blade.php
- [ ] routes/web.php expenses.\*
- [ ] layouts/app.blade.php menu
- [ ] Permissions

## Phase 6: Report

- [ ] ReportController 3 types + filters
- [ ] reports/index.blade.php

## All Phases Complete ✅

Run:

1. php artisan migrate
2. php artisan db:seed --class=ExpensePermissionSeeder
3. php artisan optimize:clear

Test modules and verify.

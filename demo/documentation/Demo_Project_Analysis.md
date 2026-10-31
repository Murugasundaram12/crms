# Demo Project Analysis

**Audit scope:** `/home/admin/web/housefix360.com/public_html/crms/demo`  
**Application name:** CRM (from `APP_NAME=CRM`)  
**Audit date:** 31 October 2026 (Asia/Calcutta)

## Executive summary

This is a Laravel-based construction/service-business finance and project CRM. The verified implementation covers clients, projects, income/payment records, wallet and transfers, general expenses, unpaid expenses, labour and labour costs, vendors and vendor costs, master data, user/role administration, dashboard analytics, and report/export endpoints.

## Technology and infrastructure

- Laravel Framework `^9.0`; PHP `^8.0.2`.
- MySQL configuration is present in `.env`; database values and credentials were not reproduced here.
- Blade server-rendered views; Bootstrap 5/Sneat-style layout; jQuery, Select2, ApexCharts and Laravel Mix.
- Sanctum API authentication package, Spatie Laravel Permission, Laravel UI authentication.
- `barryvdh/laravel-dompdf` for PDF output and `maatwebsite/excel` for Excel exports.
- File/session cache and synchronous queue are configured in the inspected environment.

## Verified route and module inventory

| Module | Verified implementation | Main routes / views |
|---|---|---|
| Authentication | Login, logout, registration routes, forgot/reset password, maintenance page | `Auth::routes()`, `auth/login`, password views |
| Dashboard & attendance | Analytics cards/charts, income/expense summaries, project summaries, clock-in/out endpoints | `dashboard`, `checking`, `checkout` |
| Users | List, create, view, edit, update, job update, delete, profile photo, phone uniqueness | `user*`, `profile_photo_upload` |
| Roles & permissions | CRUD role screens and permission assignment | `roles*`, Spatie permission tables |
| Main categories | CRUD resource and status update | `maincategory*`, `main-status-update` |
| Categories | List/create/edit/delete, status and main-category association | `category*`, `updateCategory*` |
| Stages | CRUD stage setup | `stage*` |
| Payment methods | CRUD payment method setup | `payment*` |
| Clients | CRUD plus detail page | `client*` |
| Projects | CRUD plus details and project view | `project*` |
| Wallet | Wallet history and add-money form | `wallet-history`, `wallet-create` |
| Transfers | Transfer history, create, amount check and vendor history | `transfer*`, `vendor-history` |
| General expenses | Create/edit/delete/history, import, unpaid handling, deleted history and export/PDF/report routes | `expenses*`, `unpaid*` |
| Labour | Labour master records, roles and salary screen | `labour*`, `labour-role*` |
| Labour expenses | Expense history, create/edit/delete, advance, project/labour summaries and export/PDF routes | `labour-expenses*`, `labour-salary` |
| Vendors | Vendor master records | `vendor*` |
| Vendor expenses | Vendor expense, unpaid, advance, withdrawal, history, export/PDF routes | `vendor-expenses*`, `vendor-history` |
| Reports | Client summary, payment summary, income and expense detail with export/PDF | `client-summary`, `payment-summary`, `payment-income`, `payment-expenses` |

**Total documented modules:** 18 functional areas (authentication is included as an area; overlapping submenus are consolidated).

## Data relationships verified from models and migrations

- A client can have multiple projects (`project_details.client_id`).
- Projects are referenced by wallet entries and expenses.
- Expenses reference a category, optional project, recording user, paid amount and unpaid amount; soft delete support is present.
- Main categories have many categories.
- Users use `HasRoles` and soft deletes; the permission schema contains roles, permissions, model-role and role-permission pivot tables.
- Labour records have a labour role; labour expenses reference project/labour/category and payment details.
- Vendor and vendor-expense areas maintain vendor advances, unpaid amounts and withdrawals.

## Authentication, authorization and business rules

All core routes are inside `auth` and `maintenance` middleware. The sidebar uses `@can`/`@canany` permission checks. Permission names are defined through role screens and are data-driven; an exhaustive role matrix cannot be confirmed without reading database rows, which was intentionally avoided. Controllers and views verify required fields such as names, dates, selected project/category, amounts and payment mode. Expense-related forms include insufficient-amount checks and calculate unpaid amounts from total and paid values.

## UI verification status

Blade views and route/controller code were inspected. A live UI session was not used because the project is configured for MySQL and the available URL appears to be a deployed endpoint; no database connection, login, or record mutation was authorized. Therefore the PDF contains no screenshots presented as genuine application captures.

## Not verified

- Real data, actual role assignments, menu visibility for each role, and dashboard values.
- Exact browser rendering and responsive behavior.
- Successful delivery of email reset links, external API calls, and production mail configuration.
- Whether all export/PDF endpoints work against the current database contents.
- The disabled “Weekly History” labour submenu is rendered as `href="#"` in the inspected menu and is not documented as a usable workflow.

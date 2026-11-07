# CRMS Project Analysis

**Scope:** `demo/` Laravel application  
**Audit date:** 31 October 2026 (Asia/Calcutta)  
**Evidence:** routes, controllers, models, migrations, Blade views, menu JSON and installed packages. Live database records, credentials and production UI were not accessed.

## Old CRM PDF design reference

The supplied reference PDF is a two-page landscape, image-led document (A4-like `841.68 × 595.2` points) with a strong visual cover/hero treatment and embedded raster artwork. It was used for presentation direction only: compact opening, high-contrast typography, visual hierarchy, modular explanations and a polished brochure/manual feel. No old-CRM feature, field or workflow was copied into the current CRMS content.

## Executive summary

CRMS is a Laravel 9, Blade-based project and finance CRM. Verified functionality covers authentication, dashboard attendance actions, clients, projects, wallet/transfers, general expenses, labour and labour expenses, vendors and vendor expenses, master data, users/roles and report/PDF/Excel routes. The source menu JSON is partial; therefore the mandated 15-section client structure is a documentation grouping, not a claim that all 15 labels are currently rendered as sidebar items.

## Architecture

- PHP/Laravel 9 with Blade templates and a Bootstrap/Sneat-style layout.
- Eloquent models include `ClientDetails`, `ProjectDetails`, `Expenses`, `Labour`, `LabourRole`, `Vendor`, `Payment`, `Stage`, `Wallet`, `Transfer`, `Attendance`, `Salary`, `Settings` and `User`.
- Spatie Permission provides roles and permissions; authentication and route groups protect operational pages.
- Dompdf generates PDF outputs; Laravel Excel/PhpSpreadsheet supports exports/imports.
- MySQL is configured by environment; credentials are intentionally excluded.

## Mandated module map

| # | Client section | Verified source | Status |
|---:|---|---|---|
| 1 | Dashboard | `AnalyticsController`, `/dashboard` | Verified, live values unverified |
| 2 | Clients | `ClientDetailsController`, `/client*` | Verified |
| 3 | Projects | `ProjectDetailsController`, `/project*` | Verified |
| 4 | Tools & Materials | No standalone demo route/controller/view found | Unverified |
| 5 | Salaries | `/labour/salary`, `/labour-salary` | Partially verified |
| 6 | Users | `UserController`, `/user*` | Verified |
| 7 | Attendance | `/checking`, `/checkout`, `Attendance` model | Partially verified |
| 8 | Labours | `LabourController`, `LabourRoleController` | Verified |
| 9 | Vendors | `VendorController`, `/vendor*` | Verified |
| 10 | Expenses | `ExpensesController`, `/expenses*` | Verified |
| 11 | Labour Expenses | `LabourExpensesController` | Verified |
| 12 | Vendor Expenses | `VendorExpensesController` | Verified |
| 13 | History | expense/labour/vendor history routes | Grouped sub-features verified |
| 14 | Reports | `ReportsController`, report Blade/PDF views | Verified, live output unverified |
| 15 | Settings | roles, categories, stages, payment setup | Grouped; exact parent menu unverified |

## Authentication and access

Login, logout, registration, forgot-password and reset routes are present. Operational routes are inside authenticated middleware. Sidebar and actions are permission-sensitive. Exact role assignments and production permission rows were not read.

## Relationships and business rules

- A client has many projects through `project_details.client_id`.
- Project context is reused by wallet, transfer, expense, labour expense and vendor expense flows.
- Main categories feed categories; categories feed expense forms.
- Labour records use labour roles; labour expenses reference labour and project context.
- Vendor records support vendor expense, advance, unpaid and withdrawal histories.
- Expense forms calculate or store paid/unpaid values and expose insufficient-amount checks.
- Users use roles and permissions; user phone/email uniqueness and password confirmation validations are implemented in the user flow.

## Reports and exports

Verified report families are Client Summary, Payment Summary, Payment Income and Payment Expenses. Expense, labour expense and vendor expense screens expose PDF and/or Excel routes. Exact filters, totals and file appearance need live browser/database verification.

## Quality and evidence limitations

No source code, schema, configuration or database records were changed. No live login was attempted. Genuine screenshots are therefore not embedded in the manual; the gap register identifies each pending capture. The old PDF was used only as a presentation reference: compact cover-led structure, strong visual hierarchy, captions/sections and workflow-oriented explanation.

# CRMS Module Workflow

The arrows below describe only relationships supported by inspected routes, controllers, models and views. They do not assert that every relationship is exposed in the current sidebar.

## Primary flow

`Users / Roles` → `Dashboard` → `Clients` → `Projects` → `Expenses / Labour Expenses / Vendor Expenses` → `History / Reports`

## Module relationships

| Source | Uses / feeds | Actual purpose |
|---|---|---|
| Clients | Projects | Project creation selects a client; project model stores client relationship. |
| Projects | Wallet, Transfers, Expenses | Financial records can be associated with project context. |
| Main Categories / Categories | Expenses, Labour Expenses, Vendor Expenses | Master values drive expense classification dropdowns. |
| Labours / Labour Roles | Salaries, Labour Expenses | Labour master and role data support labour cost workflows. |
| Vendors | Vendor Expenses, Vendor History | Vendor records support expense, advance, unpaid and withdrawal histories. |
| Expenses | Unpaid History, Deleted History, Reports | Paid/unpaid and deleted records are surfaced through history/report routes. |
| Labour Expenses | Labour History, Project/Labour details, Reports | Labour spending can be reviewed by project or labour and exported. |
| Vendor Expenses | Vendor History, Unpaid/Advance/Deleted views, Reports | Vendor financial lifecycle is split across dedicated subviews. |
| Users / Roles | All protected modules | Permissions control access to routes/actions and likely sidebar visibility. |

## End-to-end procedures

### Client → Project

1. Client Details-la client create pannunga.
2. Project Details-la client select pannunga.
3. Project name, advance and total amount fill pannitu Save pannunga.
4. Project show/view screen-la linked context review pannunga.

### Project → Expense → Report

1. Main category and category setup ready-a vechukonga.
2. Expenses-la project, category, amount, payment mode, date/time and paid amount enter pannunga.
3. Save pannina paid/unpaid information history-la reflect aagum.
4. Client Summary, Payment Summary or Payment Expenses-la review/export pannunga.

### Labour → Labour Expense → Salary/History

1. Labour Role create pannunga.
2. Labour master record create pannunga.
3. Labour Expenses-la labour, project and payment details select pannunga.
4. Advance, salary, labour details and history views-la review pannunga.

### Vendor → Vendor Expense → Unpaid/Advance

1. Vendor master record create pannunga.
2. Vendor Expenses-la vendor/project/category and amount fill pannunga.
3. Paid, unpaid, advance or withdrawal action applicable screen-la use pannunga.
4. Vendor history and PDF/Excel outputs-la verify pannunga.

## Not verified as implemented

Tools & Materials, a dedicated universal History screen and a complete live Attendance listing were not verified in `demo/`. They are documented as gaps, not invented workflows.

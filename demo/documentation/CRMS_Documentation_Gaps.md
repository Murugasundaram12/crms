# CRMS Documentation Gaps

## Evidence limitations

1. Live browser session and database data were unavailable/unauthorised during this documentation run.
2. No credentials were used and no real customer, employee or financial data was copied.
3. Exact rendered labels, values, pagination and responsive behaviour remain pending live UI verification.

## Screenshot register

| Screen | Status | Reason |
|---|---|---|
| Login | Pending | No browser capture session used |
| Dashboard | Pending | Live data and authentication unavailable |
| Clients list/create/edit/show | Pending | Live UI not opened |
| Projects list/create/edit/show/view | Pending | Live UI not opened |
| Tools & Materials | Unverified | No standalone demo implementation found |
| Salaries | Pending | Routes exist; rendered screen not verified |
| Users and Roles | Pending | Live UI not opened |
| Attendance | Pending | Actions exist; listing UI not verified |
| Labours and Labour Roles | Pending | Live UI not opened |
| Vendors | Pending | Live UI not opened |
| Expenses and histories | Pending | Live UI not opened |
| Labour Expenses | Pending | Live UI not opened |
| Vendor Expenses | Pending | Live UI not opened |
| Reports and exports | Pending | Live totals/file rendering not verified |
| Settings/master data | Pending | Exact parent menu placement not verified |

## Functional gaps or unverified points

- `verticalMenu.json` visibly contains only a subset of the mandated sections; the 15-section structure is documented as a client taxonomy.
- Tools & Materials has no verified standalone controller, route or Blade workflow in `demo/`.
- A dedicated universal History parent screen was not found; history is distributed across expense, labour expense, vendor expense and transfer routes.
- Salary screens have routes, but their exact fields/calculation presentation requires live verification.
- Attendance has check-in/check-out actions; a full attendance listing and all status rules were not verified.
- Exact role/permission assignments depend on database rows and were not read.
- Email delivery, export correctness with live data and production third-party configuration remain unverified.

## Recommended follow-up

Run a read-only browser walkthrough with a non-production account, capture each pending screen, validate labels against the PDF, test export samples with synthetic data, and then replace the Pending notes/callouts in the manual.

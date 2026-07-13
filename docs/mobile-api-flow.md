# CRMS Mobile API Flow Explanation (Tanglish)

Indha file mobile app team-ku API flow puriyara mathiri prepare pannathu.

Current local API base URL:

```text
http://localhost/crms/api
```

Bruno/Postman collection-la request URL ellam direct text URL ah irukkum. Example:

```text
http://localhost/crms/api/login
http://localhost/crms/api/clients/1
```

URL-la `{{base_url}}`, `{{employee_id}}`, `{{client_id}}` madhiri variables ippo use pannala. Sample detail/update/delete APIs-la ID value `1` irukkum; real DB-la id `1` illa na `not found` response varum. App integrate pannumbothu first list API call panni valid id eduthu detail/update/delete API call pannanum.

## Mobile Developer Quick Rules

App team follow panna vendiya simple rules:

1. Public settings APIs first call pannanum: `/V1/getAppSettings`, `/V1/getModuleSettings`, `/V1/getMapSettings`.
2. Login apram protected APIs-ku `Authorization: Bearer {token}` header compulsory.
3. Detail/update/delete APIs-ku URL-la sample id `1` irukkum. Real app-la first list API-la irundhu valid id eduthu use pannanum.
4. Pagination response-la `current_page`, `per_page`, `total`, `last_page` varum. Mobile app infinite scroll/list pagination-ku use pannalam.
5. `status` query parameter list filter-ku use pannuvanga. Example attendance checked-in mattum venumna `status=checked_in`.
6. Employee normal login pannina own data mattum varum. Admin/Manager permission irundha all data filter panna mudiyum.
7. Check-in illama tracking call panna koodathu. Correct order: `check_in` -> `tracking/location` -> `check_out`.
8. Leave request create panna before `/leave-requests/options` call panni active `leave_type_id` edukkanum.

## API Quick Map

| Module | API | Use |
| --- | --- | --- |
| Settings | `GET /V1/getAppSettings` | App version, tracking interval, privacy URL, time settings. |
| Settings | `GET /V1/getModuleSettings` | Attendance/tracking/tasks/expenses/wallet/leave modules enable ah check panna. |
| Settings | `GET /V1/getMapSettings` | Dashboard map center, longitude, zoom, Google Maps key. |
| Auth | `POST /login` | Employee/admin login and token create. |
| Attendance | `POST /check_in` | Work start, attendance create, first tracking point save. |
| Tracking | `POST /tracking/location` | Background location history save. |
| Tracking | `POST /devices/live-status` | Latest device/current location update. |
| Attendance | `POST /check_out` | Work end, checkout time update, final tracking point save. |
| Employees | `GET /employees` | Employee list and filters. |
| Tasks | `GET /tasks` | Assigned/all task list based on permission. |
| Clients | `GET /clients` | Client list. |
| Projects | `GET /projects` | Project list. |
| Expenses | `GET /expenses` | Expense list. |
| Payments | `GET /payments` | Payment list, read-only. |
| Wallet | `GET /wallet` | Wallet/transfer history. |
| Leave | `GET /leave-requests` | Leave request list. |
| Masters | `GET /categories`, `GET /vendors`, `GET /labours` | Dropdown/master data. |

## Route Duplicate Check

Exact duplicate API route illa. Some routes intentional aliases:

- `POST /login` and `POST /auth/login`: rendu route-um same login-ku. Old/new app support.
- `GET /V1/getAppSettings`, `GET /V1/getModuleSettings`, `GET /V1/getMapSettings`: public mobile settings endpoints.
- `GET /settings/app`, `GET /settings/modules`, `GET /settings/map`: same settings-ku authenticated aliases.
- `PUT /employees/{id}` and `POST /employees/{id}/update`: mobile client PUT support illana POST fallback.
- `PUT /tasks/{id}` and `POST /tasks/{id}/update`: same reason.
- `POST /wallet/transfer` and `POST /wallet/store`: legacy route support.

Note: lowercase `/v1/...` route active illa. App-la uppercase `/V1/...` use pannunga.

## Latest API Safety Fixes

Recent checks-la fix pannina important points:

- Missing id route call pannumbothu raw Laravel error varaathu. Example `GET /clients/999` response: `Client not found.`
- `employees` routes model name internally `User` irundhalum API response `Employee not found.` nu clean ah varum.
- Project budget calculation-la `quotations.total_amount` column DB-la missing irundha crash aagaathu; `amount` column fallback use pannum.
- `payments/options` API quotations select pannumbothu DB column missing issue avoid pannirukkom.
- Project details Expenses tab-la category object JSON print aagathu; `Main Category - Category` format-la display aagum.
- Leave request create panna active leave type id mattum accept pannum. `/leave-requests/options` la varra id use pannanum.

## Common Error Meanings

### `Client not found.`
`/clients/1` call pannirukeenga, but DB-la `clients.id = 1` illa. First `GET /clients` call panni valid id use pannunga.

### `Project not found.`, `Task not found.`, `Payment not found.`
Same logic. Detail/update/delete APIs-ku real existing id venum.

### `Selected leave type is not available...`
`leave_type_id` invalid or inactive. Correct flow:

1. `GET /leave-requests/options`
2. Response-la `leave_types` array-la active id edukkanum.
3. `POST /leave-requests` la antha id send pannanum.

Default seeded leave types:

- `Casual Leave`
- `Sick Leave`
- `Annual Leave`

### `Forbidden.`
Logged-in user-ku permission illa. Admin/Manager/Super Admin role permissions check pannanum.

### `No active attendance found...`
Tracking API call panna before employee check-in irukkanum. Flow: `check_in` -> tracking starts -> `check_out`.

## Controller Split

Munnadi ella API logic-um `MobileApiController` la irundhuchu. Ippo module-wise split pannirukkom:

- `MobileAuthController`: login/logout
- `MobileSettingsDashboardController`: settings, dashboard, options
- `MobileAttendanceTrackingController`: attendance, check-in/out, tracking, live location
- `MobileEmployeeRoleController`: employees, roles, permissions
- `MobileClientProjectController`: clients, projects
- `MobileExpensePaymentController`: expenses, payments
- `MobileLeaveMasterController`: leave requests, categories, vendors, labours
- `MobileTaskWalletController`: tasks, wallet
- `MobileApiController`: shared helper/base logic mattum

Logic change pannala; methods module files-la move pannirukkom.

## Auth Flow

### POST `/login`
Employee/admin app login panna use pannuvanga.

Fields:
- `email`: login user email.
- `password`: user password.
- `device_name`: mobile device name, token identify panna.

Response-la `token` varum. Next all protected API calls-ku `Authorization: Bearer {token}` header send pannanum.

### POST `/logout`
Current bearer token delete pannum. App logout panna use pannuvanga.

## Settings Flow

### GET `/V1/getAppSettings`
Mobile app behavior decide panna first load-la call pannuvanga.

Returns:
- `app_version`: current app version.
- `minimum_supported_version`: old app block panna minimum version.
- `force_update`: true na update compulsory.
- `privacy_policy_url`: privacy policy screen open panna URL.
- `tracking_interval_seconds`: background location send interval.
- `minimum_distance_meters`: location save panna minimum distance.
- `max_accuracy_meters`: GPS accuracy limit.
- `mock_location_allowed`: fake GPS allow pannalama.
- `offline_tracking_enabled`: internet illama queue panna allow ah.
- `attendance_time_type`: `server_time` or `device_time`.
- `server_time`: backend current time.
- `timezone`: app timezone.

### GET `/V1/getModuleSettings`
Module enable/disable rules app-ku sollum.

Main fields:
- `attendance.enabled`: attendance module show pannalama.
- `attendance.check_in_enabled`: check-in button enable ah.
- `attendance.check_out_enabled`: check-out button enable ah.
- `attendance.geofence_enabled`: geofence rule active ah.
- `attendance.geofence_radius_meters`: allowed radius.
- `attendance.qr_attendance_enabled`: QR attendance required ah.
- `attendance.ip_attendance_enabled`: office/site IP rule required ah.
- `attendance.allowed_ips`: allowed IP list.
- `tracking.enabled`: tracking API allow ah.
- `tracking.offline_tracking_enabled`: offline tracking queue allow ah.
- `tracking.interval_seconds`: background call interval.
- `modules.tasks`: task module show ah.
- `modules.expenses`: expenses module show ah.
- `modules.wallet`: wallet module show ah.
- `modules.leave_requests`: leave module show ah.

### GET `/V1/getMapSettings`
Dashboard map initial view-ku use pannuvanga.

Fields:
- `center_latitude`: map open aagum center latitude.
- `center_longitude`: map open aagum center longitude.
- `zoom_level`: Google map zoom.
- `google_maps_api_key`: `.env` la irukura `GOOGLE_MAPS_API_KEY`.

## Attendance + Tracking Flow

### POST `/check_in`
Employee work start panna call pannuvanga. Attendance record create aagum. GPS fields send pannina first tracking point save aagum.

Fields:
- `notes`: check-in note.
- `device_id`: mobile device unique id.
- `device_name`: mobile model/name.
- `latitude`: check-in latitude.
- `longitude`: check-in longitude.
- `accuracy`: GPS accuracy meters. `max_accuracy_meters` setting-ku mela irundha reject.
- `speed`: current speed.
- `activity`: `still`, `walking`, `travelling` madhiri app detect pannathu.
- `is_gps_on` / `isGpsOn`: GPS on ah.
- `is_mock_location` / `isMock`: fake location ah.
- `battery_percentage` / `batteryPercentage`: phone battery.
- `recorded_at`: device record time.

Behavior:
- Already checked-in irundha `409`.
- `attendance_enabled` or `check_in_enabled` false na `403`.
- Tracking entry `type = checked_in`.
- Current device location `employee_devices` table-la update aagum.

### POST `/tracking/location`
Employee checked-in apram background interval-la app call pannum.

Fields:
- `device_id`, `device_name`, `latitude`, `longitude`, `accuracy`, `speed`, `activity`, `is_gps_on`, `is_mock_location`, `battery_percentage`, `recorded_at`.
- `type`: `travelling` or `still`. Old `check_in/check_out` sent panna backend `checked_in/checked_out` ah normalize pannum.

Behavior:
- Active check-in illana `409`.
- `tracking_enabled` false na `403`.
- Tracking history `location_trackings` table-la save aagum.
- Current device location `employee_devices` table-la update aagum.

### POST `/devices/live-status`
Latest/current location update panna use pannuvanga. Full history save panna illa; latest status mattum.

Behavior:
- Active attendance irukkanum.
- `tracking_enabled` false na `403`.
- `employee_devices` table-la latest lat/lng update aagum.

### POST `/check_out`
Employee work end panna call pannuvanga.

Fields same as check-in. GPS fields optional.

Behavior:
- Active check-in illana `404`.
- `attendance_enabled` or `check_out_enabled` false na `403`.
- Attendance checkout time update aagum.
- GPS sent pannina final tracking entry `type = checked_out`.
- Tracking stop panna app side responsibility.

### GET `/attendance`
Attendance list get panna.

Query:
- `status=checked_in`: active attendance mattum.
- `status=checked_out`: completed attendance mattum.
- `date`: particular date.
- `from_date`, `to_date`: date range.
- `employee_id`: admin/manager filter.
- `per_page`: pagination count.

Normal employee own attendance mattum paapanga. Admin/manager permission irundha all attendance paapanga.

## Live Map Flow

### GET `/admin/employees/live-locations`
Admin dashboard live map markers-ku use pannuvanga.

Source:
- latest/current location: `employee_devices`
- today attendance status: `attendances`

### GET `/admin/employees/{employee}/timeline`
One employee full travel history paaka.

Query:
- `date`: timeline date.

Source:
- full history: `location_trackings`

## Employee APIs

### GET `/employees`
Employee list with roles. Admin/manager use.

Query:
- `status`: active/inactive.
- `q`: search name/email/phone/designation.
- `role`: role filter.
- `per_page`: pagination.

### POST `/employees`
New employee create panna.

Fields:
- `name`: employee name.
- `email`: login email, unique.
- `phone`: contact number.
- `designation`: job title.
- `role`: role assign panna.
- `address`: employee address.
- `hourly_rate`: hourly salary/rate.
- `hire_date`: joining date.
- `status`: `active` or `inactive`.
- `password`, `password_confirmation`: login password.
- `avatar`: profile image.

Behavior:
- User table-la create aagum.
- Task module `employees` mirror record sync aagum.
- Role assign aagum.

### GET `/employees/profile`
Logged-in user profile get panna.

### GET `/employees/{employee}`
Employee details, expenses, attendances, task_employee_id, stats return pannum.

### PUT `/employees/{employee}` / POST `/employees/{employee}/update`
Employee update panna.

Important:
- Non-super-admin `Super Admin` / `Manager` role assign panna mudiyadhu.
- Password empty string send pannina password change aagathu.

### DELETE `/employees/{employee}`
Hard delete illa. Status inactive aagum, mobile tokens revoke aagum, task employee mirror inactive aagum.

## Roles / Permissions

### GET `/me/permissions`
Logged-in user roles + permission keys. App menu/module hide-show panna use.

### GET `/roles`
Role list. Employee create/update screen-la role dropdown.

### GET `/permissions`
Permission list. Admin role management-ku.

## Task APIs

### GET `/tasks`
Task list.

Query:
- `status`: pending/in_progress/completed/blocked.
- `priority`: low/medium/high.
- `project_id`: project filter.
- `employee_id`: assigned employee filter.
- `q`: title/description search.
- `per_page`: pagination.

Normal employee own assigned tasks mattum paapanga. `tasks-list` permission irundha all tasks.

### POST `/tasks/assign`
Task create/assign panna.

Fields:
- `project_id`: task related project.
- `employee_id`: task employee table id.
- `user_id`: app user id; backend employee mirror id resolve pannum.
- `title`: task title.
- `description`: task details.
- `type`: general/daily/weekly.
- `auto_repeat`: recurring task create panna.
- `priority`: low/medium/high.
- `status`: pending/in_progress/completed/blocked.
- `due_date`: due date.
- `estimated_hours`: expected hours.
- `logged_hours`: spent hours.
- `is_important`: important flag.
- `sort_order`: ordering.

### GET `/tasks/{task}`
Task detail.

### PUT `/tasks/{task}` / POST `/tasks/{task}/update`
Task update.

Normal employee own task-la `status` and `logged_hours` mattum update panna allow. Admin/manager full update.

### DELETE `/tasks/{task}`
Task delete. Permission required.

## Clients APIs

### GET `/clients`
Client list.

Query:
- `status`: enquiry/active/inactive.
- `q`: name/email/phone search.
- `per_page`: pagination.

### POST `/clients`
Client create.

Fields:
- `name`: client name.
- `email`: client email.
- `phone`: contact number.
- `address`: address.
- `company`: company name.
- `status`: enquiry/active/inactive.
- `notes`: extra notes.

### GET `/clients/{client}`
Client detail with projects count.

### PUT `/clients/{client}`
Client update.

### DELETE `/clients/{client}`
Related projects/payments irundha delete block. Data loss avoid panna.

## Project APIs

### GET `/projects/options`
Project create/edit dropdown options: clients, statuses, types.

### GET `/projects`
Project list.

Query:
- `status`, `client_id`, `q`, `per_page`.

### POST `/projects`
Project create.

Fields:
- `name`: project name.
- `project_code`: unique project code.
- `client_id`: client id.
- `type`: project type.
- `status`: planning/active/on_hold/completed/cancelled.
- `start_date`: start date.
- `end_date`: end date.
- `budget`: project budget.
- `advance_amt`: advance amount.
- `profit`: profit amount.
- `description`: project details.

### GET `/projects/{project}`
Project detail with client, tasks, payments, expenses.

### PUT `/projects/{project}`
Project update.

### DELETE `/projects/{project}`
Related records irundha delete block. Cancelled/inactive status use panna safer.

## Expenses APIs

### GET `/expenses/options`
Expense form dropdowns.

### GET `/expenses`
Expense list.

Query:
- `project_id`, `client_id`, `employee_id`, `from_date`, `to_date`, `q`, `per_page`.

Normal employee own expenses mattum. Permission irundha all.

### POST `/expenses`
Expense create.

Fields:
- `project_id`: related project.
- `client_id`: related client.
- `employee_id`: employee/user.
- `category`: expense category.
- `amount`: expense amount.
- `expense_date`: expense date.
- `payment_mode`: cash/bank/upi etc.
- `description`: notes.
- `bill_no`: bill number.
- `vendor_id`: vendor.

### GET `/expenses/{expense}`
Expense detail.

### PUT `/expenses/{expense}`
Expense update.

### DELETE `/expenses/{expense}`
Expense delete. Permission required.

## Payments APIs

### GET `/payments/options`
Payment filters/dropdowns.

### GET `/payments`
Payment list.

Query:
- `client_id`, `project_id`, `status`, `from_date`, `to_date`, `per_page`.

### GET `/payments/{payment}`
Payment detail.

### GET `/payment-stages`
Payment stage list.

Important: Payment create/update/delete API intentionally add pannala. Reason: payment logic balance/wallet affect pannum. Mobile side read-only safe.

## Wallet APIs

### GET `/wallet`
Wallet/transfer history.

Query:
- `client_id`, `project_id`, `transfer_type`, `from_date`, `to_date`, `per_page`.

### GET `/wallet/options`
Wallet form dropdowns.

### POST `/wallet/transfer` / POST `/wallet/store`
Wallet transfer create.

Fields:
- `client_id`: client.
- `project_id`: project.
- `amount`: transfer amount.
- `payment_mode`: 1 Cash, 2 Bank Transfer, 3 UPI, 4 Cheque, 5 Card.
- `transfer_type`: 0 credit/income, 1 debit/expense.
- `stage_id`: payment stage.
- `description`: notes.
- `current_date`: transfer date.
- `time`: transfer time.

## Leave Request APIs

### GET `/leave-requests/options`
Leave types and status options.

### GET `/leave-requests`
Leave request list.

Query:
- `status`, `employee_id`, `from_date`, `to_date`, `per_page`.

Normal employee own leave requests mattum. Permission irundha all.

### POST `/leave-requests`
Leave request create.

Fields:
- `leave_type_id`: leave type.
- `start_date`: leave start.
- `end_date`: leave end.
- `reason`: leave reason.

### POST `/leave-requests/{leaveRequest}/action`
Approve/reject action.

Fields:
- `status`: approved/rejected.
- `admin_note`: admin note.

### DELETE `/leave-requests/{leaveRequest}`
Leave request delete.

## Master Data APIs

### GET `/categories`
Expense/category dropdown.

### GET `/main-categories`
Main category dropdown.

### GET `/vendors`
Vendor dropdown.

### GET `/labour-roles`
Labour role dropdown.

### GET `/labours`
Labour dropdown.

## Complete Mobile Flow

1. App calls `/V1/getAppSettings`.
2. App calls `/V1/getModuleSettings`.
3. App version/privacy/tracking interval/module visibility decide pannum.
4. Employee login `/login`.
5. Employee check-in `/check_in`.
6. First tracking point `checked_in` save aagum.
7. Background tracking starts.
8. Interval basis-la `/tracking/location` call pannum.
9. History `location_trackings` la save aagum.
10. Latest location `employee_devices` la update aagum.
11. Admin dashboard `/admin/employees/live-locations` call panni map marker show pannum.
12. Employee checkout `/check_out`.
13. Final tracking point `checked_out` save aagum.
14. App tracking stop pannum.

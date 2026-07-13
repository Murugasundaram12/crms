# CRMS Mobile App API Explanation For Client

Indha document client meeting-la explain panna easy-a irukkura mathiri simple Tanglish-la prepare pannathu. API na mobile app-um backend system-um pesura bridge. Mobile app data send pannum; backend database-la save pannum; admin dashboard adha view pannum.

## Base URL

Local testing API base URL:

```text
http://localhost/crms/api
```

Example:

```text
http://localhost/crms/api/login
http://localhost/crms/api/check_in
http://localhost/crms/api/projects
```

Bruno/Postman collection-la URL direct text-a irukkum. `{{base_url}}`, `{{employee_id}}` madhiri variables use pannala. Detail/update/delete API-la sample ID `1` irukkum. Real app-la list API call panni actual ID eduthu use pannanum.

## Overall Concept

CRMS mobile app mainly employees use panna build pannirukkom. Admin/Manager role permission irundha app-la admin-related APIs use panna mudiyum. So app role based-a behave pannum:

- Employee login pannina own attendance, own tasks, own expenses, own leave requests paapanga.
- Admin/Manager login pannina permission irundha employees, projects, tasks, expenses, live location map data paaka/manage panna mudiyum.
- Super Admin-ku all permissions irukkum.

## Authentication Flow

### `POST /login`

Employee/admin login panna use pannuvanga.

Request fields:

- `email`: login email.
- `password`: login password.
- `device_name`: mobile device identify panna.

Response-la token varum. App next protected API call ellathukum:

```text
Authorization: Bearer token_here
```

header send pannanum.

### `POST /auth/login`

Idhu `/login` API-ku same alias. Old/new mobile app builds rendu support panna keep pannirukkom. Function same: login panni token return pannum.

### `POST /logout`

App logout panna token delete pannum.

## Settings Flow

Settings APIs mobile app behavior control pannum. App open aagumbothu first settings APIs call pannanum.

### `GET /V1/getAppSettings`

App version, tracking interval, privacy URL, time setting ellam mobile app-ku return pannum.

Important fields:

- `app_version`: current app version.
- `minimum_supported_version`: old app version block panna use.
- `force_update`: true na app update compulsory.
- `privacy_policy_url`: privacy policy page open panna.
- `tracking_interval_seconds`: location evlo seconds-ku oru time send panna.
- `minimum_distance_meters`: employee move aana distance threshold.
- `max_accuracy_meters`: GPS accuracy poor irundha reject panna.
- `mock_location_allowed`: fake GPS allow pannalama.
- `offline_tracking_enabled`: internet illama tracking save pannalama.
- `attendance_time_type`: server time use panna venduma device time use panna venduma.
- `server_time`: backend current time.
- `timezone`: time zone.

### `GET /settings/app`

Idhu authenticated alias for `/V1/getAppSettings`. Login apram app settings refresh panna use pannalam. Response concept same.

### `GET /V1/getModuleSettings`

Mobile app-la modules enable/disable panna use.

Important fields:

- `attendance.enabled`: attendance module show panna.
- `attendance.check_in_enabled`: check-in allow panna.
- `attendance.check_out_enabled`: check-out allow panna.
- `attendance.geofence_enabled`: location radius rule enable panna.
- `attendance.geofence_radius_meters`: allowed distance radius.
- `attendance.qr_attendance_enabled`: QR based attendance required ah.
- `attendance.ip_attendance_enabled`: IP based attendance required ah.
- `tracking.enabled`: background tracking allow ah.
- `tracking.interval_seconds`: background location interval.
- `modules.tasks`: tasks module show ah.
- `modules.expenses`: expenses module show ah.
- `modules.wallet`: wallet module show ah.
- `modules.leave_requests`: leave module show ah.

### `GET /settings/modules`

Idhu authenticated alias for `/V1/getModuleSettings`. Login apram module settings refresh panna use.

### `GET /V1/getMapSettings`

Admin dashboard map view-ku use.

Fields:

- `center_latitude`: map default center latitude.
- `center_longitude`: map default center longitude.
- `zoom_level`: map zoom.
- `google_maps_api_key`: Google map load panna key.

### `GET /settings/map`

Idhu authenticated alias for `/V1/getMapSettings`. Admin dashboard/mobile dashboard login apram map setting fetch panna use.

### `GET /dashboard`

Mobile dashboard summary data fetch panna use. Login pannina user role/permission based-a dashboard-la show panna vendiya counts, summaries, attendance/task related quick data return pannum.

Use:

- Employee home screen summary.
- Admin/Manager dashboard summary.
- App open pannumbothu quick overview show panna.

### `GET /options`

Common app options/dropdowns fetch panna use. Mobile forms-la repeated-a dropdown data venumna this API help pannum.

Use:

- Status lists.
- Common module options.
- Form dropdown initial values.

## Attendance And Tracking Full Flow

This is the most important mobile flow.

```text
Employee Mobile App
    -> Login
    -> Check-in API
    -> Attendance record create
    -> First tracking entry save
    -> Background tracking starts
    -> Interval basis location API call
    -> Tracking history save
    -> Device current location update
    -> Admin dashboard live map-la location show
    -> Checkout API
    -> Attendance checkout time update
    -> Final tracking entry save
    -> Tracking stops
```

### `POST /check_in`

Employee work start panna use. Attendance record create/update aagum. GPS data send pannina first tracking point `checked_in` type-la save aagum.

Fields:

- `notes`: check-in note.
- `device_id`: phone unique id.
- `device_name`: phone model/name.
- `latitude`: check-in latitude.
- `longitude`: check-in longitude.
- `accuracy`: GPS accuracy meters.
- `speed`: employee movement speed.
- `activity`: `still`, `walking`, `travelling` madhiri current activity.
- `is_gps_on`: GPS on/off status.
- `is_mock_location`: fake location detect panna.
- `battery_percentage`: phone battery percentage.
- `recorded_at`: mobile-la record aana time.

Backend behavior:

- Already checked-in irundha duplicate check-in block pannum.
- Attendance setting disabled irundha `403 Forbidden`.
- Tracking entry save pannum.
- Latest location device table-la update pannum.

### `POST /register`

Mobile device register/update panna use. App install/login apram device details backend-ku send panna. Idhu user device identify panna and later tracking/live status update panna helpful.

Fields usually:

- `device_id`: unique device id.
- `device_name`: phone model/name.
- `platform`: android/ios.
- `app_version`: installed app version.
- `fcm_token`: push notification token irundha.

Use:

- Device record maintain panna.
- Same employee multiple devices use pannina identify panna.
- Notification/tracking support panna.

### `POST /tracking/location`

Employee checked-in apram app background-la interval basis call pannum.

Fields mostly check-in madhiri:

- `device_id`, `device_name`: device identify panna.
- `latitude`, `longitude`: current location.
- `accuracy`: GPS quality.
- `speed`: travel speed.
- `activity`: still/travelling/walking.
- `is_gps_on`: GPS on ah.
- `is_mock_location`: fake GPS ah.
- `battery_percentage`: battery level.
- `recorded_at`: device recorded time.
- `type`: `travelling` or `still`.

Backend behavior:

- Active check-in illana tracking accept pannadhu.
- Every valid location `location_trackings` table-la history-a save aagum.
- Latest location `employee_devices` table-la update aagum.

### `POST /devices/live-status`

Only latest/current device status update panna use. Full history save panna main API `/tracking/location`.

Use:

- Admin live map current marker update panna.
- Employee latest battery/GPS/location status update panna.

### `GET /settings/tracking`

Tracking related settings mattum fetch panna use. App background service start panna before interval, GPS accuracy limit, mock location rule madhiri values check pannalam.

Use:

- Background tracking interval decide panna.
- GPS accuracy validation app side-la show panna.
- Tracking enable/disable status know panna.

### `POST /check_out`

Employee work end panna use.

Backend behavior:

- Active attendance record close pannum.
- Checkout time update pannum.
- GPS sent pannina final tracking entry `checked_out` type-la save aagum.
- App side-la background tracking stop pannanum.

### `GET /attendance`

Attendance history/list view panna.

Query filters:

- `status`: `checked_in` or `checked_out`.
- `date`: one date.
- `from_date`, `to_date`: date range.
- `employee_id`: admin/manager filter.
- `per_page`: pagination count.

### `GET /attendance/status`

Employee current check-in/check-out status quickly check panna use. App home screen-la check-in button show pannanuma checkout button show pannanuma decide panna indha API use pannunga.

Query:

- `date`: optional. Default today.
- `user_id`: optional. Admin/Manager permission irundha specific employee status check panna.

Response fields:

- `status`: `checked_in` or `checked_out`.
- `is_checked_in`: true na employee currently checked-in.
- `can_check_in`: true na check-in button show pannalam.
- `can_check_out`: true na checkout button show pannalam.
- `active_attendance`: current open attendance record.
- `latest_attendance`: selected date latest attendance record.

## Live Map APIs

### `GET /admin/employees/live-locations`

Admin dashboard live map-la employee markers show panna use.

Data source:

- `employee_devices`: latest/current location.
- `attendances`: today check-in/check-out status.

### `GET /admin/employees/{employee}/timeline`

One employee full day travel history show panna use.

Query:

- `date`: timeline date.

Data source:

- `location_trackings`: full location history.

## Employee APIs

### `GET /employees`

Employee list. Admin/Manager use.

Filters:

- `status`: active/inactive.
- `q`: name/email/phone/designation search.
- `role`: role filter.
- `per_page`: pagination.

### `GET /employees/track`

Employee tracking list/map support-ku use. Admin/Manager app-la employees tracking screen irundha employee list with tracking-related data fetch panna use.

Use:

- Track panna eligible employees list.
- Employee current tracking status overview.
- Map/filter screen-la employee dropdown.

### `POST /employees`

New employee create panna.

Fields:

- `name`: employee name.
- `email`: login email. Unique-a irukkanum.
- `phone`: contact number.
- `designation`: job title.
- `role`: assign panna role.
- `address`: employee address.
- `hourly_rate`: salary/hourly rate.
- `hire_date`: joining date.
- `status`: active/inactive.
- `password`, `password_confirmation`: app login password.
- `avatar`: profile image.

Backend behavior:

- User create aagum.
- Employee mirror record sync aagum.
- Role assign aagum.

### `GET /employees/profile`

Logged-in user profile details.

### `GET /employees/{employee}`

One employee details, attendance summary, expenses, tasks related data.

### `PUT /employees/{employee}` / `POST /employees/{employee}/update`

Employee update. POST fallback mobile clients-ku support.

Important:

- Non-super-admin user `Super Admin` role assign panna mudiyadhu.
- Password empty send pannina old password continue aagum.

### `DELETE /employees/{employee}`

Employee hard delete illa. Status inactive pannum and token revoke pannum.

## Roles And Permissions APIs

### `GET /me/permissions`

Logged-in user-ku enna permissions irukku nu app-ku return pannum. Mobile app menu hide/show panna use.

Important:

- Normal list APIs-la full permissions return panna maatom.
- App-la permission based menu venumna indha API call pannanum.
- Response-la user basic details, roles basic list, permission keys varum.

### `GET /roles`

Employee create/update screen-la role dropdown.

Response basic role list mattum:

- `id`
- `name`
- `description`
- `users_count`

Full permission keys venumna `/permissions` or `/me/permissions` use pannunga.

### `GET /permissions`

Admin role management-ku permission list.

Concept:

- Permission irundha only related API access.
- Permission illa na `Forbidden` response.
- App menu permission based-a show/hide pannalam.
- List pages clean-a irukka roles basic details mattum return pannum; every list response-la permissions repeat panna maatom.

## Task APIs

### `GET /tasks`

Task list.

Filters:

- `status`: pending/in_progress/completed/blocked.
- `priority`: low/medium/high.
- `project_id`: project wise.
- `employee_id`: assigned employee wise.
- `q`: search.
- `per_page`: pagination.

Employee own assigned task paapanga. Manager/Admin permission irundha all tasks paapanga.

### `POST /tasks/assign`

Task create/assign panna.

Fields:

- `project_id`: related project.
- `employee_id`: task employee id.
- `user_id`: app user id. Backend employee id resolve pannum.
- `title`: task title.
- `description`: task details.
- `type`: general/daily/weekly.
- `auto_repeat`: recurring task.
- `priority`: task priority.
- `status`: task status.
- `due_date`: due date.
- `estimated_hours`: expected hours.
- `logged_hours`: spent hours.
- `is_important`: important flag.
- `sort_order`: display order.

### `GET /tasks/{task}`

Task detail.

### `PUT /tasks/{task}` / `POST /tasks/{task}/update`

Task update.

Employee own task-la mostly status/logged hours update panna use. Admin/Manager full update panna mudiyum.

### `DELETE /tasks/{task}`

Task delete. Permission required.

## Client APIs

### `GET /clients`

Client list.

Filters:

- `status`: enquiry/active/inactive.
- `q`: name/email/phone search.
- `per_page`: pagination.

### `POST /clients`

Client create.

Fields:

- `name`: client name.
- `email`: client email.
- `phone`: client contact number.
- `address`: client address.
- `company`: company name.
- `status`: enquiry/active/inactive.
- `notes`: extra notes.

### `GET /clients/{client}`

Client detail with project count.

### `PUT /clients/{client}`

Client update.

### `DELETE /clients/{client}`

Related projects/payments irundha delete block pannum.

## Project APIs

### `GET /projects/options`

Project form dropdown data: clients, statuses, types.

### `GET /projects`

Project list.

Filters:

- `status`: project status.
- `client_id`: client wise filter.
- `q`: search.
- `per_page`: pagination.

### `POST /projects`

Project create.

Fields:

- `name`: project name.
- `project_code`: unique project code.
- `client_id`: related client.
- `type`: project type.
- `status`: planning/active/on_hold/completed/cancelled.
- `start_date`: project start date.
- `end_date`: project end date.
- `budget`: project budget.
- `advance_amt`: advance amount.
- `profit`: profit amount.
- `description`: project details.

### `GET /projects/{project}`

Project detail. Client, tasks, payments, expenses summary include aagum.

### `PUT /projects/{project}`

Project update.

### `DELETE /projects/{project}`

Related records irundha delete block pannum.

## Expense APIs

### `GET /expenses/options`

Expense form dropdowns: projects, clients, vendors, categories.

### `GET /expenses`

Expense list.

Filters:

- `project_id`: project wise.
- `client_id`: client wise.
- `employee_id`: employee wise.
- `from_date`, `to_date`: date range.
- `q`: search.
- `per_page`: pagination.

Employee own expenses paapanga. Permission irundha all expenses.

### `POST /expenses`

Expense create.

Fields:

- `project_id`: related project.
- `client_id`: related client.
- `employee_id`: expense logged employee.
- `category`: expense category.
- `amount`: expense amount.
- `expense_date`: expense date.
- `payment_mode`: cash/bank/upi etc.
- `description`: expense notes.
- `bill_no`: bill number.
- `vendor_id`: vendor id.

### `GET /expenses/{expense}`

Expense detail.

### `PUT /expenses/{expense}`

Expense update.

### `DELETE /expenses/{expense}`

Expense delete. Permission required.

## Payment APIs

### `GET /payments/options`

Payment dropdown/filter data.

### `GET /payments`

Payment list.

Filters:

- `client_id`: client wise.
- `project_id`: project wise.
- `status`: payment status.
- `from_date`, `to_date`: date range.
- `per_page`: pagination.

### `GET /payments/{payment}`

Payment detail.

### `GET /payment-stages`

Payment stage list.

Note: Payment create/update/delete mobile API intentionally add pannala. Payment logic balance/wallet affect pannum, so mobile side read-only safe.

## Wallet APIs

### `GET /wallet`

Wallet/transfer history.

Filters:

- `client_id`: client wise.
- `project_id`: project wise.
- `transfer_type`: credit/debit.
- `from_date`, `to_date`: date range.
- `per_page`: pagination.

### `GET /wallet/options`

Wallet form dropdowns.

### `POST /wallet/transfer` / `POST /wallet/store`

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

### `GET /leave-requests/options`

Leave type and status dropdowns.

Important:

- Leave create panna active leave type id use pannanum.
- Invalid/inactive leave type id send pannina validation error varum.

### `GET /leave-requests`

Leave request list.

Filters:

- `status`: pending/approved/rejected.
- `employee_id`: employee filter.
- `from_date`, `to_date`: date range.
- `per_page`: pagination.

### `POST /leave-requests`

Leave request create.

Fields:

- `leave_type_id`: selected leave type id.
- `start_date`: leave start date.
- `end_date`: leave end date.
- `reason`: leave reason.

### `POST /leave-requests/{leaveRequest}/action`

Admin approve/reject panna.

Fields:

- `status`: approved/rejected.
- `admin_note`: admin note.

### `DELETE /leave-requests/{leaveRequest}`

Leave request delete.

## Master Data APIs

Dropdown data and common master data fetch panna use.

### `GET /categories`

Expense/category dropdown. Expense create/update form-la category select panna use.

### `GET /main-categories`

Main category dropdown. Expense category grouping or material/category selection screens-la use.

### `GET /vendors`

Vendor dropdown. Expense create pannumbothu vendor select panna use.

### `GET /labour-roles`

Labour role dropdown. Labour module-la role/type select panna use.

### `GET /labours`

Labour dropdown. Labour expense or labour assignment form-la labour select panna use.

## Common Response Concepts

### Pagination

List APIs-la pagination irukkum:

- `current_page`: current page number.
- `per_page`: one page-la evlo records.
- `total`: total records count.
- `last_page`: last page number.
- `data`: actual records.

Why pagination?

Mobile app fast-a load aaganum. 1000 records one shot-la load panna slow aagum. Pagination use panna app page by page data load pannum.

### Status Filter

`status` parameter list filter panna use.

Examples:

- Attendance-la `checked_in` users mattum.
- Project-la `active` projects mattum.
- Leave-la `pending` requests mattum.

### Permission Error

`Forbidden` response na logged-in user-ku permission illa.

Example:

- Employee user admin employee delete API call panna `Forbidden`.
- Role/permission based access backend-la protect pannirukkom.

### Not Found Error

`Client not found`, `Project not found`, `Employee not found` response na URL-la use panna id DB-la illa.

Correct flow:

1. List API call pannanum.
2. Response-la valid id edukkanum.
3. Detail/update/delete API-la antha id use pannanum.

## Client Explanation Short Script

Client-ku short-a ipdi explain pannalam:

CRMS mobile API employee mobile app and admin dashboard connect panna create pannirukkom. Employee app login pannum, settings fetch pannum, attendance check-in pannum, background-la location send pannum. Backend attendance and tracking history save pannum. Latest location device table-la update aagum. Admin dashboard live map API use panni employee current location paakum.

Other modules-la employee tasks, expenses, leave requests, clients, projects, payments and wallet data APIs irukku. Role and permission based access implement pannirukkom; employee own data mattum paapanga, admin/manager permission irundha all data paaka/manage panna mudiyum.

Settings APIs app behavior control pannum. Tracking interval, offline tracking, geofence, QR/IP attendance, privacy URL, app version ellam backend settings-la irundhu app-ku pogum. App update pannanumna backend setting change pannina app behavior change aagum.

Data safety-ku pagination, validation, permission checks, not found handling, active attendance checks implement pannirukkom. So invalid id, invalid leave type, permission missing, check-in illama tracking send panra cases ellam proper response return pannum.

## Complete API Route Coverage

Indha list `routes/api.php` actual routes based-a prepare pannathu. Oru route-um miss aagama coverage check panna use pannunga.

| # | Method | API | Use |
| --- | --- | --- | --- |
| 1 | POST | `/login` | Login and token create. |
| 2 | POST | `/auth/login` | Login alias, same as `/login`. |
| 3 | GET | `/V1/getAppSettings` | Public app settings. |
| 4 | GET | `/V1/getModuleSettings` | Public module settings. |
| 5 | GET | `/V1/getMapSettings` | Public map settings. |
| 6 | POST | `/logout` | Current token logout. |
| 7 | GET | `/dashboard` | Mobile dashboard summary. |
| 8 | GET | `/options` | Common app options/dropdowns. |
| 9 | POST | `/register` | Mobile device registration/update. |
| 10 | GET | `/attendance/status` | Current check-in/check-out status. |
| 11 | GET | `/attendance` | Attendance list/history. |
| 12 | POST | `/check_in` | Employee check-in. |
| 13 | POST | `/check_out` | Employee check-out. |
| 14 | POST | `/tracking/location` | Background tracking location save. |
| 15 | POST | `/devices/live-status` | Latest device/live status update. |
| 16 | GET | `/settings/tracking` | Tracking settings. |
| 17 | GET | `/settings/app` | Authenticated app settings alias. |
| 18 | GET | `/settings/modules` | Authenticated module settings alias. |
| 19 | GET | `/settings/map` | Authenticated map settings alias. |
| 20 | GET | `/admin/employees/live-locations` | Admin live map employee markers. |
| 21 | GET | `/admin/employees/{employee}/timeline` | One employee location timeline. |
| 22 | GET | `/employees/track` | Employee tracking list/status. |
| 23 | GET | `/employees` | Employee list. |
| 24 | POST | `/employees` | Employee create. |
| 25 | GET | `/employees/profile` | Logged-in user profile. |
| 26 | GET | `/employees/{employee}` | Employee detail. |
| 27 | PUT | `/employees/{employee}` | Employee update. |
| 28 | POST | `/employees/{employee}/update` | Employee update alias. |
| 29 | DELETE | `/employees/{employee}` | Employee inactive/delete action. |
| 30 | GET | `/me/permissions` | Logged-in user permissions. |
| 31 | GET | `/roles` | Role list. |
| 32 | GET | `/permissions` | Permission list. |
| 33 | GET | `/tasks` | Task list. |
| 34 | POST | `/tasks/assign` | Task create/assign. |
| 35 | GET | `/tasks/{task}` | Task detail. |
| 36 | PUT | `/tasks/{task}` | Task update. |
| 37 | POST | `/tasks/{task}/update` | Task update alias. |
| 38 | DELETE | `/tasks/{task}` | Task delete. |
| 39 | GET | `/wallet` | Wallet/transfer history. |
| 40 | GET | `/wallet/options` | Wallet dropdown options. |
| 41 | POST | `/wallet/store` | Wallet transfer create alias. |
| 42 | POST | `/wallet/transfer` | Wallet transfer create. |
| 43 | GET | `/clients` | Client list. |
| 44 | POST | `/clients` | Client create. |
| 45 | GET | `/clients/{client}` | Client detail. |
| 46 | PUT | `/clients/{client}` | Client update. |
| 47 | DELETE | `/clients/{client}` | Client delete/block if related data. |
| 48 | GET | `/projects/options` | Project dropdown options. |
| 49 | GET | `/projects` | Project list. |
| 50 | POST | `/projects` | Project create. |
| 51 | GET | `/projects/{project}` | Project detail. |
| 52 | PUT | `/projects/{project}` | Project update. |
| 53 | DELETE | `/projects/{project}` | Project delete/block if related data. |
| 54 | GET | `/expenses/options` | Expense dropdown options. |
| 55 | GET | `/expenses` | Expense list. |
| 56 | POST | `/expenses` | Expense create. |
| 57 | GET | `/expenses/{expense}` | Expense detail. |
| 58 | PUT | `/expenses/{expense}` | Expense update. |
| 59 | DELETE | `/expenses/{expense}` | Expense delete. |
| 60 | GET | `/payments/options` | Payment dropdown/filter options. |
| 61 | GET | `/payments` | Payment list. |
| 62 | GET | `/payments/{payment}` | Payment detail. |
| 63 | GET | `/payment-stages` | Payment stage list. |
| 64 | GET | `/leave-requests/options` | Leave dropdown/status options. |
| 65 | GET | `/leave-requests` | Leave request list. |
| 66 | POST | `/leave-requests` | Leave request create. |
| 67 | POST | `/leave-requests/{leaveRequest}/action` | Leave approve/reject action. |
| 68 | DELETE | `/leave-requests/{leaveRequest}` | Leave request delete. |
| 69 | GET | `/categories` | Category dropdown. |
| 70 | GET | `/main-categories` | Main category dropdown. |
| 71 | GET | `/vendors` | Vendor dropdown. |
| 72 | GET | `/labour-roles` | Labour role dropdown. |
| 73 | GET | `/labours` | Labour dropdown. |

## Final End-To-End Flow

```text
Admin configures app settings
    -> Mobile app fetches settings
    -> Employee login
    -> Employee check-in
    -> Attendance save
    -> First tracking save
    -> Background location tracking starts
    -> Location history save
    -> Latest device location update
    -> Admin live map shows marker
    -> Employee checkout
    -> Attendance close
    -> Final tracking save
    -> Tracking stops
```

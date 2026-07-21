<?php

use App\Http\Controllers\Api\MobileAttendanceTrackingController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileClientProjectController;
use App\Http\Controllers\Api\MobileEmployeeRoleController;
use App\Http\Controllers\Api\MobileExpensePaymentController;
use App\Http\Controllers\Api\MobileInventoryController;
use App\Http\Controllers\Api\MobileLeaveMasterController;
use App\Http\Controllers\Api\MobileSettingsDashboardController;
use App\Http\Controllers\Api\MobileTaskWalletController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [MobileAuthController::class, 'login']);
Route::post('/auth/login', [MobileAuthController::class, 'login']);

// Public settings aliases kept for existing app builds.
Route::get('/V1/getAppSettings', [MobileSettingsDashboardController::class, 'getAppSettings']);
Route::get('/V1/getModuleSettings', [MobileSettingsDashboardController::class, 'getModuleSettings']);
Route::get('/V1/getMapSettings', [MobileSettingsDashboardController::class, 'getMapSettings']);

Route::middleware('mobile.api')->group(function () {
    Route::post('/logout', [MobileAuthController::class, 'logout']);
    Route::get('/dashboard', [MobileSettingsDashboardController::class, 'dashboard']);
    Route::get('/options', [MobileSettingsDashboardController::class, 'appOptions']);
    Route::post('/checkDevice', [MobileAttendanceTrackingController::class, 'checkDevice']);
    Route::post('/devices/check', [MobileAttendanceTrackingController::class, 'checkDevice']);
    Route::post('/register', [MobileAttendanceTrackingController::class, 'registerDevice']);
    Route::post('/registerDevice', [MobileAttendanceTrackingController::class, 'registerDevice']);
    Route::post('/devices/register', [MobileAttendanceTrackingController::class, 'registerDevice']);
    Route::post('/messagingToken', [MobileAttendanceTrackingController::class, 'updateMessagingToken']);
    Route::post('/devices/messaging-token', [MobileAttendanceTrackingController::class, 'updateMessagingToken']);
    Route::get('/check_status', [MobileAttendanceTrackingController::class, 'attendanceStatus']);
    Route::get('/attendance/status', [MobileAttendanceTrackingController::class, 'attendanceStatus']);
    Route::get('/attendance', [MobileAttendanceTrackingController::class, 'attendances']);
    Route::get('/attendance/my', [MobileAttendanceTrackingController::class, 'myAttendances']);
    Route::get('/me/attendance', [MobileAttendanceTrackingController::class, 'myAttendances']);
    Route::post('/check_in', [MobileAttendanceTrackingController::class, 'checkIn']);
    Route::post('/check_out', [MobileAttendanceTrackingController::class, 'checkOut']);
    Route::post('/tracking/location', [MobileAttendanceTrackingController::class, 'updateLocation']);
    Route::post('/devices/live-status', [MobileAttendanceTrackingController::class, 'liveStatus']);
    Route::post('/updateDeviceStatus', [MobileAttendanceTrackingController::class, 'updateDeviceStatus']);
    Route::post('/devices/status', [MobileAttendanceTrackingController::class, 'updateDeviceStatus']);
    Route::get('/settings/tracking', [MobileAttendanceTrackingController::class, 'trackingSettings']);
    Route::get('/settings/app', [MobileSettingsDashboardController::class, 'getAppSettings']);
    Route::get('/settings/modules', [MobileSettingsDashboardController::class, 'getModuleSettings']);
    Route::get('/settings/map', [MobileSettingsDashboardController::class, 'getMapSettings']);
    Route::get('/admin/employees/live-locations', [MobileAttendanceTrackingController::class, 'adminLiveLocations']);
    Route::get('/admin/employees/card-view', [MobileAttendanceTrackingController::class, 'adminCardView']);
    Route::post('/admin/employees/timeline', [MobileAttendanceTrackingController::class, 'adminTimelineModule']);
    Route::get('/admin/employees/{employee}/timeline', [MobileAttendanceTrackingController::class, 'adminTimeline'])->whereNumber('employee');
    Route::get('/employees/track', [MobileEmployeeRoleController::class, 'trackEmployees']);
    Route::get('/employees', [MobileEmployeeRoleController::class, 'employees']);
    Route::post('/employees', [MobileEmployeeRoleController::class, 'storeEmployee']);
    Route::get('/employees/profile', [MobileEmployeeRoleController::class, 'employeeProfile']);
    Route::get('/employees/{employee}', [MobileEmployeeRoleController::class, 'showEmployee'])->whereNumber('employee');
    Route::put('/employees/{employee}', [MobileEmployeeRoleController::class, 'updateEmployee'])->whereNumber('employee');
    Route::post('/employees/{employee}/update', [MobileEmployeeRoleController::class, 'updateEmployee'])->whereNumber('employee');
    Route::delete('/employees/{employee}', [MobileEmployeeRoleController::class, 'deleteEmployee'])->whereNumber('employee');
    Route::get('/me/permissions', [MobileEmployeeRoleController::class, 'permissionContext']);
    Route::get('/roles', [MobileEmployeeRoleController::class, 'roles']);
    Route::get('/permissions', [MobileEmployeeRoleController::class, 'permissions']);
    Route::get('/tasks', [MobileTaskWalletController::class, 'tasks']);
    Route::post('/tasks/assign', [MobileTaskWalletController::class, 'assignTask']);
    Route::get('/tasks/{task}', [MobileTaskWalletController::class, 'showTask'])->whereNumber('task');
    Route::put('/tasks/{task}', [MobileTaskWalletController::class, 'updateTask'])->whereNumber('task');
    Route::post('/tasks/{task}/update', [MobileTaskWalletController::class, 'updateTask'])->whereNumber('task');
    Route::delete('/tasks/{task}', [MobileTaskWalletController::class, 'deleteTask'])->whereNumber('task');
    Route::get('/wallet', [MobileTaskWalletController::class, 'wallets']);
    Route::get('/wallet/options', [MobileTaskWalletController::class, 'walletOptions']);
    Route::post('/wallet/store', [MobileTaskWalletController::class, 'transferWallet']);
    Route::post('/wallet/transfer', [MobileTaskWalletController::class, 'transferWallet']);

    Route::get('/clients', [MobileClientProjectController::class, 'clients']);
    Route::post('/clients', [MobileClientProjectController::class, 'storeClient']);
    Route::get('/clients/{client}', [MobileClientProjectController::class, 'showClient'])->whereNumber('client');
    Route::put('/clients/{client}', [MobileClientProjectController::class, 'updateClient'])->whereNumber('client');
    Route::delete('/clients/{client}', [MobileClientProjectController::class, 'deleteClient'])->whereNumber('client');

    Route::get('/projects/options', [MobileClientProjectController::class, 'projectOptions']);
    Route::get('/projects', [MobileClientProjectController::class, 'projects']);
    Route::post('/projects', [MobileClientProjectController::class, 'storeProject']);
    Route::get('/projects/{project}', [MobileClientProjectController::class, 'showProject'])->whereNumber('project');
    Route::put('/projects/{project}', [MobileClientProjectController::class, 'updateProject'])->whereNumber('project');
    Route::delete('/projects/{project}', [MobileClientProjectController::class, 'deleteProject'])->whereNumber('project');

    Route::get('/expenses/options', [MobileExpensePaymentController::class, 'expenseOptions']);
    Route::get('/expenses', [MobileExpensePaymentController::class, 'expenses']);
    Route::post('/expenses', [MobileExpensePaymentController::class, 'storeExpense']);
    Route::get('/expenses/{expense}', [MobileExpensePaymentController::class, 'showExpense'])->whereNumber('expense');
    Route::put('/expenses/{expense}', [MobileExpensePaymentController::class, 'updateExpense'])->whereNumber('expense');
    Route::delete('/expenses/{expense}', [MobileExpensePaymentController::class, 'deleteExpense'])->whereNumber('expense');

    Route::get('/payments/options', [MobileExpensePaymentController::class, 'paymentOptions']);
    Route::get('/payments', [MobileExpensePaymentController::class, 'payments']);
    Route::get('/payments/{payment}', [MobileExpensePaymentController::class, 'showPayment'])->whereNumber('payment');
    Route::get('/payment-stages', [MobileExpensePaymentController::class, 'paymentStages']);

    Route::get('/inventory/options', [MobileInventoryController::class, 'inventoryOptions']);
    Route::get('/inventory/items', [MobileInventoryController::class, 'inventoryItems']);
    Route::post('/inventory/items', [MobileInventoryController::class, 'storeInventoryItem']);
    Route::get('/inventory/items/{toolMaterial}', [MobileInventoryController::class, 'showInventoryItem'])->whereNumber('toolMaterial');
    Route::put('/inventory/items/{toolMaterial}', [MobileInventoryController::class, 'updateInventoryItem'])->whereNumber('toolMaterial');
    Route::post('/inventory/items/{toolMaterial}/update', [MobileInventoryController::class, 'updateInventoryItem'])->whereNumber('toolMaterial');
    Route::delete('/inventory/items/{toolMaterial}', [MobileInventoryController::class, 'deleteInventoryItem'])->whereNumber('toolMaterial');
    Route::get('/inventory/transactions', [MobileInventoryController::class, 'inventoryTransactions']);
    Route::post('/inventory/transactions', [MobileInventoryController::class, 'storeInventoryTransaction']);
    Route::get('/inventory/transactions/{assignment}', [MobileInventoryController::class, 'showInventoryTransaction'])->whereNumber('assignment');
    Route::put('/inventory/transactions/{assignment}', [MobileInventoryController::class, 'updateInventoryTransaction'])->whereNumber('assignment');
    Route::post('/inventory/transactions/{assignment}/update', [MobileInventoryController::class, 'updateInventoryTransaction'])->whereNumber('assignment');
    Route::delete('/inventory/transactions/{assignment}', [MobileInventoryController::class, 'deleteInventoryTransaction'])->whereNumber('assignment');

    Route::get('/tools-materials/options', [MobileInventoryController::class, 'inventoryOptions']);
    Route::get('/tools-materials', [MobileInventoryController::class, 'inventoryItems']);
    Route::post('/tools-materials', [MobileInventoryController::class, 'storeInventoryItem']);
    Route::get('/tools-materials/{toolMaterial}', [MobileInventoryController::class, 'showInventoryItem'])->whereNumber('toolMaterial');
    Route::put('/tools-materials/{toolMaterial}', [MobileInventoryController::class, 'updateInventoryItem'])->whereNumber('toolMaterial');
    Route::post('/tools-materials/{toolMaterial}/update', [MobileInventoryController::class, 'updateInventoryItem'])->whereNumber('toolMaterial');
    Route::delete('/tools-materials/{toolMaterial}', [MobileInventoryController::class, 'deleteInventoryItem'])->whereNumber('toolMaterial');
    Route::get('/tools-material-assignments', [MobileInventoryController::class, 'inventoryTransactions']);
    Route::post('/tools-material-assignments', [MobileInventoryController::class, 'storeInventoryTransaction']);
    Route::get('/tools-material-assignments/{assignment}', [MobileInventoryController::class, 'showInventoryTransaction'])->whereNumber('assignment');
    Route::put('/tools-material-assignments/{assignment}', [MobileInventoryController::class, 'updateInventoryTransaction'])->whereNumber('assignment');
    Route::post('/tools-material-assignments/{assignment}/update', [MobileInventoryController::class, 'updateInventoryTransaction'])->whereNumber('assignment');
    Route::delete('/tools-material-assignments/{assignment}', [MobileInventoryController::class, 'deleteInventoryTransaction'])->whereNumber('assignment');

    Route::get('/leave-requests/options', [MobileLeaveMasterController::class, 'leaveOptions']);
    Route::get('/leave-requests', [MobileLeaveMasterController::class, 'leaveRequests']);
    Route::post('/leave-requests', [MobileLeaveMasterController::class, 'storeLeaveRequest']);
    Route::post('/leave-requests/{leaveRequest}/action', [MobileLeaveMasterController::class, 'actionLeaveRequest'])->whereNumber('leaveRequest');
    Route::delete('/leave-requests/{leaveRequest}', [MobileLeaveMasterController::class, 'deleteLeaveRequest'])->whereNumber('leaveRequest');

    Route::get('/categories', [MobileLeaveMasterController::class, 'categories']);
    Route::get('/main-categories', [MobileLeaveMasterController::class, 'mainCategories']);
    Route::get('/vendors', [MobileLeaveMasterController::class, 'vendors']);
    Route::get('/labour-roles', [MobileLeaveMasterController::class, 'labourRoles']);
    Route::get('/labours', [MobileLeaveMasterController::class, 'labours']);
});

<?php

use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceManagementController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeSalaryController;
use App\Http\Controllers\EmployeeTrackingController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseImportController;
use App\Http\Controllers\ExcelImportController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\ExpenseTransactionController;
use App\Http\Controllers\LabourController;
use App\Http\Controllers\LabourAssignmentController;
use App\Http\Controllers\LabourAttendanceController;
use App\Http\Controllers\LabourExpensesController;
use App\Http\Controllers\LabourRoleController;
use App\Http\Controllers\LabourSalaryController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\MainCategoryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PaymentStageController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PreorderController;
use App\Http\Controllers\PreorderReportController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectPdfController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Reports\ExpenseReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ToolMaterialAssignmentController;
use App\Http\Controllers\ToolMaterialController;
use App\Http\Controllers\TrackingSettingsController;
use App\Http\Controllers\TransferDetailsController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UnpaidExpensesController;
use App\Http\Controllers\VariationController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorExpensesController;
use App\Http\Controllers\WalletController;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:web-auth')->name('login');
Route::get('/login', [AuthController::class, 'showLogin']);
// Route::post('/login', [AuthController::class, 'login']);
if (config('auth.public_registration_enabled')) {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
}
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->middleware('throttle:password-reset')->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset')->name('password.update');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout.get');

Route::prefix('server-commands')->middleware(['auth', 'permission:permissions-edit'])->group(function () {
    Route::get('optimize', function () {
        Artisan::call('optimize:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');

        return redirect()->route('dashboard')->with('success', 'Application cache cleared successfully.');
    })->name('server-commands.optimize');
});

Route::middleware('auth')->group(function () {
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])
            ->middleware('permission:attendance-list')
            ->name('index');
        Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
        Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('check-out');
        Route::get('/labours/available', [LabourAttendanceController::class, 'summaryJson'])->name('labours.available');
        Route::post('/labours', [LabourAttendanceController::class, 'store'])->name('labours.store');
        Route::delete('/{attendance}', [AttendanceController::class, 'destroy'])
            ->middleware('permission:attendance-list')
            ->name('destroy');
    });

    Route::prefix('labour-attendances')->name('labour-attendances.')->group(function () {
        Route::middleware('permission:attendance-list')->group(function () {
            Route::get('/', [LabourAttendanceController::class, 'index'])->name('index');
            Route::get('/summary-json', [LabourAttendanceController::class, 'summaryJson'])->name('summary-json');
            Route::post('/store', [LabourAttendanceController::class, 'store'])->name('store');
            Route::post('/bulk-store', [LabourAttendanceController::class, 'bulkStore'])->name('bulk-store');
            Route::delete('/{labourAttendance}', [LabourAttendanceController::class, 'destroy'])->name('destroy');
        });
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/profile', [EmployeeController::class, 'profile'])
        ->name('profile.show');

    Route::get('/device-management', [DeviceManagementController::class, 'index'])
        ->middleware('permission:employees-list')
        ->name('device-management.index');
    Route::delete('/device-management/{device}', [DeviceManagementController::class, 'destroy'])
        ->middleware('permission:employees-list')
        ->name('device-management.destroy');

    Route::prefix('employee-tracking')->name('tracking.')->middleware('permission:employees-list')->group(function () {
        Route::get('/', [EmployeeTrackingController::class, 'index'])->name('index');
        Route::get('/settings', [TrackingSettingsController::class, 'edit'])->name('settings');
        Route::put('/settings', [TrackingSettingsController::class, 'update'])->name('settings.update');
        Route::get('/debug-report', [EmployeeTrackingController::class, 'debugReport'])->name('debug-report');
        Route::post('/debug-report/data', [EmployeeTrackingController::class, 'debugReportData'])->name('debug-report.data');
        Route::get('/live-location', [EmployeeTrackingController::class, 'liveMap'])->name('live-map');
        Route::get('/live', [EmployeeTrackingController::class, 'liveLocations'])->name('live');
        Route::get('/card-view', [EmployeeTrackingController::class, 'cardView'])->name('card-view');
        Route::get('/card-view/data', [EmployeeTrackingController::class, 'cardViewData'])->name('card-view.data');
        Route::get('/timeline/{employee}', [EmployeeTrackingController::class, 'timeline'])->whereNumber('employee')->name('timeline');
    });

    Route::middleware('permission:employees-list')->group(function () {
        Route::get('/liveLocation', [EmployeeTrackingController::class, 'liveMap'])->name('liveLocation');
        Route::get('/liveLocationAjax', [EmployeeTrackingController::class, 'liveLocationAjax'])->name('liveLocationAjax');
        Route::get('/cardView', [EmployeeTrackingController::class, 'cardView'])->name('cardView');
        Route::get('/dashboard/cardViewAjax', [EmployeeTrackingController::class, 'cardViewData'])->name('dashboard/cardViewAjax');
        Route::get('/timeLine', [EmployeeTrackingController::class, 'index'])->name('timeLine');
        Route::post('/dashboard/getTimeLineAjax', [EmployeeTrackingController::class, 'getTimeLineAjax'])->name('dashboard.getTimeLineAjax');
        Route::post('/dashboard/snapTimeLineRoute', [EmployeeTrackingController::class, 'snapTimeLineRoute'])->name('dashboard.snapTimeLineRoute');
        Route::post('/dashboard/reverseGeocode', [EmployeeTrackingController::class, 'reverseGeocode'])->name('dashboard.reverseGeocode');
    });

    Route::get('/home', [DashboardController::class, 'index'])
        ->name('home');

    Route::redirect('/index', '/dashboard');

    Route::get('/user', [EmployeeController::class, 'index'])
        ->middleware('permission:employees-list')
        ->name('user-index');
    Route::get('/user/create', fn () => redirect()->route('employees.index'))
        ->middleware('permission:employees-create')
        ->name('user-create');
    Route::post('/user/store', [EmployeeController::class, 'store'])
        ->middleware('permission:employees-create')
        ->name('user.store');
    Route::get('/user/show/{employee}', [EmployeeController::class, 'show'])
        ->middleware('permission:employees-list')
        ->name('user-show');
    Route::get('/user/edit/{employee}', fn (User $employee) => redirect()->route('employees.index', ['edit' => $employee->id]))
        ->middleware('permission:employees-edit')
        ->name('user-edit');
    Route::put('/user/update/{employee}', [EmployeeController::class, 'update'])
        ->middleware('permission:employees-edit')
        ->name('user.update');

    Route::get('/client', fn () => redirect()->route('clients.index'))->middleware('permission:clients-list')->name('client-index');
    Route::get('/client/create', fn () => redirect()->route('clients.create'))->middleware('permission:clients-create')->name('client-create');
    Route::get('/client/show/{client}', fn (Client $client) => redirect()->route('clients.show', $client))->middleware('permission:clients-list')->name('client-show');
    Route::get('/client/edit/{client}', fn (Client $client) => redirect()->route('clients.edit', $client))->middleware('permission:clients-edit')->name('client-edit');

    Route::get('/project', fn () => redirect()->route('projects.index'))->middleware('permission:projects-list')->name('project-index');
    Route::get('/project/create', fn () => redirect()->route('projects.create'))->middleware('permission:projects-create')->name('project-create');
    Route::get('/project/show/{project}', fn (Project $project) => redirect()->route('projects.show', $project))->middleware('permission:projects-list')->name('project-show');
    Route::get('/project/edit/{project}', fn (Project $project) => redirect()->route('projects.edit', $project))->middleware('permission:projects-edit')->name('project-edit');

    Route::get('/payment', fn () => redirect()->route('payments.index'))->middleware('permission:payments-list')->name('payment-index');
    Route::get('/stage', fn () => redirect()->route('payment-stages.index'))->middleware('permission:payment-stages-list')->name('stage-index');
    Route::get('/maincategory', fn () => redirect()->route('main_categories.index'))->middleware('permission:main-categories-list')->name('maincategory.index');
    Route::get('/category', fn () => redirect()->route('categories.index'))->middleware('permission:categories-list')->name('category-index');
    Route::get('/unit', fn () => redirect()->route('units.index'))->middleware('permission:units-list')->name('unit.index');
    Route::get('/vendor', fn () => redirect()->route('vendors.index'))->middleware('permission:vendors-list')->name('vendor-index');
    Route::get('/labour', fn () => redirect()->route('labours.index'))->middleware('permission:labours-list')->name('labour-index');
    Route::get('/labour-role', fn () => redirect()->route('labour_roles.index'))->middleware('permission:labour-roles-list')->name('labourrole-index');
    Route::get('/client-summary', fn () => redirect()->route('reports.index', ['type' => 'site']))->middleware('permission:reports-list')->name('client-summary');
    Route::get('/payment-summary', fn () => redirect()->route('reports.index', ['type' => 'office']))->middleware('permission:reports-list')->name('payment-summary');
    Route::get('/payment-income/{project}', fn (Project $project) => redirect()->route('reports.index', ['type' => 'total', 'project_id' => $project->id]))->middleware('permission:reports-list')->name('payment-income');
    Route::get('/payment-expenses/{project}', fn (Project $project) => redirect()->route('reports.index', ['type' => 'site', 'project_id' => $project->id]))->middleware('permission:reports-list')->name('payment-expenses');

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
    });

    Route::prefix('expense-reports')->name('expenseReports.')->group(function () {
        Route::get('/', [ExpenseReportController::class, 'index'])
            ->middleware('permission:expense-reports-list')
            ->name('index');
    });

    Route::prefix('projects')->name('projects.')->group(function () {
        Route::middleware('permission:projects-list')->group(function () {
            Route::get('/', [ProjectController::class, 'index'])->name('index');
            Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
            Route::get('/{project}/final-bill', [ProjectController::class, 'finalBill'])->name('final-bill');
            Route::get('/{project}/invoice', [ProjectPdfController::class, 'generate'])->name('invoice');
        });
        Route::middleware('permission:projects-create')->group(function () {
            Route::get('/create', [ProjectController::class, 'create'])->name('create');
            Route::post('/store', [ProjectController::class, 'store'])->name('store');
        });
        Route::middleware('permission:projects-edit')->group(function () {
            Route::get('/{project}/edit', [ProjectController::class, 'edit'])->name('edit');
            Route::put('/{project}/update', [ProjectController::class, 'update'])->name('update');
        });
        Route::middleware('permission:projects-delete')->group(function () {
            Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('quotations')->name('quotations.')->group(function () {
        Route::get('/by-project/{project}', [QuotationController::class, 'getQuotationsByProject'])
            ->middleware('permission:quotations-list')
            ->name('by-project');
        Route::middleware('permission:quotations-list')->group(function () {
            Route::get('/', [QuotationController::class, 'list'])->name('list');
            Route::get('/{quotation}', [QuotationController::class, 'show'])
                ->whereNumber('quotation')
                ->name('show');
            Route::get('/{quotation}/download', [QuotationController::class, 'downloadPdf'])->name('download');
            Route::get('/{quotation}/stream', [QuotationController::class, 'streamPdf'])->name('stream');
        });
        Route::middleware('permission:quotations-create')->group(function () {
            Route::get('/create', [QuotationController::class, 'create'])->name('create');
            Route::post('/store', [QuotationController::class, 'store'])->name('store');
        });
        Route::middleware('permission:quotations-edit')->group(function () {
            Route::get('/edit/{quotation}', [QuotationController::class, 'edit'])->name('edit');
            Route::post('/update/{quotation}', [QuotationController::class, 'update'])->name('update');
        });
        Route::middleware('permission:quotations-delete')->group(function () {
            Route::delete('/delete/{quotation}', [QuotationController::class, 'destroy'])->name('delete');
        });
    });

    Route::prefix('clients')->name('clients.')->group(function () {
        Route::middleware('permission:clients-create')->group(function () {
            Route::get('/create', [ClientController::class, 'create'])->name('create');
            Route::post('/store', [ClientController::class, 'store'])->name('store');
        });
        Route::middleware('permission:clients-list')->group(function () {
            Route::get('/', [ClientController::class, 'index'])->name('index');
            Route::get('/{client}', [ClientController::class, 'show'])->name('show');
        });
        Route::middleware('permission:clients-edit')->group(function () {
            Route::get('/{client}/edit', [ClientController::class, 'edit'])->name('edit');
            Route::put('/{client}/update', [ClientController::class, 'update'])->name('update');
        });
        Route::middleware('permission:clients-delete')->group(function () {
            Route::delete('/{client}', [ClientController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('expense-transactions')->name('expense-transactions.')->group(function () {
        Route::middleware('permission:expenses-list')->group(function () {
            Route::get('/', [ExpenseTransactionController::class, 'index'])->name('index');
        });
        Route::middleware('permission:expenses-create')->group(function () {
            Route::get('/create', [ExpenseTransactionController::class, 'create'])->name('create');
            Route::post('/store', [ExpenseTransactionController::class, 'store'])->name('store');
        });
        Route::middleware('permission:expenses-edit')->group(function () {
            Route::get('/{expenseTransaction}/edit', [ExpenseTransactionController::class, 'edit'])->name('edit');
            Route::put('/{expenseTransaction}/update', [ExpenseTransactionController::class, 'update'])->name('update');
        });
        Route::middleware('permission:expenses-delete')->group(function () {
            Route::delete('/{expenseTransaction}', [ExpenseTransactionController::class, 'destroy'])->name('destroy');
        });
    });

    // keep existing legacy expenses module untouched

    Route::get('/expenses-history', [ExpensesController::class, 'history'])
        ->middleware('permission:expenses-list')
        ->name('expenses.history');
    Route::get('/expenses-create', fn () => redirect()->route('expenses.history', ['create' => 1]))
        ->middleware('permission:expenses-create')
        ->name('expenses.create.legacy');
    Route::get('/expenses/edit/{id}', fn (int $id) => redirect()->route('expenses.history', ['edit' => $id]))
        ->middleware('permission:expenses-edit')
        ->name('expenses.edit.legacy');
    Route::post('/expenses/store', [ExpensesController::class, 'store'])
        ->middleware('permission:expenses-create')
        ->name('expenses.store.new');
    Route::match(['post', 'put'], '/expenses/update/{id}', [ExpensesController::class, 'update'])
        ->middleware('permission:expenses-edit')
        ->name('expenses.update.new');
    Route::post('/expenses-delete_record', [ExpensesController::class, 'deleteRecord'])
        ->middleware('permission:expenses-delete')
        ->name('expenses.delete-record');
    Route::post('/expenses-restore_record', [ExpensesController::class, 'restoreRecord'])
        ->middleware('permission:expenses-delete')
        ->name('expenses.restore-record');
    Route::get('/expenses-deleted-history', [ExpensesController::class, 'deletedHistory'])
        ->middleware('permission:expenses-list')
        ->name('expenses.deleted-history');

    Route::get('/unpaid-history', [UnpaidExpensesController::class, 'history'])
        ->middleware('permission:expenses-list')
        ->name('expenses.unpaid-history');
    Route::get('/unpaid-create/{id}', fn (int $id) => redirect()->route('expenses.unpaid-history', ['edit' => $id]))
        ->middleware('permission:expenses-edit')
        ->name('expenses.unpaid-create.legacy');
    Route::post('/unpaid-store', [UnpaidExpensesController::class, 'store'])
        ->middleware('permission:expenses-edit')
        ->name('expenses.unpaid-store');

    Route::get('/labour-expenses-history', [LabourExpensesController::class, 'history'])
        ->middleware('permission:expenses-list')
        ->name('labour-expenses.history');
    Route::post('/labour-expenses/store', [LabourExpensesController::class, 'store'])
        ->middleware('permission:expenses-create')
        ->name('labour-expenses.store');
    Route::get('/labour-expenses/create', fn () => redirect()->route('labour-expenses.history', ['create' => 1]))
        ->middleware('permission:expenses-create')
        ->name('labour-expenses.create.legacy');
    Route::get('/labour-expenses/edit/{id}', fn (int $id) => redirect()->route('labour-expenses.history', ['edit' => $id]))
        ->middleware('permission:expenses-edit')
        ->name('labour-expenses.edit.legacy');
    Route::put('/labour-expenses/update/{id}', [LabourExpensesController::class, 'update'])
        ->middleware('permission:expenses-edit')
        ->name('labour-expenses.update.legacy');
    Route::get('/labour-expenses', [LabourExpensesController::class, 'weeklyHistory'])
        ->middleware('permission:expenses-list')
        ->name('labour-expenses.weekly');
    Route::get('/labour-expenses-project', [LabourExpensesController::class, 'projectHistory'])
        ->middleware('permission:expenses-list')
        ->name('labour-expenses.project');
    Route::get('/labour-expenses-advance', [LabourExpensesController::class, 'advanceHistory'])
        ->middleware('permission:expenses-list')
        ->name('labour-expenses.advance-history');
    Route::post('/labour-advance/store', [LabourExpensesController::class, 'advanceStore'])
        ->middleware('permission:expenses-edit')
        ->name('labour-expenses.advance-store');
    Route::post('/labour-expenses-delete_record', [LabourExpensesController::class, 'deleteRecord'])
        ->middleware('permission:expenses-delete')
        ->name('labour-expenses.delete-record');
    Route::get('/labour-expenses-deleted-history', [LabourExpensesController::class, 'deletedHistory'])
        ->middleware('permission:expenses-list')
        ->name('labour-expenses.deleted-history');

    Route::get('/vendor-expenses', [VendorExpensesController::class, 'history'])
        ->middleware('permission:expenses-list')
        ->name('vendor-expenses.history');
    Route::post('/vendor-expenses/store', [VendorExpensesController::class, 'store'])
        ->middleware('permission:expenses-create')
        ->name('vendor-expenses.store');
    Route::get('/vendor-expenses/create', fn () => redirect()->route('vendor-expenses.history', ['create' => 1]))
        ->middleware('permission:expenses-create')
        ->name('vendor-expenses.create.legacy');
    Route::get('/vendor-expenses/edit/{id}', fn (int $id) => redirect()->route('vendor-expenses.history', ['edit' => $id]))
        ->middleware('permission:expenses-edit')
        ->name('vendor-expenses.edit.legacy');
    Route::put('/vendor-expenses/update/{id}', [VendorExpensesController::class, 'update'])
        ->middleware('permission:expenses-edit')
        ->name('vendor-expenses.update.legacy');
    Route::get('/vendor-expenses-unpaid-history', [VendorExpensesController::class, 'unpaidHistory'])
        ->middleware('permission:expenses-list')
        ->name('vendor-expenses.unpaid-history');
    Route::get('/vendor-expenses-unpaid-edit/{id}', fn (int $id) => redirect()->route('vendor-expenses.unpaid-history', ['edit' => $id]))
        ->middleware('permission:expenses-edit')
        ->name('vendor-expenses.unpaid-edit.legacy');
    Route::post('/vendor-expenses-unpaid-store', [VendorExpensesController::class, 'unpaidStore'])
        ->middleware('permission:expenses-edit')
        ->name('vendor-expenses.unpaid-store');
    Route::get('/vendor-expenses-advance-history', [VendorExpensesController::class, 'advanceHistory'])
        ->middleware('permission:expenses-list')
        ->name('vendor-expenses.advance-history');
    Route::post('/vendor-advance-store', [VendorExpensesController::class, 'advanceStore'])
        ->middleware('permission:expenses-edit')
        ->name('vendor-expenses.advance-store');
    Route::post('/vendor-expenses-delete_record', [VendorExpensesController::class, 'deleteRecord'])
        ->middleware('permission:expenses-delete')
        ->name('vendor-expenses.delete-record');
    Route::get('/vendor-expenses-deleted-history', [VendorExpensesController::class, 'deletedHistory'])
        ->middleware('permission:expenses-list')
        ->name('vendor-expenses.deleted-history');

    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::middleware('permission:expenses-list')->group(function () {
            Route::get('/', [ExpenseController::class, 'index'])->name('index');
        });

        Route::middleware('permission:expenses-create')->group(function () {
            Route::post('/', [ExpenseController::class, 'store'])->name('store');
        });
        Route::middleware('permission:expenses-edit')->group(function () {
            Route::put('/{expense}', [ExpenseController::class, 'update'])->name('update');
        });
        Route::middleware('permission:expenses-delete')->group(function () {
            Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('employee-salaries')->name('employee-salaries.')->group(function () {
        Route::middleware('permission:employees-salary-list')->group(function () {
            Route::get('/', [EmployeeSalaryController::class, 'index'])->name('index');
        });
        Route::middleware('permission:employees-salary-create')->group(function () {
            Route::get('/create', [EmployeeSalaryController::class, 'create'])->name('create');
            Route::post('/store', [EmployeeSalaryController::class, 'store'])->name('store');
            Route::post('/calculate', [EmployeeSalaryController::class, 'calculate'])->name('calculate');
        });
        Route::middleware('permission:employees-salary-edit')->group(function () {
            Route::get('/{employeeSalary}/edit', [EmployeeSalaryController::class, 'edit'])->name('edit');
            Route::put('/{employeeSalary}/update', [EmployeeSalaryController::class, 'update'])->name('update');
        });
        Route::middleware('permission:employees-salary-delete')->group(function () {
            Route::delete('/{employeeSalary}', [EmployeeSalaryController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('employees')->name('employees.')->group(function () {
        Route::middleware('permission:employees-create')->group(function () {
            Route::get('/create', [EmployeeController::class, 'create'])->name('create');
            Route::post('/store', [EmployeeController::class, 'store'])->name('store');
        });
        Route::middleware('permission:employees-list')->group(function () {
            Route::get('/', [EmployeeController::class, 'index'])->name('index');
            Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
        });
        Route::middleware('permission:employees-edit')->group(function () {
            Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
            Route::put('/{employee}/update', [EmployeeController::class, 'update'])->name('update');
        });
        Route::middleware('permission:employees-delete')->group(function () {
            Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::middleware('permission:tasks-list')->group(function () {
            Route::get('/', [TaskController::class, 'index'])->name('index');
            Route::get('/{task}', [TaskController::class, 'show'])->name('show');
        });
        Route::middleware('permission:tasks-create')->group(function () {
            Route::get('/create', [TaskController::class, 'create'])->name('create');
            Route::post('/store', [TaskController::class, 'store'])->name('store');
        });
        Route::middleware('permission:tasks-edit')->group(function () {
            Route::get('/{task}/edit', [TaskController::class, 'edit'])->name('edit');
            Route::put('/{task}/update', [TaskController::class, 'update'])->name('update');
        });
        Route::middleware('permission:tasks-delete')->group(function () {
            Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('tools-materials')->name('tools-materials.')->group(function () {
        Route::middleware('permission:tools-materials-list')->group(function () {
            Route::get('/', [ToolMaterialController::class, 'index'])->name('index');
        });
        Route::middleware('permission:tools-materials-create')->group(function () {
            Route::get('/create', [ToolMaterialController::class, 'create'])->name('create');
            Route::post('/store', [ToolMaterialController::class, 'store'])->name('store');
        });
        Route::middleware('permission:tools-materials-edit')->group(function () {
            Route::get('/{toolsMaterial}/edit', [ToolMaterialController::class, 'edit'])->name('edit');
            Route::put('/{toolsMaterial}/update', [ToolMaterialController::class, 'update'])->name('update');
        });
        Route::middleware('permission:tools-materials-delete')->group(function () {
            Route::delete('/{toolsMaterial}', [ToolMaterialController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('tools-material-assignments')->name('tools-material-assignments.')->group(function () {
        Route::middleware('permission:tools-materials-list')->group(function () {
            Route::get('/', [ToolMaterialAssignmentController::class, 'index'])->name('index');
        });
        Route::middleware('permission:tools-materials-create')->group(function () {
            Route::get('/create', [ToolMaterialAssignmentController::class, 'create'])->name('create');
            Route::post('/store', [ToolMaterialAssignmentController::class, 'store'])->name('store');
        });
        Route::middleware('permission:tools-materials-edit')->group(function () {
            Route::get('/{toolsMaterialAssignment}/edit', [ToolMaterialAssignmentController::class, 'edit'])->name('edit');
            Route::put('/{toolsMaterialAssignment}/update', [ToolMaterialAssignmentController::class, 'update'])->name('update');
        });
        Route::middleware('permission:tools-materials-delete')->group(function () {
            Route::delete('/{toolsMaterialAssignment}', [ToolMaterialAssignmentController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('payments')->name('payments.')->middleware('auth')->group(function () {
        Route::middleware('permission:payments-list')->group(function () {
            Route::get('/', [PaymentController::class, 'index'])->name('index');
            Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
        });
        Route::middleware('permission:payments-create')->group(function () {
            Route::post('/store', [PaymentController::class, 'store'])->name('store');
        });
        Route::middleware('permission:payments-edit')->group(function () {
            Route::put('/{payment}/update', [PaymentController::class, 'update'])->name('update');
        });
        Route::middleware('permission:payments-delete')->group(function () {
            Route::delete('/{payment}', [PaymentController::class, 'destroy'])->name('destroy');
        });
        Route::middleware('permission:payments-list')->group(function () {
            Route::get('/projects-by-client/{client}', [PaymentController::class, 'getProjectsByClient'])->name('projects-by-client');
            Route::get('/quotations-by-project/{project}', [PaymentController::class, 'getQuotationsByProject'])->name('quotations-by-project');
            Route::get('/quotations-by-client/{client}', [PaymentController::class, 'getQuotationsByClient'])->name('quotations-by-client');
            Route::get('/quotation-total/{id}', [PaymentController::class, 'quotationTotal'])->name('quotation-total');
        });
    });

    Route::prefix('payment-stages')->name('payment-stages.')->group(function () {
        Route::middleware('permission:payment-stages-list')->group(function () {
            Route::get('/', [PaymentStageController::class, 'index'])->name('index');
            Route::get('/{paymentStage}', [PaymentStageController::class, 'show'])->name('show');
        });
        Route::middleware('permission:payment-stages-create')->group(function () {
            Route::post('/store', [PaymentStageController::class, 'store'])->name('store');
        });
        Route::middleware('permission:payment-stages-edit')->group(function () {
            Route::put('/{paymentStage}/update', [PaymentStageController::class, 'update'])->name('update');
        });
        Route::middleware('permission:payment-stages-delete')->group(function () {
            Route::delete('/{paymentStage}', [PaymentStageController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('variations')->name('variations.')->group(function () {
        Route::middleware('permission:variations-list')->group(function () {
            Route::get('/', [VariationController::class, 'index'])->name('index');
            Route::get('/{variation}', [VariationController::class, 'show'])->name('show');
        });
        Route::middleware('permission:variations-create')->group(function () {
            Route::post('/store', [VariationController::class, 'store'])->name('store');
        });
        Route::middleware('permission:variations-edit')->group(function () {
            Route::get('/{variation}/edit', [VariationController::class, 'edit'])->name('edit');
            Route::put('/{variation}/update', [VariationController::class, 'update'])->name('update');
        });
        Route::middleware('permission:variations-delete')->group(function () {
            Route::delete('/{variation}', [VariationController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('roles')->name('roles.')->group(function () {
        Route::middleware('permission:roles-list')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
        });
        Route::middleware('permission:roles-create')->group(function () {
            Route::get('/create', [RoleController::class, 'create'])->name('create');
            Route::post('/store', [RoleController::class, 'store'])->name('store');
        });
        Route::middleware('permission:roles-edit')->group(function () {
            Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::put('/{role}/update', [RoleController::class, 'update'])->name('update');
        });
        Route::middleware('permission:roles-delete')->group(function () {
            Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('leave-requests')->name('leaveRequests.')->group(function () {
        Route::middleware('permission:leave-requests-create')->group(function () {
            Route::get('/create', [LeaveRequestController::class, 'create'])->name('create');
            Route::post('/store', [LeaveRequestController::class, 'store'])->name('store');
        });

        Route::middleware('permission:leave-requests-list')->group(function () {
            Route::get('/', [LeaveRequestController::class, 'index'])->name('index');
            Route::get('/{leaveRequest}', [LeaveRequestController::class, 'show'])
                ->whereNumber('leaveRequest')
                ->name('show');
        });

        Route::middleware('permission:leave-requests-edit')->group(function () {
            Route::post('/{leaveRequest}/action', [LeaveRequestController::class, 'approveOrReject'])->name('action');
        });

        Route::middleware('permission:leave-requests-delete')->group(function () {
            Route::delete('/{leaveRequest}', [LeaveRequestController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('permissions')->name('permissions.')->group(function () {

        Route::middleware('permission:permissions-list')->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->name('index');
        });
        Route::middleware('permission:permissions-create')->group(function () {
            Route::get('/create', [PermissionController::class, 'create'])->name('create');
            Route::post('/store', [PermissionController::class, 'store'])->name('store');
        });
        Route::middleware('permission:permissions-edit')->group(function () {
            Route::get('/{permission}/edit', [PermissionController::class, 'edit'])->name('edit');
            Route::put('/{permission}/update', [PermissionController::class, 'update'])->name('update');
        });
        Route::middleware('permission:permissions-delete')->group(function () {
            Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('destroy');
        });
    });

    Route::get('/manage-users', [EmployeeController::class, 'index'])
        ->middleware('permission:employees-list')
        ->name('manage-users');

    Route::prefix('labour-roles')->name('labour_roles.')->group(function () {
        Route::middleware('permission:labour-roles-list')->group(function () {
            Route::get('/', [LabourRoleController::class, 'index'])->name('index');
        });
        Route::middleware('permission:labour-roles-create')->group(function () {
            Route::get('/create', [LabourRoleController::class, 'create'])->name('create');
            Route::post('/store', [LabourRoleController::class, 'store'])->name('store');
        });
        Route::middleware('permission:labour-roles-edit')->group(function () {
            Route::get('/edit/{id}', [LabourRoleController::class, 'edit'])->name('edit');
            Route::post('/update/{id}', [LabourRoleController::class, 'update'])->name('update');
        });
        Route::middleware('permission:labour-roles-delete')->group(function () {
            Route::delete('/delete/{id}', [LabourRoleController::class, 'destroy'])->name('delete');
        });
    });

    Route::prefix('labours')->name('labours.')->group(function () {
        Route::middleware('permission:labours-list')->group(function () {
            Route::get('/', [LabourController::class, 'index'])->name('index');
        });
        Route::middleware('permission:labours-create')->group(function () {
            Route::get('/create', [LabourController::class, 'create'])->name('create');
            Route::post('/store', [LabourController::class, 'store'])->name('store');
        });
        Route::middleware('permission:labours-edit')->group(function () {
            Route::get('/edit/{id}', [LabourController::class, 'edit'])->name('edit');
            Route::post('/update/{id}', [LabourController::class, 'update'])->name('update');
        });
        Route::middleware('permission:labours-delete')->group(function () {
            Route::delete('/delete/{id}', [LabourController::class, 'destroy'])->name('delete');
        });
    });

    Route::prefix('labour-assignments')->name('labour_assignments.')->group(function () {
        Route::middleware('permission:labour-assignments-list')->group(function () {
            Route::get('/', [LabourAssignmentController::class, 'index'])->name('index');
        });
        Route::middleware('permission:labour-assignments-create')->group(function () {
            Route::post('/store', [LabourAssignmentController::class, 'store'])->name('store');
        });
        Route::middleware('permission:labour-assignments-edit')->group(function () {
            Route::get('/{labourAssignment}/edit', [LabourAssignmentController::class, 'edit'])->name('edit');
            Route::put('/{labourAssignment}', [LabourAssignmentController::class, 'update'])->name('update');
        });
        Route::middleware('permission:labour-assignments-delete')->group(function () {
            Route::delete('/{labourAssignment}', [LabourAssignmentController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('labour-salaries')->name('labour-salaries.')->group(function () {
        Route::middleware('permission:labour-salaries-list')->group(function () {
            Route::get('/', [LabourSalaryController::class, 'index'])->name('index');
        });
        Route::middleware('permission:labour-salaries-create')->group(function () {
            Route::get('/create', [LabourSalaryController::class, 'create'])->name('create');
            Route::get('/calculate', [LabourSalaryController::class, 'calculate'])->name('calculate');
            Route::post('/store', [LabourSalaryController::class, 'store'])->name('store');
        });
        Route::middleware('permission:labour-salaries-edit')->group(function () {
            Route::get('/{labourSalary}/edit', [LabourSalaryController::class, 'edit'])->name('edit');
            Route::put('/{labourSalary}/update', [LabourSalaryController::class, 'update'])->name('update');
        });
        Route::middleware('permission:labour-salaries-delete')->group(function () {
            Route::delete('/{labourSalary}', [LabourSalaryController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('vendors')->name('vendors.')->group(function () {
        Route::middleware('permission:vendors-list')->group(function () {
            Route::get('/', [VendorController::class, 'index'])->name('index');
        });
        Route::middleware('permission:vendors-create')->group(function () {
            Route::get('/create', [VendorController::class, 'create'])->name('create');
            Route::post('/store', [VendorController::class, 'store'])->name('store');
        });
        Route::middleware('permission:vendors-edit')->group(function () {
            Route::get('/edit/{id}', [VendorController::class, 'edit'])->name('edit');
            Route::post('/update/{id}', [VendorController::class, 'update'])->name('update');
        });
        Route::middleware('permission:vendors-delete')->group(function () {
            Route::delete('/destroy/{id}', [VendorController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('main-categories')->name('main_categories.')->group(function () {
        Route::middleware('permission:main-categories-list')->group(function () {
            Route::get('/', [MainCategoryController::class, 'index'])->name('index');
        });
        Route::middleware('permission:main-categories-create')->group(function () {
            Route::get('/create', [MainCategoryController::class, 'create'])->name('create');
            Route::post('/store', [MainCategoryController::class, 'store'])->name('store');
        });
        Route::middleware('permission:main-categories-edit')->group(function () {
            Route::get('/edit/{id}', [MainCategoryController::class, 'edit'])->name('edit');
            Route::put('/update/{id}', [MainCategoryController::class, 'update'])->name('update');
        });
        Route::middleware('permission:main-categories-delete')->group(function () {
            Route::delete('/destroy/{id}', [MainCategoryController::class, 'destroy'])->name('destroy');
        });

        Route::post('/{id}/toggle', [MainCategoryController::class, 'toggle'])
            ->middleware('permission:main-categories-edit')
            ->name('toggle');
    });

    // Transfer module
    Route::prefix('transfers')->name('transfers.')->group(function () {
        Route::middleware('permission:transfers-list')->group(function () {
            Route::get('/', [TransferDetailsController::class, 'index'])->name('index');
        });
        Route::middleware('permission:transfers-create')->group(function () {
            Route::get('/create', [TransferDetailsController::class, 'create'])->name('create');
            Route::post('/store', [TransferDetailsController::class, 'store'])->name('store');
        });
        Route::middleware('permission:transfers-edit')->group(function () {
            Route::get('/{id}/edit', [TransferDetailsController::class, 'edit'])->name('edit');
            Route::put('/{id}/update', [TransferDetailsController::class, 'update'])->name('update');
        });
        Route::middleware('permission:transfers-delete')->group(function () {
            Route::delete('/{id}', [TransferDetailsController::class, 'destroy'])->name('destroy');
        });
    });

    Route::get('/wallet-history', [WalletController::class, 'index'])
        ->middleware('permission:transfers-list')
        ->name('wallet.index');
    Route::get('/wallet-create', [WalletController::class, 'create'])
        ->middleware('permission:transfers-create')
        ->name('wallet.create');
    Route::post('/wallet/store', [WalletController::class, 'store'])
        ->middleware('permission:transfers-create')
        ->name('wallet.store');

    Route::prefix('categories')->name('categories.')->group(function () {
        Route::middleware('permission:categories-list')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
        });
        Route::middleware('permission:categories-create')->group(function () {
            Route::get('/create', [CategoryController::class, 'create'])->name('create');
            Route::post('/store', [CategoryController::class, 'store'])->name('store');
        });
        Route::middleware('permission:categories-edit')->group(function () {
            Route::get('/edit/{id}', [CategoryController::class, 'edit'])->name('edit');
            Route::put('/update/{id}', [CategoryController::class, 'update'])->name('update');
        });
        Route::middleware('permission:categories-delete')->group(function () {
            Route::delete('/destroy/{id}', [CategoryController::class, 'destroy'])->name('destroy');
        });

        Route::post('/assign', [CategoryController::class, 'assign'])
            ->middleware('permission:categories-edit')
            ->name('assign');
    });

    Route::prefix('units')->name('units.')->group(function () {
        Route::middleware('permission:units-list')->group(function () {
            Route::get('/', [UnitController::class, 'index'])->name('index');
        });
        Route::middleware('permission:units-create')->group(function () {
            Route::get('/create', [UnitController::class, 'create'])->name('create');
            Route::post('/store', [UnitController::class, 'store'])->name('store');
        });
        Route::middleware('permission:units-edit')->group(function () {
            Route::get('/edit/{unit}', [UnitController::class, 'edit'])->name('edit');
            Route::put('/update/{unit}', [UnitController::class, 'update'])->name('update');
        });
        Route::middleware('permission:units-delete')->group(function () {
            Route::delete('/destroy/{unit}', [UnitController::class, 'destroy'])->name('destroy');
        });
    });
    Route::get('/excel/import', function () {
        return view('pages.excel.expense_import');
    })->middleware('permission:expenses-create')->name('excel.import.form');
    Route::post('/excel/import', [ExpenseImportController::class, 'import'])
        ->middleware('permission:expenses-create')
        ->name('excel.import');
    Route::get('/clients/import', [ExcelImportController::class, 'clients'])->middleware('permission:clients-create')->name('clients.import.form');
    Route::post('/clients/import', [ExcelImportController::class, 'importClients'])->middleware('permission:clients-create')->name('clients.import');
    Route::get('/employees/import', [ExcelImportController::class, 'employees'])->middleware('permission:employees-create')->name('employees.import.form');
    Route::post('/employees/import', [ExcelImportController::class, 'importEmployees'])->middleware('permission:employees-create')->name('employees.import');
    Route::get('/projects/import', [ExcelImportController::class, 'projects'])->middleware('permission:projects-create')->name('projects.import.form');
    Route::post('/projects/import', [ExcelImportController::class, 'importProjects'])->middleware('permission:projects-create')->name('projects.import');
    Route::get('/vendors/import', [ExcelImportController::class, 'vendors'])->middleware('permission:vendors-create')->name('vendors.import.form');
    Route::post('/vendors/import', [ExcelImportController::class, 'importVendors'])->middleware('permission:vendors-create')->name('vendors.import');
    Route::get('/labours/import', [ExcelImportController::class, 'labours'])->middleware('permission:labours-create')->name('labours.import.form');
    Route::post('/labours/import', [ExcelImportController::class, 'importLabours'])->middleware('permission:labours-create')->name('labours.import');
    Route::get('/labour-roles/import', [ExcelImportController::class, 'labourRoles'])->middleware('permission:labour-roles-create')->name('labour_roles.import.form');
    Route::post('/labour-roles/import', [ExcelImportController::class, 'importLabourRoles'])->middleware('permission:labour-roles-create')->name('labour_roles.import');
    Route::get('/main-categories/import', [ExcelImportController::class, 'mainCategories'])->middleware('permission:main-categories-create')->name('main_categories.import.form');
    Route::post('/main-categories/import', [ExcelImportController::class, 'importMainCategories'])->middleware('permission:main-categories-create')->name('main_categories.import');
    Route::get('/categories/import', [ExcelImportController::class, 'categories'])->middleware('permission:categories-create')->name('categories.import.form');
    Route::post('/categories/import', [ExcelImportController::class, 'importCategories'])->middleware('permission:categories-create')->name('categories.import');
    Route::get('/units/import', [ExcelImportController::class, 'units'])->middleware('permission:units-create')->name('units.import.form');
    Route::post('/units/import', [ExcelImportController::class, 'importUnits'])->middleware('permission:units-create')->name('units.import');
    Route::get('/payment-methods/import', [ExcelImportController::class, 'paymentMethods'])->middleware('permission:payment-methods-create')->name('payment-methods.import.form');
    Route::post('/payment-methods/import', [ExcelImportController::class, 'importPaymentMethods'])->middleware('permission:payment-methods-create')->name('payment-methods.import');
    Route::get('/payment-stages/import',[ExcelImportController::class,'paymentStages'])->middleware('permission:payment-stages-create')->name('payment-stages.import.form');
    Route::post('/payment-stages/import',[ExcelImportController::class,'importPaymentStages'])->middleware('permission:payment-stages-create')->name('payment-stages.import');
    Route::get('/tasks/import',[ExcelImportController::class,'tasks'])->middleware('permission:tasks-create')->name('tasks.import.form');
    Route::post('/tasks/import',[ExcelImportController::class,'importTasks'])->middleware('permission:tasks-create')->name('tasks.import');
    Route::get('/tools-materials/import',[ExcelImportController::class,'toolsMaterials'])->middleware('permission:tools-materials-create')->name('tools-materials.import.form');
    Route::post('/tools-materials/import',[ExcelImportController::class,'importToolsMaterials'])->middleware('permission:tools-materials-create')->name('tools-materials.import');

    Route::prefix('payment-methods')->name('payment-methods.')->group(function () {
        Route::middleware('permission:payment-methods-list')->group(function () {
            Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
        });
        Route::middleware('permission:payment-methods-create')->group(function () {
            Route::get('/create', [PaymentMethodController::class, 'create'])->name('create');
            Route::post('/store', [PaymentMethodController::class, 'store'])->name('store');
        });
        Route::middleware('permission:payment-methods-edit')->group(function () {
            Route::get('/{paymentMethod}/edit', [PaymentMethodController::class, 'edit'])->name('edit');
            Route::put('/{paymentMethod}/update', [PaymentMethodController::class, 'update'])->name('update');
            Route::post('/{paymentMethod}/toggle', [PaymentMethodController::class, 'toggle'])->name('toggle');
        });
        Route::middleware('permission:payment-methods-delete')->group(function () {
            Route::delete('/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('preorders')->name('preorders.')->group(function () {
        // 1. Static creation route BEFORE dynamic /{preorder} parameter routes
        Route::middleware('permission:tools-materials-create')->group(function () {
            Route::get('/create', [PreorderController::class, 'create'])->name('create');
            Route::post('/store', [PreorderController::class, 'store'])->name('store');
        });

        // 2. Static listing & summary report routes
        Route::middleware('permission:tools-materials-list')->group(function () {
            Route::get('/', [PreorderController::class, 'index'])->name('index');
            Route::get('/reports/summary', [PreorderReportController::class, 'index'])->name('reports');
        });

        // 3. Document deletion
        Route::middleware('permission:tools-materials-create')->group(function () {
            Route::delete('/documents/{document}', [PreorderController::class, 'deleteDocument'])->name('documents.destroy');
        });

        // 4. Dynamic /{preorder} model routes (constrained with whereNumber)
        Route::middleware('permission:tools-materials-list')->group(function () {
            Route::get('/convert/{preorder}', [PreorderController::class, 'showConvertToPurchase'])
                ->whereNumber('preorder')->name('convert.form');
            Route::get('/{preorder}', [PreorderController::class, 'show'])
                ->whereNumber('preorder')->name('show');
        });

        Route::middleware('permission:tools-materials-create')->group(function () {
            Route::post('/convert/{preorder}', [PreorderController::class, 'convertToPurchase'])
                ->whereNumber('preorder')->name('convert');
            Route::post('/{preorder}/advances', [PreorderController::class, 'addAdvance'])
                ->whereNumber('preorder')->name('advances.store');
            Route::post('/{preorder}/deliveries', [PreorderController::class, 'recordDelivery'])
                ->whereNumber('preorder')->name('deliveries.store');
            Route::post('/{preorder}/documents', [PreorderController::class, 'uploadDocument'])
                ->whereNumber('preorder')->name('documents.store');
        });

        Route::middleware('permission:tools-materials-edit')->group(function () {
            Route::get('/{preorder}/edit', [PreorderController::class, 'edit'])
                ->whereNumber('preorder')->name('edit');
            Route::put('/{preorder}/update', [PreorderController::class, 'update'])
                ->whereNumber('preorder')->name('update');
            Route::post('/{preorder}/approve', [PreorderController::class, 'approve'])
                ->whereNumber('preorder')->name('approve');
            Route::post('/{preorder}/reject', [PreorderController::class, 'reject'])
                ->whereNumber('preorder')->name('reject');
            Route::post('/{preorder}/change-status', [PreorderController::class, 'changeStatus'])
                ->whereNumber('preorder')->name('change-status');
        });

        Route::middleware('permission:tools-materials-delete')->group(function () {
            Route::delete('/{preorder}', [PreorderController::class, 'destroy'])
                ->whereNumber('preorder')->name('destroy');
        });
    });
});

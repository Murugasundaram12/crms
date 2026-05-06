<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeSalaryController;
use App\Http\Controllers\LabourController;
use App\Http\Controllers\LabourRoleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Artisan;

Route::get('/', [AuthController::class, 'showLogin'])->name('login.form');
Route::post('/', [AuthController::class, 'login'])->name('login');
Route::get('/login', [AuthController::class, 'showLogin']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout.get');

Route::prefix('server-commands')->group(function () {
    Route::get('optimize', function () {
        Artisan::call('optimize:clear');
        Artisan::call('route:clear');
        Artisan::call('route:cache');
        Artisan::call('config:clear');
        dd("Done!");
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/home', [DashboardController::class, 'index'])
        ->name('home');

    Route::redirect('/index', '/dashboard');

    Route::prefix('reports')->name('reports.')->middleware('permission:reports-list')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
    });

    Route::prefix('projects')->name('projects.')->group(function () {
        Route::middleware('permission:projects-list')->group(function () {
            Route::get('/', [ProjectController::class, 'index'])->name('index');
            Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
            Route::get('/{project}/final-bill', [ProjectController::class, 'finalBill'])->name('final-bill');
            Route::get('/{project}/invoice', [\App\Http\Controllers\ProjectPdfController::class, 'generate'])->name('invoice');
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
        Route::get('/by-project/{project}', [\App\Http\Controllers\QuotationController::class, 'getQuotationsByProject'])->name('by-project');
        Route::middleware('permission:quotations-list')->group(function () {
            Route::get('/', [\App\Http\Controllers\QuotationController::class, 'list'])->name('list');
        });
        Route::middleware('permission:quotations-create')->group(function () {
            Route::get('/create', [\App\Http\Controllers\QuotationController::class, 'create'])->name('create');
            Route::post('/store', [\App\Http\Controllers\QuotationController::class, 'store'])->name('store');
        });
        Route::middleware('permission:quotations-edit')->group(function () {
            Route::get('/edit/{quotation}', [\App\Http\Controllers\QuotationController::class, 'edit'])->name('edit');
            Route::post('/update/{quotation}', [\App\Http\Controllers\QuotationController::class, 'update'])->name('update');
        });
        Route::middleware('permission:quotations-delete')->group(function () {
            Route::delete('/delete/{quotation}', [\App\Http\Controllers\QuotationController::class, 'destroy'])->name('delete');
        });
    });

    Route::prefix('clients')->name('clients.')->group(function () {
        Route::middleware('permission:clients-list')->group(function () {
            Route::get('/', [ClientController::class, 'index'])->name('index');
            Route::get('/{client}', [ClientController::class, 'show'])->name('show');
        });
        Route::middleware('permission:clients-create')->group(function () {
            Route::get('/create', [ClientController::class, 'create'])->name('create');
            Route::post('/store', [ClientController::class, 'store'])->name('store');
        });
        Route::middleware('permission:clients-edit')->group(function () {
            Route::get('/{client}/edit', [ClientController::class, 'edit'])->name('edit');
            Route::put('/{client}/update', [ClientController::class, 'update'])->name('update');
        });
        Route::middleware('permission:clients-delete')->group(function () {
            Route::delete('/{client}', [ClientController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::middleware('permission:expenses-list')->group(function () {
            Route::get('/', [\App\Http\Controllers\ExpenseController::class, 'index'])->name('index');
        });
        Route::middleware('permission:expenses-create')->group(function () {
            Route::post('/', [\App\Http\Controllers\ExpenseController::class, 'store'])->name('store');
        });
        Route::middleware('permission:expenses-edit')->group(function () {
            Route::put('/{expense}', [\App\Http\Controllers\ExpenseController::class, 'update'])->name('update');
        });
        Route::middleware('permission:expenses-delete')->group(function () {
            Route::delete('/{expense}', [\App\Http\Controllers\ExpenseController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('employee-salaries')->name('employee-salaries.')->group(function () {
        Route::middleware('permission:employees-salary-list')->group(function () {
            Route::get('/', [EmployeeSalaryController::class, 'index'])->name('index');
        });
        Route::middleware('permission:employees-salary-create')->group(function () {
            Route::get('/create', [EmployeeSalaryController::class, 'create'])->name('create');
            Route::post('/store', [EmployeeSalaryController::class, 'store'])->name('store');
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
        Route::middleware('permission:employees-list')->group(function () {
            Route::get('/', [EmployeeController::class, 'index'])->name('index');
            Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
        });
        Route::middleware('permission:employees-create')->group(function () {
            Route::get('/create', [EmployeeController::class, 'create'])->name('create');
            Route::post('/store', [EmployeeController::class, 'store'])->name('store');
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
        Route::get('/quotations-by-client/{client}', [PaymentController::class, 'getQuotationsByClient'])->name('quotations-by-client');
        Route::get('/quotation-total/{id}', [PaymentController::class, 'quotationTotal'])->name('quotation-total');
        Route::get('/project-by-client/{client}', [PaymentController::class, 'getProjectByClient'])->name('project-by-client');
    });

    Route::prefix('payment-stages')->name('payment-stages.')->group(function () {
        Route::middleware('permission:payment-stages-list')->group(function () {
            Route::get('/', [\App\Http\Controllers\PaymentStageController::class, 'index'])->name('index');
            Route::get('/{paymentStage}', [\App\Http\Controllers\PaymentStageController::class, 'show'])->name('show');
        });
        Route::middleware('permission:payment-stages-create')->group(function () {
            Route::post('/store', [\App\Http\Controllers\PaymentStageController::class, 'store'])->name('store');
        });
        Route::middleware('permission:payment-stages-edit')->group(function () {
            Route::put('/{paymentStage}/update', [\App\Http\Controllers\PaymentStageController::class, 'update'])->name('update');
        });
        Route::middleware('permission:payment-stages-delete')->group(function () {
            Route::delete('/{paymentStage}', [\App\Http\Controllers\PaymentStageController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('variations')->name('variations.')->group(function () {
        Route::middleware('permission:variations-list')->group(function () {
            Route::get('/', [\App\Http\Controllers\VariationController::class, 'index'])->name('index');
            Route::get('/{variation}', [\App\Http\Controllers\VariationController::class, 'show'])->name('show');
        });
        Route::middleware('permission:variations-create')->group(function () {
            Route::post('/store', [\App\Http\Controllers\VariationController::class, 'store'])->name('store');
        });
        Route::middleware('permission:variations-edit')->group(function () {
            Route::get('/{variation}/edit', [\App\Http\Controllers\VariationController::class, 'edit'])->name('edit');
            Route::put('/{variation}/update', [\App\Http\Controllers\VariationController::class, 'update'])->name('update');
        });
        Route::middleware('permission:variations-delete')->group(function () {
            Route::delete('/{variation}', [\App\Http\Controllers\VariationController::class, 'destroy'])->name('destroy');
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

    Route::prefix('vendors')->name('vendors.')->group(function () {
        Route::middleware('permission:vendors-list')->group(function () {
            Route::get('/', [\App\Http\Controllers\VendorController::class, 'index'])->name('index');
        });
        Route::middleware('permission:vendors-create')->group(function () {
            Route::get('/create', [\App\Http\Controllers\VendorController::class, 'create'])->name('create');
            Route::post('/store', [\App\Http\Controllers\VendorController::class, 'store'])->name('store');
        });
        Route::middleware('permission:vendors-edit')->group(function () {
            Route::get('/edit/{id}', [\App\Http\Controllers\VendorController::class, 'edit'])->name('edit');
            Route::post('/update/{id}', [\App\Http\Controllers\VendorController::class, 'update'])->name('update');
        });
        Route::middleware('permission:vendors-delete')->group(function () {
            Route::delete('/destroy/{id}', [\App\Http\Controllers\VendorController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('main-categories')->name('main_categories.')->group(function () {
        Route::middleware('permission:main-categories-list')->group(function () {
            Route::get('/', [\App\Http\Controllers\MainCategoryController::class, 'index'])->name('index');
        });
        Route::middleware('permission:main-categories-create')->group(function () {
            Route::get('/create', [\App\Http\Controllers\MainCategoryController::class, 'create'])->name('create');
            Route::post('/store', [\App\Http\Controllers\MainCategoryController::class, 'store'])->name('store');
        });
        Route::middleware('permission:main-categories-edit')->group(function () {
            Route::get('/edit/{id}', [\App\Http\Controllers\MainCategoryController::class, 'edit'])->name('edit');
            Route::put('/update/{id}', [\App\Http\Controllers\MainCategoryController::class, 'update'])->name('update');
        });
        Route::middleware('permission:main-categories-delete')->group(function () {
            Route::delete('/destroy/{id}', [\App\Http\Controllers\MainCategoryController::class, 'destroy'])->name('destroy');
        });

        Route::post('/{id}/toggle', [\App\Http\Controllers\MainCategoryController::class, 'toggle'])
            ->middleware('permission:main-categories-edit')
            ->name('toggle');
    });

    Route::prefix('categories')->name('categories.')->group(function () {
        Route::middleware('permission:categories-list')->group(function () {
            Route::get('/', [\App\Http\Controllers\CategoryController::class, 'index'])->name('index');
        });
        Route::middleware('permission:categories-create')->group(function () {
            Route::get('/create', [\App\Http\Controllers\CategoryController::class, 'create'])->name('create');
            Route::post('/store', [\App\Http\Controllers\CategoryController::class, 'store'])->name('store');
        });
        Route::middleware('permission:categories-edit')->group(function () {
            Route::get('/edit/{id}', [\App\Http\Controllers\CategoryController::class, 'edit'])->name('edit');
            Route::put('/update/{id}', [\App\Http\Controllers\CategoryController::class, 'update'])->name('update');
        });
        Route::middleware('permission:categories-delete')->group(function () {
            Route::delete('/destroy/{id}', [\App\Http\Controllers\CategoryController::class, 'destroy'])->name('destroy');
        });

        Route::post('/assign', [\App\Http\Controllers\CategoryController::class, 'assign'])
            ->middleware('permission:categories-edit')
            ->name('categories.assign');
    });

    // Route::prefix('quotations')->name('quotations.')->group(function () {
    //     Route::middleware('permission:quotations-list')->group(function () {
    //         Route::get('/', [QuotationController::class, 'index'])->name('index');
    //     });
    //     Route::middleware('permission:quotations-create')->group(function () {
    //         Route::get('/create', [QuotationController::class, 'create'])->name('create');
    //         Route::post('/store', [QuotationController::class, 'store'])->name('store');
    //     });
    //     Route::middleware('permission:quotations-edit')->group(function () {
    //         Route::get('/{quotation}/edit', [QuotationController::class, 'edit'])->name('edit');
    //         Route::post('/{quotation}/update', [QuotationController::class, 'update'])->name('update');
    //     });
    //     Route::middleware('permission:quotations-delete')->group(function () {
    //         Route::delete('/{quotation}', [QuotationController::class, 'destroy'])->name('destroy');
    //     });
    // });
});

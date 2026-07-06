<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\dashboard\AnalyticsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientDetailsController;
use App\Http\Controllers\ProjectDetailsController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\UnpaidExpensesController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StageController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LabourController;
use App\Http\Controllers\LabourExpensesController;
use App\Http\Controllers\LabourRoleController;
use App\Http\Controllers\MainCategoryController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorExpensesController;
use App\Http\Controllers\ExpenseImportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


$controller_path = 'App\Http\Controllers';
Route::prefix('server-commands')->group(function () {
    Route::get('optimize', function () {
        \Artisan::call('optimize:clear');
        \Artisan::call('route:clear');
        \Artisan::call('route:cache');
        \Artisan::call('config:clear');
        dd("Done!");
    });
});
// Main Page Route
Route::get('/', function () {
    return Auth::check() ? to_route('dashboard') : to_route('login');
})->name('root');

// authentication
Route::get('/welcome', function () {
     return to_route('login');
})->name('welcome');

Auth::routes();
Route::get('/forget-password',[LoginController::class,'forget_password'])->name('forget-password');
Route::get('/check-mail',[LoginController::class,'checkmail'])->name('check-mail');
Route::get('send-mail', [LoginController::class, 'send_mail'])->name('send-mail');
Route::get('password-reset/{id}', [LoginController::class, 'password_reset'])->name('password-reset');
Route::put('update-password/{id}', [LoginController::class, 'update_password'])->name('update-password');
Route::get('/home', $controller_path . '\HomeController@index')->name('home');

Route::get('maintenance',[MaintenanceController::class,'maintenance'])->name('maintenance');

Route::group(['middleware' => ['auth','maintenance']], function () {
    Route::get('/dashboard', [AnalyticsController::class, 'index'])->name('dashboard');
    Route::post('/checking', [AnalyticsController::class, 'store'])->name('checking.store');
    Route::put('/checkout', [AnalyticsController::class, 'update'])->name('checking.update');

Route::resource('/roles', RoleController::class);
Route::get('/roles/delete/{id}',[RoleController::class,'roledelete'])->name('roles-delete');
Route::get('/user', [UserController::class, 'index'])->name('user-index');
Route::get('/user/create', [UserController::class, 'create'])->name('user-create');
Route::post('/user/store', [UserController::class, 'store'])->name('user.store');
Route::get('/user/show/{id}', [UserController::class, 'show'])->name('user-show');
Route::get('/user/edit/{id}', [UserController::class, 'edit'])->name('user-edit');
Route::put('/user/update/{id}', [UserController::class, 'update'])->name('user.update');
Route::put('/user/jobupdate/{id}', [UserController::class, 'jobupdate'])->name('user.jobupdate');
Route::get('/user/delete/{id}',[UserController::class,'userdelete'])->name('user-delete');
Route::get('/phoneunique',[UserController::class,'phoneunique'])->name('phoneunique');
Route::post('/profile_photo_upload',[UserController::class,'profile_photo_upload'])->name('profile_photo_upload');

// category list routes started
Route::get('/category', [CategoryController::class, 'index'])->name('category-index');
Route::get('/category/create', [CategoryController::class, 'create'])->name('category-create');
Route::post('/category/store', [CategoryController::class, 'store'])->name('category.store');
Route::get('/category/edit/{id}', [CategoryController::class, 'edit'])->name('category-edit');
Route::put('/category/update/{id}', [CategoryController::class, 'update'])->name('category.update');
Route::get('/category/delete/{id}',[CategoryController::class,'categorydelete'])->name('category-delete');
Route::get('/updateCategory',[CategoryController::class,'updateCategory'])->name('updateCategory');
Route::post('/updateCategoryStatus',[CategoryController::class,'updateCategoryStatus'])->name('updateCategoryStatus');
// category list routes ended

// stage list routes started
Route::get('/stage', [StageController::class, 'index'])->name('stage-index');
Route::get('/stage/create', [StageController::class, 'create'])->name('stage-create');
Route::post('/stage/store', [StageController::class, 'store'])->name('stage.store');
Route::get('/stage/edit/{id}', [StageController::class, 'edit'])->name('stage-edit');
Route::put('/stage/update/{id}', [StageController::class, 'update'])->name('stage.update');
Route::get('/stage/delete/{id}',[StageController::class,'stagedelete'])->name('stage-delete');
// stage list routes ended

// Payment list routes started
Route::get('/payment', [PaymentController::class, 'index'])->name('payment-index');
Route::get('/payment/create', [PaymentController::class, 'create'])->name('payment-create');
Route::post('/payment/store', [PaymentController::class, 'store'])->name('payment.store');
Route::get('/payment/edit/{id}', [PaymentController::class, 'edit'])->name('payment-edit');
Route::put('/payment/update/{id}', [PaymentController::class, 'update'])->name('payment.update');
Route::get('/payment/delete/{id}',[PaymentController::class,'paymentdelete'])->name('payment-delete');
// payment list routes ended

// client details routes started
Route::get('/client', [ClientDetailsController::class, 'index'])->name('client-index');
Route::get('/client/create', [ClientDetailsController::class, 'create'])->name('client-create');
Route::post('/client/store', [ClientDetailsController::class, 'store'])->name('client.store');
Route::get('/client/edit/{id}', [ClientDetailsController::class, 'edit'])->name('client-edit');
Route::put('/client/update/{id}', [ClientDetailsController::class, 'update'])->name('client.update');
Route::get('/client/delete/{id}',[ClientDetailsController::class,'clientdelete'])->name('client-delete');
Route::get('/client/show/{id}', [ClientDetailsController::class, 'show'])->name('client-show');

// client details routes ended
// project details routes started
Route::get('/project', [ProjectDetailsController::class, 'index'])->name('project-index');
Route::get('/project/create', [ProjectDetailsController::class, 'create'])->name('project-create');
Route::post('/project/store', [ProjectDetailsController::class, 'store'])->name('project.store');
Route::get('/project/show/{id}', [ProjectDetailsController::class, 'show'])->name('project-show');
Route::get('/project/edit/{id}', [ProjectDetailsController::class, 'edit'])->name('project-edit');
Route::put('/project/update/{id}', [ProjectDetailsController::class, 'update'])->name('project.update');
Route::get('/project/delete/{id}',[ProjectDetailsController::class,'projectdelete'])->name('project-delete');
Route::get('/project/view/{id}', [ProjectDetailsController::class, 'view'])->name('project-view');
// project details routes ended
// wallet routes started
Route::get('/wallet-history',[WalletController::class,'index'])->name('wallet-history');
Route::get('/wallet-create',[WalletController::class,'create'])->name('wallet-create');
Route::post('/wallet/store',[WalletController::class,'store'])->name('wallet.store');
// wallet routes ended

//transfer details started
Route::get('/transfer',[TransferController::class,'index'])->name('transfer-history');
Route::get('/transfer-create',[TransferController::class,'create'])->name('transfer-create');
Route::post('/transfer/store',[TransferController::class,'store'])->name('transfer.store');
Route::get('/transfer/amountcheck',[TransferController::class,'insufficientamt'])->name('transfer.amount-check');
Route::get('vendor-history',[TransferController::class,'vendor_history'])->name('transfer.vendor.history');
// transfer details ended

// expenses started
Route::get('/expenses-history',[ExpensesController::class,'index'])->name('expenses-history');
Route::get('/expenses-create',[ExpensesController::class,'create'])->name('expenses-create');
Route::post('/expenses/store',[ExpensesController::class,'store'])->name('expenses.store');
Route::post('/expenses/import',[ExpenseImportController::class,'store'])->name('expenses.import');
Route::get('/amountcheck',[ExpensesController::class,'insufficientamt'])->name('amount-check');
Route::get('/unpaidex-create/{id}',[ExpensesController::class,'unpaid_create'])->name('unpaidex-create');
Route::post('/unpaidex-store',[ExpensesController::class,'unpaid_store'])->name('unpaidex.store');
Route::get('/expenses/edit/{id}', [ExpensesController::class, 'edit'])->name('expenses-edit');
Route::put('/expenses/update/{id}', [ExpensesController::class, 'update'])->name('expenses.update');
Route::get('/expenses/delete',[ExpensesController::class,'expensedelete'])->name('expenses-delete');
Route::get('/expenses/image/{id}',[ExpensesController::class,'image_delete'])->name('image-delete');
Route::get('/expenses/category',[ExpensesController::class,'new_category'])->name('new-category');
Route::get('/expenses-export',[ExpensesController::class,'expense_export'])->name('expenses-export');
Route::get('/expenses-pdf',[ExpensesController::class,'expense_pdf'])->name('expenses-pdf');
Route::get('/expenses-delete_record',[ExpensesController::class,'delete_record'])->name('expenses-delete_record');
Route::get('/deleteexpenses-export',[ExpensesController::class,'delete_expense_export'])->name('deleteexpenses-export');
Route::get('/deleteexpenses-pdf',[ExpensesController::class,'delete_expense_pdf'])->name('deleteexpenses-pdf');
Route::delete('/expense-delete-all',[ExpensesController::class,'expense_delete_all'])->name('expense-delete-all');
Route::get('/expense-report',[ExpensesController::class,'reports_history'])->name('expenses-report-history');
Route::get('/expenses-report-export',[ExpensesController::class,'expense_report_export'])->name('expenses-report-export');
Route::get('/expenses-report-pdf',[ExpensesController::class,'expense_report_pdf'])->name('expenses-report-pdf');
Route::get('/expenses/getcategory',[ExpensesController::class,'category'])->name('expenses.category');
// expenses ended
// unpaidexpenses start
Route::get('/unpaid-history',[UnpaidExpensesController::class,'index'])->name('unpaid-history');
Route::get('/unpaid-create/{id}',[UnpaidExpensesController::class,'unpaid_create'])->name('unpaid-create');
Route::post('/unpaid-store',[UnpaidExpensesController::class,'unpaid_store'])->name('unpaid.store');
Route::get('/unpaidexpenses-export',[UnpaidExpensesController::class,'unpaid_expense_export'])->name('unpaidexpenses-export');
Route::get('/unpaidexpenses-pdf',[UnpaidExpensesController::class,'unpaid_expense_pdf'])->name('unpaidexpenses-pdf');
Route::get('/unpaid-delete',[UnpaidExpensesController::class,'expensedelete'])->name('unpaid-delete');
// unpaidexpenses ended
//reports started
Route::get('/client-summary',[ReportsController::class,'client_summary'])->name('client-summary');
Route::get('/payment-summary',[ReportsController::class,'payment_summary'])->name('payment-summary');
Route::get('/payment-income/{id}',[ReportsController::class,'payment_income'])->name('payment-income');
Route::get('/payment-expenses/{id}',[ReportsController::class,'payment_expenses'])->name('payment-expenses');
Route::get('/clientsummary-export',[ReportsController::class,'client_summary_export'])->name('clientsummary-export');
Route::get('/clientsummary-pdf',[ReportsController::class,'client_summary_pdf'])->name('clientsummary-pdf');
Route::get('/paymentincome-export',[ReportsController::class,'payment_income_export'])->name('paymentincome-export');
Route::get('/paymentincome-pdf',[ReportsController::class,'payment_income_pdf'])->name('paymentincome-pdf');
Route::get('/paymentexpense-export',[ReportsController::class,'payment_expense_export'])->name('paymentexpense-export');
Route::get('/paymentexpense-pdf',[ReportsController::class,'payment_expense_pdf'])->name('paymentexpense-pdf');

// expenses ended
// reports ended
//labour details started
Route::get('/labour', [LabourController::class, 'index'])->name('labour-index');
Route::get('/labour/create',[LabourController::class, 'create'])->name('labour-create');
Route::post('/labour/store', [LabourController::class, 'store'])->name('labour.store');
Route::get('/labour/edit/{id}',[LabourController::class, 'edit'])->name('labour-edit');
Route::put('/labour/update/{id}', [LabourController::class, 'update'])->name('labour.update');
Route::get('/labour/delete/{id}',[LabourController::class,'labourdelete'])->name('labour-delete');
Route::get('/labour/salary',[LabourController::class,'salary_get'])->name('salary-get');
//labour details ended
//labour role started
Route::get('/labour-role', [LabourRoleController::class, 'index'])->name('labourrole-index');
Route::get('/labour-role/create',[LabourRoleController::class, 'create'])->name('labourrole-create');
Route::post('/labour-role/store', [LabourRoleController::class, 'store'])->name('labourrole.store');
Route::get('/labour-role/edit/{id}',[LabourRoleController::class, 'edit'])->name('labourrole-edit');
Route::put('/labour-role/update/{id}', [LabourRoleController::class, 'update'])->name('labourrole.update');
Route::get('/labour-role/delete/{id}',[LabourRoleController::class,'labourdelete'])->name('labourrole-delete');
//labour role ended
//vendor started
Route::get('/vendor', [VendorController::class, 'index'])->name('vendor-index');
Route::get('/vendor/create',[VendorController::class, 'create'])->name('vendor-create');
Route::post('/vendor/store', [VendorController::class, 'store'])->name('vendor.store');
Route::get('/vendor/edit/{id}',[VendorController::class, 'edit'])->name('vendor-edit');
Route::put('/vendor/update/{id}', [VendorController::class, 'update'])->name('vendor.update');
Route::get('/vendor/delete/{id}',[VendorController::class,'labourdelete'])->name('vendor-delete');
//vendor ended
//labour expenses started
Route::get('/labour-expenses', [LabourExpensesController::class, 'index'])->name('labour-expenses-index');
Route::get('/labour-expenses/create',[LabourExpensesController::class, 'create'])->name('labour-expenses-create');
Route::post('/labour-expenses/store', [LabourExpensesController::class, 'store'])->name('labour-expenses.store');
Route::get('/labour-expenses/edit/{id}',[LabourExpensesController::class, 'edit'])->name('labour-expenses-edit');
Route::put('/labour-expenses/update/{id}', [LabourExpensesController::class, 'update'])->name('labour-expenses.update');
Route::get('/labour-expenses/delete',[LabourExpensesController::class,'labourdelete'])->name('labour-expenses-delete');
Route::get('/labour-salary',[LabourExpensesController::class, 'labour_salary'])->name('labour-salary');
Route::get('/labour-expenses-project',[LabourExpensesController::class, 'labour_expense_project'])->name('labour-expenses-project');
Route::get('/labour-expenses-labour',[LabourExpensesController::class, 'labour_expenses_details'])->name('labour-expenses-labour');
Route::post('/labour-expenses-store', [LabourExpensesController::class, 'labour_expenses_store'])->name('labour-expenses-store');
Route::get('/labour-expenses-history', [LabourExpensesController::class, 'labour_expenses_history'])->name('labour-expenses-history');
Route::get('/labour-expenses-advance', [LabourExpensesController::class, 'labour_advance'])->name('labour-expenses-advance');
Route::get('/advanceform/{id}', [LabourExpensesController::class, 'advance_form'])->name('advanceform');
Route::get('/labour_project_amount', [LabourExpensesController::class, 'labour_project_amount'])->name('labour_project_amount');
Route::post('/labour-advance/store', [LabourExpensesController::class, 'labour_advance_store'])->name('labour-advance.store');
Route::get('/labour-expenses-delete_record',[LabourExpensesController::class,'delete_record'])->name('labour-expenses-delete_record');
Route::get('/labour-expenses-export',[LabourExpensesController::class,'labour_expense_export'])->name('labour-expenses-export');
Route::get('/labour-expenses-pdf',[LabourExpensesController::class,'labour_expense_pdf'])->name('labour-expenses-pdf');
Route::get('/labour-delete-expenses-pdf',[LabourExpensesController::class,'labour_delete_expense_pdf'])->name('labour-delete-expenses-pdf');
Route::get('/labour-delete-expenses-export',[LabourExpensesController::class,'labour_delete_expense_export'])->name('labour-delete-expenses-export');
Route::get('/labour-total-records', [LabourExpensesController::class, 'labour_total_records'])->name('labour-total-records');

//vendor ended
//vendor started
Route::get('/vendor-expenses', [VendorExpensesController::class, 'index'])->name('vendor-expenses-index');
Route::get('/vendor-expenses/create',[VendorExpensesController::class, 'create'])->name('vendor-expenses-create');
Route::post('/vendor-expenses/store', [VendorExpensesController::class, 'store'])->name('vendor-expenses.store');
Route::get('/vendor-expenses/edit/{id}',[VendorExpensesController::class, 'edit'])->name('vendor-expenses-edit');
Route::put('/vendor-expenses/update/{id}', [VendorExpensesController::class, 'update'])->name('vendor-expenses.update');
Route::get('/vendor-expenses/delete',[VendorExpensesController::class,'vendordelete'])->name('vendor-expenses-delete');
Route::get('/vendor-salary',[VendorExpensesController::class, 'vendor_salary'])->name('vendor-salary');
Route::get('/vendor-expenses-delete_record',[VendorExpensesController::class,'delete_record'])->name('vendor-expenses-delete_record');
Route::get('/vendor-expenses-unpaid-history',[VendorExpensesController::class,'unpaid_expenses'])->name('vendor-expenses-unpaid-history');
Route::get('/vendor-expenses-unpaid-edit/{id}',[VendorExpensesController::class,'unpaid_edit'])->name('vendor-expenses-unpaid-edit');
Route::post('/vendor-expenses-unpaid-store',[VendorExpensesController::class,'unpaid_store'])->name('vendor-expenses-unpaid-store');
Route::get('/vendor-expenses-advance-history',[VendorExpensesController::class,'advance_expenses'])->name('vendor-expenses-advance-history');
Route::get('/vendor-advanceform/{id}', [VendorExpensesController::class, 'advance_form'])->name('vendor-advanceform');
Route::post('/vendor-advance-store',[VendorExpensesController::class,'advance_store'])->name('vendor-advance.store');
Route::get('/vendor_project_amount', [VendorExpensesController::class, 'vendor_project_amount'])->name('vendor_project_amount');
Route::get('/vendor-expenses-pdf',[VendorExpensesController::class,'vendor_expense_pdf'])->name('vendor-expenses-pdf');
Route::get('/vendor-expenses-export',[VendorExpensesController::class,'vendor_expense_export'])->name('vendor-expenses-export');
Route::get('/vendor-delete-expenses-pdf',[VendorExpensesController::class,'vendor_delete_expense_pdf'])->name('vendor-delete-expenses-pdf');
Route::get('/vendor-delete-expenses-export',[VendorExpensesController::class,'vendor_delete_expense_export'])->name('vendor-delete-expenses-export');
Route::get('/vendor-unpaid-expenses-export',[VendorExpensesController::class,'unpaid_expenses_export'])->name('vendor-unpaid-expenses-export');
Route::get('/vendor-unpaid-expenses-pdf',[VendorExpensesController::class,'unpaid_expenses_pdf'])->name('vendor-unpaid-expenses-pdf');
Route::get('vendor/withdraw',[VendorExpensesController::class,'withdraw'])->name('vendor-withdraw');
Route::post('vendor/withdraw/save',[VendorExpensesController::class,'withdraw_save'])->name('vendor.withdraw-save');

Route::get('userdetails',[TransferController::class,'userDetail'])->name('transfer.userdetail');
Route::get('vendor-insufficent',[VendorExpensesController::class,'vendor_insufficant'])->name('vendor-insufficent');
Route::get('/vendorhistory/{id}',[VendorExpensesController::class,'vendor_history'])->name('vendor.vendor-history');
Route::resource('maincategory',MainCategoryController::class);
Route::post('changepassword/{id}',[UserController::class,'change_password'])->name('admin.changepassword');
Route::get('status-update',[UserController::class,'changestatus'])->name('admin.changestatus');
Route::get('main-status-update',[MainCategoryController::class,'update_status'])->name('admin.main.status');
//vendor ended
});

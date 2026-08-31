<?php
namespace App\Http\Controllers;
use App\Services\Excel\ExcelImportService;
use App\Services\Excel\Imports\ClientImport;
use App\Services\Excel\Imports\EmployeeImport;
use App\Services\Excel\Imports\ProjectImport;
use App\Services\Excel\Imports\VendorImport;
use App\Services\Excel\Imports\LabourImport;
use App\Services\Excel\Imports\LabourRoleImport;
use App\Services\Excel\Imports\MainCategoryImport;
use App\Services\Excel\Imports\CategoryImport;
use App\Services\Excel\Imports\UnitImport;
use App\Services\Excel\Imports\PaymentMethodImport;
use App\Services\Excel\Imports\PaymentStageImport;
use App\Services\Excel\Imports\TaskImport;
use App\Services\Excel\Imports\ToolMaterialImport;
use Illuminate\Http\Request;
class ExcelImportController extends Controller
{
    public function clients() { return view('pages.excel.import', ['module'=>'clients','title'=>'Client Import','action'=>route('clients.import')]); }
    public function importClients(Request $request, ExcelImportService $service)
    {
        $request->validate(['file'=>['required','file','mimes:xlsx,xls,csv','max:10240']]);
        $file = $request->file('file'); $path = $file->getRealPath(); $result = $service->run($path, $file->getClientOriginalName(), (int)auth()->id(), new ClientImport());
        return back()->with($result->status === 'failed' ? 'error' : 'success', "Import {$result->status}: {$result->imported_rows} imported, {$result->failed_rows} failed.");
    }

    public function employees() { return view('pages.excel.import', ['module'=>'employees','title'=>'Employee Import','action'=>route('employees.import')]); }
    public function importEmployees(Request $request, ExcelImportService $service)
    {
        $request->validate(['file'=>['required','file','mimes:xlsx,xls,csv','max:10240']]);
        $file = $request->file('file');
        $result = $service->run($file->getRealPath(), $file->getClientOriginalName(), (int) auth()->id(), new EmployeeImport());
        return back()->with($result->status === 'failed' ? 'error' : 'success', "Import {$result->status}: {$result->imported_rows} imported, {$result->failed_rows} failed.");
    }

    public function projects() { return view('pages.excel.import', ['module'=>'projects','title'=>'Project Import','action'=>route('projects.import')]); }
    public function importProjects(Request $request, ExcelImportService $service)
    {
        $request->validate(['file'=>['required','file','mimes:xlsx,xls,csv','max:10240']]);
        $file = $request->file('file'); $result = $service->run($file->getRealPath(), $file->getClientOriginalName(), (int) auth()->id(), new ProjectImport());
        return back()->with($result->status === 'failed' ? 'error' : 'success', "Import {$result->status}: {$result->imported_rows} imported, {$result->failed_rows} failed.");
    }

    public function vendors() { return view('pages.excel.import', ['module'=>'vendors','title'=>'Vendor Import','action'=>route('vendors.import')]); }
    public function importVendors(Request $request, ExcelImportService $service)
    {
        $request->validate(['file'=>['required','file','mimes:xlsx,xls,csv','max:10240']]);
        $file = $request->file('file'); $result = $service->run($file->getRealPath(), $file->getClientOriginalName(), (int) auth()->id(), new VendorImport());
        return back()->with($result->status === 'failed' ? 'error' : 'success', "Import {$result->status}: {$result->imported_rows} imported, {$result->failed_rows} failed.");
    }

    public function labours() { return view('pages.excel.import', ['module'=>'labours','title'=>'Labour Import','action'=>route('labours.import')]); }
    public function importLabours(Request $request, ExcelImportService $service)
    {
        $request->validate(['file'=>['required','file','mimes:xlsx,xls,csv','max:10240']]);
        $file = $request->file('file'); $result = $service->run($file->getRealPath(), $file->getClientOriginalName(), (int) auth()->id(), new LabourImport());
        return back()->with($result->status === 'failed' ? 'error' : 'success', "Import {$result->status}: {$result->imported_rows} imported, {$result->failed_rows} failed.");
    }

    public function labourRoles() { return view('pages.excel.import', ['module'=>'labour_roles','title'=>'Labour Role Import','action'=>route('labour_roles.import')]); }
    public function importLabourRoles(Request $request, ExcelImportService $service)
    {
        $request->validate(['file'=>['required','file','mimes:xlsx,xls,csv','max:10240']]);
        $file = $request->file('file'); $result = $service->run($file->getRealPath(), $file->getClientOriginalName(), (int) auth()->id(), new LabourRoleImport());
        return back()->with($result->status === 'failed' ? 'error' : 'success', "Import {$result->status}: {$result->imported_rows} imported, {$result->failed_rows} failed.");
    }

    public function mainCategories() { return view('pages.excel.import', ['module'=>'main_categories','title'=>'Main Category Import','action'=>route('main_categories.import')]); }
    public function importMainCategories(Request $request, ExcelImportService $service)
    {
        $request->validate(['file'=>['required','file','mimes:xlsx,xls,csv','max:10240']]);
        $file = $request->file('file'); $result = $service->run($file->getRealPath(), $file->getClientOriginalName(), (int) auth()->id(), new MainCategoryImport());
        return back()->with($result->status === 'failed' ? 'error' : 'success', "Import {$result->status}: {$result->imported_rows} imported, {$result->failed_rows} failed.");
    }

    public function categories() { return view('pages.excel.import', ['module'=>'categories','title'=>'Category Import','action'=>route('categories.import')]); }
    public function importCategories(Request $request, ExcelImportService $service)
    {
        $request->validate(['file'=>['required','file','mimes:xlsx,xls,csv','max:10240']]);
        $file = $request->file('file'); $result = $service->run($file->getRealPath(), $file->getClientOriginalName(), (int) auth()->id(), new CategoryImport());
        return back()->with($result->status === 'failed' ? 'error' : 'success', "Import {$result->status}: {$result->imported_rows} imported, {$result->failed_rows} failed.");
    }

    public function units() { return view('pages.excel.import', ['module'=>'units','title'=>'Unit Import','action'=>route('units.import')]); }
    public function importUnits(Request $request, ExcelImportService $service)
    {
        $request->validate(['file'=>['required','file','mimes:xlsx,xls,csv','max:10240']]);
        $file = $request->file('file'); $result = $service->run($file->getRealPath(), $file->getClientOriginalName(), (int) auth()->id(), new UnitImport());
        return back()->with($result->status === 'failed' ? 'error' : 'success', "Import {$result->status}: {$result->imported_rows} imported, {$result->failed_rows} failed.");
    }

    public function paymentMethods() { return view('pages.excel.import', ['module'=>'payment_methods','title'=>'Payment Method Import','action'=>route('payment-methods.import')]); }
    public function importPaymentMethods(Request $request, ExcelImportService $service)
    {
        $request->validate(['file'=>['required','file','mimes:xlsx,xls,csv','max:10240']]);
        $file = $request->file('file'); $result = $service->run($file->getRealPath(), $file->getClientOriginalName(), (int) auth()->id(), new PaymentMethodImport());
        return back()->with($result->status === 'failed' ? 'error' : 'success', "Import {$result->status}: {$result->imported_rows} imported, {$result->failed_rows} failed.");
    }
    private function import(Request $request, ExcelImportService $service, object $definition) { $request->validate(['file'=>['required','file','mimes:xlsx,xls,csv','max:10240']]); $file=$request->file('file'); $result=$service->run($file->getRealPath(),$file->getClientOriginalName(),(int)auth()->id(),$definition); return back()->with($result->status==='failed'?'error':'success',"Import {$result->status}: {$result->imported_rows} imported, {$result->failed_rows} failed."); }
    public function paymentStages(){return view('pages.excel.import',['module'=>'payment_stages','title'=>'Payment Stage Import','action'=>route('payment-stages.import')]);}
    public function importPaymentStages(Request $r,ExcelImportService $s){return $this->import($r,$s,new PaymentStageImport());}
    public function tasks(){return view('pages.excel.import',['module'=>'tasks','title'=>'Task Import','action'=>route('tasks.import')]);}
    public function importTasks(Request $r,ExcelImportService $s){return $this->import($r,$s,new TaskImport());}
    public function toolsMaterials(){return view('pages.excel.import',['module'=>'tools_materials','title'=>'Tools / Materials Import','action'=>route('tools-materials.import')]);}
    public function importToolsMaterials(Request $r,ExcelImportService $s){return $this->import($r,$s,new ToolMaterialImport());}
}

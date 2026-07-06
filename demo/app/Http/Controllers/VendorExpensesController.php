<?php

namespace App\Http\Controllers;

use App\Exports\VendorDeleteExpensesExport;
use App\Exports\VendorExpensesExport;
use App\Exports\VendorUnpaidExpensesExport;
use App\Http\Controllers\Controller;
use App\Models\AdvanceHistory;
use App\Models\Category;
use App\Models\Expenses;
use App\Models\ExpensesUnpaidDate;
use App\Models\MainCategory;
use App\Models\Payment;
use App\Models\ProjectDetails;
use App\Models\Transfer;
use App\Models\User;
use PDF;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\ValidationException;
use App\Services\ExpenseLedgerService;
use App\Support\ExpenseAmounts;

class VendorExpensesController extends Controller
{
  public function index(Request $request)
  {
    //dd($request->search);
    $from = null;
    $to = null;

    if (!empty($request->date_range)) {
      [$from, $to] = array_map('trim', explode('-', $request->date_range));

      $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
      $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }

    $paginate = $request->paginate ?? 10;
    $auth = Auth::user()->id;

    $role = DB::table('model_has_roles')
      ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
      ->where('model_has_roles.model_id', $auth)
      ->pluck('roles.id')
      ->first();

    $expenses = Expenses::whereNotNull('expenses.vendor_id')
      ->leftJoin('category', 'category.id', '=', 'expenses.category_id')
      ->leftJoin('vendor_details as l', 'l.id', '=', 'expenses.vendor_id')
      ->leftJoin('project_details', function ($join) {
        $join->on('project_details.id', '=', 'expenses.project_id')
          ->whereNotNull('expenses.project_id');
      })
      ->leftjoin('main_category','main_category.id','=','expenses.main_category_id')
      ->leftJoin('payment', 'payment.id', '=', 'expenses.payment_mode')
      ->leftJoin('users', 'users.id', '=', 'expenses.editedBy')
      ->leftJoin('users as users_add', 'users_add.id', '=', 'expenses.user_id')
      ->leftJoin('users as labour_ad', 'labour_ad.id', '=', 'expenses.is_advance')
      ->where(['category.active_status' => 1, 'category.delete_status' => 0])
      ->select(
        'expenses.*',
        'category.name as category_name',
        'project_details.name as project_name',
        'payment.name as payment_name',
        'users.first_name',
        'users.last_name',
        'users_add.first_name as first',
        'users_add.last_name as last',
        'l.name as vendor_name',
        'labour_ad.first_name as labour_first',
        'labour_ad.last_name as labour_last',
        'main_category.name as main_category_name'
      )
      ->when($from, function ($query, $from) {
        $query->whereDate('expenses.current_date', '>=', $from);
      })
      ->when($to, function ($query, $to) {
        $query->whereDate('expenses.current_date', '<=', $to);
      })
      ->when(request('main_category_id'), function($query,$main_category_id){
        $query->where('expenses.main_category_id',$main_category_id);
      })
      ->when(request('category_id'), function ($query, $category_id) {
        $query->where('expenses.category_id', $category_id);
      })
      ->when(request('project_id'), function ($query, $project_id) {
        $query->where('expenses.project_id', $project_id);
      })
      ->when(request('user_id'), function ($query, $user_id) {
        $query->where('expenses.vendor_id', $user_id);
      })
      ->when(request('search'), function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('category.name', 'like', "%$search%")
            ->orWhere('project_details.name', 'like', "%$search%")
            ->orWhere('payment.name', 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(users.first_name, ' ', users.last_name)"), 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(users_add.first_name, ' ', users_add.last_name)"), 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(labour_ad.first_name, ' ', labour_ad.last_name)"), 'like', "%$search%")
            ->orWhere('users.first_name', 'like', "%$search%")
            ->orWhere('users.last_name', 'like', "%$search%")
            ->orWhere('users_add.first_name', 'like', "%$search%")
            ->orWhere('users_add.last_name', 'like', "%$search%")
            ->orWhere('l.name', 'like', "%$search%")
            ->orWhere('labour_ad.first_name', 'like', "%$search%")
            ->orWhere('labour_ad.last_name', 'like', "%$search%")
            ->orWhere('expenses.amount', 'like', "%$search%")
            ->orWhere('expenses.paid_amt', 'like', "%$search%")
            ->orWhere('expenses.unpaid_amt', 'like', "%$search%")
            ->orWhere('expenses.extra_amt', 'like', "%$search%")
            ->orWhere('main_category.name','like',"%$search%")
            ->orWhere('expenses.description', 'like', "%$search%");
        });
      });


    $totals = ExpenseLedgerService::totals($expenses);
    if ($from && $to) {
      $expenses = $expenses->orderBy('expenses.current_date', 'desc')->paginate($paginate)->withQueryString();
    } else {
      $expenses = $expenses->orderBy('expenses.id', 'desc')->paginate($paginate)->withQueryString();
    }



    $category = Category::where(['active_status' => 1, 'delete_status' => 0])->get();
    $project = ProjectDetails::where(['active_status' => 1, 'delete_status' => 0])->get();
    $user = Vendor::get();
    $main_category = MainCategory::where('status',1)->latest()->get();
    $sum = $totals->total_amount;
    $paid_amt = $totals->total_paid;
    $unpaid_amt = $totals->total_unpaid;
    $advanced_amt = $totals->total_extra;
    //dd($advanced_amt);

    return view('vendor-expenses.index', ['expenses' => $expenses, 'category' => $category,  'project' => $project, 'user' => $user,  'sum' => $sum, 'paid_amt' => $paid_amt, 'unpaid_amt' => $unpaid_amt, 'amount' => $request->amount, 'advanced_amt' => $advanced_amt,'main_category' =>$main_category]);
  }
  public function create(Request $request)
  {
    $category = Category::where(['active_status' => 1, 'delete_status' => 0])->get();
    $payment = Payment::where(['active_status' => 1, 'delete_status' => 0])->get();
    $project = ProjectDetails::where(['active_status' => 1, 'delete_status' => 0, "project_status" => 0])->get();
    $vendors = Vendor::get();
    $main_category = MainCategory::where('status',1)->latest()->get();
    return view('vendor-expenses.create', ['category' => $category, 'project' => $project, 'payment' => $payment, 'vendors' => $vendors,'main_category' =>$main_category]);
  }
  public function store(Request $request)
  {
    $validated = $request->validate([
      'main_category_id' => ['required', 'exists:main_category,id'],
      'category_id' => ['required', 'exists:category,id'],
      'project_id' => ['required', 'exists:project_details,id'],
      'vendor_id' => ['required', 'exists:vendor_details,id'],
      'amount' => ['required', 'numeric', 'gt:0'],
      'paid_amt' => ['nullable', 'numeric', 'min:0'],
      'payment_mode' => ['required', 'exists:payment,id'],
      'description' => ['nullable', 'string', 'max:2000'],
      'current_date' => ['required', 'date'],
      'time' => ['required'],
      'image' => ['nullable', 'image', 'max:5120'],
    ]);
    $amount = (float) $validated['amount'];
    $paidAmount = (float) ($validated['paid_amt'] ?? 0);
    $input = $validated;
    unset($input['time']);
    $input['user_id'] = Auth::id();
    $input = array_merge($input, ExpenseAmounts::calculate($amount, $paidAmount));
    $input['current_date'] = $validated['current_date'] . ' ' . $validated['time'];
    if ($image = $request->file('image')) {
      $destinationPath = public_Path('images');
      $profileImage = uniqid('vendor_expense_', true) . "." . $image->getClientOriginalExtension();
      $image->move($destinationPath, $profileImage);
      $input['image'] = $profileImage;
    }
    DB::transaction(function () use ($input, $paidAmount, $validated) {
      $vendor = Vendor::whereKey($validated['vendor_id'])->lockForUpdate()->firstOrFail();
      if ((float) $vendor->advance_amt < $paidAmount) {
        throw ValidationException::withMessages(['paid_amt' => 'Insufficient vendor advance balance.']);
      }
      Expenses::create($input);
      $vendor->advance_amt = (float) $vendor->advance_amt - $paidAmount;
      $vendor->save();
    });
    return redirect()->route('vendor-expenses-create')
      ->with('expenses-popup', 'Vendor Expenses Added Successfully');
  }
  public function vendor_salary(Request $request)
  {
    $labour = Vendor::where('id', $request->id)->first();
    return response()->json($labour);
  }
  public function edit(Request $request)
  {
    $expense = Expenses::leftjoin('vendor_details', 'vendor_details.id', '=', 'expenses.vendor_id')->leftjoin('users', 'users.id', '=', 'expenses.user_id')->where('expenses.id', '=', $request->id)->select('expenses.*', 'users.wallet', 'vendor_details.advance_amt')->first();
    $category = Category::where(['active_status' => 1, 'delete_status' => 0])->get();
    $payment = Payment::where(['active_status' => 1, 'delete_status' => 0])->get();
    $project = ProjectDetails::where(['active_status' => 1, 'delete_status' => 0, "project_status" => 0])->get();
    $datetime = explode(' ', $expense->current_date);
    $current_date = $datetime[0];
    $current_time = $datetime[1];
    $vendor = Vendor::latest()->get();
    $main_category = MainCategory::where('status',1)->latest()->get();
    return view('vendor-expenses.edit', ['expense' => $expense, 'category' => $category, 'project' => $project, 'payment' => $payment, 'current_date' => $current_date, 'current_time' => $current_time, 'vendors' => $vendor,'main_category' => $main_category]);
  }
  public function update(Request $request, ExpenseLedgerService $ledger)
  {
    $validated = $request->validate([
      'id' => ['required', 'exists:expenses,id'],
      'main_category_id' => ['required', 'exists:main_category,id'],
      'category_id' => ['required', 'exists:category,id'],
      'project_id' => ['required', 'exists:project_details,id'],
      'amount' => ['required', 'numeric', 'gt:0'],
      'paid_amt' => ['required', 'numeric', 'min:0'],
      'payment_mode' => ['required', 'exists:payment,id'],
      'description' => ['nullable', 'string', 'max:2000'],
      'current_date' => ['required', 'date'],
      'time' => ['required'],
      'image' => ['nullable', 'image', 'max:5120'],
    ]);
    $expenseId = (int) $validated['id'];
    unset($validated['id'], $validated['time'], $validated['image']);
    $validated['current_date'] = $request->current_date . ' ' . $request->time;
    if ($image = $request->file('image')) {
      $profileImage = uniqid('vendor_expense_', true) . '.' . $image->getClientOriginalExtension();
      $image->move(public_path('images'), $profileImage);
      $validated['image'] = $profileImage;
    }
    $ledger->update($expenseId, $validated);
    return redirect()->route('vendor-expenses-index')
      ->with('expenses-popup', 'Vendor Detail Updated Successfully');
  }
  public function vendordelete(Request $request, ExpenseLedgerService $ledger)
  {
    $validated = $request->validate([
      'id' => ['required', 'exists:expenses,id'],
      'reason' => ['required', 'string', 'max:1000'],
    ]);
    $ledger->delete((int) $validated['id'], $validated['reason']);
    return redirect()->route('vendor-expenses-index')
      ->with('expenses-popup', 'Vendor Detail Deleted Successfully');
  }
  public function delete_record(Request $request)
  {
    $from = null;
    $to = null;

    if (!empty($request->date_range)) {
      [$from, $to] = array_map('trim', explode('-', $request->date_range));

      $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
      $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }

    $paginate = $request->paginate ?? 10;
    $auth = Auth::user()->id;

    $role = DB::table('model_has_roles')
      ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
      ->where('model_has_roles.model_id', $auth)
      ->pluck('roles.id')
      ->first();

    $expenses = Expenses::join('category', 'category.id', '=', 'expenses.category_id')
      ->whereNotNull('expenses.vendor_id')->leftjoin('vendor_details as l', 'l.id', '=', 'expenses.vendor_id')->leftJoin('project_details', function ($join) {
        $join->on('project_details.id', 'expenses.project_id')
          ->where('expenses.project_id', '!=', null);
      })
      ->leftjoin('main_category','main_category.id','=','expenses.main_category_id')
      ->leftjoin('payment', 'payment.id', '=', 'expenses.payment_mode')
      ->where(['category.active_status' => 1, 'category.delete_status' => 0])
      ->leftjoin('users', 'users.id', '=', 'expenses.editedBy')
      ->leftjoin('users as users_add', 'users_add.id', '=', 'expenses.user_id')
      ->leftjoin('users as labour_ad', 'labour_ad.id', '=', 'expenses.is_advance')
      ->select('expenses.*', 'category.name as category_name', 'project_details.name as project_name', 'payment.name as payment_name', 'users.first_name', 'users.last_name', 'users_add.first_name as first', 'users_add.last_name as last', 'l.name as labour_name', 'labour_ad.first_name as labour_first', 'labour_ad.last_name as labour_last','main_category.name as main_category_name')
      ->when($from,function($query,$from){
        $query->wheredate('current_date', '>=', $from);
      })
      ->when($to,function($query,$to){
        $query->wheredate('current_date', '<=', $to);
      })
      ->when(request('main_category_id'),function($query,$main_category){
        $query->where('expenses.main_category_id',$main_category);
      })
      ->when(request('category_id'),function($query,$category_id){
        $query->where('expenses.category_id', $category_id);
      })
      ->when(request('project_id'),function($query,$project_id){
        $query->where('expenses.project_id', $project_id);
      })
      ->when(request('user_id'),function($query,$user_id){
        $query->where('expenses.vendor_id', $user_id);
      })
      ->when(request('search'),function($query,$search){
             $query->where(function ($q) use ($search) {
          $q->where('category.name', 'like', "%$search%")
            ->orWhere('project_details.name', 'like', "%$search%")
            ->orWhere('payment.name', 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(users.first_name, ' ', users.last_name)"), 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(users_add.first_name, ' ', users_add.last_name)"), 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(labour_ad.first_name, ' ', labour_ad.last_name)"), 'like', "%$search%")
            ->orWhere('users.first_name', 'like', "%$search%")
            ->orWhere('users.last_name', 'like', "%$search%")
            ->orWhere('users_add.first_name', 'like', "%$search%")
            ->orWhere('users_add.last_name', 'like', "%$search%")
            ->orWhere('l.name', 'like', "%$search%")
            ->orWhere('labour_ad.first_name', 'like', "%$search%")
            ->orWhere('labour_ad.last_name', 'like', "%$search%")
            ->orWhere('expenses.amount', 'like', "%$search%")
            ->orWhere('expenses.paid_amt', 'like', "%$search%")
            ->orWhere('expenses.unpaid_amt', 'like', "%$search%")
            ->orWhere('expenses.extra_amt', 'like', "%$search%")
            ->orWhere('expenses.reason','like',"%$search%")
            ->orWhere('main_category.name','like',"%$search%")
            ->orWhere('expenses.description', 'like', "%$search%");
        });
      });
    $totals = ExpenseLedgerService::totals((clone $expenses)->onlyTrashed());
    if ($from != '' && $to != '') {
      $expenses = $expenses->onlyTrashed()->orderBy('expenses.current_date', 'desc')->paginate($paginate)->withQueryString();
    } else {
      $expenses = $expenses->onlyTrashed()->orderBy('expenses.id', 'desc')->paginate($paginate)->withQueryString();
    }
  
    $category = Category::where(['active_status' => 1, 'delete_status' => 0])->get();
    $project = ProjectDetails::where(['active_status' => 1, 'delete_status' => 0])->get();
    $user = Vendor::get();

    $sum = $totals->total_amount;
    $paid_amt = $totals->total_paid;
    $unpaid_amt = $totals->total_unpaid;
    $advanced_amt = $totals->total_extra;
    $main_category = MainCategory::where('status',1)->latest()->get();
    return view('vendor-expenses.vendordeletedrecord', ['expenses' => $expenses, 'category' => $category,  'project' => $project, 'user' => $user,  'sum' => $sum, 'paid_amt' => $paid_amt, 'unpaid_amt' => $unpaid_amt, 'amount' => $request->amount, 'advanced_amt' => $advanced_amt,'main_category' => $main_category]);
  }
  public function unpaid_expenses(Request $request)
  {
    $from = null;
    $to = null;

    if (!empty($request->date_range)) {
      [$from, $to] = array_map('trim', explode('-', $request->date_range));

      $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
      $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }

    $paginate = $request->paginate ?? 10;
    $auth = Auth::user()->id;

    $role = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->join('users', 'users.id', '=', 'model_has_roles.model_id')->where('users.id', $auth)->pluck('roles.id')->first();

    $expenses = Expenses::whereNotNull('vendor_id')->where('expenses.unpaid_amt', '!=', 0)->leftjoin('vendor_details as l', 'l.id', '=', 'expenses.vendor_id')->leftjoin('category', 'category.id', '=', 'expenses.category_id')
      ->leftJoin('project_details', function ($join) {
        $join->on('project_details.id', 'expenses.project_id')
          ->where('expenses.project_id', '!=', null);
      })
      ->leftjoin('main_category','main_category.id','=','expenses.main_category_id')
      ->leftjoin('payment', 'payment.id', '=', 'expenses.payment_mode')
      ->leftjoin('users', 'users.id', '=', 'expenses.editedBy')
      ->leftjoin('users as users_add', 'users_add.id', '=', 'expenses.user_id')
      ->leftjoin('users as labour_ad', 'labour_ad.id', '=', 'expenses.is_advance')
      ->where(['category.active_status' => 1, 'category.delete_status' => 0])
      ->select('expenses.*', 'category.name as category_name', 'project_details.name as project_name', 'payment.name as payment_name', 'users.first_name', 'users.last_name', 'users_add.first_name as first', 'users_add.last_name as last', 'l.name as vendor_name', 'labour_ad.first_name as labour_first', 'labour_ad.last_name as labour_last','main_category.name as main_category_name')
      ->when($from,function($query,$from){
        $query->wheredate('current_date', '>=', $from);
      })
      ->when($to,function($query,$to){
        $query->wheredate('current_date', '<=', $to);
      })
      ->when(request('main_category_id'),function($query,$main_category){
        $query->where('expenses.main_category_id',$main_category);
      })
      ->when(request('category_id'),function($query,$category_id){
        $query->where('expenses.category_id', $category_id);
      })
      ->when(request('project_id'),function($query,$project_id){
        $query->where('expenses.project_id', $project_id);
      })
      ->when(request('user_id'),function($query,$user_id){
        $query->where('expenses.vendor_id', $user_id);
      })
     ->when($request->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('category.name', 'like', "%$search%")
            ->orWhere('project_details.name', 'like', "%$search%")
            ->orWhere('payment.name', 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(users.first_name, ' ', users.last_name)"), 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(users_add.first_name, ' ', users_add.last_name)"), 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(labour_ad.first_name, ' ', labour_ad.last_name)"), 'like', "%$search%")
            ->orWhere('users.first_name', 'like', "%$search%")
            ->orWhere('users.last_name', 'like', "%$search%")
            ->orWhere('users_add.first_name', 'like', "%$search%")
            ->orWhere('users_add.last_name', 'like', "%$search%")
            ->orWhere('l.name', 'like', "%$search%")
            ->orWhere('labour_ad.first_name', 'like', "%$search%")
            ->orWhere('labour_ad.last_name', 'like', "%$search%")
            ->orWhere('expenses.amount', 'like', "%$search%")
            ->orWhere('expenses.paid_amt', 'like', "%$search%")
            ->orWhere('expenses.unpaid_amt', 'like', "%$search%")
            ->orWhere('expenses.extra_amt', 'like', "%$search%")
            ->orWhere('main_category.name', 'like', "%$search%")
            ->orWhere('expenses.description', 'like', "%$search%");
        });
      });


    $totals = ExpenseLedgerService::totals($expenses);
    if ($from && $to) {
      $expenses = $expenses->orderBy('expenses.current_date', 'desc')->paginate($paginate)->withQueryString();
    } else {
      $expenses = $expenses->orderBy('expenses.id', 'desc')->paginate($paginate)->withQueryString();
    }
    $category = Category::where(['active_status' => 1, 'delete_status' => 0])->get();
    $project = ProjectDetails::where(['active_status' => 1, 'delete_status' => 0])->get();
    $user = Vendor::get();
    $main_category = MainCategory::where('status',1)->latest()->get();
    $sum = $totals->total_amount;
    $paid_amt = $totals->total_paid;
    $unpaid_amt = $totals->total_unpaid;
    $advanced_amt = $totals->total_extra;

    return view('vendor-expenses.unpaidexpenses', ['expenses' => $expenses, 'category' => $category, 'project' => $project, 'user' => $user,  'sum' => $sum, 'paid_amt' => $paid_amt, 'unpaid_amt' => $unpaid_amt, 'amount' => $request->amount, 'advanced_amt' => $advanced_amt,'main_category' =>$main_category]);
  }
  public function unpaid_edit(Request $request)
  {
    $unpaid = Expenses::where('id', $request->id)->first();
    $datetime = explode(' ', $unpaid->current_date);
    $current_date = $datetime[0];
    $current_time = $datetime[1];
    return view('vendor-expenses.unpaidform', ['unpaid' => $unpaid, 'current_date' => $current_date, 'current_time' => $current_time]);
  }
  public function unpaid_store(Request $request, ExpenseLedgerService $ledger)
  {
    $validated = $request->validate([
      'expense_id' => ['required', 'exists:expenses,id'],
      'unpaid_amt' => ['required', 'numeric', 'gt:0'],
      'current_date' => ['required', 'date'],
      'time' => ['required'],
    ]);
    $ledger->settle(
      (int) $validated['expense_id'],
      (float) $validated['unpaid_amt'],
      $validated['current_date'] . ' ' . $validated['time']
    );
    return redirect()->route('vendor-expenses-unpaid-history')
      ->with('expenses-popup', 'Vendor Unpaid Amount Updated Successfully');
  }
  public function advance_expenses(Request $request)
  {
    $paginate = $request->paginate ?? 15;

    $users = Vendor::when(request('search'), function ($query, $search) {
      $query->where('name', 'like', "%$search%")
        ->orWhere('phone', 'like', "%$search%")
        ->orWhere('address', 'like', "%$search%")
        ->orWhere('advance_amt', 'like', "%$search%");
    })->latest()->paginate($paginate);
    return view('vendor-expenses.advanceexpense', ['users' => $users]);
  }
  public function advance_form($id)
  {
    $labour = Vendor::find($id);
    $project = Expenses::where('vendor_id', $id)->where(function ($query) {
      $query->where('extra_amt', '>', 0)
        ->orWhere('unpaid_amt', '>', 0);
    })->leftjoin('project_details', 'project_details.id', '=', 'expenses.project_id')->select('project_details.*')->groupBy('project_details.id')->get();
    return view('vendor-expenses.advanceform', ['labour' => $labour, 'project' => $project]);
  }
  public function advance_store(Request $request, ExpenseLedgerService $ledger)
  {
    $validated = $request->validate([
      'labour_id' => ['required', 'exists:vendor_details,id'],
      'project_id' => ['required', 'exists:project_details,id'],
      'extra_amt' => ['required', 'numeric', 'gt:0'],
      'gender' => ['required', 'in:1,2'],
    ]);
    $ledger->adjustAdvance(
      'vendor',
      (int) $validated['labour_id'],
      (int) $validated['project_id'],
      (float) $validated['extra_amt'],
      (int) $validated['gender'] === 2
    );
    return redirect()->route('vendor-expenses-advance-history')->with('popup', 'open');
  }
  public function vendor_project_amount(Request $request)
  {
    $amount =  Expenses::where('vendor_id', $request->labour_id)->where('project_id', $request->project_id)->get();
    // dd($amount);
    $advance = $amount->sum('extra_amt');
    $unpaid_amt = $amount->sum('unpaid_amt');
    // dd($amount);
    return response()->json(['advance' => $advance, 'unpaid_amt' => $unpaid_amt]);
  }
  public function vendor_expense_pdf(Request $request)
  {
    $category_filter = $request->category_id;
    $project_filter = $request->project_id;
    $user_filter = $request->user_id;
    $from = null;
    $to = null;

    if (!empty($request->date_range)) {
      [$from, $to] = array_map('trim', explode('-', $request->date_range));

      $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
      $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }




    $auth = Auth::user()->id;
    $role = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->join('users', 'users.id', '=', 'model_has_roles.model_id')->where('users.id', $auth)->pluck('roles.id')->first();

    $expenses = Expenses::whereNotNull('expenses.vendor_id')->leftjoin('category', 'category.id', '=', 'expenses.category_id')->leftjoin('vendor_details as l', 'l.id', '=', 'expenses.vendor_id')
      ->leftJoin('project_details', function ($join) {
        $join->on('project_details.id', 'expenses.project_id')
          ->where('expenses.project_id', '!=', null);
      })
      ->leftjoin('main_category','main_category.id','=','expenses.main_category_id')
      ->leftjoin('payment', 'payment.id', '=', 'expenses.payment_mode')
      ->where(['category.active_status' => 1, 'category.delete_status' => 0])
      ->leftjoin('users', 'users.id', '=', 'expenses.editedBy')
      ->leftjoin('users as users_add', 'users_add.id', '=', 'expenses.user_id')
      ->leftjoin('users as labour_ad', 'labour_ad.id', '=', 'expenses.is_advance')
      ->select(
        'expenses.*',
        'category.name as category_name',
        'project_details.name as project_name',
        'payment.name as payment_name',
        'users.first_name',
        'users.last_name',
        'users_add.first_name as first',
        'users_add.last_name as last',
        'l.name as vendor_name',
        'labour_ad.first_name as labour_first',
        'labour_ad.last_name as labour_last',
        'main_category.name as main_category_name'
      )
      ->when($from, function ($query, $from) {
        $query->wheredate('current_date', '>=', $from);
      })
      ->when($to, function ($query, $to) {
        $query->wheredate('current_date', '<=', $to);
      })
      ->when(request('main_category_id'), function($query,$main_category_id){
        $query->where('expenses.main_category_id',$main_category_id);
      })
      ->when($category_filter, function ($query, $category_filter) {
        $query->where('expenses.category_id', $category_filter);
      })
      ->when($project_filter, function ($query, $project_filter) {
        $query->where('expenses.project_id', $project_filter);
        //dd($expenses);exit;
      })
      ->when($user_filter, function ($query, $user_filter) {
        $query->where('expenses.vendor_id', $user_filter);
      })->when($request->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('category.name', 'like', "%$search%")
            ->orWhere('project_details.name', 'like', "%$search%")
            ->orWhere('payment.name', 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(users.first_name, ' ', users.last_name)"), 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(users_add.first_name, ' ', users_add.last_name)"), 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(labour_ad.first_name, ' ', labour_ad.last_name)"), 'like', "%$search%")
            ->orWhere('users.first_name', 'like', "%$search%")
            ->orWhere('users.last_name', 'like', "%$search%")
            ->orWhere('users_add.first_name', 'like', "%$search%")
            ->orWhere('users_add.last_name', 'like', "%$search%")
            ->orWhere('l.name', 'like', "%$search%")
            ->orWhere('labour_ad.first_name', 'like', "%$search%")
            ->orWhere('labour_ad.last_name', 'like', "%$search%")
            ->orWhere('expenses.amount', 'like', "%$search%")
            ->orWhere('expenses.paid_amt', 'like', "%$search%")
            ->orWhere('expenses.unpaid_amt', 'like', "%$search%")
            ->orWhere('expenses.extra_amt', 'like', "%$search%")
            ->orWhere('main_category.name','like',"%$search%")
            ->orWhere('expenses.description', 'like', "%$search%");
        });
      });

    //dd($expenses);
    if ($from != '' && $to != '') {
      $expenses = $expenses->orderBy('expenses.current_date', 'desc')->get();
    } else {
      $expenses = $expenses->orderBy('expenses.id', 'desc')->get();
    }
    // $expenses = $expenses->orderBy('expenses.id', 'desc')->get();
    $pdf = PDF::loadView('vendor-expenses.vendorpdf', compact('expenses'));

    return $pdf->download('vendor-expenses.pdf');
  }
  public function vendor_expense_export(Request $request)
  {
    //  dd($request->all());
    $category_filter = $request->category_id;
    $project_filter = $request->project_id;
    $user_filter = $request->user_id;
    $search = $request->search;
    $main_category = $request->main_category_id;
    $from = null;
    $to = null;

    if (!empty($request->date_range)) {
      [$from, $to] = array_map('trim', explode('-', $request->date_range));

      $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
      $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }





    $auth = Auth::user()->id;
    $role = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->join('users', 'users.id', '=', 'model_has_roles.model_id')->where('users.id', $auth)->pluck('roles.id')->first();

    return Excel::download((new VendorExpensesExport($category_filter, $project_filter, $user_filter, $from, $to, $auth, $role, $search, $main_category)), 'vendor-expenses.xlsx');
  }
  public function vendor_delete_expense_pdf(Request $request)
  {
    
    $from = null;
    $to = null;

    if (!empty($request->date_range)) {
      [$from, $to] = array_map('trim', explode('-', $request->date_range));

      $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
      $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }




    $auth = Auth::user()->id;
    $role = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->join('users', 'users.id', '=', 'model_has_roles.model_id')->where('users.id', $auth)->pluck('roles.id')->first();

    $expenses = Expenses::join('category', 'category.id', '=', 'expenses.category_id')
      ->whereNotNull('expenses.vendor_id')->leftjoin('vendor_details as l', 'l.id', '=', 'expenses.labour_id')->leftJoin('project_details', function ($join) {
        $join->on('project_details.id', 'expenses.project_id')
          ->where('expenses.project_id', '!=', null);
      })
      ->leftjoin('main_category','main_category.id','=','expenses.main_category_id')
      ->leftjoin('payment', 'payment.id', '=', 'expenses.payment_mode')
      ->where(['category.active_status' => 1, 'category.delete_status' => 0])
      ->leftjoin('users', 'users.id', '=', 'expenses.editedBy')
      ->leftjoin('users as users_add', 'users_add.id', '=', 'expenses.user_id')
      ->leftjoin('users as labour_ad', 'labour_ad.id', '=', 'expenses.is_advance')
      ->select('expenses.*', 'category.name as category_name', 'project_details.name as project_name', 'payment.name as payment_name', 'users.first_name', 'users.last_name', 'users_add.first_name as first', 'users_add.last_name as last', 'l.name as labour_name', 'labour_ad.first_name as labour_first', 'labour_ad.last_name as labour_last','main_category.name as main_category_name')
      ->when($from,function($query,$from){
        $query->wheredate('current_date', '>=', $from);
      })
      ->when($to,function($query,$to){
        $query->wheredate('current_date', '<=', $to);
      })
       ->when(request('main_category_id'),function($query,$main_category_id){
        $query->where('expenses.main_category_id', $main_category_id);
      })
      ->when(request('category_id'),function($query,$category_id){
        $query->where('expenses.category_id', $category_id);
      })
      ->when(request('project_id'),function($query,$project_id){
        $query->where('expenses.project_id', $project_id);
      })
      ->when(request('user_id'),function($query,$user_id){
        $query->where('expenses.vendor_id', $user_id);
      })
      ->when($request->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('category.name', 'like', "%$search%")
            ->orWhere('project_details.name', 'like', "%$search%")
            ->orWhere('payment.name', 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(users.first_name, ' ', users.last_name)"), 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(users_add.first_name, ' ', users_add.last_name)"), 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(labour_ad.first_name, ' ', labour_ad.last_name)"), 'like', "%$search%")
            ->orWhere('users.first_name', 'like', "%$search%")
            ->orWhere('users.last_name', 'like', "%$search%")
            ->orWhere('users_add.first_name', 'like', "%$search%")
            ->orWhere('users_add.last_name', 'like', "%$search%")
            ->orWhere('l.name', 'like', "%$search%")
            ->orWhere('labour_ad.first_name', 'like', "%$search%")
            ->orWhere('labour_ad.last_name', 'like', "%$search%")
            ->orWhere('expenses.amount', 'like', "%$search%")
            ->orWhere('expenses.paid_amt', 'like', "%$search%")
            ->orWhere('expenses.unpaid_amt', 'like', "%$search%")
            ->orWhere('expenses.extra_amt', 'like', "%$search%")
            ->orWhere('expenses.reason','like',"%$search%")
            ->orWhere('main_category.name','like',"%$search%")
            ->orWhere('expenses.description', 'like', "%$search%");
        });
      });

    if ($from != '' && $to != '') {
      $expenses = $expenses->onlyTrashed()->orderBy('expenses.current_date', 'desc')->get();
    } else {
      $expenses = $expenses->onlyTrashed()->orderBy('expenses.id', 'desc')->get();
    }
//dd($expenses);
    // $expenses = $expenses->onlyTrashed()->orderBy('expenses.id', 'desc')->get();
    $pdf = PDF::loadView('vendor-expenses.deletepdf', compact('expenses'));

    return $pdf->download('vendor-delete-expenses.pdf');
  }
  public function vendor_delete_expense_export(Request $request)
  {
    $category_filter = $request->category_id;
    $project_filter = $request->project_id;
    $user_filter = $request->user_id;
    $main_category = $request->main_category_id;
    $search = $request->search;
    $from = null;
    $to = null;

    if (!empty($request->date_range)) {
      [$from, $to] = array_map('trim', explode('-', $request->date_range));

      $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
      $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }




    $auth = Auth::user()->id;
    $role = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->join('users', 'users.id', '=', 'model_has_roles.model_id')->where('users.id', $auth)->pluck('roles.id')->first();

    return Excel::download((new VendorDeleteExpensesExport($category_filter, $project_filter, $user_filter, $from, $to, $auth, $role,$search,$main_category)), 'vendor-delete-expenses.xlsx');
  }
  public function unpaid_expenses_export(Request $request)
  {
    $category_filter = $request->category_id;
    $project_filter = $request->project_id;
    $user_filter = $request->user_id;
    $search = $request->search;
    $main_category = $request->main_category_id;
    $from = null;
    $to = null;

    if (!empty($request->date_range)) {
      [$from, $to] = array_map('trim', explode('-', $request->date_range));

      $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
      $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }




    $auth = Auth::user()->id;
    $role = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->join('users', 'users.id', '=', 'model_has_roles.model_id')->where('users.id', $auth)->pluck('roles.id')->first();
    return Excel::download((new VendorUnpaidExpensesExport($category_filter, $project_filter, $user_filter, $from, $to, $auth, $role,$search,$main_category)), 'vendor-unpaid-expenses.xlsx');
  }
  public function unpaid_expenses_pdf(Request $request)
  {
   $category_filter = $request->category_id;
    $project_filter = $request->project_id;
    $user_filter = $request->user_id;
    $search = $request->search;
    $from = null;
    $to = null;

    if (!empty($request->date_range)) {
      [$from, $to] = array_map('trim', explode('-', $request->date_range));

      $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
      $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }



    $auth = Auth::user()->id;
    $role = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->join('users', 'users.id', '=', 'model_has_roles.model_id')->where('users.id', $auth)->pluck('roles.id')->first();

    $expenses = Expenses::whereNotNull('expenses.vendor_id')->where('expenses.unpaid_amt', '!=', 0)->leftjoin('category', 'category.id', '=', 'expenses.category_id')->leftjoin('vendor_details as l', 'l.id', '=', 'expenses.vendor_id')
      ->leftJoin('project_details', function ($join) {
        $join->on('project_details.id', 'expenses.project_id')
          ->where('expenses.project_id', '!=', null);
      })
      ->leftjoin('main_category','main_category.id','=','expenses.main_category_id')
      ->leftjoin('payment', 'payment.id', '=', 'expenses.payment_mode')
      ->where(['category.active_status' => 1, 'category.delete_status' => 0])
      ->leftjoin('users', 'users.id', '=', 'expenses.editedBy')->leftjoin('users as users_add', 'users_add.id', '=', 'expenses.user_id')->leftjoin('users as labour_ad', 'labour_ad.id', '=', 'expenses.is_advance')
      ->select('expenses.*', 'category.name as category_name', 'project_details.name as project_name', 'payment.name as payment_name', 'users.first_name', 'users.last_name', 'users_add.first_name as first', 'users_add.last_name as last', 'l.name as vendor_name', 'labour_ad.first_name as labour_first', 'labour_ad.last_name as labour_last','main_category.name as main_category_name')
      ->when($from,function($query,$from){
        $query->wheredate('current_date', '>=', $from);
      })
      ->when($to,function($query,$to){
        $query->wheredate('current_date', '<=', $to);
      })
      ->when(request('main_category_id'),function($query,$main_category){
        $query->where('expenses.main_category_id',$main_category);
      })
      ->when(request('category_id'),function($query,$category_id){
        $query->where('expenses.category_id', $category_id);
      })
      ->when(request('project_id'),function($query,$project_id){
        $query->where('expenses.project_id', $project_id);
      })
      ->when(request('user_id'),function($query,$user_id){
        $query->where('expenses.user_id', $user_id);
      })
      ->when(request('search'), function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('category.name', 'like', "%$search%")
            ->orWhere('project_details.name', 'like', "%$search%")
            ->orWhere('payment.name', 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(users.first_name, ' ', users.last_name)"), 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(users_add.first_name, ' ', users_add.last_name)"), 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(labour_ad.first_name, ' ', labour_ad.last_name)"), 'like', "%$search%")
            ->orWhere('users.first_name', 'like', "%$search%")
            ->orWhere('users.last_name', 'like', "%$search%")
            ->orWhere('users_add.first_name', 'like', "%$search%")
            ->orWhere('users_add.last_name', 'like', "%$search%")
            ->orWhere('l.name', 'like', "%$search%")
            ->orWhere('labour_ad.first_name', 'like', "%$search%")
            ->orWhere('labour_ad.last_name', 'like', "%$search%")
            ->orWhere('expenses.amount', 'like', "%$search%")
            ->orWhere('expenses.paid_amt', 'like', "%$search%")
            ->orWhere('expenses.unpaid_amt', 'like', "%$search%")
            ->orWhere('expenses.extra_amt', 'like', "%$search%")
            ->orWhere('main_category.name','like',"%$search%")
            ->orWhere('expenses.description', 'like', "%$search%");
        });
      });
    if ($from != '' && $to != '') {
      $expenses = $expenses->orderBy('expenses.current_date', 'desc')->get();
    } else {
      $expenses = $expenses->orderBy('expenses.id', 'desc')->get();
    }
    // $expenses = $expenses->orderBy('expenses.id', 'desc')->get();
    $pdf = PDF::loadView('vendor-expenses.vendorpdf', compact('expenses'));

    return $pdf->download('vendor-unpaid-expenses.pdf');
  }
  public function vendor_insufficant(Request $request)
  {
    $vendor = Vendor::where('id', $request->vendor_id)->first();
    //  dd($vendor);
    $amount = $request->amount;
    $wal_amt = (int)$vendor->advance_amt;
    $response = true;
    if (($wal_amt >= 0) && ($amount <= $wal_amt)) {
      $response = false;
    }
    return response()->json($response);
  }
  public function vendor_history(Request $request, $id)
  {
    // dd($request->member_id);
    $paginate = $request->paginate;
    $vendor = Transfer::leftJoin('users', 'users.id', '=', 'transferdetails.user_id')
      ->leftJoin('vendor_details', 'vendor_details.id', '=', 'transferdetails.vendor_id')
      ->leftJoin('payment', 'payment.id', '=', 'transferdetails.payment_mode')
      ->where('transferdetails.vendor_id', '=', $id)
      ->select(
        'transferdetails.*',
        'vendor_details.name as name',
        'payment.name as payment_mode',
        'users.first_name',
        'users.last_name'
      )
      ->where('transferdetails.is_vendor', 1)
      ->when($request->from_date, function ($query, $from_date) {
        $query->whereDate('current_date', '>=', $from_date);
      })
      ->when($request->to_date, function ($query, $to_date) {
        $query->whereDate('current_date', '<=', $to_date);
      })
      ->when($request->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('users.first_name', 'like', "%$search%")
            ->orWhere('users.last_name', 'like', "%$search%")
            ->orWhere('transferdetails.amount', 'like', "%$search%")
            ->orWhere('payment.name', 'like', "%$search%")
            ->orWhere('transferdetails.description', 'like', "%$search%");
        });
      })->when($request->member_id, function ($query, $member_id) {
        $query->where('users.id', $member_id);
      });

  //  if (Auth::user()->hasRole('Admin')) {
      $vendor = $vendor->orderBy('transferdetails.id', 'DESC')
        ->paginate($paginate)->withQueryString();
    // } else {
    //   $vendor = $vendor->where('user_id', Auth::user()->id)
    //     ->orderBy('transferdetails.id', 'DESC')
    //     ->paginate($paginate)->withQueryString();
    // }
    $sum = $vendor->sum('amount');
    $user_list = User::latest()->get();
    return view('vendor-expenses.vendorhistory', ['vendor' => $vendor, 'user_list' => $user_list, 'sum' => $sum, 'id' => $id]);
  }
  public function withdraw(Request $request){
   $vendor_id = $request->id;
   $member = User::where('active_status',1)->get();
   $view = view('vendor-expenses.amount_reduction',['member' => $member,'vendor_id' => $vendor_id])->render();
   return response()->json($view);
  }
  public function withdraw_save(Request $request, ExpenseLedgerService $ledger){
    $validated = $request->validate([
      'vendor_id' => ['required', 'exists:vendor_details,id'],
      'member_id' => ['required', 'exists:users,id'],
      'amount' => ['required', 'numeric', 'gt:0'],
    ]);
    $ledger->withdrawVendor(
      (int) $validated['vendor_id'],
      (int) $validated['member_id'],
      (float) $validated['amount']
    );
    return redirect()->route('vendor-expenses-advance-history')->with('message', 'Amount withdrawn successfully');
  }
}

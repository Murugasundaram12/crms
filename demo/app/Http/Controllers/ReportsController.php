<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ClientDetails;
use App\Models\Expenses;
use App\Models\Category;
use App\Models\Wallet;
use App\Models\ProjectDetails;
use App\Exports\ClientSummaryExport;
use App\Exports\PaymentIncomeExport;
use App\Exports\PaymentExpenseExport;
use App\Models\MainCategory;
use App\Models\User;
use Carbon\Carbon;
use Excel;
use PDF;

class ReportsController extends Controller
{

  /**
   * Display a listing of the resource.
   */
  public function client_summary(Request $request)
  {
    $paginate = $request->paginate ?? 10;
    $from = null;
    $to = null;
    if (!empty($request->date_range)) {
      [$from, $to] = array_map('trim', explode('-', $request->date_range));

      $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
      $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }

    $clients = ProjectDetails::leftjoin('wallet', 'wallet.project_id', '=', 'project_details.id')
      ->leftjoin('stage', 'stage.id', '=', 'wallet.stage_id')
      ->leftjoin('clientdetails', 'wallet.client_id', '=', 'clientdetails.id')
      ->leftjoin('payment', 'payment.id', '=', 'wallet.payment_mode')
      ->select(
        'project_details.*',
        'stage.name as stage_name',
        'wallet.amount',
        'payment.name as payment',
        'wallet.current_date as currentdate',
        'clientdetails.first_name',
        'clientdetails.last_name'
      )
      ->when($from, function ($query, $from) {
        $query->whereDate('wallet.current_date', '>=', $from);
      })
      ->when($to, function ($query, $to) {
        $query->whereDate('wallet.current_date', '<=', $to);
      })
      ->when(request('category_id'), function ($query, $category_id) {
        $query->where('wallet.client_id', $category_id);
      })
      ->when(request('project_id'), function ($query, $project_id) {
        $query->where('wallet.project_id', $project_id);
      })
      ->when(request('search'), function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('clientdetails.first_name', 'like', "%$search%")
            ->orWhere('clientdetails.last_name', 'like', "%$search%")
            ->orWhere('project_details.name', 'like', "%$search%")
            ->orWhere('wallet.amount', 'like', "%$search%")
            ->orWhere('project_details.total_amt', 'like', "%$search%")
            ->orWhere('payment.name', 'like', "%$search")
            ->orWhere('stage.name', 'like', "%$search");
        });
      })
      ->paginate($paginate)->withQueryString();
    //dd($clients);
    $project = ProjectDetails::where('project_status', 0)->get();
    $user = ClientDetails::where('active_status', 1)->where('delete_status', 0)->get();
    // dd($clients);
    // exit;
    return view('reports.clientsummary', compact('clients', 'user', 'project'));
  }
  public function payment_summary(Request $request)
  {


    $expenses = ProjectDetails::where('project_details.delete_status', 0)->leftjoin('expenses', 'project_details.id', 'expenses.project_id')->whereNull('expenses.deleted_at')
      // ->where('expenses.project_id', '!=', null)
      // })
      ->selectRaw('project_details.id as projectid,project_details.name as project_name,project_details.advance_amt,SUM(expenses.amount) as paid_amt1,expenses.*')->groupBy('project_details.id')->get();
    //dd($expenses);
    return view('reports.paymentsummary', compact('expenses'));
  }
  public function payment_income(Request $request)
  {
    $paginate = $request->paginate??10;
 $from = null;
    $to = null;
       if (!empty($request->date_range)) {
      [$from, $to] = array_map('trim', explode('-', $request->date_range));

      $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
      $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }

    $project = Wallet::leftjoin('clientdetails', 'clientdetails.id', '=', 'wallet.client_id')
                ->leftjoin('payment', 'payment.id', '=', 'wallet.payment_mode')?->leftjoin('stage', 'stage.id', '=', 'wallet.stage_id')?->where('wallet.project_id', $request->id)
                ->select('wallet.*', 'clientdetails.first_name', 'clientdetails.last_name', 'payment.name as payment_name', 'stage.name as stage_name')
                ->when($from,function($query,$from){
                  $query->whereDate('wallet.current_date','>=',$from);
                })
                ->when($to,function($query,$to){
                  $query->whereDate('wallet.current_date','<=',$to);
                })
                ->when(request('category_id'),function($query,$category_id){
                  $query->where('wallet.client_id',$category_id);
                })
                ->when(request('search'),function($query,$search){
                  $query->where(function ($q) use ($search) {
          $q->where('clientdetails.first_name', 'like', "%$search%")
            ->orWhere('clientdetails.last_name', 'like', "%$search%")
            ->orWhere('wallet.amount', 'like', "%$search%")
            ->orWhere('wallet.description', 'like', "%$search%")
            ->orWhere('payment.name', 'like', "%$search")
            ->orWhere('stage.name', 'like', "%$search");
        });
                })->paginate($paginate);
    //dd($project);
    $user = ClientDetails::where('active_status', 1)->where('delete_status', 0)->get();
    return view('reports.paymentincome', ['projects' => $project,  'user' => $user,  'id' => $request->id]);
  }
  public function payment_expenses(Request $request)
  {
     $paginate = $request->paginate??10;
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

    $projects = Expenses::leftjoin('users', 'users.id', '=', 'expenses.user_id')->leftjoin('category', 'category.id', '=', 'expenses.category_id')->leftjoin('payment', 'payment.id', '=', 'expenses.payment_mode')->where('expenses.project_id', $request->id)->whereNull('expenses.deleted_at')->select('expenses.*', 'category.name as category_name', 'users.first_name', 'users.last_name', 'payment.name as payment_name')
      ->when($from,function($query,$from){
                  $query->whereDate('expenses.current_date','>=',$from);
                })
                ->when($to,function($query,$to){
                  $query->whereDate('expenses.current_date','<=',$to);
                })
                 ->when(request('category_id'),function($query,$category_id){
                  $query->where('expenses.category_id', $category_id);
                })
             
                ->when(request('user_id'),function($query,$user_id){
                  $query->where('expenses.user_id', $user_id);
                })
                ->when(request('search'),function($query,$search){
                  $query->where(function ($q) use ($search) {
          $q->where('users.first_name', 'like', "%$search%")
            ->orWhere('users.last_name', 'like', "%$search%")
            ->orWhere('expenses.amount', 'like', "%$search%")
            ->orWhere('expenses.description', 'like', "%$search%")
            ->orWhere('payment.name', 'like', "%$search"); 
        });
        })->paginate($paginate);
 
    //dd($project);
    $category = Category::where('active_status', 1)->where('delete_status', 0)->get();
    $user = User::where('active_status', 1)->where('delete_status', 0)->get();
   
    return view('reports.paymentexpense', ['projects' => $projects,  'user' => $user, 'user_filter' => $user_filter, 'id' => $request->id, 'category_filter' => $category_filter, 'category' => $category,]);
  }

  public function client_summary_export(Request $request)
  {
    $project_id = $request->project_id;
    $category_id = $request->category_id;
    $from = null;
    $to = null;
    $search = $request->search;
    if (!empty($request->date_range)) {
      [$from, $to] = array_map('trim', explode('-', $request->date_range));

      $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
      $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }


    return Excel::download((new ClientSummaryExport($project_id, $category_id, $from, $to, $search)), 'clientsummary.xlsx');
  }
  public function client_summary_pdf(Request $request)
  {
    
    $from = null;
    $to = null;
   if (!empty($request->date_range)) {
      [$from, $to] = array_map('trim', explode('-', $request->date_range));

      $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
      $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }

$clients = ProjectDetails::leftjoin('wallet', 'wallet.project_id', '=', 'project_details.id')
      ->leftjoin('stage', 'stage.id', '=', 'wallet.stage_id')
      ->leftjoin('clientdetails', 'wallet.client_id', '=', 'clientdetails.id')
      ->leftjoin('payment', 'payment.id', '=', 'wallet.payment_mode')
      ->select(
        'project_details.*',
        'stage.name as stage_name',
        'wallet.amount',
        'payment.name as payment',
        'wallet.current_date as currentdate',
        'clientdetails.first_name',
        'clientdetails.last_name'
      )
      ->when($from, function ($query, $from) {
        $query->whereDate('wallet.current_date', '>=', $from);
      })
      ->when($to, function ($query, $to) {
        $query->whereDate('wallet.current_date', '<=', $to);
      })
      ->when(request('category_id'), function ($query, $category_id) {
        $query->where('wallet.client_id', $category_id);
      })
      ->when(request('project_id'), function ($query, $project_id) {
        $query->where('wallet.project_id', $project_id);
      })
      ->when(request('search'), function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('clientdetails.first_name', 'like', "%$search%")
            ->orWhere('clientdetails.last_name', 'like', "%$search%")
            ->orWhere('project_details.name', 'like', "%$search%")
            ->orWhere('wallet.amount', 'like', "%$search%")
            ->orWhere('project_details.total_amt', 'like', "%$search%")
            ->orWhere('payment.name', 'like', "%$search")
            ->orWhere('stage.name', 'like', "%$search");
        });
      })->get();
    $pdf = PDF::loadView('reports.clientsummarypdf', compact('clients'));

    return $pdf->download('clientsummary.pdf');
  }
  public function payment_income_export(Request $request)
  {

    $category_id = $request->category_id;
    $search = $request->search;
 $from = null;
    $to = null;
       if (!empty($request->date_range)) {
      [$from, $to] = array_map('trim', explode('-', $request->date_range));

      $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
      $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }

    return Excel::download((new PaymentIncomeExport($category_id, $from, $to, $request->id,$search)), 'payment-income.xlsx');
  }
  public function payment_income_pdf(Request $request)
  {
    $project_filter = $request->project_id;
    $user_filter = $request->user_id;
   $from = null;
    $to = null;
       if (!empty($request->date_range)) {
      [$from, $to] = array_map('trim', explode('-', $request->date_range));

      $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
      $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }
    $project = Wallet::leftjoin('clientdetails', 'clientdetails.id', '=', 'wallet.client_id')->leftjoin('payment', 'payment.id', '=', 'wallet.payment_mode')?->leftjoin('stage', 'stage.id', '=', 'wallet.stage_id')?->where('wallet.project_id', $request->id)->select('wallet.*', 'clientdetails.first_name', 'clientdetails.last_name', 'payment.name as payment_name', 'stage.name as stage_name');
    if ($from != '' && $to != '') {
      $project = $project->whereBetween('wallet.current_date', [$from, $to]);
    }

    if ($user_filter != 'undefined' && $user_filter != '') {
      $project = $project->where('wallet.client_id', $user_filter);
    }
    $project = $project->get();
    //dd($project);
    $pdf = PDF::loadView('reports.paymentincomepdf', compact('project'));

    return $pdf->download('paymentincome.pdf');
  }
  public function payment_expense_export(Request $request)
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
    return Excel::download((new PaymentExpenseExport($user_filter, $from, $to, $request->id, $category_filter)), 'payment-expense.xlsx');
  }
  public function payment_expense_pdf(Request $request)
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

    $project = Expenses::leftjoin('users', 'users.id', '=', 'expenses.user_id')->leftjoin('category', 'category.id', '=', 'expenses.category_id')->leftjoin('payment', 'payment.id', '=', 'expenses.payment_mode')->where('expenses.project_id', $request->id)->select('expenses.*', 'category.name as category_name', 'users.first_name', 'users.last_name', 'payment.name as payment_name');
    if ($from != '' && $to != '') {
      $project = $project->whereBetween('wallet.current_date', [$from, $to]);
    }
    if ($category_filter != 'undefined' && $category_filter != '') {
      $project = $project->where('expenses.category_id', $category_filter);
    }

    if ($user_filter != 'undefined' && $user_filter != '') {
      $project = $project->where('expenses.user_id', $user_filter);
    }
    $project = $project->get();
    $pdf = PDF::loadView('reports.paymentexpensepdf', compact('project'));

    return $pdf->download('paymentexpense.pdf');
  }
}


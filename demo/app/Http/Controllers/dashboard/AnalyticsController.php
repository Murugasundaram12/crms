<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\Attendance;
use App\Models\User;
use App\Models\ProjectDetails;
use App\Models\Expenses;
use App\Models\Transfer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

//use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
  public function index()
  {
    $user = User::join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->where('users.id', Auth::user()->id)->select('users.*', 'roles.name as role_name')->first();
    if ($user->role_name == 'Admin') {
      $checking = Attendance::where('user_id', Auth::user()->id)->whereDate('created_at', '=', now())->orderBy('id', 'desc')->first();
      $member = User::count();
      $project_open = ProjectDetails::where('active_status', 1)->where('delete_status', 0)->where('project_status', 0)->count();
      $project_close = ProjectDetails::where('active_status', 1)->where('delete_status', 0)->where('project_status', 1)->count();
      if ($user->role_name == 'Admin') {
        $unpaid_amt = Expenses::sum('unpaid_amt');
        $paid_amt = Expenses::sum('paid_amt');
        $monthly_transfer = Transfer::where('created_at', '>', now()->subDays(30)->endOfDay())
          ->sum('amount');
      } else {
        $unpaid_amt = Expenses::where('user_id', Auth::user()->id)->sum('unpaid_amt');
        $paid_amt = Expenses::where('user_id', Auth::user()->id)->sum('paid_amt');
        $monthly_transfer = Transfer::where('user_id', Auth::user()->id)->where('created_at', '>', now()->subDays(30)->endOfDay())
          ->sum('amount');
      }
      $allMonths = [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
      ];

      $income = Wallet::selectRaw('DATE_FORMAT(current_date, "%b") as month, SUM(amount) as total')
        ->whereYear('current_date', now()->year)
        ->groupBy('month')
        ->orderByRaw('FIELD(month, ?)', [implode(',', $allMonths)])
        ->get();

      // dd($income);
      $totalIncome = $income->sum('total');

      // Calculate the overall percentage for each month
      $incomeWithPercentage = $income->map(function ($item) use ($totalIncome) {
        if ($totalIncome != 0)
          $percentage = ($item->total / $totalIncome) * 100;
        else
          $percentage = 0;
        return [
          'percentage' => round($percentage, 2) // Round to two decimal places
        ];
      });

      // Calculate the percentage for the current week
      $currentWeekIncome = Wallet::whereBetween('current_date', [
        now()->startOfWeek(),
        now()->endOfWeek()
      ])->sum('amount');
      if ($currentWeekIncome != 0 && $totalIncome != 0) {
        $currentWeekPercentage = round(($currentWeekIncome / $totalIncome) * 100);
      } else {
        $currentWeekPercentage = 0;
      }

      //expenses
      $expense = Expenses::selectRaw('DATE_FORMAT(current_date, "%b") as month, SUM(paid_amt) as total')
        ->where('current_date', '>', now()->year)
        ->groupBy('month')
        ->orderByRaw('FIELD(month, ?)', [implode(',', $allMonths)])
        ->get();
      $totalExpense = $expense->sum('total');

      // Calculate the overall percentage for each month
      $expenseWithPercentage = $expense->map(function ($item) use ($totalExpense) {
        $percentage = ($item->total / $totalExpense) * 100;
        return [
          'percentage' => round($percentage, 2) // Round to two decimal places
        ];
      });

      // Calculate the percentage for the current week
      $currentWeekExpense = Expenses::whereBetween('current_date', [
        now()->startOfWeek(),
        now()->endOfWeek()
      ])->sum('paid_amt');
      if ($currentWeekExpense != 0 && $totalExpense != 0) {
        $currentWeekExpensePercentage = round(($currentWeekExpense / $totalExpense) * 100);
      } else {
        $currentWeekExpensePercentage = 0;
      }
      //dd($income);
      $wallet = User::where('active_status', 1)->where('delete_status', 0)->sum('wallet');

      $transfer_history = User::select('users.id as member_id', 'users.first_name as first_name', 'users.last_name as last_name', DB::raw('SUM(CASE WHEN transferdetails.amount > 0 THEN transferdetails.amount ELSE 0 END) as total_amount'))
        ->leftJoin('transferdetails', 'users.id', '=', 'transferdetails.member_id')
        ->groupBy('users.id', 'users.first_name', 'users.last_name')
        ->get();
      $recent_transfer_history = User::select('users.id as member_id', 'users.first_name as first_name', 'users.last_name as last_name', DB::raw('SUM(CASE WHEN transferdetails.amount > 0 THEN transferdetails.amount ELSE 0 END) as total_amount'))
        ->leftJoin('transferdetails', function ($join) {
          $join->on('users.id', '=', 'transferdetails.member_id')
            ->whereDate('transferdetails.current_date', today());
        })
        ->groupBy('users.id', 'users.first_name', 'users.last_name')
        ->get();

     // dd($recent_transfer_history);
      $clocked_in = Attendance::wheredate('created_at', Carbon::today())->count();
      $clocked_out = User::whereExists(function ($query) {
        $query->select(DB::raw(1))
          ->from('attendance')
          ->whereRaw('attendance.user_id = users.id')
          ->whereDate('attendance.created_at', Carbon::today());
      }, 'and', true) // The 'and' and false parameters are used to negate the condition
        ->count();
      $all_projects = ProjectDetails::where(['active_status' => 1, 'delete_status' => 0, 'project_status' => 0])
        ->select('id as project_id', 'name as project_name')
        ->get();

      $today_income = Wallet::selectRaw('project.id as project_id, project.name as project_name, COALESCE(SUM(amount), 0) as total')
        ->leftJoin('project_details as project', 'project.id', 'wallet.project_id')
        ->whereDate('current_date', today())
        ->groupBy('project.id', 'project.name')->get();

      $all_projects_with_income = $all_projects->map(function ($project) use ($today_income) {
        $income = $today_income->firstWhere('project_id', $project->project_id);
        return [
          'total' => $income ? $income->total : 0,
          'project_name' => $project->project_name,
        ];
      });
      $today_expense = Expenses::selectRaw('project.id as project_id,project.name as project_name, COALESCE(SUM(paid_amt),0) as total')->leftJoin('project_details as project', 'project.id', 'expenses.project_id')
        ->whereDate('expenses.current_date', today())
        ->groupBy('project.id', 'project.name')->get();
      $all_projects_with_expense = $all_projects->map(function ($project) use ($today_expense) {
        $income = $today_expense->firstWhere('project_id', $project->project_id);
        return [
          'total' => $income ? $income->total : 0,
          'project_name' => $project->project_name,
        ];
      });
      //dd($all_projects_with_expense);
      return view('content.dashboard.dashboards-analytics', ['checking' => $checking, 'member' => $member, 'project_open' => $project_open, 'project_close' => $project_close, 'unpaid_amt' => $unpaid_amt, 'paid_amt' => $paid_amt, 'monthly_transfer' => $monthly_transfer, 'income' => $income, 'wallet' => $wallet, 'incomeWithPercentage' => $incomeWithPercentage, 'currentWeekPercentage' => $currentWeekPercentage, 'expenseWithPercentage' => $expenseWithPercentage, 'currentWeekExpensePercentage' => $currentWeekExpensePercentage, 'expense' => $expense, 'transfer_history' => $transfer_history, 'clocked_in' => $clocked_in, 'clocked_out' => $clocked_out, "today_income" => $all_projects_with_income, 'today_expense' => $all_projects_with_expense, 'recent_transfer_history' => $recent_transfer_history]);
    } else {
      $checking = Attendance::where('user_id', Auth::user()->id)->whereDate('created_at', '=', now())->orderBy('id', 'desc')->first();

      if ($user->role_name == 'Admin') {
        $unpaid_amt = Expenses::sum('unpaid_amt');
        $paid_amt = Expenses::sum('paid_amt');
        $monthly_transfer = Transfer::where('created_at', '>', now()->subDays(30)->endOfDay())
          ->sum('amount');
      } else {
        $unpaid_amt = Expenses::where('user_id', Auth::user()->id)->sum('unpaid_amt');
        $paid_amt = Expenses::where('user_id', Auth::user()->id)->sum('paid_amt');
        $monthly_transfer = Transfer::where('user_id', Auth::user()->id)->where('created_at', '>', now()->subDays(30)->endOfDay())
          ->sum('amount');
      }
      $allMonths = [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
      ];


      $expense = Expenses::selectRaw('DATE_FORMAT(current_date, "%b") as month, SUM(paid_amt) as total')
        ->where('user_id', Auth::user()->id)
        ->where('current_date', '>', now()->year)
        ->groupBy('month')
        ->orderByRaw('FIELD(month, ?)', [implode(',', $allMonths)])
        ->get();
      //dd($expense);
      $totalExpense = $expense->sum('total');

      // Calculate the overall percentage for each month
      $expenseWithPercentage = $expense->map(function ($item) use ($totalExpense) {
        if ($totalExpense != 0)
          $percentage = ($item->total / $totalExpense) * 100;
        else
          $percentage = 0;
        return [
          'percentage' => round($percentage, 2) // Round to two decimal places
        ];
      });

      // Calculate the percentage for the current week
      $currentWeekExpense = Expenses::where('user_id', Auth::user()->id)->whereBetween('current_date', [
        now()->startOfWeek(),
        now()->endOfWeek()
      ])->sum('paid_amt');

      if ($currentWeekExpense != 0 && $totalExpense != 0) {
        $currentWeekExpensePercentage = round(($currentWeekExpense / $totalExpense) * 100);
      } else {
        $currentWeekExpensePercentage = 0;
      }
      //dd($income);
      $wallet = User::where('active_status', 1)->where('delete_status', 0)->where('id', Auth::user()->id)->sum('wallet');

      $transfer_history = User::leftJoin('transferdetails', function ($join) {
        $join->on('users.id', '=', 'transferdetails.member_id')
            ->where('transferdetails.user_id', '=', Auth::user()->id);
    })
    ->select(
        'users.id as member_id',
        'users.first_name as first_name',
        'users.last_name as last_name',
        DB::raw('COALESCE(SUM(transferdetails.amount), 0) as total_amount')
    )
    ->groupBy('users.id', 'users.first_name', 'users.last_name')
    ->get();
    $recent_transfer_history =User::leftJoin('transferdetails', function ($join) {
      $join->on('users.id', '=', 'transferdetails.member_id')
      ->wheredate('transferdetails.current_date',today())
          ->where('transferdetails.user_id', '=', Auth::user()->id);
  })
  ->select(
      'users.id as member_id',
      'users.first_name as first_name',
      'users.last_name as last_name',
      DB::raw('COALESCE(SUM(transferdetails.amount), 0) as total_amount')
  )
  ->groupBy('users.id', 'users.first_name', 'users.last_name')
  ->get();
  $all_projects = ProjectDetails::where(['active_status' => 1, 'delete_status' => 0, 'project_status' => 0])
        ->select('id as project_id', 'name as project_name')
        ->get();
  $today_expense = Expenses::selectRaw('project.id as project_id,project.name as project_name, COALESCE(SUM(paid_amt),0) as total')->leftJoin('project_details as project', 'project.id', 'expenses.project_id')
  ->where('expenses.user_id',Auth::user()->id)
        ->whereDate('expenses.current_date', today())
        ->groupBy('project.id', 'project.name')->get();
      $all_projects_with_expense = $all_projects->map(function ($project) use ($today_expense) {
        $income = $today_expense->firstWhere('project_id', $project->project_id);
        return [
          'total' => $income ? $income->total : 0,
          'project_name' => $project->project_name,
        ];
      });

       //dd($all_projects_with_expense);
      return view('content.dashboard.userdashboard', ['checking' => $checking,  'unpaid_amt' => $unpaid_amt, 'paid_amt' => $paid_amt, 'monthly_transfer' => $monthly_transfer,  'expenseWithPercentage' => $expenseWithPercentage, 'currentWeekExpensePercentage' => $currentWeekExpensePercentage, 'expense' => $expense, 'transfer_history' => $transfer_history, 'wallet' => $wallet,'recent_transfer_history' => $recent_transfer_history,'today_expense' => $all_projects_with_expense]);
    }
  }
  public function store()
  {
    $user_id = Auth::user()->id;
    $input['user_id'] = $user_id;
    $input['notes'] = 0;
    $checking = Attendance::create($input);
    return redirect()->route('dashboard')
      ->with('popup', 'Check-in Successfully.');
  }
  public function update()
  {
    $user_id = Auth::user()->id;
    $input['user_id'] = $user_id;
    $input['notes'] = 1;

    $checking = Attendance::where('user_id', $user_id)->whereDate('created_at', '=', now())->orderBy('id', 'desc')->first();
    $startTime = Carbon::parse($checking->created_at);
    $finishTime = Carbon::parse(now());

    $totalDuration = $finishTime->diffInSeconds($startTime);
    $hours = $this->convert_hrs($totalDuration);
    $input['duration'] = $hours;
    $checking->update($input);
    return redirect()->route('dashboard')
      ->with('popup', 'Check-out Successfully.');
  }
  public function convert_hrs($value)
  {
    $day = floor($value / 86400);
    $hours = floor(($value - ($day * 86400)) / 3600);
    $minutes = floor(($value / 60) % 60);
    $seconds = $value % 60;
    //"$day:$hours:$minutes:$seconds";
    return $hours . ' hours ' . $minutes . ' minutes ';
  }
}

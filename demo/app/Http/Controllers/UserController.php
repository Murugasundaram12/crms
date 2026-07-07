<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Models\Expenses;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Category;
use App\Models\ExpensesUnpaidDate;
use App\Models\ProjectDetails;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{


  /**
   * Display a listing of the resource.
   */
  public function index(Request $request)
  {
    $paginate = $request->paginate ?? 15;
    //return view('roles.index');
    $id = Auth::user()->id;
    $users = User::whereNot('email','superadmin@gmail.com')->when(request('search'), function ($query, $search) {
      $query->where(function ($q) use ($search) {
      $q->where('first_name', 'like', "%$search%")
        ->orWhere('last_name', 'like', "%$search%")
        ->orWhere('job_title', 'like', "%$search%")
        ->orWhere('email', 'like', "%$search%")
        ->orWhere('phone', 'like', "%$search%")
        ->orWhere('wallet', 'like', "%$search");
      });
    })
      ->where('active_status', 1)->where('delete_status', 0)
      ->where('id', '!=', $id)->orderBy('id', 'desc')->paginate($paginate)->withQueryString();
    $unpaid = Expenses::all();
    $role = Role::join('model_has_roles', 'model_has_roles.role_id', '=', 'roles.id')->where('roles.name', 'Admin')->select('model_has_roles.model_id')->first();
    //dd($role);
    return view('user.index', compact('users', 'unpaid', 'role'));
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create()
  {
    $role = Role::all();
    return view('user.create', ['role' => $role]);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    $input = $request->all();
    $input['confirm_password'] = Hash::make($request->confirm_password);
    // dd($input);
    if ($image = $request->file('image')) {

      $destinationPath = public_Path('images');
      'public/images/';
      $profileImage = date('YmdHis') . "." . $image->getClientOriginalExtension();
      $image->move($destinationPath, $profileImage);


      $input['image'] = $profileImage;
    }
    //print_r($request->file('image'));exit;
    $user = User::create($input);
  $user->assignRole($request->input('roles'));
    return redirect()->route('user-edit', ['id' => $user->id, 'tab' => 'job-info']);
  }

  /**
   * Display the specified resource.
   */
  public function show(Request $request)
  {
    $tab = $request->tab??'general-info';
    $user = User::join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->where('users.id', $request->id)->select('users.*', 'roles.name as role_name')->first();
    //print_r($user);exit;
    $time =  Attendance::where('user_id', $request->id)
      ->where('created_at', '>', now()->subDays(30)->endOfDay())
      ->sum(FacadesDB::raw('TIMESTAMPDIFF(SECOND, created_at, updated_at)'));
    $hours = $this->convert_hrs($time);
    //dd($hours);
    $from = null;
    $to = null;

    if (!empty($request->date_range)) {
      [$from, $to] = array_map('trim', explode('-', $request->date_range));

      $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
      $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }
    $paginate = $request->paginate ?? 10;
    $auth = Auth::user()->id;
    $role = FacadesDB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->join('users', 'users.id', '=', 'model_has_roles.model_id')->where('users.id', $auth)->pluck('roles.id')->first();

    $expenses = Expenses::where('expenses.user_id', $request->id)->join('category', 'category.id', '=', 'expenses.category_id')
      ->leftJoin('project_details', function ($join) {
        $join->on('project_details.id', 'expenses.project_id')
          ->where('expenses.project_id', '!=', null);
      })
      ->leftjoin('payment', 'payment.id', '=', 'expenses.payment_mode')
      ->leftjoin('labour_details as l','l.id','=','expenses.labour_id')
      ->leftjoin('vendor_details as ve','ve.id','=','expenses.vendor_id')
      ->where(['category.active_status' => 1, 'category.delete_status' => 0])

      ->when(request('category_id'), function ($query, $category_id) {
        $query->where('expenses.category_id', $category_id);
      })
      ->when(request('project_id'), function ($query, $project_id) {
        $query->where('expenses.project_id', $project_id);
      })
      ->when(request('user_id'), function ($query, $user_id) {
        $query->where('expenses.user_id', $user_id);
      });
    if ($role != 1) {
      $expenses = $expenses->leftjoin('users', 'users.id', '=', 'expenses.user_id');
      $expenses = $expenses->select('expenses.*', 'category.name as category_name', 'project_details.name as project_name', 'payment.name as payment_name', 'users.first_name', 'users.last_name')
      ->when(request('search'), function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('category.name', 'like', "%$search%")
            ->orWhere('project_details.name', 'like', "%$search%")
            ->orWhere('payment.name', 'like', "%$search%")
            ->orWhere('users.first_name', 'like', "%$search%")
            ->orWhere('users.last_name', 'like', "%$search%")
            ->orWhere('expenses.amount', 'like', "%$search")
            ->orWhere('expenses.paid_amt', 'like', "%$search")
            ->orWhere('expenses.unpaid_amt', 'like', "%$search")
            ->orWhere('expenses.extra_amt', 'like', "%$search")
            ->orWhere('l.name','like',"%$search%")
            ->orWhere('ve.name','like',"%$search%")
            ->orWhere('expenses.description', 'like', "%$search%");
        });
      });
    } else {
      $expenses = $expenses->leftjoin('users', 'users.id', '=', 'expenses.editedBy')->leftjoin('users as users_add', 'users_add.id', '=', 'expenses.user_id')
      ->select('expenses.*', 'category.name as category_name', 'project_details.name as project_name', 'payment.name as payment_name', 'users.first_name', 'users.last_name', 'users_add.first_name as first', 'users_add.last_name as last')
      ->when(request('search'), function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('category.name', 'like', "%$search%")
            ->orWhere('project_details.name', 'like', "%$search%")
            ->orWhere('payment.name', 'like', "%$search%")
            ->orWhere('users.first_name', 'like', "%$search%")
            ->orWhere('users.last_name', 'like', "%$search%")
            ->orWhere('users_add.first_name', 'like', "%$search%")
            ->orWhere('users_add.last_name', 'like', "%$search%")
            ->orWhere('expenses.amount', 'like', "%$search")
            ->orWhere('expenses.paid_amt', 'like', "%$search")
            ->orWhere('expenses.unpaid_amt', 'like', "%$search")
            ->orWhere('expenses.extra_amt', 'like', "%$search")
             ->orWhere('l.name','like',"%$search%")
            ->orWhere('ve.name','like',"%$search%")
            ->orWhere('expenses.description', 'like', "%$search%");
        });
      });
    }

    $expenses = $expenses->orderBy('expenses.id', 'desc')->paginate($paginate);


    $unpaid_date = ExpensesUnpaidDate::select('expense_id', 'updated_at')->orderBy('id', 'desc')->first();
    $category = Category::where(['active_status' => 1, 'delete_status' => 0])->get();
    $project = ProjectDetails::where(['active_status' => 1, 'delete_status' => 0])->get();
    $user1 = User::join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->where(['users.active_status' => 1, 'users.delete_status' => 0])->select('users.*', 'roles.name')->get();

    $sum = $expenses->sum('amount');
    $paid_amt = $expenses->sum('paid_amt');
    $unpaid_amt = $expenses->sum('unpaid_amt');
    $paginate1 = $request->paginate1??15;
    $attendance = Attendance::when($from, function ($query, $from) {
      $query->whereDate('created_at', '>=', $from);
    })
    ->when($to, function ($query, $to) {
      $query->whereDate('created_at', '<=', $to);
    })->where('user_id', $request->id)->paginate($paginate1);


    // dd($expenses);
    return view('user.view', ['user' => $user, 'hours' => $hours, 'expenses' => $expenses, 'unpaid_date' => $unpaid_date, 'category' => $category,  'project' => $project, 'user1' => $user1,  'sum' => $sum, 'paid_amt' => $paid_amt, 'unpaid_amt' => $unpaid_amt, 'amount' => $request->amount, 'attendances' => $attendance,'tab' => $tab]);
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(Request $request)
  {
    $id = $request->id;
    $tab = 'general-info';
    if ($request->tab != '') {
      $tab = $request->tab;
    }
    $user = User::where('id', $id)->first();
    $roles = Role::all();
    $modeluser = FacadesDB::table('model_has_roles')->where('model_id', $id)->first();
   // dd($modeluser);
    return view('user.edit', ["user" => $user, 'role' => $roles, 'modeluser' => $modeluser, 'tab' => $tab]);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
    //dd($request->all());
    $input = $request->all();
    //dd($input);
    $user = User::find($id);
    if ($image = $request->file('image')) {

      $destinationPath = public_Path('images');
      'public/images/';
      $profileImage = date('YmdHis') . "." . $image->getClientOriginalExtension();
      $image->move($destinationPath, $profileImage);


      $input['image'] = $profileImage;
    }
    // print_r($user);
    // exit;
    $user->update($input);
    $value = FacadesDB::table('model_has_roles')->where('model_id', $id)->delete();

    $user->assignRole($request->input('roles'));

    return redirect()->route('user-edit', ['id' => $request->id, 'tab' => 'job-info']);
  }
  public function jobupdate(Request $request, string $id)
  {

    $input = $request->all();
    //print_r($input);exit;
    $user = User::find($id);
    // print_r($request->file('government_image'));
    if ($image = $request->file('government_image')) {

      $destinationPath = public_Path('images');
      'public/images/';
      $profileImage = date('YmdHis') . "." . $image->getClientOriginalExtension();
      $image->move($destinationPath, $profileImage);


      $input['government_image'] = $profileImage;
    }

    $user->update($input);

    return redirect()->route('user-index')
      ->with('message', 'User updated successfully');
  }

  /**
   * Remove the specified resource from storage.
   */
  public function userdelete(Request $request)
  {
    $user = User::find($request->id);
    $user['active_status'] = 0;
    $user['delete_status'] = 1;
    $user->update();
    return redirect()->route('user-index')
      ->with('message', 'User Deleted Successfully');
  }
  public function phoneunique(Request $request)
  {

    $response = false;

    $user = User::where(['phone' => $request->phone])->first();
    if (!empty($user)) {
      $response = true;
      return response()->json($response);
    }
    return response()->json($response);
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
  public function profile_photo_upload(Request $request)
  {
    //dd($request->all());
    $user = User::where('id', Auth::user()->id)->first();
    if ($image = $request->file('image')) {

      $destinationPath = public_Path('images');
      $profileImage = date('YmdHis') . "." . $image->getClientOriginalExtension();
      $image->move($destinationPath, $profileImage);


      $input['image'] = $profileImage;
    }
    $user->update($input);
    return response()->json($user);
  }
  public function change_password(Request $request,$id){
    $user = User::where('id',$id)->first();
    $user->password = $request->new_password;
    $user->update();
    return redirect()->route('user-index')->with('message','Password changed successfully');
  }
  public function changestatus(Request $request){
   $user = User::where('id',$request->id)->first();
   $user->status = $request->status;
   $user->update();
   return response()->json($user);
  }
}

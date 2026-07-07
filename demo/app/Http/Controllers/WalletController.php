<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ClientDetails;
use App\Models\ProjectDetails;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Payment;
use App\Models\Stage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class WalletController extends Controller
{
  public function index(Request $request)
  {
    $paginate = $request->paginate ?? 10;
    $from = null;
    $to = null;

    if (!empty($request->date_range)) {
      [$from, $to] = array_map('trim', explode('-', $request->date_range));

      $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
      $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }

    $user_id = Auth::user()->id;
    $role = Role::join('model_has_roles', 'model_has_roles.role_id', '=', 'roles.id')->where('roles.id', $user_id)->pluck('model_has_roles.model_id')->first();
    // dd($role);

    $wallet = Wallet::leftjoin('users', 'users.id', '=', 'wallet.user_id')->leftjoin('clientdetails', 'clientdetails.id', '=', 'wallet.client_id')
      ->leftjoin('project_details', 'project_details.id', '=', 'wallet.project_id')
      ->leftjoin('payment', 'payment.id', '=', 'wallet.payment_mode')
      ->leftjoin('stage', 'stage.id', '=', 'wallet.stage_id');
    if ($role != 1) {
      $wallet = $wallet->where('wallet.user_id', $user_id);
    }
    $wallet = $wallet->select(
      'wallet.*',
      'clientdetails.first_name as client_first',
      'clientdetails.last_name as client_last',
      'payment.name as payment_name',
      'users.first_name as first_name',
      'users.last_name as last_name',
      'stage.name as stage_name',
      'project_details.name as project_name'
    )
      ->when($from, function ($query, $from) {
        $query->whereDate('wallet.current_date', '>=', $from);
      })
      ->when($to, function ($query, $to) {
        $query->whereDate('wallet.current_date', '<=', $to);
      })
      ->when(request('client_id'), function ($query, $client_id) {
        $query->where('clientdetails.id', $client_id);
      })
      ->when(request('project_id'), function ($query, $project_id) {
        $query->where('project_details.id', $project_id);
      })
      ->when(request('search'), function ($query, $search) {
        $query->where(function ($q) use ($search) {
          // Check if search is the word 'credited'
          if (strtolower(trim($search)) === 'credited') {
            $q->where('wallet.transfer_type', 0);
          } else if (strtolower(trim($search)) === 'debited') {
            $q->where('wallet.transfer_type', 1);
          } else {
            $q->where('clientdetails.first_name', 'like', "%$search%")
              ->orWhere('clientdetails.last_name', 'like', "%$search%")
              ->orWhere('payment.name', 'like', "%$search%")
              ->orWhere('users.first_name', 'like', "%$search%")
              ->orWhere('users.last_name', 'like', "%$search%")
              ->orWhere('stage.name', 'like', "%$search%")
              ->orWhere('project_details.name', 'like', "%$search%")
              ->orWhere('wallet.amount', 'like', "%$search%")
              ->orWhere('wallet.description', 'like', "%$search%");
          }
        });
      })
      ->latest()->paginate($paginate)->withQueryString();
    $clients = ClientDetails::where(['active_status' => 1, 'delete_status' => 0])->get();
    $projects = ProjectDetails::where(['active_status' => 1, 'delete_status' => 0])->get();
    $sum = $wallet->sum('amount');
    return view('wallet.index', ['wallet' => $wallet, 'sum' => $sum, 'clients' => $clients, 'projects' => $projects]);
  }

  public function create(Request $request)
  {
    $client = ClientDetails::where(['active_status' => 1, 'delete_status' => 0])->select('*')->get();
    $project = ProjectDetails::where(['active_status' => 1, 'delete_status' => 0, 'project_status' => 0])->select('*')->get();
    $payment_mode = Payment::where(['active_status' => 1, 'delete_status' => 0])->get();
    $stages = Stage::where('active_status', 1)->where('delete_status', 0)->get();
    return View('wallet.form', ['client' => $client, 'project' => $project, 'payment' => $payment_mode, 'stages' => $stages]);
    //return response()->json($view);
  }
  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    $validated = $request->validate([
      'client_id' => ['required', 'exists:clientdetails,id'],
      'project_id' => ['required', 'exists:project_details,id'],
      'amount' => ['required', 'numeric', 'gt:0'],
      'payment_mode' => ['required', 'exists:payment,id'],
      'transfer_type' => ['required', 'in:0,1'],
      'stage_id' => ['nullable', 'exists:stage,id'],
      'description' => ['nullable', 'string', 'max:2000'],
      'current_date' => ['required', 'date'],
      'time' => ['required'],
    ]);

    DB::transaction(function () use ($validated) {
      $user = User::whereKey(Auth::id())->lockForUpdate()->firstOrFail();
      $project = ProjectDetails::whereKey($validated['project_id'])->lockForUpdate()->firstOrFail();
      $amount = (float) $validated['amount'];
      $isDebit = (int) $validated['transfer_type'] === 1;

      if ($isDebit && (float) $user->wallet < $amount) {
        throw ValidationException::withMessages(['amount' => 'Insufficient wallet balance.']);
      }
      if ($isDebit && (float) $project->advance_amt < $amount) {
        throw ValidationException::withMessages(['amount' => 'Amount exceeds the project advance balance.']);
      }

      Wallet::create([
        'user_id' => $user->id,
        'client_id' => $validated['client_id'],
        'project_id' => $project->id,
        'amount' => $amount,
        'payment_mode' => $validated['payment_mode'],
        'stage_id' => $validated['stage_id'] ?? null,
        'transfer_type' => $validated['transfer_type'],
        'description' => $validated['description'] ?? null,
        'current_date' => $validated['current_date'] . ' ' . $validated['time'],
      ]);

      $direction = $isDebit ? -1 : 1;
      $project->advance_amt = (float) $project->advance_amt + ($direction * $amount);
      $project->profit = (float) $project->profit - ($direction * $amount);
      $project->save();

      $user->wallet = (float) $user->wallet + ($direction * $amount);
      $user->save();
    });

    return redirect()->route('dashboard')
      ->with('popup', 'Wallet Details Created Successfully.');
  }
}

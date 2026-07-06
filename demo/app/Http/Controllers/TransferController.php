<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\Transfer;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Payment;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class TransferController extends Controller
{
  public function index(Request $request)
  {
   // dd($request->all());
    $auth = Auth::user()->id;
   
    $from = null;
    $to = null;
    
    if (!empty($request->date_range)) {
        [$from, $to] = array_map('trim', explode('-', $request->date_range));
    
        $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
        $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
    }
    
    $paginate = $request->paginate ?? 10;

    
      $transfers = Transfer::leftjoin('users', 'users.id', '=', 'transferdetails.member_id')
                    ->leftjoin('users as user_table', 'user_table.id', '=', 'transferdetails.user_id')
                    ->leftjoin('payment', 'payment.id', '=', 'transferdetails.payment_mode')
                    ->where('transferdetails.is_vendor',0);
  if (!(Auth::user()->hasRole('Admin'))) {
    $transfers = $transfers->where(function($query) use ($auth) {
        $query->where('users.id', $auth)
              ->orWhere('user_table.id', $auth);
    });
}
      $transfers = $transfers->select('transferdetails.*', 'users.first_name', 'users.last_name', 'user_table.first_name as firstname',
                     'user_table.last_name as lastname', 'payment.name as payment_name')
      ->when($from, function($query, $from){
        $query->whereDate('transferdetails.current_date','>=',$from);
      })
      ->when($to, function($query, $to){
        $query->whereDate('transferdetails.current_date','<=',$to);
      })
      ->when(request('fmember_id'),function($query,$fmember_id){
        $query->where('user_table.id',$fmember_id);
      })
      ->when(request('tmember_id'),function($query,$tmember_id){
        $query->where('users.id',$tmember_id);
      })
      ->when(request('search'),function($query,$search){
         $query->where(function ($q) use ($search) {
          $q->where('user_table.first_name', 'like', "%$search%")
            ->orWhere('user_table.last_name', 'like', "%$search%")
            ->orWhere('payment.name', 'like', "%$search%")
            ->orWhere('users.first_name', 'like', "%$search%")
            ->orWhere('users.last_name', 'like', "%$search%")
            ->orWhere('transferdetails.amount', 'like', "%$search")
            ->orWhere('transferdetails.description', 'like', "%$search%");
        });
      })
      ->latest()->paginate($paginate)->withQueryString();

    $user = User::latest()->get();
   // dd($user);
    $sum = $transfers->sum('amount');
    return view('transfer.index', ['transfers' => $transfers,  'user' => $user,  'from_date' => $request->from_date, 'to_date1' => $request->to_date, 'sum' => $sum]);
  }

  public function create(Request $request)
  {
    $id = Auth::user()->id;
    $member = User::where(['active_status' => 1, 'delete_status' => 0])->where('id', '!=', $id)->select('*')->get();
    $payment = Payment::where(['active_status' => 1, 'delete_status' => 0])->get();
    return view('transfer.create', ['member' => $member, 'payment' => $payment]);
    //return response()->json($view);
  }
  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    $validated = $request->validate([
      'user_type' => ['required', 'in:0,1'],
      'member_id' => ['required', 'integer'],
      'amount' => ['required', 'numeric', 'gt:0'],
      'payment_mode' => ['required', 'exists:payment,id'],
      'current_date' => ['required', 'date'],
      'time' => ['required'],
      'description' => ['nullable', 'string', 'max:2000'],
    ]);

    $isVendor = (int) $validated['user_type'] === 1;

    DB::transaction(function () use ($validated, $isVendor) {
      $sender = User::whereKey(Auth::id())->lockForUpdate()->firstOrFail();
      $amount = (float) $validated['amount'];

      if ((float) $sender->wallet < $amount) {
        throw ValidationException::withMessages(['amount' => 'Insufficient wallet balance.']);
      }

      if ($isVendor) {
        $recipient = Vendor::whereKey($validated['member_id'])->lockForUpdate()->firstOrFail();
        $recipient->advance_amt = (float) $recipient->advance_amt + $amount;
      } else {
        if ((int) $validated['member_id'] === (int) $sender->id) {
          throw ValidationException::withMessages(['member_id' => 'You cannot transfer money to yourself.']);
        }
        $recipient = User::whereKey($validated['member_id'])->lockForUpdate()->firstOrFail();
        $recipient->wallet = (float) $recipient->wallet + $amount;
      }

      Transfer::create([
        'user_id' => $sender->id,
        'member_id' => $isVendor ? null : $recipient->id,
        'vendor_id' => $isVendor ? $recipient->id : null,
        'is_vendor' => $isVendor ? 1 : 0,
        'amount' => $amount,
        'payment_mode' => $validated['payment_mode'],
        'description' => $validated['description'] ?? null,
        'current_date' => $validated['current_date'] . ' ' . $validated['time'],
      ]);

      $sender->wallet = (float) $sender->wallet - $amount;
      $sender->save();
      $recipient->save();
    });

    return redirect()->route($isVendor ? 'transfer.vendor.history' : 'transfer-history')
      ->with('success', 'Transfer Successfully');
  }
  public function insufficientamt(Request $request)
  {

    $wallet = Auth::user()->wallet;
    // $wallet = User::where('id', $request->user_id)->first();
    $amount = $request->amount;
    $wal_amt = (int)$wallet;
    $response = true;
    if (($wal_amt >= 0) && ($amount <= $wallet)) {
      $response = false;
    }
    return response()->json($response);
  }
  public function userDetail(Request $request)
  {
    $id = $request->id;
    if ($id == 0) {
      $user =  User::where(['active_status' => 1, 'delete_status' => 0])->where('id', '!=', Auth::user()->id)->select('*')->get();
    } else {
      $user = Vendor::latest()->get();
    }
    return response()->json($user);
  }
  public function vendor_history(Request $request)
  {
    $paginate = $request->paginate??10;
    $vendor = Transfer::leftJoin('users', 'users.id', '=', 'transferdetails.user_id')
    ->leftJoin('vendor_details', 'vendor_details.id', '=', 'transferdetails.vendor_id')
    ->leftJoin('payment', 'payment.id', '=', 'transferdetails.payment_mode')
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
    })->when($request->member_id, function($query, $member_id){
      $query->where('vendor_details.id',$member_id);
    });

  //  if (Auth::user()->hasRole('Admin')) {
      $vendor = $vendor->orderBy('transferdetails.id','DESC')
      ->paginate($paginate)->withQueryString();
    // } else {
    //   $vendor = $vendor->where('transferdetails.is_vendor', 1)
    //   ->where('user_id', Auth::user()->id)
    //   ->orderBy('transferdetails.id', 'DESC')
    //   ->paginate($paginate)->withQueryString();
    // }
    $sum = $vendor->sum('amount');
    $vendor_list = Vendor::latest()->get();
    return view('transfer.vendor_history', ['vendor' => $vendor, 'vendor_list' => $vendor_list, 'sum' => $sum]);
  }
}

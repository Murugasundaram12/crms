<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\Expenses;
use App\Models\Transfer;



class PaymentController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $paginate = $request->paginate??15;
        $payments = Payment::when(request('search'),function($query,$search){
            $query->where('name','like',"%$search%");
        })->where('active_status',1)->where('delete_status',0)->orderBy('id','desc')->paginate($paginate)->withQueryString();
        $wallet = Wallet::where('active_status',1)->where('delete_status',0)->pluck('payment_mode')->toArray();
        $expenses = Expenses::pluck('payment_mode')->toArray();
        $transfer = Transfer::pluck('payment_mode')->toArray();
        return view('payment.index',compact('payments','wallet','expenses','transfer'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('payment.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $cat = Payment::where(['active_status' => 1,
                    'delete_status' => 0,
                    'name' => $request->name])->first();
        if(!empty($cat)){
            return redirect()->route('payment-index')
            ->with('msg','Payment already Created');
        }
    else{
        $payment = Payment::create($request->all());
        return redirect()->route('payment-index')
        ->with('message','Payment Created Successfully');
    }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $payment = Payment::where('id',$id)->first();
        return view('payment.edit',["payment"=>$payment]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $cat = Payment::where(['active_status' => 1,
                    'delete_status' => 0,
                    'name' => $request->name ])->where('id','!=',$id)->first();
        if(!empty($cat)){
            return redirect()->route('payment-index')
            ->with('msg','Payment already Created');
        }
        else{
            $payment = Payment::find($id);
            $payment->name = $request->input('name');
            $payment->save();
            return redirect()->route('payment-index')
            ->with('message','Payment Updated Successfully');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function paymentdelete(Request $request)
    {
        $payment = Payment::find($request->id);
        $payment['active_status'] = 0;
        $payment['delete_status'] = 1;
        $payment->update();
        return redirect()->route('payment-index')
        ->with('message','Payment Deleted Successfully');
    }
}

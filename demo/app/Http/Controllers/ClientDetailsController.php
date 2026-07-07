<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use App\Models\ClientDetails;
use App\Models\ProjectDetails;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB as FacadesDB;

class ClientDetailsController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $paginate = $request->paginate??15;
      
            $clients = ClientDetails::where('active_status',1)->where('delete_status',0)->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%$search%")
                      ->orWhere('last_name', 'like', "%$search%")
                      ->orWhere('company_name', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%")
                      ->orWhere('phone', 'like', "%$search%");
                });
            })
            ->orderBy('id','desc')->paginate($paginate)->withQueryString();
       // }
        
        $project = ProjectDetails::where('active_status',1)->where('delete_status',0)->pluck('client_id')->toArray();
        $wallet = Wallet::where('active_status',1)->where('delete_status',0)->pluck('client_id')->toArray();
        return view('client.index',compact('clients','project','wallet','search','paginate'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('client.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        
        $cat = ClientDetails::where(['active_status' => 1, 
                    'delete_status' => 0, 
                    'email' => $request->email])->first();
        if(!empty($cat)){
            return redirect()->route('client-index')
            ->with('msg','Client email id already existed');
        }
        else{
            ClientDetails::create($request->all());
            return redirect()->route('client-index')
            ->with('message','Client Details Stored Successfully');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $from = null;
        $to = null;
        $paginate = $request->paginate;
        
        if (!empty($request->date_range)) {
            [$from, $to] = array_map('trim', explode('-', $request->date_range));
        
            $from = Carbon::createFromFormat('m/d/Y', $from)->format('Y-m-d');
            $to = Carbon::createFromFormat('m/d/Y', $to)->format('Y-m-d');
        }
       // dd($from,$to);
       $projects = ProjectDetails::when($from && $to, function($query) use ($from, $to) {
        $query->where(function($query) use ($from, $to) {
            $query->whereBetween('start_date', [$from, $to])
                  ->orWhereBetween('end_date', [$from, $to]);
        });
    })
    ->when(request('search'), function($query, $search){
        $query->where(function($query) use ($search) {
            $query->where('name','like',"%$search%")
                  ->orWhere('advance_amt','like',"$search%")
                  ->orWhere('total_amt','like',"$search%")
                  ->orWhere('profit','like',"$search%");
        });
    })
    ->where([
        'client_id' => $request->id,
        'active_status' => 1,
        'delete_status' => 0
    ])
    ->orderBy('id','desc')
    ->paginate($paginate)->withQueryString();
    
       // dd($projects);
      $sum = $projects->sum('advance_amt');
      $total = $projects->sum('total_amt');
      $remaining = $projects->sum('profit');
        //->get();
        return view('client.show',['client_id'=>$request->id,'projects' =>$projects,'sum' => $sum, 'total' => $total,'remaining' => $remaining]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $client = ClientDetails::where('id',$id)->first();
        return view('client.edit',["client"=>$client]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $cat = ClientDetails::where(['active_status' => 1, 
                    'delete_status' => 0, 
                    'email' => $request->email])->where('id',"!=",$id)->first();
        if(!empty($cat)){
            return redirect()->route('client-index')
            ->with('msg','Client email id already existed');
        }
        else{
        $input =$request->all();
        $client = ClientDetails::find($id);
        $client->update($input);
    
        return redirect()->route('client-index')
                        ->with('message','Client Details Updated Successfully');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function clientdelete(Request $request)
    {
       $client = ClientDetails::find($request->id);
       $client['active_status'] = 0;
       $client['delete_status'] = 1;
       $client->update();
       return redirect()->route('client-index')
       ->with('message','Client Details Deleted Successfully');
    }
}

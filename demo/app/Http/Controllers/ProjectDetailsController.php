<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ProjectDetails;
use App\Models\ClientDetails;
use App\Models\Expenses;
use App\Models\Payment;
use App\Models\Wallet;


class ProjectDetailsController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $paginate = $request->paginate??15;
        $projects = ProjectDetails::join('clientdetails','clientdetails.id','=','project_details.client_id')
                    ->where('project_details.active_status',1)
                    ->where('project_details.delete_status',0)
                    ->select('project_details.*','clientdetails.first_name as first_name',
                    'clientdetails.last_name as last_name')
                    ->orderBy('project_details.id','desc')
                    ->when(request('search'),function($query,$search){
                        $query->where('name','like',"%$search%")
                        ->orWhere('clientdetails.first_name','like',"%$search%")
                        ->orWhere('clientdetails.last_name','like',"%$search%")
                        ->orWhere('advance_amt','like',"%$search")
                        ->orWhere('total_amt','like',"%$search")
                        ->orWhere('profit','like',"%$search");
                    })
                    ->paginate($paginate)->withQueryString();
        $expenses = Expenses::pluck('project_id')->toArray();
        $wallet = Wallet::where('active_status',1)->where('delete_status',0)->pluck('project_id')->toArray();
        $sum = $projects->sum('advance_amt');
        $total = $projects->sum('total_amt');
        $remaining = $projects->sum('profit');
        //print_r($projects);exit;
        return view('projectdetails.index',compact('projects','sum','total','remaining','expenses','wallet'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $client = ClientDetails::where(['active_status' => 1, 'delete_status' => 0])->select('*')->get();
        $payment = Payment::all();
        //dd($client);
        return view('projectdetails.create',['client' => $client,'payment' => $payment]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        
        $cat = ProjectDetails::where(['active_status' => 1, 
                    'delete_status' => 0, 
                    'name' => $request->name])->first();
        if(!empty($cat)){
            return redirect()->route('project-index')
            ->with('msg','Project name  already existed');
        }
        else{
            $input = $request->all();
            if($request->project_status == 0){
                $input['start_date'] = now();
            }
            else{
                $input['end_date'] = now();
            }
            $input['profit'] = $request->total_amt - $request->advance_amt;
            ProjectDetails::create($input);
            return redirect()->route('project-index')
            ->with('message','Project Details Created Successfully');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $paginate = $request->paginate??15;
        $expenses = Expenses::join('category','category.id','=','expenses.category_id')
        ->when(request('search'),function($query,$search){
            $query->where('expenses.amount','like',"%$search")
            ->orWhere('category.name','like',"%$search%")
            ->orWhere('expenses.paid_amt','like',"%$search")
            ->orWhere('expenses.unpaid_amt','like',"%$search")
            ->orWhere('expenses.description','like',"%$search%");
        })
        ->where('expenses.project_id',$id)->select('expenses.*','category.name as category_name')
        ->orderBy('expenses.id','desc')->paginate($paginate); 
        
        return view('projectdetails.show',['expenses' => $expenses,'project_id'=>$id]);
    }
    public function view(Request $request, $id){
        $paginate = $request->paginate??15;
        $project = ProjectDetails::join('wallet','wallet.project_id','=','project_details.id')
                    ->join('stage','stage.id','=','wallet.stage_id')->join('payment','payment.id','=','wallet.payment_mode')
                   
                    ->select('project_details.*','stage.name as stage_name','wallet.amount',
                    'payment.name as payment','wallet.current_date as currentdate')
                    ->when(request('search'),function($query,$search){
                        $query->where('project_details.name','like',"%$search%")
                        ->orWhere('wallet.amount','like',"%$search")
                        ->orWhere('project_details.total_amt','like',"%$search")
                        ->orWhere('payment.name','like',"%$search%")
                        ->orWhere('stage.name','like',"%$search%");
                    })->where('project_details.id',$id)->paginate($paginate);
        $sum = $project->sum('amount');
        $total = $project->sum('total_amt');
        return view('projectdetails.view',['project' =>$project,'sum' =>$sum,'total' => $total]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {

        $client = ClientDetails::where(['active_status' => 1, 'delete_status' => 0])->select('*')->get();
        $project = ProjectDetails::where('active_status',1)->where('delete_status',0)->where('id',$id)->first();
        $payment = Payment::all();
        return view('projectdetails.edit',["client"=>$client,'project' => $project,'payment' =>$payment]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $cat = ProjectDetails::where(['active_status' => 1, 
                    'delete_status' => 0, 
                    'name' => $request->name])->where('id',"!=",$id)->first();
        if(!empty($cat)){
            return redirect()->route('project-index')
            ->with('msg','Project name already existed');
        }
        else{
        $input =$request->all();
        if($request->project_status == 0){
            $input['start_date'] = now();
        }
        else{
            $input['end_date'] = now();
        }
       
        $project = ProjectDetails::find($id);
         $input['profit'] = $request->total_amt - $project->advance_amt;
        //print_r($input);exit;
        $project->update($input);
    
        return redirect()->route('project-index')
                        ->with('message','Project Details Updated Successfully');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function projectdelete(Request $request)
    {
       $project = ProjectDetails::find($request->id);
       $project['active_status'] = 0;
       $project['delete_status'] = 1;
       $project->update();
       return redirect()->route('project-index')
       ->with('message','Project Details Deleted Successfully');
    }
}

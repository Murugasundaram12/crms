<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Stage;
use App\Models\Wallet;



class StageController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $paginate = $request->paginate??15;
        $stages = Stage::when(request('search'),function($query,$search){
            $query->where('name','like',"%$search%");
        })->orderBy('id','desc')->where(['active_status' => 1, 'delete_status' =>0])->paginate($paginate)->withQueryString();
        $wallet = Wallet::where('active_status',1)->where('delete_status',0)->pluck('stage_id')->toArray();
        return view('stage.index',compact('stages','wallet'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('stage.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $cat = Stage::where(['active_status' => 1, 
                    'delete_status' => 0, 
                    'name' => $request->name])->first();
        if(!empty($cat)){
            return redirect()->route('stage-index')
            ->with('msg','Stage already Created');
        }
    else{
        $stage = Stage::create($request->all());
        return redirect()->route('stage-index')
        ->with('message','Stage Created Successfully');
    }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $stage = Stage::where('id',$id)->first();
        return view('stage.edit',["stage"=>$stage]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        
        $cat = Stage::where(['active_status' => 1, 
                    'delete_status' => 0, 
                    'name' => $request->name])->first();
        if(!empty($cat)){
            return redirect()->route('stage-index')
            ->with('msg','Stage already Created');
        }
        else{
            $stage = Stage::find($id);
            $stage->name = $request->input('name');
            $stage->save();
            return redirect()->route('stage-index')
            ->with('message','Stage Updated Successfully');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function stagedelete(Request $request)
    {
        $stage = Stage::find($request->id);
        $stage['active_status'] = 0;
        $stage['delete_status'] = 1;
        $stage->update();
        return redirect()->route('stage-index')
        ->with('message','Stage Deleted Successfully');
    }
}

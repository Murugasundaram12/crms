<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MainCategory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class MainCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

     public function __construct()
     {
         $this->middleware('permission:maincategory-list|maincategory-create|maincategory-edit|maincategory-delete', ['only' => ['index','show']]);

         $this->middleware('permission:maincategory-create', ['only' => ['create','store']]);

         $this->middleware('permission:maincategory-edit', ['only' => ['edit','update']]);

         $this->middleware('permission:maincategory-delete', ['only' => ['destroy']]);
     }
    public function index()
    {
        $maincategory = MainCategory::latest()->paginate();
       // $categorynot = 
        return view('main_category.index',['categorys' => $maincategory]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $view = view('main_category.create')->render();
        return response()->json($view);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //dd($request->all());
        $name = strtolower($request->name);
        $main = MainCategory::where('name',$name)->get();
        if(count($main) > 0){
          return redirect()->back()->with('error_sweet','Already Exists');
        }else{
          $main = MainCategory::create($request->all());
          return redirect()->route('maincategory.index')->with('success','Main category added successfully');
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $category = MainCategory::where('id',$id)->first();
        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
       // dd($request->all());
       $validator = Validator::make($request->all(), [
        'name' => [
            
            Rule::unique('main_category', 'name')->ignore($id),
        ],
    ]);

    if ($validator->fails()) {
        return redirect()->back()
                         ->with('error_sweet','This  name already exists.')
                         ->withInput();
    }else{
       $category = MainCategory::where('id',$id)->first();
       $category->update([
        'name' => $request->input('name')
    ]);
       return redirect()->route('maincategory.index')->with('success','Main category updated successfully');
    }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
      $category = MainCategory::find($id);
      $category->delete();
      return redirect()->route('maincategory.index')
      ->with('success','Main category deleted successfully');
    }
    public function update_status(Request $request){
      $main = MainCategory::where('id',$request->id)->update(['status'=> $request->status]);
      return response()->json(['success' => true]);

    }
}


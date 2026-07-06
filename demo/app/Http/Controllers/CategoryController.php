<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Expenses;
use App\Models\MainCategory;

class CategoryController extends Controller
{

     public function __construct()
     {
         $this->middleware('permission:category-list|category-create|category-edit|category-delete', ['only' => ['index','show']]);

         $this->middleware('permission:category-create', ['only' => ['create','store']]);

         $this->middleware('permission:category-edit', ['only' => ['edit','update']]);

         $this->middleware('permission:category-delete', ['only' => ['categorydelete']]);
     }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $paginate = $request->paginate??15;
        $categorys = Category::with('maincategory')->when(request('search'),function($query,$search){
            $query->where('name','like',"%$search%")
            ->orWhereHas('maincategory', function ($q) use ($search) {
                  $q->where('name', 'like', "%$search%");
              });
        })->orderBy('id','desc')->where(['active_status' => 1, 'delete_status' =>0])->latest()->paginate($paginate)->withQueryString();
        $categorynot = Expenses::pluck('category_id')->toArray();
        //dd($categorynot);
        return view('category.index',compact('categorys','categorynot'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $maincategory = MainCategory::where('status',1)->latest()->get();
        return view('category.create',['maincategory' => $maincategory]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       // dd($request->all());
        $cat = Category::where(['active_status' => 1, 
                    'delete_status' => 0, 
                    'main_category_id' => $request->main_category_id,
                    'name' => $request->name])->first();
        if(!empty($cat)){
            return redirect()->route('category-index')
            ->with('error_sweet','Category already Created');
        }
    else{
        $category = Category::create($request->all());
        return redirect()->route('category-index')
        ->with('message','Category Created Successfully');
    }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $maincategory = MainCategory::where('status',1)->latest()->get();
        $category = Category::where('id',$id)->first();
        return response()->json([
        'maincategory' => $maincategory,
        'category' => $category,
    ]);
       // return view('category.edit',["category"=>$category,'maincategory' => $maincategory]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        
        $cat = Category::where(['active_status' => 1, 
                    'delete_status' => 0,
                    'name' => $request->name])->where('id','!=',$id)->first();
        if(!empty($cat)){
            return redirect()->back()
            ->with('error_sweet','Category already Created');
        }
        else{
            $category = Category::find($id);
            $category->name = $request->input('name');
            $category->main_category_id = $request->main_category_id;
            $category->save();
            return redirect()->route('category-index')
            ->with('success','Category Updated Successfully');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function categorydelete(Request $request)
    {
        $category = Category::find($request->id);
        $category['active_status'] = 0;
        $category['delete_status'] = 1;
        $category->update();
        return redirect()->route('category-index')
        ->with('message','Category Deleted Successfully');
    }
    public function updateCategory(Request $request){
        $maincategory = MainCategory::where('status',1)->latest()->get();
        $category = Category::where(['active_status' => 1, 'delete_status' => 0])->get();
        return response()->json([
            'main_category' => $maincategory,
            'category' =>$category
        ]);
    }
    public function updateCategoryStatus(Request $request){
       // dd($request->all());
       foreach($request->category_id as $category_id){
        Category::where('id',$category_id)->update(['main_category_id' => $request->main_category_id]);
       }
        return redirect()->route('category-index')
            ->with('success','Category Updated Successfully');
    }
}


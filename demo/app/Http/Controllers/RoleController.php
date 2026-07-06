<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB as FacadesDB;

class RoleController extends Controller
{
// permission list
    function __construct()
    {
        //  $this->middleware('permission:role-list|role-create|role-edit|role-delete', ['only' => ['index','store']]);
        //  $this->middleware('permission:role-create', ['only' => ['create','store']]);
        //  $this->middleware('permission:role-edit', ['only' => ['edit','update']]);
        //  $this->middleware('permission:role-delete', ['only' => ['roledelete']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $paginate = $request->paginate ?? 15;
        if(!empty($search)){
           // dd($search);
           $roles = Role::where('name','like','%'.$search.'%')->orderBy('id','desc')->latest()->paginate($paginate)->withQueryString();
        }else{
            $roles = Role::orderBy('id','desc')->latest()->paginate($paginate)->withQueryString();
        }
        
        $user = FacadesDB::table('model_has_roles')->pluck('role_id')->toArray();
        return view('roles.index',compact('roles','user','paginate','search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = Permission::all();
        return view('roles.create',['permissions' =>$permissions]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $permission = Permission::whereIn('name',$request->permissions)->pluck('id');

        $r1 = Role::where(['name' => $request->name])->first();
        if(!empty($r1)){
            return redirect()->route('roles.index')
            ->with('msg','Role already Created');
        }
        else{
        $role = Role::create($request->all());
        if(!empty($permission)) {
            $role->givePermissionTo($permission);
        }
        return redirect()->route('roles.index')
        ->with('message','Role Created Successfully');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $roles = Role::where('id',$id)->first();
        
        $permissions = Permission::all();
        $access = FacadesDB::table('role_has_permissions')->where('role_id',$id)->pluck('permission_id')->toArray();
        $checked_role = Permission::whereIn('id',$access)->pluck('name')->toArray();
       // dd($checked_role);
        //print_r($access);exit;
        return view('roles.edit',["roles"=>$roles,"permissions" => $permissions,'access' =>$access,'checked_role' =>$checked_role]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
       // dd($request->all());
        $permission_name = Permission::whereIn('name',$request->permissions)->pluck('id');
        $r1 = Role::where(['name' => $request->name])->where('id','!=',$id)->first();
        if(!empty($r1)){
            return redirect()->route('roles.index')
            ->with('msg','Role already Created');
        }
        else{
        $role = Role::find($id);
        $role->name = $request->input('name');
        $permissions = $permission_name ?? [];
        $role->syncPermissions($permissions);
        $role->save();
        return redirect()->route('roles.index')
        ->with('message','Role Updated Successfully');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function roledelete(Request $request)
    {
       Role::find($request->id)->delete();
       return redirect()->route('roles.index')
       ->with('message','Role Deleted Successfully');
    }
}

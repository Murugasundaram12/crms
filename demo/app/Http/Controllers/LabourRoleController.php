<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Labour;
use App\Models\LabourRole;
use Illuminate\Http\Request;

class LabourRoleController extends Controller
{
  public function index(Request $request)
  {
    $paginate = $request->paginate ?? 15;
    $users = LabourRole::when(request('search'), function ($query, $search) {
      $query->where(function ($q) use ($search) {
          $q->where('name', 'like', "%$search%")
            ->orWhereRaw("CAST(salary AS CHAR) LIKE ?", ["%$search%"]);
      });
  
      $searchLower = strtolower($search);
      if ($searchLower === 'daily') {
          $query->orWhere('salary_type', 1);
      } elseif ($searchLower === 'weekly') {
          $query->orWhere('salary_type', 2);
      } elseif ($searchLower === 'monthly') {
          $query->orWhere('salary_type', 3);
      }
  })
  ->orderBy('id','desc')->paginate($paginate)->withQueryString();
  

    return view('labourrole.index', compact('users'));
  }
  public function create(Request $request)
  {
    return view('labourrole.create');
  }
  public function store(Request $request)
  {
    $labour_role = LabourRole::create($request->all());
    return redirect()->route('labourrole-index')->with('message', 'Labour Role Created Successfully');
  }
  public function edit(Request $request)
  {
    $id = $request->id;
    $user = LabourRole::where('id', $id)->first();
    return view('labourrole.edit', ["user" => $user]);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
    //dd($request->all());
    $input = $request->all();
    //dd($input);
    $user = LabourRole::find($id);
    $user->update($input);
    $salary = Labour::where('labour_role', $user->id)->get();
    //dd($salary);
    foreach ($salary as $labour) {
      $labour['salary'] = $user->salary;
      $labour->update();
    }

    // print_r($user);
    // exit;

    return redirect()->route('labourrole-index')->with('message', 'Labour Role details created successfully');
  }
  public function labourdelete(Request $request)
  {
    $user = LabourRole::find($request->id);
    $user->delete();
    return redirect()->route('labourrole-index')
      ->with('message', 'Labour Role Deleted Successfully');
  }
}

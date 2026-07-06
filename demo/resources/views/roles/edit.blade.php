@extends('layouts/contentNavbarLayout')

@section('title', 'Edit | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')
<!-- <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Forms/</span></h4> -->

<!-- Basic Layout & Basic with Icons -->
<h4 class="fw-bold py-3 mb-4">
  <span class="text-muted fw-light">Edit Roles
</h4>
        <div class="row" style="position:absolute; top:150px; right:50px ">
  <div class="col-md-12">
        <ul class="nav nav-pills flex-column flex-md-row mb-3">
      <li class="nav-item"><a class="nav-link active" href="{{route('roles.index')}}"><i class="bx me-1"></i> Back</a></li>

    </ul>
  </div></div>
<div class="row">
  <!-- Basic Layout -->
  <div class="col-xxl">
    <div class="card mb-4" style="top:30px">


      <div class="card-body">
        <form name="editRole" action="{{route('roles.update',$roles->id)}}" method="post" >
            @csrf
            {{ method_field('PUT') }}
            <input type="hidden" value="{{$roles->id}}" name="id">
          <div class="row mb-3">
            <label class="col-sm-2 col-form-label" for="basic-default-name">Name</label>
            <div class="col-sm-10">
              <input type="text" name="name" class="form-control" id="basic-default-name" placeholder="Enter Roles"  value="{{$roles->name}}" {{$roles->id == 1 || $roles->name =='Super Admin' ? 'readonly' : ''}} />
            </div>

            <div class="row"><div class="col-12">
            <label class="col-sm-2 col-form-label" for="basic-default-name" style="margin-left: -17px; margin-top:20px;">Permission</label>
            <div class="container">
    <div class="row">
        <div class="col-12"></div>
    </div>

    @php
    $permissionsByPrefix = [];
    @endphp

    @foreach ($permissions as $permission)
    @php
    $parts = explode('-', $permission->name);
    $prefix = $parts[0];
    $permissionName = end($parts);

    if (!isset($permissionsByPrefix[$prefix])) {
        $permissionsByPrefix[$prefix] = [
            'heading' => $prefix,
            'permissions' => [],
        ];
    }

    $permissionsByPrefix[$prefix]['permissions'][] = [
        'name' => $permission->name,
        'permissionName' => $permissionName,
    ];
    @endphp
    @endforeach

    <div class="row" style="margin-top:23px;">
        @foreach ($permissionsByPrefix as $prefixData)
        <h5>{{ $prefixData['heading'] }}</h5> <!-- Display the heading -->
        <div class="form-check col-12" >
            @foreach ($prefixData['permissions'] as $permissionData)

            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permissionData['name'] }}" {{in_array($permissionData['name'],$checked_role) ? 'checked' : ''}} id="flexCheckDefault{{ $loop->parent->index }}-{{ $loop->index }}">
            <label class="form-check-label" style="display:inline-flex; width:91px; margin-bottom:15px; font-weight:100;" for="flexCheckDefault{{ $loop->parent->index }}-{{ $loop->index }}">
                {{ $permissionData['permissionName'] }}
            </label>
            @endforeach
        </div>
        @endforeach
    </div>
</div>
         <div class="row ">
            <div class="col-sm-10 text-right">
              <button type="submit" class="btn btn-primary">Update</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- Basic with Icons -->
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.2/jquery.validate.min.js"></script>
<script>
$('form[name="editRole"]').validate({
  rules: {
    name:{
        required: true,
    }
  },
  messages: {
    name:{
        required:"Enter the role name"
    }
  },
  submitHandler: function(form) {
    form.submit();
  }
});
</script>
@endsection

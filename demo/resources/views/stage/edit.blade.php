@extends('layouts/contentNavbarLayout')

@section('title', 'Edit | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')
<!-- <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Forms/</span></h4> -->

<!-- Basic Layout & Basic with Icons -->
<h4 class="fw-bold py-3 mb-4">
  <span class="text-muted fw-light">Edit Stage
</h4>
        <div class="row" style="position:absolute; top:150px; right:50px ">
  <div class="col-md-12">
        <ul class="nav nav-pills flex-column flex-md-row mb-3">
      <li class="nav-item"><a class="nav-link active" href="{{route('stage-index')}}"><i class="bx me-1"></i> Back</a></li>

    </ul>
  </div></div>
<div class="row">
  <!-- Basic Layout -->
  <div class="col-xxl">
    <div class="card mb-4" style="top:30px">


      <div class="card-body">
        <form name="editStage" action="{{route('stage.update',$stage->id)}}" method="post" >
            @csrf
            {{ method_field('PUT') }}
            <input type="hidden" value="{{$stage->id}}" name="id">
          <div class="row mb-3">
            <label class="col-sm-2 col-form-label" for="basic-default-name">Name</label>
            <div class="col-sm-10">
              <input type="text" name="name" class="form-control" id="basic-default-name" placeholder="Enter category" value="{{$stage->name}}" />
            </div>
          </div>

         <div class="row justify-content-end">
            <div class="col-sm-10">
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
$('form[name="editStage"]').validate({
  rules: {
    name:{
        required: true,
    }
  },
  messages: {
    name:{
        required:"Enter the stage name"
    }
  },
  submitHandler: function(form) {
    form.submit();
  }
});
</script>
@endsection

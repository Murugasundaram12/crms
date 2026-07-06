
<div class="d-flex justify-content-center align-items-center" >
 
        <form name="createCategory" action="{{ route('maincategory.store') }}" method="post">
          @csrf
          <div class="row mb-3 justify-content-center">
            <label class="col-sm-3 col-form-label text-end" for="basic-default-name">Name</label>
            <div class="col-sm-7">
              <input type="text" autofocus name="name" class="form-control" id="basic-default-name" placeholder="Enter Main Category" />
            </div>
          </div>

          <div class="row justify-content-center">
            <div class="col-sm-10 text-center">
              <button type="submit" class="btn btn-primary">Save</button>
              <a href="{{ route('maincategory.index') }}" class="btn btn-secondary ms-2">Back</a>
            </div>
          </div>
        </form>
     
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.2/jquery.validate.min.js"></script>
<script>
$('form[name="createCategory"]').validate({
  rules: {
    name: { required: true }
  },
  messages: {
    name: { required:"Enter the main category name" }
  },
  submitHandler: function(form) {
    form.submit();
  }
});
</script>
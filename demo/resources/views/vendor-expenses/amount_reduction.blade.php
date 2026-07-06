 
 <div class="modal-body">
     <form name="" action="{{ route('vendor.withdraw-save') }}" id="withdrawsave" method="post">
        @csrf
        <input type="hidden" name="vendor_id" value="{{ $vendor_id }}">
         <div class="mb-3">
             <label for="amount" class="form-label">Amount:</label>
             <input type="text" name="amount" class="form-control" id="amount">
              <label id="amount-error" class="error" for="basic-default-email">Amount is
                                                  required</label>
         </div>
         <div class="mb-3">
             <label for="message-text" class="form-label">To transfer:</label>
             <select class="form-control " name="member_id" id="member_id"
                                            style="width:100%"  >
                                            <option value="">Select member </option>
                                            @foreach($member as $member)
                                                <option value="{{ $member->id }}" >{{ $member->first_name }} {{ $member->last_name }}</option>
                                                @endforeach
                                        </select>
             <label id="member-error" class="error" for="basic-default-email">Member is
                                                  required</label>
         </div>
    
 </div>
 <div class="modal-footer">
     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
     <button type="submit" class="btn btn-primary withdrawsave" >Update</button>
 </div>
 </form>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script>
        $(document).ready(function(){
            $('.error').addClass('hide');
        });
        $('.withdrawsave').on('click',function(){
           var amount = $('#amount').val();
           var member = $('#member_id').find(':selected').val();
           var amt_name = false;
           var member_name = false;
           if(amount == ''){
            $('#amount-error').removeClass('hide');
            amt_name = false;
           }else{
            $('#amount-error').addClass('hide');
            amt_name = true;
           } 
           if(member == ''){
            $('#member-error').removeClass('hide');
            member_name = false;
           }else{
            $('#member-error').addClass('hide');
            member_name = true;
           } 
           if(amt_name == true && member_name == true){
                 document.getElementById("withdrawsave").submit();
             
           }
        });
    </script>
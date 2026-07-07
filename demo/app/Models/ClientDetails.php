<?php

namespace App\Models; 
  
use Illuminate\Database\Eloquent\Model; 
  
class ClientDetails extends Model 
{ 

    protected $table = 'clientdetails';
    protected $fillable = ['id','first_name','last_name','email','company_name','address','phone','active_status','delete_status'];
} 
?>
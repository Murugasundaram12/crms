<?php

namespace App\Models; 
  
use Illuminate\Database\Eloquent\Model; 
  
class Transfer extends Model 
{ 

    protected $table = 'transferdetails';
    protected $fillable = ['id','amount','member_id','user_id','current_date','description','history','payment_mode','is_vendor','vendor_id'];
} 
?>
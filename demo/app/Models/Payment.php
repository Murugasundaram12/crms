<?php

namespace App\Models; 
  
use Illuminate\Database\Eloquent\Model; 
  
class Payment extends Model 
{ 

    protected $table = 'payment';
    protected $fillable = ['id','name','active_status','delete_status'];
} 
?>
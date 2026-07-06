<?php

namespace App\Models; 
  
use Illuminate\Database\Eloquent\Model; 
  
class Stage extends Model 
{ 

    protected $table = 'stage';
    protected $fillable = ['id','name','active_status','delete_status'];
} 
?>
<?php

namespace App\Models; 
  
use Illuminate\Database\Eloquent\Model; 
  
class Attendance extends Model 
{ 

    protected $table = 'attendance';
    protected $fillable = ['id','user_id','notes','duration'];
} 
?>
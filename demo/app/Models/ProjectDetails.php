<?php

namespace App\Models; 
  
use Illuminate\Database\Eloquent\Model; 
  
class ProjectDetails extends Model 
{ 

    protected $table = 'project_details';
    protected $fillable = ['id','name','advance_amt','total_amt','client_id','profit','project_status','active_status','delete_status','payment_mode','start_date','end_date'];
} 
?>
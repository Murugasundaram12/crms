<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Labour extends Model
{
  use SoftDeletes;
  protected $dates = ['deleted_at'];
    protected $table = 'labour_details';
    protected $fillable = ['id','name','job_title','phone','gender','salary','government_image','advance_amt','labour_role'];
}
?>
